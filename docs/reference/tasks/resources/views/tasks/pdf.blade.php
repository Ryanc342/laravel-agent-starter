{{--
    The PDF of one task. DOMPDF reads CSS 2.1 only: no flexbox and no grid, so
    the layout is tables. It shows the same facts, in the same order, as the
    print page (resources/js/pages/tasks/print.tsx); the values come from
    TaskResource, so neither calculates anything.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <title>{{ $task['title'] }}</title>
        <style>
            @font-face { font-family: "Noto Sans SC"; font-weight: normal; src: url("{{ resource_path('fonts/NotoSansSC-Regular.ttf') }}") format("truetype"); }
            @font-face { font-family: "Noto Sans SC"; font-weight: bold; src: url("{{ resource_path('fonts/NotoSansSC-Bold.ttf') }}") format("truetype"); }
            @page { margin: 15mm; }
            body { font-family: "Noto Sans SC", "DejaVu Sans", sans-serif; font-size: 12px; color: #1f2430; }
            h1 { font-size: 20px; font-weight: bold; margin: 0 0 12px 0; }
            table { width: 100%; border-collapse: collapse; }
            th, td { text-align: left; vertical-align: top; padding: 10px 0; border-top: 1px solid #dcdfe6; }
            th { width: 30%; font-weight: normal; color: #6b7280; }
        </style>
    </head>
    <body>
        <h1>{{ $task['title'] }}</h1>

        <table>
            <tr>
                <th>{{ __('Status') }}</th>
                <td>{{ $task['status_label'] }}@if ($task['is_overdue']) · {{ __('Overdue') }}@endif</td>
            </tr>
            <tr>
                <th>{{ __('Due date') }}</th>
                <td>{{ $task['due_on'] ? \Illuminate\Support\Carbon::parse($task['due_on'])->format('j M Y') : __('No due date') }}</td>
            </tr>
        </table>
    </body>
</html>
