<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'SALAMA ATTENDANCE - Rapport' }}</title>
    <style>
        @page {
            margin: 1.5cm;
            footer: page-footer;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1a592e;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .logo-text {
            font-size: 20px;
            font-weight: bold;
            color: #1a592e;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 9px;
            color: #666;
            margin-top: -5px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #111;
            text-align: right;
            margin: 0;
        }
        .meta-info {
            font-size: 10px;
            color: #555;
            text-align: right;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #1a592e;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 5px;
            text-align: left;
            border: 1px solid #1a592e;
        }
        td {
            padding: 7px 5px;
            border: 1px solid #e0e0e0;
            vertical-align: middle;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-ok { background-color: #dcfce7; color: #166534; }
        .badge-no { background-color: #fee2e2; color: #991b1b; }
        .badge-info { background-color: #e0f2fe; color: #075985; }
        .badge-warn { background-color: #fef9c3; color: #854d0e; }

        .text-bold { font-weight: bold; }
        .text-muted { color: #777; font-size: 9px; }
        .text-center { text-align: center; }

        footer {
            position: fixed;
            bottom: -0.5cm;
            left: 0;
            right: 0;
            height: 1cm;
            font-size: 8px;
            color: #999;
            text-align: center;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="border: none; padding: 0;">
                <div class="logo-text">SALAMA ATTENDANCE</div>
                <div class="company-sub">Système de Gestion de Présence de SALAMA GROUP</div>
            </td>
            <td style="border: none; padding: 0; text-align: right;">
                <div class="report-title">{{ $title ?? 'Rapport Export' }}</div>
                <div class="meta-info">
                    Généré le : {{ date('d/m/Y H:i') }}<br>
                    @if(!empty($metaLines) && is_array($metaLines))
                        @foreach($metaLines as $line)
                            {{ $line }}<br>
                        @endforeach
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @yield('body')

    <footer>
        SALAMA GROUP LTD - Rapport de présence automatisé - Page <span class="pagenum"></span>
    </footer>
</body>
</html>
