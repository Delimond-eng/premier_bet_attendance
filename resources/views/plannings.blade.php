@extends("layouts.app")


@section("content")

    <div class="content">

        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Gestion de planning des rotations</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            Admin
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Gestion planning.</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">

                <div class="mb-2">
                    <a href="#" class="btn btn-primary d-flex align-items-center"><i class="ti ti-upload me-2"></i>Charger le planning excel</a>
                </div>

            </div>
        </div>
        <!-- /Breadcrumb -->

        <!-- Leads List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Planning de rotation par station</h5>

            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle text-center">
                            <thead class="table-dark">
                            <tr>
                                <th class="text-start">Agent</th>
                                <th>Lundi</th>
                                <th>Mardi</th>
                                <th>Mercredi</th>
                                <th>Jeudi</th>
                                <th>Vendredi</th>
                                <th>Samedi</th>
                                <th>Dimanche</th>
                            </tr>
                            </thead>
                            <tbody>

                            <!-- SECTION SITE -->
                            <tr class="table-primary">
                                <td colspan="8" class="text-uppercase fw-bold fs-5 text-start">
                                    <h5>STATION PARIS</h5>
                                </td>
                            </tr>

                            <!-- AGENT -->
                            <tr>
                                <td class="text-start">
                                    <strong class="me-2">ST0470</strong>
                                    MAMOLI NGWATA ANDY
                                </td>
                                <td>07:00 - 16:30</td>
                                <td>17:00 - 07:00</td>
                                <td><span class="badge bg-danger">OFF</span></td>
                                <td>07:00 - 16:30</td>
                                <td>17:00 - 07:00</td>
                                <td><span class="badge bg-danger">OFF</span></td>
                                <td>07:00 - 16:30</td>
                            </tr>

                            <tr>
                                <td class="text-start">
                                    <strong class="me-2">ST0473</strong>
                                    KAKESE LUMALIZA
                                </td>
                                <td><span class="badge bg-danger">OFF</span></td>
                                <td>07:00 - 16:30</td>
                                <td>17:00 - 07:00</td>
                                <td><span class="badge bg-danger">OFF</span></td>
                                <td>07:00 - 16:30</td>
                                <td>17:00 - 07:00</td>
                                <td><span class="badge bg-danger">OFF</span></td>
                            </tr>

                            <!-- AUTRE SECTION -->
                            <tr class="table-primary">
                                <td colspan="8" class="text-uppercase fw-bold fs-5 text-start">
                                    <h5>STATION GAMBIE</h5>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-start">
                                    <strong class="me-2">ST0490</strong>
                                    MOZINGO MOBA ROSTAND
                                </td>
                                <td><span class="badge bg-danger">OFF</span></td>
                                <td>07:00 - 16:30</td>
                                <td>17:00 - 07:00</td>
                                <td><span class="badge bg-danger">OFF</span></td>
                                <td>07:00 - 16:30</td>
                                <td>17:00 - 07:00</td>
                                <td><span class="badge bg-danger">OFF</span></td>
                            </tr>

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
        <!-- /Leads List -->
    </div>

@endsection

