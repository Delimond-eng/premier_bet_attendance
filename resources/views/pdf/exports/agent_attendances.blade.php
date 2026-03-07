@extends('pdf.exports._base')

@php
    $headers = is_array($headers ?? null) ? $headers : [];
    $rows = is_array($rows ?? null) ? $rows : [];
    $columnWidth = count($headers) > 0 ? (100 / count($headers)) : 100;
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            @foreach($headers as $header)
                <th style="width: {{ $columnWidth }}%;">{{ $header }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach(($row ?? []) as $cell)
                    <td>{{ (string) $cell }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ max(count($headers), 1) }}" class="muted">Aucune donnee</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endsection
