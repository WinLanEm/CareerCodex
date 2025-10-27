<?php

namespace App\Http\Resources\AllActivities;

use Illuminate\Http\Resources\Json\ResourceCollection;


class ActivitiesCursorResource extends ResourceCollection
{
    public $collects = AllActivitiesResource::class;

    private $meta;

    public function __construct($resource, $meta = [])
    {
        $this->meta = $meta;
        parent::__construct($resource);
    }

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'meta' => $this->meta
        ];
    }
}
