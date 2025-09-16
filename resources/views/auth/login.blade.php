@extends('layouts.auth')
@php
    use App\Models\Utility;
    $logo = asset(Storage::url('uploads/logo/'));
    $settings = Utility::settings();
@endphp
@push('custom-scripts')
    @if ($settings['recaptcha_module'] == 'yes')
        {!! NoCaptcha::renderJs() !!}
    @endif
@endpush
@section('page-title')
    {{ __('Login') }}
@endsection

@section('auth-lang')
    @php
        $languages = App\Models\Utility::languages();
    @endphp
    <div class="lang-dropdown-only-desk">
        <li class="dropdown dash-h-item drp-language">
            <a class="dash-head-link dropdown-toggle btn" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="drp-text"> {{ ucFirst($languages[$lang]) }}
                </span>
            </a>
            <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                @foreach ($languages as $code => $language)
                    <a href="{{ route('login', $code) }}" tabindex="0"
                        class="dropdown-item {{ $code == $lang ? 'active' : '' }}">
                        <span>{{ ucFirst($language) }}</span>
                    </a>
                @endforeach
            </div>
        </li>
    </div>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between">
        <h2 class="mb-3 f-w-600">{{ __('Login') }}</h2>
    </div>
    {{ Form::open(['route' => 'login', 'method' => 'post', 'id' => 'loginForm']) }}
    @csrf
    @if (session('status'))
        <div class="mb-4 font-medium text-lg text-green-600 text-danger">
            {{session('status') }}
        </div>
    @endif
    <div class="">
        <div class="form-group mb-3">
            <label for="email"
                class="form-label d-flex align-items-center justify-content-between">{{ __('Email') }}</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email"
                value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')
                <div class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group mb-3">
            <label for="password"
                class="form-label d-flex align-items-center justify-content-between">{{ __('Password') }}</label>
            <input class="form-control @error('password') is-invalid @enderror" id="password" type="password"
                name="password" required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror

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
        <div class="form-group mb-4">
            @if (Route::has('password.request'))
                <a href="{{ route('password.request', $lang) }}"
                    class="text-xs d-flex align-items-center justify-content-between">{{ __('Forgot Your Password?') }}</a>
            @endif
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary btn-block mt-2" id="login_button">{{ __('Login') }}</button>

        </div>
        <!-- @if ($settings['enable_signup'] == 'on')
            <p class="my-4 text-center">{{ __("Don't have an account?") }} <a href="{{ route('register', $lang) }}"
                    class="text-primary">{{ __('Register') }}</a></p>
        @endif -->

        <!-- <div class="row">
            <div class="col-6 d-grid">
                <a href="{{ route('customer.login') }}"
                    class="btn-login btn btn-secondary btn-block mt-2 text-white">{{ __('Customer Login') }}</a>

            </div>
            <div class="col-6 d-grid">
                <a href="{{ route('vender.login') }}"
                    class="btn-login btn btn-secondary btn-block mt-2 text-white">{{ __('Vendor Login') }}</a>
            </div>
        </div> -->

    </div>
    {{ Form::close() }}
@endsection

@push('custom-scripts')
    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $("#loginForm").submit(function(e) {
                $("#login_button").attr("disabled", true);
                return true;
            });
        });
    </script>
@endpush
