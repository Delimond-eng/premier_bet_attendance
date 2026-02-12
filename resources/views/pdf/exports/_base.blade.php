<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Export' }}</title>
    <style>
        @page { margin: 16px 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 8px; margin-bottom: 10px; }
        .title { font-size: 16px; font-weight: 700; margin: 0; }
        .meta { font-size: 10px; color: #374151; margin-top: 4px; }
        .meta span { display: inline-block; margin-right: 12px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #E5E7EB; padding: 6px 6px; vertical-align: top; }
        th { background: #F3F4F6; font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: .02em; }
        td { font-size: 10px; }
        tr:nth-child(even) td { background: #FAFAFA; }
        .muted { color: #6B7280; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 9px; border: 1px solid #E5E7EB; }
        .badge-ok { background: #ECFDF5; border-color: #10B981; color: #065F46; }
        .badge-no { background: #FEF2F2; border-color: #EF4444; color: #7F1D1D; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">{{ $title ?? 'Export' }}</p>
        <div class="meta">
            @if(!empty($metaLines) && is_array($metaLines))
                @foreach($metaLines as $line)
                    <span>{{ $line }}</span>
                @endforeach
            @endif
        </div>
    </div>

    @yield('body')
</body>
</html>

