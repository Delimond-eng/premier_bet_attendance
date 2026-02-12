@extends('layouts.auth')

@section('content')
<div class="main-wrapper" id="auth-app" v-cloak>
    <div class="login-page-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="login-glass-card shadow-2xl">
                        <div class="row g-0">
                            <!-- Section Illustration -->
                            <div class="col-lg-6 d-none d-lg-flex flex-column align-items-center justify-content-center bg-illustration">
                                <div class="illustration-content p-5 text-center">
                                    <!-- Embedded Vector SVG: Time Attendance Illustration -->
                                    <svg width="350" height="300" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg" class="floating-img mb-4">
                                        <circle cx="250" cy="200" r="150" fill="#EBF4FF"/>
                                        <rect x="180" y="120" width="140" height="180" rx="10" fill="#FFFFFF" stroke="#3B82F6" stroke-width="4"/>
                                        <rect x="200" y="145" width="100" height="15" rx="4" fill="#E2E8F0"/>
                                        <rect x="200" y="175" width="80" height="15" rx="4" fill="#E2E8F0"/>
                                        <rect x="200" y="205" width="90" height="15" rx="4" fill="#E2E8F0"/>
                                        <circle cx="250" cy="340" r="40" fill="#3B82F6" fill-opacity="0.1"/>
                                        <path d="M250 320V340L265 350" stroke="#3B82F6" stroke-width="4" stroke-linecap="round"/>
                                        <circle cx="250" cy="340" r="30" stroke="#3B82F6" stroke-width="3"/>
                                        <path d="M380 150L420 190L380 230" stroke="#3B82F6" stroke-width="8" stroke-linecap="round" stroke-linejoin="round" opacity="0.2"/>
                                        <rect x="100" y="250" width="40" height="40" rx="8" fill="#3B82F6" opacity="0.1"/>
                                    </svg>
                                    <h2 class="fw-bold text-dark mt-2">Salama Attendance</h2>
                                    <p class="text-muted lead px-4">La plateforme moderne pour la gestion simplifiée des présences.</p>
                                </div>
                            </div>

                            <!-- Section Formulaire -->
                            <div class="col-lg-6 col-md-12 bg-white">
                                <div class="form-container p-4 p-md-5">
                                    <div class="text-center mb-5">
                                        <!-- Time Attendance Icon (Clock & User) -->
                                        <div class="mb-3 d-flex justify-content-center">
                                            <div class="icon-circle bg-opacity-10">
                                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"></circle>
                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                    <path d="M12 12h.01"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <h3 class="fw-bold text-dark">Authentification !</h3>
                                        <p class="text-secondary">Connectez-vous pour accéder à votre espace</p>
                                    </div>

                                    <form @submit.prevent="handleLogin" class="mt-4">
                                        <div class="mb-4">
                                            <label class="form-label fw-bold text-dark-75 mb-2">Adresse Email</label>
                                            <input type="email" v-model="form.email" class="form-control pro-input shadow-sm" placeholder="exemple@salama.com" required>
                                        </div>

                                        <div class="mb-4">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label fw-bold text-dark-75 mb-0">Mot de passe</label>
                                                <a href="#" class="text-primary text-decoration-none small fw-bold">Oublié ?</a>
                                            </div>
                                            <div class="position-relative">
                                                <input :type="passwordVisible ? 'text' : 'password'" v-model="form.password" class="form-control pro-input shadow-sm" placeholder="••••••••" required>
                                                <button type="button" class="btn-eye" @click="passwordVisible = !passwordVisible">
                                                    <i class="ti" :class="passwordVisible ? 'ti-eye' : 'ti-eye-off'"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-4 d-flex align-items-center justify-content-between">
                                            <div class="form-check custom-checkbox">
                                                <input class="form-check-input" type="checkbox" id="remember" v-model="form.remember">
                                                <label class="form-check-label text-muted small" for="remember">Rester connecté</label>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary-pro w-100 fw-bold py-3 shadow-lg" :disabled="loading">
                                            <span v-if="loading" class="spinner-border text-white spinner-border-sm me-2"></span>
                                            <span v-else>Se Connecter</span>
                                        </button>
                                    </form>

                                    <div class="mt-5 text-center">
                                        <p class="text-muted small mb-0">Propulsé par <strong>Salama Group LTD</strong> &copy; 2024</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [v-cloak] { display: none; }

    .login-page-wrapper {
        background-color: #F8FAFC;
        min-height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.5);
        border-radius: 20px;
        overflow: hidden;
        min-height: 600px;
        display: flex;
        flex-direction: column;
    }

    .icon-circle {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bg-illustration {
        background-color: #F1F5F9;
        border-right: 1px solid #E2E8F0;
    }

    .pro-input {
        background-color: #FFFFFF !important;
        border: 1px solid #E2E8F0 !important;
        border-radius: 12px !important;
        padding: 15px 20px !important;
        font-size: 1rem;
        transition: all 0.3s ease;
        color: #1E293B !important;
        width: 100%; /* Ensure full width */
    }

    .pro-input:focus {
        border-color: #3B82F6 !important;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
        outline: none;
    }

    .btn-primary-pro {
        background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
        border: none;
        border-radius: 12px;
        color: white;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .btn-primary-pro:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        color: white;
    }

    .btn-eye {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94A3B8;
        cursor: pointer;
        font-size: 1.2rem;
        z-index: 10;
    }

    .custom-checkbox .form-check-input {
        border-radius: 4px;
        border: 1px solid #CBD5E1;
    }

    .custom-checkbox .form-check-input:checked {
        background-color: #3B82F6;
        border-color: #3B82F6;
    }

    .floating-img {
        animation: float 5s ease-in-out infinite;
    }

    @keyframes float {
        0% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-15px) rotate(2deg); }
        100% { transform: translateY(0px) rotate(0deg); }
    }

    .text-dark-75 {
        color: #334155;
    }

    /* Responsive adjustments */
    @media (max-width: 991.98px) {
        .login-glass-card {
            background: #ffffff;
            border-radius: 20px;
        }
        .login-page-wrapper {
            background-color: #ffffff;
            padding: 0;
        }
    }
</style>
@endsection

@push("scripts")
<script type="module" src="{{ asset('assets/js/scripts/auth.js') }}"></script>
@endpush
