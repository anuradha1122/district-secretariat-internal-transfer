<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <style>
        body {
            font-family: kaputaunicode, sans-serif;
            font-size: 9pt;
            color: #1f2937;
        }

        h1 {
            font-size: 16pt;
            margin-bottom: 4px;
        }

        .meta {
            color: #4b5563;
            margin-bottom: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #e5e7eb;
            text-align: left;
        }

        th,
        td {
            border: 1px solid #9ca3af;
            padding: 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .empty {
            padding: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>

    <div class="meta">
        Generated: {{ $generatedAt }} (Sri Lanka time)
        <br>
        Rows: {{ $rows->count() }}
    </div>

    @if ($rows->isEmpty())
        <div class="empty">No matching records were found.</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td>{{ $value ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>