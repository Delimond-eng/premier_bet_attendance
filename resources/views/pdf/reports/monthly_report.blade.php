<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Rapport Présences {{ $mois }}/{{ $annee }}</title>
<style>
    @page { margin: 10px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border: 1px solid #000; padding: 3px; text-align: center; font-size: 9px; }
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    tr { page-break-inside: avoid; }
    th { background-color: #f2f2f2; }
    td { word-wrap: break-word; }

    .week-table { margin-bottom: 15px; page-break-inside: avoid; }
    .week-table th, .week-table td { font-size: 9px; }

    /* Couleurs pour les absences / présences */
    .absent { background-color: #f8d7da; }  /* rouge clair */
    .present { background-color: #d4edda; } /* vert clair */
    .partial { background-color: #fff3cd; } /* orange clair */
    .off { background-color: #e9ecef; color: #6c757d; } /* gris clair */

    h3 { text-align: center; margin-bottom: 15px; }
</style>
</head>
<body>
<h3>Rapport Mensuel des Présences - {{ $mois }}/{{ $annee }}</h3>

@if(empty($data))
    <p style="text-align: center;">Aucune donnée disponible pour cette période.</p>
@else
    @php
        $firstAgentKey = array_key_first($data);
        $jours = array_keys($data[$firstAgentKey]);
        $chunks = array_chunk($jours, 7, true); // découpe par semaine
    @endphp

    @foreach($chunks as $week)
        <table class="week-table">
            <thead>
                <tr>
                    <th style="width: 150px;">Agent</th>
                    <th style="width: 80px;">Horaire</th>
                    @foreach($week as $jour)
                        <th>{{ $jour }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data as $agent => $joursData)
                    <tr>
                        <td style="text-align: left;">{{ $agent }}</td>
                        <td style="font-size: 7px;">{{ $joursData[array_key_first($joursData)]['horaire'] ?? '--' }}</td>
                        @foreach($week as $jour)
                            @php
                                $cell = $joursData[$jour] ?? null;
                                $status = $cell['status'] ?? 'absent';
                                $arr = $cell['arrivee'] ?? '--:--';
                                $dep = $cell['depart'] ?? '--:--';

                                $class = 'absent';
                                if (in_array($status, ['present', 'retard', 'retard_justifie'])) {
                                    $class = 'present';
                                } elseif ($status === 'off' || $status === 'future') {
                                    $class = 'off';
                                } elseif (in_array($status, ['conge', 'autorisation', 'absence_justifiee'])) {
                                    $class = 'partial';
                                }
                            @endphp
                            <td class="{{ $class }}">
                                <div>{{ $arr }}</div>
                                @if(!empty($dep) && $dep !== '--:--')
                                    <div style="border-top: 0.5px solid #ccc; font-size: 7px;">{{ $dep }}</div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
@endif

</body>
</html>
