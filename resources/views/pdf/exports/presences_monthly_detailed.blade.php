@extends('pdf.exports._base')

@section('body')
    <style>
        table { font-size: 7px; table-layout: fixed; width: 100%; border-spacing: 0; }
        th, td { padding: 2px 1px; border: 0.5px solid #ccc; text-align: center; overflow: hidden; }
        .col-agent { width: 140px; text-align: left; padding-left: 3px; }
        .col-day { width: auto; }
        .col-total { width: 18px; font-weight: bold; font-size: 6px; }
        .badge { font-size: 6px; padding: 1px; }
        .text-left { text-align: left; }
    </style>

    <table>
        <thead>
        <tr>
            <th class="col-agent">Agent</th>
            @for($d = 1; $d <= $daysInMonth; $d++)
                <th class="col-day">{{ sprintf('%02d', $d) }}</th>
            @endfor
            <th class="col-total">Tot.</th>
            <th class="col-total">Pres.</th>
            <th class="col-total">Abs.</th>
            <th class="col-total">Ret.</th>
            <th class="col-total">Aut.</th>
            <th class="col-total">Congé</th>
            <th class="col-total">H.Sup</th>
            <th class="col-total">OFF</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $r)
            <tr>
                <td class="col-agent text-left">
                    <strong>{{ $r['agent']['fullname'] ?? '-' }}</strong><br>
                    <small class="text-muted">{{ $r['agent']['matricule'] ?? '' }}</small>
                </td>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php($code = $r['days'][sprintf('%02d', $d)] ?? '--')
                    <td class="col-day">{{ $code }}</td>
                @endfor
                <td class="col-total">{{ $r['total_count'] }}</td>
                <td class="col-total">{{ $r['total_presences'] }}</td>
                <td class="col-total">{{ $r['total_absences'] }}</td>
                <td class="col-total">{{ $r['total_retards'] }}</td>
                <td class="col-total">{{ $r['total_autorisations'] }}</td>
                <td class="col-total">{{ $r['total_conges'] }}</td>
                <td class="col-total">{{ $r['overtime_display'] }}</td>
                <td class="col-total">{{ $r['total_off'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="margin-top: 10px; font-size: 8px;">
        <strong>Légende:</strong> 1 = Présence | 1-R = Retard | A = Absence | OFF = Repos | C = Congé | AS = Autorisation spéciale | AUT = Autres | -- = Futur
    </div>
@endsection
