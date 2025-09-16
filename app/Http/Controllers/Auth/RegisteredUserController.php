<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Utility;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */

    public function __construct()
    {
        if (!file_exists(storage_path() . "/installed")) {
            header('location:install');
            die;
        }

        $settings = Utility::settings();

        if ($settings['recaptcha_module'] == 'yes') {
            config(['captcha.secret' => $settings['google_recaptcha_secret']]);
            config(['captcha.sitekey' => $settings['google_recaptcha_key']]);
        }
    }

    // public function create($lang = '')
    // {
    //     if($lang == '')
    //     {
    //         $lang = Utility::getValByName('default_language');
    //     }

    //     \App::setLocale($lang);  

    //     return view('auth.register', compact('lang'));
    // }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {

        // ReCpatcha
        $settings = Utility::settings();

        if ($settings['recaptcha_module'] == 'yes') {
            $validation['g-recaptcha-response'] = 'required|captcha';
        } else {
            $validation = [];
        }
        $this->validate($request, $validation);

        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $password = Hash::make($request->password);
        if (isset($request->created_by_lazim)) {
            $password = null;
        }
        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => $password,
            'type'              => 'company',
            'lang'              => \App\Models\Utility::getValByName('default_language'),
            'plan'              => 1,
            'created_by'        => 1,
            'referral_code'         => Utility::generateReferralCode(),
            'used_referral_code'    => isset($request->ref_code) ? $request->ref_code : 0,
            'owner_association_id' => $request->owner_association_id
        ]);

        Auth::login($user);

        if ($settings['email_verification'] == 'off') {
            try {
                $uArr = [
                    'email'     => $request->email,
                    'password'  => $request->password,
                ];
                Utility::sendEmailTemplate('user_created', [$user->id => $user->email], $uArr);
            } catch (\Throwable $th) {
            }
        }

        if (Utility::getValByName('email_verification') == 'on' && !isset($request->created_by_lazim) ) {
            try {
                Utility::getSMTPDetails(1);
                event(new Registered($user));
                $role_r = Role::findByName('company');
                Utility::chartOfAccountData($user);
                $user->assignRole($role_r);
                $user->userDefaultDataRegister($user->id);
            } catch (\Exception $e) {
                $user->delete();

                return redirect('/register/lang?')->with('status', __('Email SMTP settings does not configure so please contact to your site admin.'));
            }
            return view('auth.verify-email');
        } else {
            // $user = User::create([
            //     'name' => $request->name,
            //     'email' => $request->email,
            //     'password' => Hash::make($request->password),
            //     'type' => 'company',
            //     'lang' => \App\Models\Utility::getValByName('default_language'),
            //     'created_by' => 1,
            // ]);

            $user->email_verified_at = date('h:i:s');
            $user->save();

            // Auth::login($user);

            $role_r = Role::findByName('company');

            $user->assignRole($role_r);
            $user->userDefaultDataRegister($user->id);
            // event(new Registered($user));

            // try {
            //     $resp = Utility::sendEmailTemplate('user_created', [$user->id => $user->email], $uArr);
            // } catch (\Exception $e) {
            //     $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            // }

            $userArr = [
                'email' => $user->email,
                'password' => $user->password,
            ];

            $resp = Utility::sendUserEmailTemplate('new_user', [$user->id => $user->email], $userArr);
            if($request->created_by_lazim){
                return '';
            }
            return redirect(RouteServiceProvider::HOME);
        }
    }

    public function showRegistrationForm(Request $request, $ref = '', $lang = '')
    {
        $settings = Utility::settings();
        if ($settings['enable_signup'] == 'on') {
            $langList = Utility::langList();
            $lang = array_key_exists($lang, $langList) ? $lang : 'en';

            if ($lang == '') {
                $lang = Utility::getValByName('default_language');
            }

            \App::setLocale($lang);

            if ($ref == '') {
                $ref = 0;
            }

            $refCode = User::where('referral_code', '=', $ref)->first();
            if ($refCode == null ||  $refCode->referral_code != $ref) {
                return view('auth.register', compact('lang', 'ref'));
            }

            return view('auth.register', compact('lang', 'ref'));
        } else {
            return redirect('login');
        }

        return view('auth.register', compact('lang', 'ref'));
    }
}
