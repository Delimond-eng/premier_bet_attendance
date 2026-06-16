@extends('pdf.exports._base')

@php
    $metaLines = [
        'Période: ' . ($from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : 'Début') . ' au ' . ($to ? \Carbon\Carbon::parse($to)->format('d/m/Y') : 'Fin'),
        'Station: ' . (($station->name ?? null) ?: 'Toutes les stations'),
        'Nombre d\'interventions: ' . (isset($tasks) ? $tasks->count() : 0),
    ];
@endphp

@section('body')
    @foreach(($tasks ?? []) as $task)
        <div style="margin-bottom: 30px; border: 1px solid #eee; padding: 15px; border-radius: 8px; page-break-inside: avoid;">
            <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; width: 70%;">
                            <h2 style="margin: 0; color: #004182;">{{ $task->title }}</h2>
                            <p style="margin: 5px 0; color: #666;">{{ $task->description }}</p>
                        </td>
                        <td style="border: none; text-align: right;">
                            <div style="font-weight: bold; font-size: 14px;">
                                @if($task->status === 'completed')
                                    <span style="color: #28a745;">✔ TERMINÉE</span>
                                @elseif($task->status === 'in_progress')
                                    <span style="color: #177dff;">⚙ EN COURS</span>
                                @else
                                    <span style="color: #ffc107;">⌛ EN ATTENTE</span>
                                @endif
                            </div>
                            <div style="font-size: 12px; margin-top: 5px;">Priorité: {{ strtoupper($task->priority) }}</div>
                        </td>
                    </tr>
                </table>
            </div>

            <table style="width: 100%; margin-bottom: 15px;">
                <tr>
                    <td style="width: 33%;"><strong>Site:</strong> {{ $task->station->name }}</td>
                    <td style="width: 33%;"><strong>Échéance:</strong> {{ \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') }}</td>
                    <td style="width: 33%;"><strong>Progression:</strong> {{ $task->progress }}%</td>
                </tr>
                <tr>
                    <td colspan="3">
                        <strong>Techniciens:</strong>
                        @if($task->is_global)
                            <span class="badge badge-info">Équipe complète du site</span>
                        @else
                            {{ $task->agents->pluck('fullname')->implode(', ') }}
                        @endif
                    </td>
                </tr>
            </table>

            @if($task->subtasks->count() > 0)
                <div style="margin-bottom: 15px;">
                    <h4 style="margin-bottom: 8px; border-bottom: 1px solid #ddd;">Check-list d'intervention</h4>
                    <table style="width: 100%;">
                        @foreach($task->subtasks as $st)
                            <tr>
                                <td style="width: 30px; text-align: center;">
                                    {!! $st->is_completed ? '<span style="color: green;">[✔]</span>' : '<span style="color: #ccc;">[ ]</span>' !!}
                                </td>
                                <td>{{ $st->title }}</td>
                                <td style="text-align: right; color: #888; font-size: 11px;">
                                    {{ $st->completed_at ? 'Fait le ' . \Carbon\Carbon::parse($st->completed_at)->format('d/m/Y H:i') : '' }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            @if($task->evidences->count() > 0)
                <div>
                    <h4 style="margin-bottom: 10px; border-bottom: 1px solid #ddd;">Preuves de réalisation (Photos)</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        @foreach($task->evidences as $ev)
                            @php
                                $imgSource = null;
                                if ($ev->image_path) {
                                    // Nettoyer l'URL si c'est une URL complète pour obtenir le chemin relatif au dossier public
                                    $relativePath = str_replace(url('/'), '', $ev->image_path);
                                    $relativePath = ltrim($relativePath, '/');
                                    $fullPath = public_path($relativePath);

                                    if (file_exists($fullPath)) {
                                        $imgSource = $fullPath;
                                    }
                                }
                            @endphp

                            @if($imgSource)
                                <div style="display: inline-block; width: 30%; margin-right: 10px; margin-bottom: 10px; text-align: center;">
                                    <img src="{{ $imgSource }}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 5px; border: 1px solid #ddd;">
                                    <div style="font-size: 10px; margin-top: 3px; color: #666;">
                                        Par {{ $ev->agent->fullname }}<br>
                                        {{ $ev->created_at->format('d/m/Y H:i') }}
                                    </div>
                                    @if($ev->note)
                                        <div style="font-size: 10px; font-style: italic; color: #444; background: #fffde7; padding: 3px; margin-top: 2px;">
                                            "{{ $ev->note }}"
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
@endsection

<style>
    .badge-info { background: #e1f5fe; color: #0288d1; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
</style>
