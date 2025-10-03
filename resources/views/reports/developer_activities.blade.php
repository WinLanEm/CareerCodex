<!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Отчет по активностям разработчика</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            background-color: #f9f9f9;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header h1 {
            text-align: center;
            margin-bottom: 5px;
        }
        .header p {
            text-align: center;
            margin-top: 0;
            font-size: 14px;
            color: #666;
            margin-bottom: 30px;
        }

        /* Стили для карточки активности */
        .activity-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
            font-size: 11px;
            color: #666;
        }
        .activity-id {
            font-weight: bold;
            color: #333;
        }
        .activity-repo {
            font-style: italic;
        }

        .activity-body .activity-title {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 10px 0;
            /* Позволяет длинным строкам переноситься */
            word-wrap: break-word;
            white-space: normal;
        }

        .activity-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
        }

        .activity-type {
            background-color: #e9ecef;
            padding: 3px 8px;
            border-radius: 10px;
            color: #495057;
        }

        .activity-stats {
            display: flex;
            gap: 15px;
        }

        .additions {
            color: #28a745; /* Зеленый цвет для добавлений */
            font-weight: bold;
        }
        .deletions {
            color: #dc3545; /* Красный цвет для удалений */
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            background-color: #fff;
            border: 1px dashed #ddd;
            border-radius: 5px;
            color: #888;
        }

        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Отчет по активностям разработчика</h1>
        <p>Сформирован: {{ date('d.m.Y H:i') }}</p>
    </div>

    @forelse ($activities as $activity)
        <div class="activity-card">
            <div class="activity-header">
                <span class="activity-id">#{{ $activity->id }}</span>
                <span class="activity-repo">{{ $activity->repository_name }}</span>
                <span class="activity-date">{{ \Carbon\Carbon::parse($activity->completed_at)->format('d.m.Y H:i') }}</span>
            </div>

            <div class="activity-body">
                <p class="activity-title">{{ $activity->title }}</p>
            </div>

            <div class="activity-footer">
                <span class="activity-type">{{ $activity->type }}</span>
                <div class="activity-stats">
                    <span class="additions">+{{ $activity->additions ?? 0 }}</span>
                    <span class="deletions">-{{ $activity->deletions ?? 0 }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <p>Нет данных для отображения.</p>
        </div>
    @endforelse

    <div class="footer">
        <p>Всего активностей: {{ count($activities) }}</p>
    </div>
</div>
</body>
</html>
