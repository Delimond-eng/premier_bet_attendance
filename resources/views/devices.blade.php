@extends('layouts.app')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Gestion des Terminaux Mobiles</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Tableau de bord</a></li>
                    <li class="breadcrumb-item active">Terminaux</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="card card-table">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-center mb-0 datatable">
                            <thead>
                                <tr>
                                    <th>IMEI</th>
                                    <th>Nom du Terminal</th>
                                    <th>Plateforme</th>
                                    <th>Token FCM (Tronqué)</th>
                                    <th>Dernière Connexion</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($devices as $device)
                                <tr>
                                    <td>{{ $device->imei }}</td>
                                    <td>{{ $device->device_name ?? 'Inconnu' }}</td>
                                    <td>
                                        @if($device->platform == 'android')
                                            <span class="badge bg-success"><i class="fab fa-android"></i> Android</span>
                                        @elseif($device->platform == 'ios')
                                            <span class="badge bg-info"><i class="fab fa-apple"></i> iOS</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $device->platform ?? 'N/A' }}</span>
                                        @endif
                                    </td>
                                    <td title="{{ $device->firebase_token }}">
                                        {{ Str::limit($device->firebase_token, 20) }}
                                    </td>
                                    <td>{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Jamais' }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-primary btn-sm btn-sync"
                                                data-id="{{ $device->id }}"
                                                data-name="{{ $device->device_name ?? $device->imei }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#syncModal">
                                            <i class="feather-refresh-cw"></i> Sync Biométrie
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $devices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Synchronisation -->
<div class="modal fade" id="syncModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Synchroniser les Empreintes - <span id="modalDeviceName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    Sélectionnez les agents dont vous souhaitez envoyer l'empreinte biométrique vers ce terminal.
                </div>

                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="feather-search"></i></span>
                        <input type="text" id="searchAgent" class="form-control" placeholder="Rechercher un agent ou matricule...">
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-center">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>Matricule</th>
                                <th>Agent</th>
                                <th>Qualité</th>
                                <th>Dernière MAJ</th>
                            </tr>
                        </thead>
                        <tbody id="biometricAgentsList">
                            @forelse($biometrics as $bio)
                            <tr class="bio-row" data-search="{{ strtolower($bio->matricule . ' ' . ($bio->agent->fullname ?? '')) }}">
                                <td>
                                    <input type="checkbox" name="matricules[]" value="{{ $bio->matricule }}" class="form-check-input agent-checkbox">
                                </td>
                                <td><strong>{{ $bio->matricule }}</strong></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md">
                                            @if($bio->agent->photo)
                                                <img src="{{ $bio->agent->photo }}" data-zoom="{{ $bio->agent->photo }}" class="img-fluid rounded-circle" alt="user">
                                            @else
                                                <img src="{{ asset('assets/img/avatar.jpg') }}" class="img-fluid rounded-circle" alt="user">
                                            @endif
                                        </a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0 font-weight-bold">{{ $bio->agent->fullname ?? 'N/A' }}</p>
                                            <span class="fs-12 text-muted">{{ $bio->agent->station->name ?? '--' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($bio->quality_score >= 0.8)
                                        <span class="text-success"><i class="feather-check-circle"></i> {{ $bio->quality_score * 100 }}%</span>
                                    @else
                                        <span class="text-warning">{{ $bio->quality_score * 100 }}%</span>
                                    @endif
                                </td>
                                <td>{{ $bio->updated_at->format('d/m/Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">Aucun embedding biométrique trouvé en base.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnSubmitSync" class="btn btn-success">
                    <i class="feather-send"></i> Synchroniser vers ce terminal
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentDeviceId = null;

    // Ouverture du modal
    $('.btn-sync').on('click', function() {
        currentDeviceId = $(this).data('id');
        $('#modalDeviceName').text($(this).data('name'));
        $('.agent-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
    });

    // Sélectionner tout
    $('#selectAll').on('change', function() {
        $('.agent-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Recherche instantanée
    $('#searchAgent').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.bio-row').each(function() {
            $(this).toggle($(this).data('search').indexOf(val) > -1);
        });
    });

    // Soumission de la synchronisation
    $('#btnSubmitSync').on('click', function() {
        let selectedMatricules = [];
        $('.agent-checkbox:checked').each(function() {
            selectedMatricules.push($(this).val());
        });

        if (selectedMatricules.length === 0) {
            Swal.fire('Erreur', 'Veuillez sélectionner au moins un agent.', 'error');
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Envoi...');

        $.ajax({
            url: `/admin/devices/${currentDeviceId}/sync`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                matricules: selectedMatricules
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Succès', response.message, 'success');
                    $('#syncModal').modal('hide');
                } else {
                    Swal.fire('Erreur', response.message, 'error');
                }
            },
            error: function(xhr) {
                let msg = 'Une erreur est survenue lors de l\'envoi de la commande.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire('Erreur', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="feather-send"></i> Synchroniser vers ce terminal');
            }
        });
    });
});
</script>
@endpush
