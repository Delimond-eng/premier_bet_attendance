<div class="header">
    <div class="main-header">

        <div class="header-left">
            <a href="https://smarthr.co.in/demo/html/template/index.html" class="logo">
                <img src="https://smarthr.co.in/demo/html/template/assets/img/logo.svg" alt="Logo">
            </a>
            <a href="https://smarthr.co.in/demo/html/template/index.html" class="dark-logo">
                <img src="https://smarthr.co.in/demo/html/template/assets/img/logo-white.svg" alt="Logo">
            </a>
        </div>

        <a id="mobile_btn" class="mobile_btn" href="#sidebar">
            <span class="bar-icon">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </a>

        <div class="header-user">
            <div class="nav user-menu nav-list">

                <div class="me-auto d-flex align-items-center" id="header-search">
                    <!--<a id="toggle_btn" href="javascript:void(0);" class="btn btn-menubar me-2">
                        <i class="ti ti-arrow-bar-to-left"></i>
                    </a>-->
                    <!-- Search -->
                    <div class="input-group input-group-flat d-inline-flex me-2">
                        <input type="text" class="form-control" placeholder="Recherche...">
                        <span class="input-group-text p-2">
                            <i class="ti ti-search"></i>
                        </span>
                    </div>
                    <!-- /Search -->

                </div>

                <div class="d-flex align-items-center">
                    <div class="me-2">
                        <a href="#" class="btn btn-menubar btnFullscreen">
                            <i class="ti ti-maximize"></i>
                        </a>
                    </div>


                    <div class="dropdown profile-dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle d-flex align-items-center"
                           data-bs-toggle="dropdown">
									<span class="avatar avatar-md online">
										<img src="{{asset("assets/img/avatar.jpg")}}" alt="Img" class="img-fluid rounded-circle">
									</span>
                        </a>
                        <div class="dropdown-menu shadow-none">
                            <div class="card mb-0">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
												<span class="avatar avatar-lg me-2 avatar-rounded">
													<img src="{{asset("assets/img/avatar.jpg")}}" alt="img">
												</span>
                                        <div>
                                            <h5 class="mb-0">{{Auth::user()->name}}</h5>
                                            <p class="fs-12 fw-medium mb-0"><a href="#" >{{ Auth::user()->getRoleNames()->first() ?? Auth::user()->role }}</a></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item d-inline-flex align-items-center p-0 py-2 js-logout">
                                        <i class="ti ti-login me-2"></i>Deconnexion
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="dropdown mobile-user-menu">
            <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="https://smarthr.co.in/demo/html/template/profile.html">My Profile</a>
                <a class="dropdown-item" href="https://smarthr.co.in/demo/html/template/profile-settings.html">Settings</a>
                <form method="POST" action="{{ route('logout') }}" class="dropdown-item m-0 p-0">
                    @csrf
                    <button type="submit" class="btn btn-link w-100 text-start p-0 js-logout">Logout</button>
                </form>
            </div>
        </div>
        <!-- /Mobile Menu -->

    </div>

</div>
