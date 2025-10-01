<!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Отчет по достижениям</title>
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

        /* Стили для карточки достижения */
        .achievement-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .card-header .title {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }
        .card-header .project {
            font-size: 12px;
            color: #555;
            margin-top: 5px;
            font-style: italic;
        }

        .card-body .label {
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            color: #333;
        }
        .card-body .description {
            line-height: 1.5;
            color: #444;
            white-space: pre-wrap; /* Сохраняет переносы строк из текста */
            word-wrap: break-word;
        }

        .card-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 11px;
            color: #777;
        }
        .skills-list {
            margin-top: 10px;
            padding: 0;
            list-style: none;
            display: block;
        }
        .skill-tag {
            display: inline-block;
            background-color: #e9ecef;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 10px;
            color: #495057;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        .result-badge {
            font-weight: bold;
            color: #28a745;
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
        <h1>Отчет по достижениям</h1>
        <p>Сформирован: {{ date('d.m.Y H:i') }}</p>
    </div>

    @forelse ($achievements as $achievement)
        <div class="achievement-card">
            <div class="card-header">
                <h2 class="title">{{ $achievement->title }}</h2>
                @if($achievement->project_name)
                    <p class="project">Проект: {{ $achievement->project_name }}</p>
                @endif
            </div>

            <div class="card-body">
                <p class="label">Описание:</p>
                <p class="description">{{ $achievement->description }}</p>

                @if(is_array($achievement->skills) && !empty($achievement->skills))
                    <p class="label">Примененные навыки:</p>
                    <div class="skills-list">
                        @foreach($achievement->skills as $skill)
                            <span class="skill-tag">{{ $skill }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card-footer">
                <div class="meta-info">
                    <span class="result-badge">Результат: {{ $achievement->result }}</span>
                    <span>ID: {{ $achievement->id }}</span>
                    <span>{{ \Carbon\Carbon::parse($achievement->date)->format('d.m.Y') }}</span>
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <p>Нет данных для отображения.</p>
        </div>
    @endforelse

    <div class="footer">
        <p>Всего достижений: {{ count($achievements) }}</p>
    </div>
</div>
</body>
</html>
