<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Rapport d'absences — Tous les sites</title>
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
  h1 { font-size: 18px; margin: 0 0 6px; }
  h2 { font-size: 14px; margin: 18px 0 8px; color:#333; }
  .small { color:#666; font-size:11px; margin-bottom: 12px; }
  table { width:100%; border-collapse: collapse; margin-top: 8px; }
  th, td { border:1px solid #444; padding:6px 8px; text-align:left; vertical-align: top; }
  th { background:#f2f2f2; }
  .site-header { margin-top: 14px; }
  .site-meta { color:#555; font-size: 11px; }
  .spacer { height: 10px; }
</style>
</head>
<body>
  <h1>Rapport d'absences</h1>
  <div class="small">
    Généré le {{ $now->format('d/m/Y H:i') }} (Africa/Kinshasa)
  </div>

  @forelse($packs as $pack)
    @php
      $site = $pack['site'];
      $rows = $pack['rows'];
    @endphp

    <div class="site-header">
      <h2>{{ $site->code ?? '' }} — {{ $site->name }}</h2>
    </div>

    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Matricule</th>
          <th>Nom complet</th>
          <th>Groupe</th>
          <th>Horaire prévu</th>
          <th>Début attendu</th>
          <!-- <th>Deadline (+ tolérance)</th>
          <th>Motif</th> -->
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $i => $r)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $r['matricule'] }}</td>
            <td>{{ $r['fullname'] }}</td>
            <td>{{ $r['groupe'] }}</td>
            <td>{{ $r['horaire_label'] }}</td>
            <td>{{ $r['expected_start'] }}</td>
            <!-- <td>{{ $r['deadline'] }}</td>
            <td>{{ $r['reason'] }}</td> -->
          </tr>
        @endforeach
      </tbody>
    </table>
    {{-- espace entre sites --}}
    <div class="spacer"></div>
  @empty
    <p>Aucune absence détectée sur l'ensemble des sites.</p>
  @endforelse
</body>
</html>
