@extends('layouts.public')

@section('title', __('Help & Support'))

@section('content')
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="mb-4">
                        <div class="blf-page-eyebrow">{{ __('Public Information') }}</div>
                        <h1 class="h3 text-navy fw-semibold mb-1">{{ __('Help, Support & Data Privacy') }}</h1>
                        <p class="small text-secondary mb-0">{{ __('Guidance for residents, tourists, finders, and item owners on Boracay Island.') }}</p>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body p-4">
                            <h2 class="h6 text-navy mb-2">{{ __('Where to Surrender Found Items') }}</h2>
                            <p class="small text-secondary mb-0">
                                {{ __('Found items may be turned over to the nearest Boracay Police Beach Booth (tent stand) or the Malay Municipal Police Station — Boracay Tourist Assistance Center. Unclaimed booth items are brought back to the Police Station at the end of shift for safekeeping.') }}
                            </p>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body p-4">
                            <h2 class="h6 text-navy mb-2">{{ __('Privacy Notice (RA 10173)') }}</h2>
                            <p class="small text-secondary mb-0">
                                {{ __('Personal contact details, finder information, and ownership verification evidence are kept strictly confidential and are never published on public listings. Only authorized institutional personnel handling the report or claim may review private records.') }}
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body p-4">
                            <h2 class="h6 text-navy mb-2">{{ __('Google Account Sign-In') }}</h2>
                            <p class="small text-secondary mb-0">
                                {{ __('The portal requests only your basic Google identity (name, verified email, and profile photo) to register your account and track your reports and claims.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
