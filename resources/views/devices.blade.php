@extends('layouts.app')

@section('content')
<div class="content container-fluid" id="DeviceApp">
    <!-- Breadcrumb -->
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
        <div class="my-auto mb-2">
            <h2 class="mb-1">Gestion des Terminaux</h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="/"><i class="ti ti-smart-home"></i></a>
                    </li>
                    <li class="breadcrumb-item">Administration</li>
                    <li class="breadcrumb-item active" aria-current="page">Terminaux Mobiles</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
            @can('devices.sync')
            <div class="mb-2">
                <button type="button" id="btnTestFcm" class="btn btn-dark d-flex align-items-center">
                    <i class="ti ti-broadcast me-2"></i> Tester Connexion (Terminaux)
                </button>
            </div>
            @endcan
        </div>
    </div>
    <!-- /Breadcrumb -->

    <div class="row">
        <div class="col-sm-12">
            <div class="card card-table">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                    <h5 class="mb-0">Liste des terminaux mobiles enregistrés</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-center mb-0 datatable">
                            <thead>
                                <tr>
                                    <th>PHONE ID</th>
                                    <th>Nom du Terminal</th>
                                    <th>Plateforme</th>
                                    <th>Dernière Connexion</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($devices as $device)
                                <tr>
                                    <td>{{ $device->imei }}</td>
                                    <td>
                                        <span id="deviceName_{{ $device->id }}" class="fw-bold text-dark">{{ $device->device_name ?? 'Inconnu' }}</span>
                                    </td>
                                    <td>
                                        @if($device->platform == 'android')
                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-brand-android me-1"></i> Android</span>
                                        @elseif($device->platform == 'ios')
                                            <span class="badge badge-soft-info d-inline-flex align-items-center"><i class="ti ti-brand-apple me-1"></i> iOS</span>
                                        @else
                                            <span class="badge badge-soft-secondary">{{ $device->platform ?? 'N/A' }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Jamais' }}</td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            @can('devices.update')
                                            <button class="btn btn-info btn-sm btn-edit"
                                                    data-id="{{ $device->id }}"
                                                    data-name="{{ $device->device_name }}"
                                                    title="Modifier nom">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            @endcan
                                            @can('devices.sync')
                                            <button class="btn btn-primary btn-sm btn-sync"
                                                    data-id="{{ $device->id }}"
                                                    data-name="{{ $device->device_name ?? $device->imei }}"
                                                    title="Sync Biométrie">
                                                <i class="ti ti-refresh"></i>
                                            </button>
                                            <button class="btn btn-secondary btn-sm btn-face-list"
                                                    data-id="{{ $device->id }}"
                                                    data-name="{{ $device->device_name ?? $device->imei }}"
                                                    title="Demander la liste des matricules du terminal">
                                                <i class="ti ti-list-search"></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm btn-view-face-list"
                                                    data-id="{{ $device->id }}"
                                                    data-name="{{ $device->device_name ?? $device->imei }}"
                                                    title="Voir la dernière liste reçue">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                            @endcan
                                            @can('devices.delete')
                                            <button class="btn btn-danger btn-sm btn-delete"
                                                    data-id="{{ $device->id }}"
                                                    data-name="{{ $device->device_name ?? $device->imei }}"
                                                    title="Supprimer">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 devices-pagination">
                        {{ $devices->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@can('devices.update')
<!-- Modal Editer Device -->
<div class="modal fade" id="editDeviceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Modifier le terminal</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="editDeviceForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nom du terminal <span class="text-danger">*</span></label>
                        <input type="text" id="edit_device_name" name="device_name" class="form-control" placeholder="Entrez un nom pour ce terminal">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnSaveDevice" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
@endcan

@can('devices.sync')
<!-- Modal de Synchronisation -->
<div class="modal fade" id="syncModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Synchroniser les Empreintes - <span id="modalDeviceName" class="text-primary"></span></h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body pb-0">
                <div class="alert alert-soft-info d-flex align-items-center mb-3">
                    <i class="ti ti-info-circle me-2 fs-20"></i>
                    <span>Sélectionnez les agents dont vous souhaitez envoyer l'empreinte biométrique vers ce terminal.</span>
                </div>

                <div class="mb-3">
                    <div class="input-group input-group-flat">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" id="searchAgent" class="form-control" placeholder="Rechercher un agent ou matricule...">
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-center mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 40px;">
                                    <div class="form-check form-check-md">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </div>
                                </th>
                                <th>MATRICULE</th>
                                <th>AGENT</th>
                                <th>QUALITÉ</th>
                                <th>DERNIÈRE MAJ</th>
                                <th class="text-end">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody id="biometricAgentsList">
                            @forelse($biometrics as $bio)
                            <tr class="bio-row" data-search="{{ strtolower($bio->matricule . ' ' . ($bio->agent->fullname ?? '')) }}">
                                <td>
                                    <div class="form-check form-check-md">
                                        <input type="checkbox" name="matricules[]" value="{{ $bio->matricule }}" class="form-check-input agent-checkbox">
                                    </div>
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
                                        <div class="ms-2 text-start">
                                            <p class="text-dark mb-0 fw-medium">{{ $bio->agent->fullname ?? 'N/A' }}</p>
                                            <span class="fs-12 text-muted">{{ $bio->agent->station->name ?? '--' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($bio->quality_score >= 0.8)
                                        <span class="badge badge-soft-success">Excellent ({{ $bio->quality_score * 100 }}%)</span>
                                    @else
                                        <span class="badge badge-soft-warning">Moyen ({{ $bio->quality_score * 100 }}%)</span>
                                    @endif
                                </td>
                                <td>{{ $bio->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <button class="btn btn-danger btn-sm btn-delete-biometric" data-id="{{ $bio->id }}" data-name="{{ $bio->agent->fullname ?? $bio->matricule }}" title="Supprimer donnée biométrique">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Aucun embedding biométrique trouvé en base.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="btnDeleteFromDevice" class="btn btn-danger me-2">
                    <i class="ti ti-trash me-1"></i> Supprimer sur le terminal
                </button>
                <button type="button" id="btnSubmitSync" class="btn btn-primary">
                    <i class="ti ti-send me-1"></i> Envoyer la synchronisation
                </button>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

<!-- Modal pour la liste des matricules reçue du terminal -->
<div class="modal fade" id="faceListModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Liste des matricules reçus - <span id="faceListDeviceName" class="text-primary"></span></h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3 text-end small text-muted" id="faceListMeta"></div>
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllFaceList" class="form-check-input">
                                </th>
                                <th>#</th>
                                <th>Matricule</th>
                                <th>Agent</th>
                            </tr>
                        </thead>
                        <tbody id="faceListRows"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnDeleteFaceListSelection" class="btn btn-danger me-2">
                    <i class="ti ti-trash me-1"></i> Supprimer sélection du terminal
                </button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<style>
    .devices-pagination .page-item:first-child,
    .devices-pagination .page-item:last-child {
        display: none;
    }
</style>

@push('scripts')
<script>
$(document).ready(function() {
    let currentDeviceId = null;

    // --- TEST FCM GLOBAL ---
    $('#btnTestFcm').on('click', function() {
        console.log('Envoi de la requête de test FCM...');
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Envoi en cours...');

        $.ajax({
            url: `{{ route('admin.devices.test_fcm') }}`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                console.log('Réponse test FCM reçue:', response);
                Swal.fire({
                    title: response.success ? 'Succès' : 'Information',
                    text: response.message,
                    icon: response.success ? 'success' : 'info'
                });
            },
            error: function(xhr) {
                console.error('Erreur lors du test FCM:', xhr);
                Swal.fire('Erreur', 'Impossible de tester le service FCM.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ti ti-broadcast me-2"></i> Tester Connexion (FCM)');
            }
        });
    });

    // --- EDITION DEVICE ---
    $(document).on('click', '.btn-edit', function() {
        currentDeviceId = $(this).data('id');
        $('#edit_device_name').val($(this).data('name'));
        $('#editDeviceModal').modal('show');
    });

    $('#btnSaveDevice').on('click', function() {
        let name = $('#edit_device_name').val();
        if (!name) {
            Swal.fire('Attention', 'Le nom du terminal est requis.', 'warning');
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            url: `/admin/devices/${currentDeviceId}/update`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', device_name: name },
            success: function(response) {
                if (response.success) {
                    $(`#deviceName_${currentDeviceId}`).text(name);
                    $(`.btn-edit[data-id="${currentDeviceId}"]`).data('name', name);
                    Swal.fire('Mis à jour', response.message, 'success');
                    $('#editDeviceModal').modal('hide');
                }
            },
            error: function() {
                Swal.fire('Erreur', 'Échec de la mise à jour.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).text('Enregistrer');
            }
        });
    });

    // --- SUPPRESSION DEVICE ---
    $(document).on('click', '.btn-delete', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Supprimer ce terminal ?',
            text: `Voulez-vous vraiment supprimer "${name}" ? Cette action est irréversible.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/devices/${id}/delete`,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Supprimé', response.message, 'success').then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function() {
                        Swal.fire('Erreur', 'Impossible de supprimer le terminal.', 'error');
                    }
                });
            }
        });
    });

    // --- FACE LIST REQUEST ---
    $(document).on('click', '.btn-face-list', function() {
        let deviceId = $(this).data('id');
        let deviceName = $(this).data('name');

        Swal.fire({
            title: 'Questionner le terminal ?',
            text: `Interroger le terminal "${deviceName}" pour demander la liste des visages disponibles sur le terminal ?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Oui, envoyer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            let btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: `/admin/devices/${deviceId}/face-list`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Requête envoyée', '', 'success');
                    }
                },
                error: function(xhr) {
                    let msg = 'Erreur lors de l\'envoi de la requête.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    Swal.fire('Erreur', msg, 'error');
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="ti ti-list-search"></i>');
                }
            });
        });
    });

    $(document).on('click', '.btn-view-face-list', function() {
        const deviceId = $(this).data('id');
        const deviceName = $(this).data('name');
        window.currentFaceListDeviceId = deviceId;

        $('#faceListDeviceName').text(deviceName);
        $('#faceListMeta').text('Chargement...');
        $('#faceListRows').html('<tr><td colspan="4" class="text-center py-4">Chargement...</td></tr>');
        $('#selectAllFaceList').prop('checked', false);
        $('#faceListModal').modal('show');

        $.ajax({
            url: `/admin/devices/${deviceId}/face-list`,
            method: 'GET',
            success: function(response) {
                if (!response.success) {
                    $('#faceListRows').html('<tr><td colspan="4" class="text-center py-4">'+(response.message || 'Aucune donnée disponible.')+'</td></tr>');
                    $('#faceListMeta').text('');
                    return;
                }

                const items = response.data.matricules || [];
                $('#faceListMeta').text(`Total: ${response.data.count} matricules • Reçu le ${response.data.received_at || '-'}`);

                if (!items.length) {
                    $('#faceListRows').html('<tr><td colspan="4" class="text-center py-4">Aucun matricule reçu.</td></tr>');
                    return;
                }

                const rows = items.map((item, index) => {
                    const matricule = item && item.matricule ? item.matricule : (item || '---');
                    const fullname = item && item.fullname ? item.fullname : 'N/A';
                    const photo = item && item.photo ? item.photo : '{{ asset('assets/img/avatar.jpg') }}';
                    const station = item && item.station_name ? item.station_name : '--';

                    return `
                        <tr>
                            <td><input type="checkbox" class="form-check-input face-list-checkbox" value="${matricule}"></td>
                            <td>${index + 1}</td>
                            <td><strong>${matricule}</strong></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-sm rounded-circle overflow-hidden border">
                                        <img src="${photo}" class="img-fluid rounded-circle" alt="agent" style="width: 32px; height: 32px; object-fit: cover;">
                                    </span>
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold text-dark">${fullname}</span>
                                        <small class="text-muted">${station}</small>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');

                $('#faceListRows').html(rows);
            },
            error: function(xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Impossible de récupérer la liste du terminal.';
                $('#faceListRows').html('<tr><td colspan="4" class="text-center py-4">'+msg+'</td></tr>');
                $('#faceListMeta').text('');
            }
        });
    });

    $('#selectAllFaceList').on('change', function() {
        $('.face-list-checkbox').prop('checked', $(this).is(':checked'));
    });

    $('#btnDeleteFaceListSelection').on('click', function() {
        if (!window.currentFaceListDeviceId) {
            Swal.fire('Attention', 'Aucun terminal sélectionné.', 'warning');
            return;
        }

        const selected = $('.face-list-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (!selected.length) {
            Swal.fire('Attention', 'Sélectionnez au moins un matricule à supprimer sur le terminal.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Supprimer du terminal ?',
            text: `Voulez-vous supprimer ${selected.length} matricule(s) sur ce terminal ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: `/admin/devices/${window.currentFaceListDeviceId}/delete-biometric`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', matricules: selected },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Suppression envoyée', response.message, 'success');
                        $('#faceListModal').modal('hide');
                    }
                },
                error: function(xhr) {
                    const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Erreur lors de la suppression sur le terminal.';
                    Swal.fire('Erreur', msg, 'error');
                }
            });
        });
    });

    // --- SYNC BIOMETRIE ---
    $(document).on('click', '.btn-sync', function() {
        currentDeviceId = $(this).data('id');
        $('#modalDeviceName').text($(this).data('name'));
        $('.agent-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        $('#syncModal').modal('show');
    });

    $('#selectAll').on('change', function() {
        $('.agent-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#searchAgent').on('keyup', function() {
        let val = $(this).val().toLowerCase();
        $('.bio-row').each(function() {
            $(this).toggle($(this).data('search').indexOf(val) > -1);
        });
    });

    function sendBiometricDeleteToDevice(deviceId, matricules, successMessage) {
        if (!matricules.length) {
            Swal.fire('Attention', 'Sélectionnez au moins un matricule.', 'warning');
            return;
        }

        $.ajax({
            url: `/admin/devices/${deviceId}/delete-biometric`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', matricules: matricules },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Suppression envoyée', successMessage || response.message, 'success');
                }
            },
            error: function(xhr) {
                let msg = 'Erreur lors de la suppression sur le terminal.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Erreur', msg, 'error');
            }
        });
    }

    $('#btnDeleteFromDevice').on('click', function() {
        let selectedMatricules = [];
        $('.agent-checkbox:checked').each(function() {
            selectedMatricules.push($(this).val());
        });

        if (!selectedMatricules.length) {
            Swal.fire('Attention', 'Veuillez sélectionner au moins un agent.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Supprimer sur le terminal ?',
            text: `Voulez-vous supprimer ${selectedMatricules.length} matricule(s) sur ce terminal ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (!result.isConfirmed) return;
            sendBiometricDeleteToDevice(currentDeviceId, selectedMatricules, 'Les matricules sélectionnées ont été envoyées pour suppression sur le terminal.');
        });
    });

    $('#btnSubmitSync').on('click', function() {
        let selectedMatricules = [];
        $('.agent-checkbox:checked').each(function() {
            selectedMatricules.push($(this).val());
        });

        if (selectedMatricules.length === 0) {
            Swal.fire('Attention', 'Veuillez sélectionner au moins un agent.', 'warning');
            return;
        }

        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Envoi...');

        $.ajax({
            url: `/admin/devices/${currentDeviceId}/sync`,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', matricules: selectedMatricules },
            success: function(response) {
                if (response.success) {
                    Swal.fire('Commande envoyée', response.message, 'success');
                    $('#syncModal').modal('hide');
                }
            },
            error: function(xhr) {
                let msg = 'Erreur lors de l\'envoi FCM.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Erreur', msg, 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> Envoyer la synchronisation');
            }
        });
    });

    // --- SUPPRESSION BIOMETRIE ---
    $(document).on('click', '.btn-delete-biometric', function() {
        let id = $(this).data('id');
        let name = $(this).data('name');

        Swal.fire({
            title: 'Supprimer l\'empreinte ?',
            text: `Voulez-vous supprimer les données biométriques de "${name}" ?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/biometrics/${id}/delete`,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Supprimé', response.message, 'success').then(() => {
                                $(`.btn-delete-biometric[data-id="${id}"]`).closest('tr').fadeOut(300, function() {
                                    $(this).remove();
                                });
                            });
                        } else {
                            Swal.fire('Erreur', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Erreur', 'Une erreur est survenue lors de la suppression.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
