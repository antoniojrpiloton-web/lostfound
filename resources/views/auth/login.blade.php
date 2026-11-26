@extends('layouts.public')

@section('title', __('Sign In'))

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-5">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="text-center mb-3">
                                <img src="{{ asset('images/boracay-lf-logo.png') }}" alt="{{ __('Boracay Lost and Found') }}" width="88" height="88" class="mb-2" style="object-fit: contain;">
                                <h1 class="h4 text-navy fw-semibold mb-1">{{ __('Sign in to Boracay Lost and Found') }}</h1>
                                <p class="text-secondary small mb-0">
                                    {{ __('Use your Google account to submit lost or found reports, track report status, or file an ownership claim.') }}
                                </p>
                            </div>

                            @if ($errors->has('google'))
                                <div class="alert alert-danger py-2 px-3 small" role="alert">
                                    <i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i>
                                    {{ $errors->first('google') }}
                                </div>
                            @endif

                            <a href="{{ route('auth.google.redirect', request()->only('invite')) }}" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-google" aria-hidden="true"></i>
                                <span>{{ __('Continue with Google') }}</span>
                            </a>

                            <hr class="my-4">

                            <p class="small text-body-secondary mb-0">
                                {{ __('Police and authorized desk personnel also sign in with Google. Administrative access is granted only through an authorized invitation.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
