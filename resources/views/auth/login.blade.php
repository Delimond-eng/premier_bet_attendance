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
                                    <div class="mb-4 floating-img">
                                        <img src="{{ asset('assets/img/salama.png') }}" alt="Logo" style="max-width: 280px;">
                                    </div>
                                    <h2 class="fw-bold text-dark mt-2">SALAMA ATTANDANCE</h2>
                                    <p class="text-muted lead px-4">La plateforme moderne pour la gestion simplifiée des présences et du temps de travail.</p>
                                </div>
                            </div>

                            <!-- Section Formulaire -->
                            <div class="col-lg-6 col-md-12 bg-white">
                                <div class="form-container p-4 p-md-5">
                                    <div class="text-center mb-5">
                                        <div class="d-flex justify-content-center">
                                            <div class="icon-circle">
                                                <img src="{{ asset('assets/img/salama.png') }}" alt="Logo Small" style="height: 50px;">
                                            </div>
                                        </div>
                                    </div>

                                    <form @submit.prevent="handleLogin" class="mt-4">
                                        <div class="mb-4">
                                            <label class="form-label fw-bold text-dark-75 mb-2">Adresse Email</label>
                                            <div class="input-group-pro">
                                                <span class="input-pro-icon"><i class="ti ti-mail"></i></span>
                                                <input type="email" v-model="form.email" class="form-control pro-input" placeholder="nom@rdtech.com" required>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label fw-bold text-dark-75 mb-0">Mot de passe</label>
                                                <a href="#" class="text-accent text-decoration-none small fw-bold">Oublié ?</a>
                                            </div>
                                            <div class="input-group-pro position-relative">
                                                <span class="input-pro-icon"><i class="ti ti-lock"></i></span>
                                                <input :type="passwordVisible ? 'text' : 'password'" v-model="form.password" class="form-control pro-input" placeholder="••••••••" required>
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

                                        <button type="submit" class="btn btn-primary-pro w-100 fw-bold py-3" :disabled="loading">
                                            <span v-if="loading" class="spinner-border text-white spinner-border-sm me-2"></span>
                                            <span v-else>Se Connecter</span>
                                        </button>
                                    </form>

                                    <div class="mt-5 text-center">
                                        <p class="text-muted small mb-0">BY SALAMA DRC &copy; 2026</p>
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
    /* Variables de couleurs basées sur le logo */
    :root {
        --rd-primary: #004182;
        --rd-primary-disabled: #628DB8;
        --rd-accent: #42bad2;
        --rd-bg: #f0f4f8;
    }

    [v-cloak] { display: none; }

    .login-page-wrapper {
        background-color: var(--rd-bg);
        background-image: radial-gradient(circle at 20% 30%, rgba(66, 186, 210, 0.1) 0%, transparent 40%),
                          radial-gradient(circle at 80% 70%, rgba(0, 65, 130, 0.05) 0%, transparent 40%);
        min-height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-glass-card {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 24px;
        overflow: hidden;
        min-height: 600px;
    }

    .bg-illustration {
        background-color: #f8fafc;
        border-right: 1px solid #e2e8f0;
        position: relative;
    }

    .bg-illustration::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2342bad2' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .icon-circle {
        background: rgba(66, 186, 210, 0.1);
        padding: 15px;
        border-radius: 16px;
    }

    .input-group-pro {
        position: relative;
        display: flex;
        align-items: center;
    }

    .input-pro-icon {
        position: absolute;
        left: 15px;
        color: #94a3b8;
        font-size: 1.2rem;
        z-index: 5;
    }

    .pro-input {
        background-color: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 12px 15px 12px 45px !important;
        height: 52px !important;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }

    .pro-input:focus {
        border-color: var(--rd-accent) !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 4px rgba(66, 186, 210, 0.1) !important;
    }

    .btn-primary-pro {
        background: var(--rd-primary);
        border: none;
        border-radius: 12px;
        color: white;
        height: 52px;
        font-size: 1rem;
        letter-spacing: 0.5px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(0, 65, 130, 0.2);
    }
    .btn-primary-pro:disabled{
        background: var(--rd-primary-disabled);
        border: none;
        border-radius: 12px;
        color: white;
        height: 52px;
        font-size: 1rem;
        letter-spacing: 0.5px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(0, 65, 130, 0.2);
    }

    .btn-primary-pro:hover {
        background: #003366;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 65, 130, 0.3);
        color: white;
    }

    .text-accent {
        color: var(--rd-accent);
    }

    .btn-eye {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        z-index: 10;
    }

    .floating-img {
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    @media (max-width: 991.98px) {
        .login-glass-card { border-radius: 0; min-height: 100vh; width: 100%; }
        .login-page-wrapper { padding: 0; background: #fff; }
    }
</style>
@endsection

@push("scripts")
<script type="module" src="{{ asset('assets/js/scripts/auth.js') }}"></script>
@endpush
