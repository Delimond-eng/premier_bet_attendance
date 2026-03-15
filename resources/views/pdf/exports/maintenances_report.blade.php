<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #444; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 12px; color: #666; }

        .meta { margin-bottom: 15px; }
        .meta-item { margin-bottom: 3px; }
        .meta-label { font-weight: bold; width: 120px; display: inline-block; }

        .summary-box { margin-bottom: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; }
        .summary-box table { width: 100%; }
        .summary-box td { padding: 5px; }
        .summary-value { font-size: 14px; font-weight: bold; color: #2563eb; }

        table.main-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.main-table th, table.main-table td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        table.main-table th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 10px; }

        .badge { padding: 2px 5px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef9c3; color: #854d0e; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Salama Attendance System</p>
    </div>

    <div class="meta">
        @foreach($metaLines as $line)
            <div class="meta-item">{{ $line }}</div>
        @endforeach
    </div>

    <div class="summary-box">
        <table>
            <tr>
                <td>Total Interventions: <span class="summary-value">{{ $summary['total'] }}</span></td>
                <td>Terminées: <span class="summary-value">{{ $summary['completed'] }}</span></td>
                <td>En cours: <span class="summary-value">{{ $summary['ongoing'] }}</span></td>
            </tr>
        </table>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                @foreach($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    @foreach($row as $index => $val)
                        <td>
                            @if($index === 6) {{-- Statut --}}
                                <span class="badge {{ $val === 'Cloturee' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $val }}
                                </span>
                            @else
                                {{ $val }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Généré le {{ now()->format('d/m/Y H:i') }} | Page 1
    </div>
</body>
</html>
