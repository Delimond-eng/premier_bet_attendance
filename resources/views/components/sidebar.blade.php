<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <a href="/" class="logo logo-normal">
            <span class="salama-logo salama-logo--normal" aria-label="SALAMA ATTENDANCE">
                <span class="salama-logo__icon" aria-hidden="true">
                    <span class="salama-logo__h">H</span>
                </span>
                <span class="salama-logo__wordmark">
                    <span class="salama-logo__name">SALAMA</span>
                    <span class="salama-logo__sub">ATTENDANCE</span>
                </span>
            </span>
        </a>
        <a href="/" class="logo-small">
            <span class="salama-logo salama-logo--small" aria-label="SALAMA ATTENDANCE">
                <span class="salama-logo__icon" aria-hidden="true">
                    <span class="salama-logo__h">H</span>
                </span>
            </span>
        </a>
        <a href="/" class="dark-logo">
            <span class="salama-logo salama-logo--normal" aria-label="SALAMA ATTENDANCE">
                <span class="salama-logo__icon" aria-hidden="true">
                    <span class="salama-logo__h">H</span>
                </span>
                <span class="salama-logo__wordmark">
                    <span class="salama-logo__name">SALAMA</span>
                    <span class="salama-logo__sub">ATTENDANCE</span>
                </span>
            </span>
        </a>
    </div>

    <div class="modern-profile p-3 pb-0">
        <div class="text-center rounded bg-light p-3 mb-4 user-profile">
            <div class="avatar avatar-lg online mb-3">
                <img src="{{ asset('assets/img/profiles/avatar-02.jpg') }}" alt="Img" class="img-fluid rounded-circle">
            </div>
            <h6 class="fs-12 fw-normal mb-1">{{ Auth::user()->name }}</h6>
            <p class="fs-10">{{ Auth::user()->getRoleNames()->first() ?? Auth::user()->role }}</p>
        </div>
    </div>

    <div class="sidebar-header p-3 pb-0 pt-2">
        <div class="text-center rounded bg-light p-2 mb-4 sidebar-profile d-flex align-items-center">
            <div class="avatar avatar-md onlin">
                <img src="{{ asset('assets/img/profiles/avatar-02.jpg') }}" alt="Img" class="img-fluid rounded-circle">
            </div>
            <div class="text-start sidebar-profile-info ms-2">
                <h6 class="fs-12 fw-normal mb-1">{{ Auth::user()->name }}</h6>
                <p class="fs-10">{{ Auth::user()->getRoleNames()->first() ?? Auth::user()->role }}</p>
            </div>
        </div>
        <div class="input-group input-group-flat d-inline-flex mb-4">
            <span class="input-icon-addon">
                <i class="ti ti-search"></i>
            </span>
            <input type="text" class="form-control" placeholder="Recherche...">
            <span class="input-group-text">
                <kbd>CTRL + / </kbd>
            </span>
        </div>
    </div>

    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">
            <ul>
                <li class="menu-title"><span>MENU PRINCIPAL</span></li>
                <li>
                    <ul>
                        @canany(['dashboard_admin.view', 'presences.view'])
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(['dashboard','dashboard.maintenance.view','presences.live'])">
                                <i class="ti ti-smart-home"></i>
                                <span>Tableau de bord</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @can('dashboard_admin.view')
                                <li><a class="@active(['dashboard'])" href="{{ route('dashboard') }}">Vue globale</a></li>
                                @if(str_contains(request()->getHost(), 'electrocool'))
                                <li><a class="@active(['dashboard.maintenance.view'])" href="{{ route('dashboard.maintenance.view') }}">Statistiques maintenance</a></li>
                                @endif
                                @endcan
                                @can('presences.view')
                                <li><a class="@active(['presences.live'])" href="{{ route('presences.live') }}">Journal de pointage</a></li>
                                @endcan
                            </ul>
                        </li>
                        @endcanany
                    </ul>
                </li>

                <!-- ALERTES REGROUPEES -->
                @canany(['rapport_absences.view', 'rapport_retards.view', 'rapport_presences.view'])
                <li class="menu-title"><span>NOTIFICATIONS</span></li>
                @php
                    $alertMenuCounts = ['absences' => 0, 'retards' => 0, 'departs' => 0];
                    try {
                        $alertMenuCounts = app(\App\Services\CumulativeAlertService::class)->getSidebarCounts(1);
                    } catch (\Throwable $e) {
                        $alertMenuCounts = ['absences' => 0, 'retards' => 0, 'departs' => 0];
                    }
                    $totalAlerts = array_sum($alertMenuCounts);
                @endphp
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(['reports.alerts.*'])">
                                <i class="ti ti-bell-ringing"></i>
                                <span>Alertes & Suivi</span>
                                @if($totalAlerts > 0)
                                <span class="badge badge-danger fs-10 fw-medium text-white ms-2">{{ $totalAlerts }}</span>
                                @endif
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li>
                                    <a class="{{ request()->query('type') === 'absences' ? 'active' : '' }}" href="{{ route('reports.alerts.view', ['type' => 'absences', 'period' => 'daily', 'threshold' => 1]) }}">
                                        Absences
                                        <span class="badge badge-soft-danger ms-auto">{{ $alertMenuCounts['absences'] }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->query('type') === 'retards' ? 'active' : '' }}" href="{{ route('reports.alerts.view', ['type' => 'retards', 'period' => 'daily', 'threshold' => 1]) }}">
                                        Retards
                                        <span class="badge badge-soft-danger ms-auto">{{ $alertMenuCounts['retards'] }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="{{ request()->query('type') === 'departs' ? 'active' : '' }}" href="{{ route('reports.alerts.view', ['type' => 'departs', 'period' => 'daily']) }}">
                                        Départs anticipés
                                        <span class="badge badge-soft-danger ms-auto">{{ $alertMenuCounts['departs'] }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                @endcanany

                <!-- MAINTENANCE & TACHES -->
                <li class="menu-title"><span>OPERATIONS</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(['tasks.*', 'maintenance.*', 'dashboard.maintenance.view'])">
                                <i class="ti ti-tool"></i>
                                <span>Maintenance & Tâches</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @can('dashboard_admin.view')
                                    @if(str_contains(request()->getHost(), 'chanimetal'))
                                    <li><a class="@active(['dashboard.maintenance.view'])" href="{{ route('dashboard.maintenance.view') }}">Statistiques Maintenance</a></li>
                                    @endif
                                @endcan
                                <li><a class="@active(['tasks.index'])" href="{{ route('tasks.index') }}">Configuration des Tâches</a></li>
                                <li><a class="@active(['tasks.monitoring'])" href="{{ route('tasks.monitoring') }}">Suivi de Progression</a></li>
                                <li><a class="@active(['tasks.reports'])" href="{{ route('tasks.reports') }}">Rapports d'Intervention</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <li class="menu-title"><span>RH</span></li>

                <li>
                    <ul>
                        @canany(['horaires.view', 'groupes.view', 'plannings.view'])
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(['rh.*'])">
                                <i class="ti ti-calendar-time"></i>
                                <span>Gestion des horaires</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @can('horaires.view')
                                <li><a class="@active(['rh.horaires.view'])" href="{{ route('rh.horaires.view') }}">Horaire de presence</a></li>
                                @endcan
                                @can('groupes.view')
                                <li><a class="@active(['rh.groupes.view'])" href="{{ route('rh.groupes.view') }}">Groupe agent</a></li>
                                @endcan
                                @can('plannings.view')
                                <li><a class="@active(['rh.plannings.view'])" href="{{ route('rh.plannings.view') }}">Planning de rotation</a></li>
                                @endcan
                            </ul>
                        </li>
                        @endcanany

                        @can('stations.view')
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(['stations.*', 'map.view'])">
                                <i class="ti ti-location-cog"></i><span>Gestion stations</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="@active(['stations.view'])" href="{{ route('stations.view') }}">Liste des stations</a></li>
                                <li><a class="@active(['map.view'])" href="{{ route('map.view') }}">Cartographie Live</a></li>
                            </ul>
                        </li>
                        @endcan

                        @can('agents.view')
                        <li class="@active(['agents.view','agents.view.attendances'])">
                            <a href="{{ route('agents.view') }}">
                                <i class="ti ti-user-cog"></i><span>Gestion agents</span>
                            </a>
                        </li>
                        @endcan


                        @canany(['rapport_presences.view', 'rapport_absences.view', 'rapport_retards.view'])
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(['reports.*'])">
                                <i class="ti ti-report"></i><span>Rapports</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @can('rapport_presences.view')
                                <li><a class="@active(['reports.presences'])" href="{{ route('reports.presences') }}">Presences journalieres</a></li>
                                <li><a class="@active(['reports.weekly.view'])" href="{{ route('reports.weekly.view') }}">Presences hebdomadaire</a></li>
                                <li><a class="@active(['reports.monthly.view'])" href="{{ route('reports.monthly.view') }}">Presences mensuelles</a></li>
                                @if(str_contains(request()->getHost(), 'electrocool'))
                                <li><a class="@active(['reports.maintenance.view'])" href="{{ route('reports.maintenance.view') }}">Rapport de maintenance</a></li>
                                @endif

                                @endcan
                                @can('rapport_absences.view')
                                <li><a class="@active(['reports.absences.daily.view'])" href="{{ route('reports.absences.daily.view') }}">Absences journalieres</a></li>
                                @endcan
                                @can('rapport_retards.view')
                                <li><a class="@active(['reports.retards.daily.view'])" href="{{ route('reports.retards.daily.view') }}">Rapport des retards</a></li>
                                @endcan
                            </ul>
                        </li>
                        @endcanany

                        @canany(['timesheet.view', 'conges.view', 'attributions.view', 'authorizations.view', 'justifications.view'])
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(['rh.*'])">
                                <i class="ti ti-user-screen"></i><span>Ressources humaines</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @can('timesheet.view')
                                <li><a class="@active(['rh.timesheet.view'])" href="{{ route('rh.timesheet.view') }}">Pointage mensuel</a></li>
                                @endcan
                                <li class="submenu">
                                    <a href="javascript:void(0);" class="@active(['rh.conges.view', 'rh.attributions.view'])">
                                        Conges & attribution
                                        <span class="menu-arrow"></span>
                                    </a>
                                    <ul>
                                        @can('conges.view')
                                        <li><a class="@active(['rh.conges.view'])" href="{{ route('rh.conges.view') }}">Conges</a></li>
                                        @endcan
                                        @can('attributions.view')
                                        <li><a class="@active(['rh.attributions.view'])" href="{{ route('rh.attributions.view') }}">Attribution agent</a></li>
                                        @endcan
                                    </ul>
                                </li>
                                @can('authorizations.view')
                                <li><a class="@active(['rh.authorizations.view'])" href="{{ route('rh.authorizations.view') }}">Autorisation speciale</a></li>
                                @endcan
                                @can('justifications.view')
                                <li><a class="@active(['rh.justifications.retard.view'])" href="{{ route('rh.justifications.retard.view') }}">Justification retard</a></li>
                                <li><a class="@active(['rh.justifications.absence.view'])" href="{{ route('rh.justifications.absence.view') }}">Justification absence</a></li>
                                @endcan
                            </ul>
                        </li>
                        @endcanany
                    </ul>
                </li>

                @canany(['devices.view', 'users.view', 'roles.view', 'logs.view'])
                <li class="menu-title"><span>ADMINISTRATION</span></li>
                <li>
                    <ul>
                        @can('devices.view')
                        <li class="@active(['admin.devices.index'])">
                            <a href="{{ route('admin.devices.index') }}">
                                <i class="ti ti-device-mobile"></i><span>Gestion Terminaux</span>
                            </a>
                        </li>
                        @endcan

                        @if(Auth::user()->hasRole('admin'))
                        @canany(['users.view', 'roles.view', 'logs.view'])
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(['admin.*'])">
                                <i class="ti ti-shield-share"></i><span>Gestion d'habilitation</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                @can('users.view')
                                <li><a class="@active(['admin.users'])" href="{{ route('admin.users') }}">Utilisateurs</a></li>
                                @endcan
                                @can('roles.view')
                                <li><a class="@active(['admin.roles'])" href="{{ route('admin.roles') }}">Roles & Permissions</a></li>
                                @endcan
                                @can('logs.view')
                                <li><a class="@active(['admin.logs'])" href="{{ route('admin.logs') }}">Journal d'acces</a></li>
                                @endcan
                            </ul>
                        </li>
                        @endcanany
                        @endif
                    </ul>
                </li>
                @endcanany

            </ul>
        </div>
    </div>
</div>
