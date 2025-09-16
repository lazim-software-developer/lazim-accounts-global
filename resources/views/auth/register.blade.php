@extends('layouts.auth')
@php
    use App\Models\Utility;
    $logo = asset(Storage::url('uploads/logo/'));
    $company_logo = App\Models\Utility::getValByName('company_logo');
    $settings = Utility::settings();
@endphp

@section('page-title')
    {{ __('Register') }}
@endsection
@push('custom-scripts')
    @if ($settings['recaptcha_module'] == 'yes')
        {!! NoCaptcha::renderJs() !!}
    @endif
@endpush

@section('auth-lang')
    @php
        $languages = App\Models\Utility::languages();
    @endphp
    <div class="lang-dropdown-only-desk">
        <li class="dropdown dash-h-item drp-language">
            <a class="dash-head-link dropdown-toggle btn" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="drp-text">{{ ucfirst($languages[$lang] ?? '') }}</span>
            </a>
            <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                @foreach ($languages as $code => $language)
                    <a href="{{ route('register', ['lang' => $code]) }}" tabindex="0"
                        class="dropdown-item {{ $code == $lang ? 'active' : '' }}">
                        <span>{{ ucfirst($language) }}</span>
                    </a>
                @endforeach
            </div>
        </li>
    </div>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between">
        <h2 class="mb-3 f-w-600">{{ __('Register') }}</h2>
    </div>
    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="">
            @if (session('status'))
                <div class="mb-4 font-medium text-lg text-green-600 text-danger">
                    {{ __('Email SMTP settings does not configured so please contact to your site admin.') }}
                </div>
            @endif
            <div class="form-group">
                <label for="name"
                    class="form-label d-flex align-items-center justify-content-between">{{ __('Full Name') }}</label>
                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                    value="{{ old('name') }}" placeholder="Enter Your Name" required autocomplete="name" autofocus>
                @error('name')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            <div class="form-group">
                <label for="email"
                    class="form-label d-flex align-items-center justify-content-between">{{ __('Email') }}</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" type="email"
                    name="email" value="{{ old('email') }}" placeholder="Enter Your Email" required autocomplete="email"
                    autofocus>
                @error('email')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                <div class="invalid-feedback">
                    {{ __('Please fill in your email') }}
                </div>
            </div>
            <div class="form-group">
                <label for="password"
                    class="form-label d-flex align-items-center justify-content-between">{{ __('Password') }}</label>
                <input id="password" type="password" data-indicator="pwindicator"
                    class="form-control pwstrength @error('password') is-invalid @enderror" name="password"
                    placeholder="Enter Your Password" required autocomplete="new-password">
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
                <div id="pwindicator" class="pwindicator">
                    <div class="bar"></div>
                    <div class="label"></div>
                </div>
            </div>
            <div class="form-group">
                <label for="password_confirmation"
                    class="form-label d-flex align-items-center justify-content-between">{{ __('Password Confirmation') }}</label>
                <input id="password_confirmation" type="password" class="form-control" name="password_confirmation"
                    placeholder="Your Confirm Password" required autocomplete="new-password">
            </div>

            @if ($settings['recaptcha_module'] == 'yes')
                <div class="form-group mb-3">
                    {!! NoCaptcha::display() !!}
                    @error('g-recaptcha-response')
                        <span class="small text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            @endif

            <div class="d-grid">
                <input type="hidden" name="ref_code" value="{{!empty($ref) ? $ref : ''}}">

                <button type="submit" class="btn-login btn btn-primary btn-block mt-2"
                    id="login_button">{{ __('Register') }}</button>
            </div>
            <p class="my-4 text-center">{{ __("Already' have an account?") }} <a href="{{ route('login',$lang) }}"
                    class="text-primary">{{ __('Login') }}</a></p>

        </div>
        {{ Form::close() }}
    @endsection
