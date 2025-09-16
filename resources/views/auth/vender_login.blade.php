@extends('layouts.auth')
@php
    $logo=asset(Storage::url('uploads/logo/'));
    $company_logo=Utility::getValByName('company_logo');
    $settings = Utility::settings();

@endphp
@push('custom-scripts')
    @if($settings['recaptcha_module'] == 'yes')
        {!! NoCaptcha::renderJs() !!}
    @endif
@endpush
@section('page-title')
    {{__('Login')}}
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
                @foreach($languages as $code => $language)
                    <a href="{{ route('vender.login.lang',$code) }}" tabindex="0" class="dropdown-item {{ $code == $lang ? 'active':'' }}">
                        <span>{{ ucFirst($language)}}</span>
                    </a>
                @endforeach
            </div>
        </li>
    </div>
@endsection


@section('content')
    <div class="">
        <h2 class="mb-3 f-w-600">{{__('Sign in')}}</h2>
    </div>
    {{Form::open(array('route'=>'vender.login','method'=>'post','id'=>'loginForm'))}}
    @csrf
    @if (session('status'))
        <div class="mb-4 font-medium text-lg text-green-600 text-danger">
            {{session('status') }}
        </div>
    @endif
    <div class="">
        <div class="form-group mb-3">
            <label for="email" class="form-label">{{__('Enter Email address')}}</label>
            <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')
            <div class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group mb-3">
            <label for="password" class="form-label">{{__('Enter Password')}}</label>
            <input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" required autocomplete="current-password">
            @error('password')
            <div class="invalid-feedback" role="alert">{{ $message }}</div>
            @enderror

        </div>

        @if($settings['recaptcha_module'] == 'yes')
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
                <a href="{{ route('vender.change.langPass', $lang) }}" class="text-xs">{{ __('Forgot Your Password?') }}</a>
        </div>

        <div class="d-grid">
            <button type="submit" class="btn-login btn btn-primary btn-block mt-2" id="login_button">{{__('Sign In')}}</button>

        </div>
        <div class="d-flex flex-wrap autorized-btn">
            <a href="{{route('login')}}" class="btn-login btn btn-secondary btn-block mt-2 text-white">{{__('User Login')}}</a>
            {{-- <a href="{{route('vender.login')}}" class="btn-login btn btn-secondary btn-block mt-2 text-white">{{__('Vendor Login')}}</a> --}}
            <a href="{{route('customer.login')}}" class="btn btn-secondary btn-block mt-2 text-white">{{__('Customer Login')}}</a>
        </div>
    </div>
    {{Form::close()}}
@endsection

<script src="{{asset('js/jquery.min.js')}}"></script>
<script>
    $(document).ready(function () {
        $("#form_data").submit(function (e) {
            $("#login_button").attr("disabled", true);
            return true;
        });
    });
</script>





