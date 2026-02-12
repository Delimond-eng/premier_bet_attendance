@extends("layouts.app")


@section("content")
    <div class="content">

        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Historique des Présences de l'agent : TAMBUE IGORE</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="https://smarthr.co.in/demo/html/template/index.html"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            RH
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Historique des présences</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">

                <div class="me-2 mb-2">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Exporter
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-end p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>Exporter en PDF</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Exporter en Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
        <!-- /Breadcrumb -->

        <div class="row">
            <div class="col-xl-3 col-lg-4 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="mb-3 text-center">
                            <h6 class="fw-medium text-gray-5 mb-2">Profile Agent</h6>
                            <h4>OREOR Gaston delimond</h4>
                        </div>
                        <div class="attendance-circle-progress mx-auto mb-3"  data-value='65'>
									<span class="progress-left">
										<span class="progress-bar border-success"></span>
									</span>
                            <span class="progress-right">
										<span class="progress-bar border-success"></span>
									</span>
                            <div class="avatar avatar-xxl avatar-rounded">
                                <img src="{{asset("assets/img/profiles/avatar-27.jpg")}}" alt="Img">
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="badge badge-md badge-primary mb-3">Présent</div>
                            <h6 class="fw-medium d-flex align-items-center justify-content-center mb-3">
                                <i class="ti ti-fingerprint text-primary me-1"></i>
                                Arrivé à  10.00 AM
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-9 col-lg-8 d-flex">
                <div class="row flex-fill">
                    <div class="col-xl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="border-bottom mb-2 pb-2">
                                    <span class="avatar avatar-sm bg-primary mb-2"><i class="ti ti-clock-stop"></i></span>
                                    <h2 class="mb-2">8.36 / <span class="fs-20 text-gray-5"> 9</span></h2>
                                    <p class="fw-medium text-truncate">Total Heure d'aujourd'hui</p>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="border-bottom mb-2 pb-2">
                                    <span class="avatar avatar-sm bg-dark mb-2"><i class="ti ti-clock-up"></i></span>
                                    <h2 class="mb-2">10 / <span class="fs-20 text-gray-5"> 40</span></h2>
                                    <p class="fw-medium text-truncate">Total Heure Hebdo</p>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="border-bottom mb-2 pb-2">
                                    <span class="avatar avatar-sm bg-info mb-2"><i class="ti ti-calendar-up"></i></span>
                                    <h2 class="mb-2">75 / <span class="fs-20 text-gray-5"> 98</span></h2>
                                    <p class="fw-medium text-truncate">Total Heure Mensuel</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-4">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-dark-transparent me-1"></i>Horaire d'aujourd'hui</p>
                                            <h3>Matinale</h3>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-success me-1"></i>Heure début</p>
                                            <h3>08h 36m</h3>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-warning me-1"></i>Heure Fin</p>
                                            <h3>22m 15s</h3>
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="progress bg-transparent-dark mb-3" style="height: 24px;">
                                            <div class="progress-bar bg-white rounded" role="progressbar" style="width: 18%;"></div>
                                            <div class="progress-bar bg-success rounded me-2" role="progressbar" style="width: 18%;"></div>
                                            <div class="progress-bar bg-warning rounded me-2" role="progressbar" style="width: 5%;"></div>
                                            <div class="progress-bar bg-success rounded me-2" role="progressbar" style="width: 28%;"></div>
                                            <div class="progress-bar bg-warning rounded me-2" role="progressbar" style="width: 17%;"></div>
                                            <div class="progress-bar bg-success rounded me-2" role="progressbar" style="width: 22%;"></div>
                                            <div class="progress-bar bg-warning rounded me-2" role="progressbar" style="width: 5%;"></div>
                                            <div class="progress-bar bg-info rounded me-2" role="progressbar" style="width: 3%;"></div>
                                            <div class="progress-bar bg-info rounded" role="progressbar" style="width: 2%;"></div>
                                            <div class="progress-bar bg-white rounded" role="progressbar" style="width: 18%;"></div>
                                        </div>
                                    </div>
                                    <div class="co-md-12">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                                            <span class="fs-10">06:00</span>
                                            <span class="fs-10">07:00</span>
                                            <span class="fs-10">08:00</span>
                                            <span class="fs-10">09:00</span>
                                            <span class="fs-10">10:00</span>
                                            <span class="fs-10">11:00</span>
                                            <span class="fs-10">12:00</span>
                                            <span class="fs-10">01:00</span>
                                            <span class="fs-10">02:00</span>
                                            <span class="fs-10">03:00</span>
                                            <span class="fs-10">04:00</span>
                                            <span class="fs-10">05:00</span>
                                            <span class="fs-10">06:00</span>
                                            <span class="fs-10">07:00</span>
                                            <span class="fs-10">08:00</span>
                                            <span class="fs-10">09:00</span>
                                            <span class="fs-10">10:00</span>
                                            <span class="fs-10">11:00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Historique des présences</h5>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                    <div class="me-3">
                        <div class="input-icon position-relative">
									<span class="input-icon-addon">
										<i class="ti ti-calendar text-gray-9"></i>
									</span>
                            <input type="text" class="form-control date-range bookingrange" placeholder="dd/mm/yyyy - dd/mm/yyyy">
                        </div>
                    </div>
                    <div class="dropdown me-3">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                            Statut
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-end p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Present</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Absent</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Retard</a>
                            </li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                            Filtrer par : Cette sémaine
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-end p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Récent</a>
                            </li>

                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Cette Semaine</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Semaine Passé</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Ce Mois</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Mois Passé</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="custom-datatable-filter table-responsive">
                    <table class="table datatable">
                        <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Statut</th>
                            <th>Retard</th>
                            <th>Total Heures</th>
                        </tr>
                        </thead>
                        <tbody>
                            @for($i=0; $i<10; $i++)
                                <tr>
                                    <td>
                                        14 Jan 2024
                                    </td>
                                    <td>09:32 AM</td>

                                    <td>
                                        06:45 PM
                                    </td>

                                    <td>
                                        <span class="badge badge-success-transparent d-inline-flex align-items-center">
                                            <i class="ti ti-point-filled me-1"></i>Present
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning d-inline-flex align-items-center">
                                            <i class="ti ti-clock-hour-11 me-1"></i>8.55 Min
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge badge-success d-inline-flex align-items-center">
                                            <i class="ti ti-clock-hour-11 me-1"></i>8.55 Hrs
                                        </span>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
@endsection
