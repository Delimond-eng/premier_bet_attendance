<div class="sidebar" id="sidebar">
    <!-- Logo -->
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
    <!-- /Logo -->
    <div class="modern-profile p-3 pb-0">
        <div class="text-center rounded bg-light p-3 mb-4 user-profile">
            <div class="avatar avatar-lg online mb-3">
                <img src="{{asset("assets/img/profiles/avatar-02.jpg")}}" alt="Img" class="img-fluid rounded-circle">
            </div>
            <h6 class="fs-12 fw-normal mb-1">{{Auth::user()->name}}</h6>
            <p class="fs-10">{{Auth::user()->role}}</p>
        </div>
    </div>
    <div class="sidebar-header p-3 pb-0 pt-2">
        <div class="text-center rounded bg-light p-2 mb-4 sidebar-profile d-flex align-items-center">
            <div class="avatar avatar-md onlin">
                <img src="{{asset("assets/img/profiles/avatar-02.jpg")}}" alt="Img" class="img-fluid rounded-circle">
            </div>
            <div class="text-start sidebar-profile-info ms-2">
                <h6 class="fs-12 fw-normal mb-1">{{Auth::user()->name}}</h6>
                <p class="fs-10">{{Auth::user()->role}}</p>
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
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(["dashboard","presences.live"])">
                                <i class="ti ti-smart-home"></i>
                                <span>Tableau de bord</span>
                                <span class="badge badge-danger fs-10 fw-medium text-white p-1">admin</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="@active(["dashboard"])" href="{{route("dashboard")}}">Vue globale</a></li>
                                <li><a class="@active(["presences.live"])" href="{{ route('presences.live') }}">Journal de pointage</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <li class="menu-title"><span>RH</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(["rh.*"])">
                                <i class="ti ti-calendar-time"></i><span>Gestion des horaires</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="@active(["rh.horaires.view"])" href="{{route("rh.horaires.view")}}">Horaire de présence</a></li>
                                <li><a class="@active(["rh.groupes.view"])" href="{{route("rh.groupes.view")}}">Groupe agent</a></li>
                                <li><a class="@active(["rh.plannings.view"])" href="{{route("rh.plannings.view")}}">Planning de rotation</a></li>
                            </ul>
                        </li>

                        <li class="@active(["stations.view"])">
                            <a href="{{route("stations.view")}}">
                                <i class="ti ti-location-cog"></i><span>Gestion stations</span>
                            </a>
                        </li>

                        <li class="@active(["agents.view","agents.view.attendances"])">
                            <a href="{{route("agents.view")}}">
                                <i class="ti ti-user-cog"></i><span>Gestion agents</span>
                            </a>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(["reports.*"])">
                                <i class="ti ti-report"></i><span>Rapports</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="@active(["reports.presences"])" href="{{ route('reports.presences') }}">Présences journalières</a></li>
                                <li><a class="@active(["reports.absences.daily.view"])" href="{{ route('reports.absences.daily.view') }}">Absences journalières</a></li>
                                <li><a class="@active(["reports.weekly.view"])" href="{{ route('reports.weekly.view') }}">Présences hebdomadaire</a></li>
                                <li><a class="@active(["reports.monthly.view"])" href="{{ route('reports.monthly.view') }}">Présences mensuelles</a></li>
                            </ul>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(["rh.*"])">
                                <i class="ti ti-user-screen"></i><span>Ressources humaines</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="@active(["rh.timesheet.view"])" href="{{ route('rh.timesheet.view') }}">Pointage mensuel</a></li>
                                <li class="submenu">
                                    <a href="javascript:void(0);" class="@active(["rh.conges.view", "rh.attributions.view" ])">Congés & attribution<span class="menu-arrow"></span></a>
                                    <ul>
                                        <li><a class="@active(["rh.conges.view"])" href="{{ route('rh.conges.view') }}">Congés</a></li>
                                        <li><a class="@active(["rh.attributions.view"])" href="{{ route('rh.attributions.view') }}">Attribution agent</a></li>
                                    </ul>
                                </li>
                                <li><a class="@active(["rh.authorizations.view"])" href="{{ route('rh.authorizations.view') }}">Autorisation spéciale</a></li>
                                <li><a class="@active(["rh.justifications.retard.view"])"  href="{{ route('rh.justifications.retard.view') }}">Justification retard</a></li>
                                <li><a class="@active(["rh.justifications.absence.view"])" href="{{ route('rh.justifications.absence.view') }}">Justification absence</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <li class="menu-title"><span>ADMINISTRATION</span></li>
                <li>
                    <ul>
                        <li class="submenu">
                            <a href="javascript:void(0);" class="@active(["admin.*"])">
                                <i class="ti ti-shield-share"></i><span>Gestion d'habilitation</span>
                                <span class="menu-arrow"></span>
                            </a>
                            <ul>
                                <li><a class="@active(["admin.users"])" href="{{route("admin.users")}}" class="@active(["admin.users"])">Utilisateurs</a></li>
                                <li><a class="@active(["admin.roles"])" href="{{route("admin.roles")}}" class="@active(["admin.roles"])">Roles & Permissions</a></li>
                                <li><a href="#">Journal d'accès</a></li>
                            </ul>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>
    </div>
</div>
