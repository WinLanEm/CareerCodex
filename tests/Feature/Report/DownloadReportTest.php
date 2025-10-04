<?php

namespace Report;

use App\Contracts\Services\Report\DownloadReportStrategyInterface;
use App\Enums\ReportTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Mockery\MockInterface;
use Tests\TestCase;

class DownloadReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_authentication_to_download_a_report()
    {
        $response = $this->getJson(route('report.download', ['type' => 'developer_activity']));
        $response->assertUnauthorized();
    }

    public function test_it_requires_a_valid_type_parameter()
    {
        $user = User::factory()->create();

        $response1 = $this->actingAs($user)->getJson(route('report.download'));
        $response1->assertUnprocessable()->assertJsonValidationErrors('type');

        $response2 = $this->actingAs($user)->getJson(route('report.download', ['type' => 'invalid_report_type']));
        $response2->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_it_calls_the_correct_strategy_for_developer_activity_report()
    {
        $user = User::factory()->create();
        $startDate = '2025-01-01';
        $endDate = '2025-01-31';

        $this->mock(DownloadReportStrategyInterface::class, function (MockInterface $mock) use ($user, $startDate, $endDate) {
            $mock->shouldReceive('downloadReport')
                ->once()
                ->with(
                    ReportTypeEnum::DEVELOPER_ACTIVITY,
                    $user->id,
                    $startDate,
                    $endDate
                )
                ->andReturn(new Response('fake pdf content', 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="developer-activities-report.pdf"'
                ]));
        });

        $response = $this->actingAs($user)->get(route('report.download', [
            'type' => 'developer_activity',
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="developer-activities-report.pdf"');
    }

    public function test_it_calls_the_correct_strategy_for_achievement_report()
    {
        $user = User::factory()->create();

        $this->mock(DownloadReportStrategyInterface::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('downloadReport')
                ->once()
                ->with(
                    ReportTypeEnum::ACHIEVEMENT,
                    $user->id,
                    null,
                    null
                )
                ->andReturn(new Response('fake pdf content', 200, [
                    'Content-Type' => 'application/pdf',
                ]));
        });

        $response = $this->actingAs($user)->get(route('report.download', [
            'type' => 'achievement'
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
