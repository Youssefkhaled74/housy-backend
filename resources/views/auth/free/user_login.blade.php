@extends('auth.layouts.authentication')

@section('content')
    <!-- aiz-main-wrapper -->
    <div class="aiz-main-wrapper d-flex flex-column justify-content-center bg-white">
        <section class="bg-white overflow-hidden" style="min-height:100vh;">
            <div class="row" style="min-height: 100vh;">
                <!-- Left Side Image-->
                <div class="col-xxl-6 col-lg-7">
                    <div class="h-100">
                        <img src="{{ uploaded_asset(get_setting('customer_login_page_image')) }}" alt="" class="img-fit h-100">
                    </div>
                </div>
                
                <!-- Right Side -->
                <div class="col-xxl-6 col-lg-5">
                    <div class="right-content">
                        <div class="row align-items-center justify-content-center justify-content-lg-start h-100">
                            <div class="col-xxl-6 p-4 p-lg-5">
                                <!-- Site Icon -->
                                <div class="size-48px mb-3 mx-auto mx-lg-0">
                                    <img src="{{ uploaded_asset(get_setting('site_icon')) }}" alt="{{ translate('Site Icon')}}" class="img-fit h-100">
                                </div>
                                <!-- Titles -->
                                <div class="text-center text-lg-left">
                                    <h1 class="fs-20 fs-md-24 fw-700 text-primary" style="text-transform: uppercase;">{{ translate('Welcome Back !')}}</h1>
                                    <h5 class="fs-14 fw-400 text-dark">{{ translate('Login to your account')}}</h5>
                                </div>
                                <!-- Login form -->
                                <div class="pt-3 pt-lg-4 bg-white">
                                    <div class="">
                                        <form class="form-default loginForm" id="user-login-form"  role="form" action="{{ route('login') }}" method="POST">
                                            @csrf
                                            
                                            <!-- Email or Phone -->
                                            @if (addon_is_activated('otp_system'))
                                                <div class="form-group phone-form-group mb-1">
                                                    <label for="phone" class="fs-12 fw-700 text-soft-dark">{{  translate('Phone') }}</label>
                                                    <input type="tel" id="phone-code" class="form-control{{ $errors->has('phone') ? ' is-invalid' : '' }} rounded-0" value="{{ old('phone') }}" placeholder="" name="phone" autocomplete="off">
                                                </div>

                                                <input type="hidden" name="country_code" value="">
                                                
                                                <div class="form-group email-form-group mb-1 d-none">
                                                    <label for="email" class="fs-12 fw-700 text-soft-dark">{{  translate('Email') }}</label>
                                                    <input type="email" class="form-control rounded-0 {{ $errors->has('email') ? ' is-invalid' : '' }}" value="{{ old('email') }}" placeholder="{{  translate('johndoe@example.com') }}" name="email" id="email" autocomplete="off">
                                                    @if ($errors->has('email'))
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $errors->first('email') }}</strong>
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                <div class="form-group text-right">
                                                    <button class="btn btn-link p-0 text-primary" type="button" onclick="toggleEmailPhone(this)"><i>*{{ translate('Use Email Instead') }}</i></button>
                                                </div>
                                            @else
                                                <div class="form-group">
                                                    <label for="email" class="fs-12 fw-700 text-soft-dark">{{  translate('Email') }}</label>
                                                    <input type="email" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }} rounded-0" value="{{ old('email') }}" placeholder="{{  translate('johndoe@example.com') }}" name="email" id="email" autocomplete="off">
                                                    @if ($errors->has('email'))
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $errors->first('email') }}</strong>
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                            <div class="password-login-block">
                                                <!-- password -->
                                                <div class="form-group">
                                                    <label for="password" class="fs-12 fw-700 text-soft-dark">{{  translate('Password') }}</label>
                                                    <div class="position-relative">
                                                        <input type="password" class="form-control rounded-0 {{ $errors->has('password') ? ' is-invalid' : '' }}" placeholder="{{ translate('Password')}}" name="password" id="password">
                                                        <i class="password-toggle las la-2x la-eye"></i>
                                                    </div>
                                                </div>

                                                <!-- Recaptcha -->
                                                @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_customer_login') == 1)
                                                    
                                                    @if ($errors->has('g-recaptcha-response'))
                                                        <span class="border invalid-feedback rounded p-2 mb-3 bg-danger text-white" role="alert" style="display: block;">
                                                            <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                                                        </span>
                                                    @endif
                                                @endif

                                                <div class="row mb-2">
                                                    <!-- Remember Me -->
                                                    <div class="col-5">
                                                        <label class="aiz-checkbox">
                                                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                                            <span class="has-transition fs-12 fw-400 text-gray-dark hov-text-primary">{{  translate('Remember Me') }}</span>
                                                            <span class="aiz-square-check"></span>
                                                        </label>
                                                    </div>
                                                    <!-- Forgot password -->
                                                    <div class="col-7 text-right">
                                                        @if(get_setting('login_with_otp'))
                                                            <a href="javascript:void(0);" class="text-reset fs-12 fw-400 text-gray-dark hov-text-primary toggle-login-with-otp" onclick="toggleLoginPassOTP(this)">{{ translate('Login With OTP') }} / </a>
                                                        @endif
                                                        <a href="{{ route('password.request') }}" class="text-reset fs-12 fw-400 text-gray-dark hov-text-primary"><u>{{ translate('Forgot password?')}}</u></a>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="mb-4 mt-4">
                                                <button type="submit" class="btn btn-primary btn-block fw-700 fs-14 rounded-0 submit-button">{{  translate('Login') }}</button>
                                            </div>
                                        </form>

                                        <!-- DEMO MODE -->
                                        @if (env("DEMO_MODE") == "On")
                                            <div class="mb-4">
                                                <table class="table table-bordered mb-0">
                                                    <tbody>
                                                        <tr>
                                                            <td>{{ translate('Customer Account')}}</td>
                                                            <td>
                                                                <button class="btn btn-info btn-sm" onclick="autoFillCustomer()">{{ translate('Copy credentials') }}</button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif

                                        <!-- Social Login -->
                                        @if(get_setting('google_login') == 1 || get_setting('facebook_login') == 1 || get_setting('twitter_login') == 1 || get_setting('apple_login') == 1)
                                            <div class="text-center mb-3">
                                                <span class="bg-white fs-12 text-gray">{{ translate('Or Login With')}}</span>
                                            </div>
                                            <ul class="list-inline social colored text-center mb-4">
                                                @if (get_setting('facebook_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'facebook']) }}" class="facebook">
                                                            <i class="lab la-facebook-f"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if(get_setting('google_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'google']) }}" class="google">
                                                            <i class="lab la-google"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (get_setting('twitter_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'twitter']) }}" class="twitter">
                                                            <i class="lab la-twitter"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (get_setting('apple_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'apple']) }}"
                                                            class="apple">
                                                            <i class="lab la-apple"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        @endif
                                    </div>

                                    <!-- Register Now -->
                                    <p class="fs-12 text-gray mb-0">
                                        {{ translate('Dont have an account?')}}
                                        <a href="{{ route(get_setting('customer_registration_verify') === '1' ? 'registration.verification' : 'user.registration') }}" class="ml-2 fs-14 fw-700 animate-underline-primary">{{ translate('Register Now')}}</a>
                                        {{-- <a href="{{ route('user.registration') }}" class="ml-2 fs-14 fw-700 animate-underline-primary">{{ translate('Register Now')}}</a> --}}
                                    </p>
                                    <!-- Go Back -->
                                    <a href="{{ url()->previous() }}" class="mt-3 fs-14 fw-700 d-flex align-items-center text-primary" style="max-width: fit-content;">
                                        <i class="las la-arrow-left fs-20 mr-1"></i>
                                        {{ translate('Back to Previous Page')}}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function autoFillCustomer(){
            $('#email').val('customer@example.com');
            $('#password').val('123456');
        }
    </script>

    @if(get_setting('google_recaptcha') == 1 && get_setting('recaptcha_customer_login') == 1)
        <script src="https://www.google.com/recaptcha/api.js?render={{ env('CAPTCHA_KEY') }}"></script>
        
        <script type="text/javascript">
                document.getElementById('user-login-form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    grecaptcha.ready(function() {
                        grecaptcha.execute(`{{ env('CAPTCHA_KEY') }}`, {action: 'register'}).then(function(token) {
                            var input = document.createElement('input');
                            input.setAttribute('type', 'hidden');
                            input.setAttribute('name', 'g-recaptcha-response');
                            input.setAttribute('value', token);
                            e.target.appendChild(input);

                            var actionInput = document.createElement('input');
                            actionInput.setAttribute('type', 'hidden');
                            actionInput.setAttribute('name', 'recaptcha_action');
                            actionInput.setAttribute('value', 'recaptcha_customer_login');
                            e.target.appendChild(actionInput);
                            
                            e.target.submit();
                        });
                    });
                });
        </script>
    @endif
@endsection