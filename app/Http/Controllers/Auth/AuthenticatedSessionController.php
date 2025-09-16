<?php

namespace App\Http\Controllers\Auth;

use App\Core\Traits\AuthenticationTrait;
use App\Core\Traits\SettingTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\User;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\LoginDetail;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{

    use AuthenticationTrait, SettingTrait;
    /**
     * Display the login view.
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


    public function create($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        // \App::setLocale($lang);
        $this->setAppLocale($lang);
        return view('auth.login', compact('lang'));
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user != null && $user->is_disable == 0 && $user->type != 'company' && $user->type != 'super admin') {
            return redirect()->back()->with('status', __('Your Account is disable,please contact your Administrator.'));
        }

        if ($user != null && $user->is_enable_login == 0 && $user->type != 'super admin') {
            return redirect()->back()->with('status', __('Your Account is disable from company.'));
        }

        // ReCpatcha
        $settings = Utility::settings();

        if ($settings['recaptcha_module'] == 'yes') {
            $validation['g-recaptcha-response'] = 'required|captcha';
        } else {
            $validation = [];
        }
        $this->validate($request, $validation);

        $request->authenticate();

        $request->session()->regenerate();

        // $user = Auth::user();
        $user = $this->getAuthenticatedUser();
        if ($user->delete_status == 0) {
            auth()->logout();
        }

        if ($user->is_active == 0) {
            auth()->logout();
        }

        if ($user->is_disable == 0) {
            return redirect()->back()->with('status', 'Your Account is disable,please contact your Administrate.');
        }

        $ip = $_SERVER['REMOTE_ADDR']; // your ip address here

        // $ip = '49.36.83.154'; // This is static ip address

        $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));

        if (isset($query['status']) &&  $query['status'] != 'fail') {
            $whichbrowser = new \WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
            if ($whichbrowser->device->type == 'bot') {
                return;
            }
            $referrer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : null;

            /* Detect extra details about the user */
            $query['browser_name'] = $whichbrowser->browser->name ?? null;
            $query['os_name'] = $whichbrowser->os->name ?? null;
            $query['browser_language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
            $query['device_type'] = get_device_type($_SERVER['HTTP_USER_AGENT']);
            $query['referrer_host'] = !empty($referrer['host']);
            $query['referrer_path'] = !empty($referrer['path']);

            isset($query['timezone']) ? date_default_timezone_set($query['timezone']) : '';

            $json = json_encode($query);
            if ($user->type != 'company') {
                $login_detail = new LoginDetail();
                $login_detail->user_id = Auth::user()->id;
                $login_detail->ip = $ip;
                $login_detail->date = date('Y-m-d H:i:s');
                $login_detail->Details = $json;
                $login_detail->type = 'user';
                $login_detail->created_by = $this->getAuthenticatedUser()->creatorId();
                $login_detail->save();
            }
        }

        if ($user->type == 'company') {
            $plan = Plan::find($user->plan);
            if ($user->plan != $plan->id) {
                if (date('Y-m-d') > $user->plan_expire_date) {
                    $creatorId = $this->getAuthenticatedUser()->creatorId();
                    $user->plan             = $plan->id;
                    $user->plan_expire_date = null;
                    $user->save();

                    // $users     = User::where('created_by', '=', \Auth::user()->creatorId())->get();
                    // $customers = Customer::where('created_by', '=', \Auth::user()->creatorId())->get();
                    // $venders   = Vender::where('created_by', '=', \Auth::user()->creatorId())->get();
                    $models = [User::class, Customer::class, Vender::class];
                    $data = array_map(fn($model) => $model::where('created_by', $creatorId)->get(), $models);
                    [$users, $customers, $venders] = $data;


                    // if ($plan->max_users == -1) {
                    //     foreach ($users as $user) {
                    //         $user->is_active = 1;
                    //         $user->save();
                    //     }
                    // } else {
                    //     $userCount = 0;
                    //     foreach ($users as $user) {
                    //         $userCount++;
                    //         if ($userCount <= $plan->max_users) {
                    //             $user->is_active = 1;
                    //             $user->save();
                    //         } else {
                    //             $user->is_active = 0;
                    //             $user->save();
                    //         }
                    //     }
                    // }

                    $maxUsers = $plan->max_users;
                    $users->each(fn($user, $index) => $user->update(['is_active' => $maxUsers == -1 || $index < $maxUsers ? 1 : 0]));



                    // if ($plan->max_customers == -1) {
                    //     foreach ($customers as $customer) {
                    //         $customer->is_active = 1;
                    //         $customer->save();
                    //     }
                    // } else {
                    //     $customerCount = 0;
                    //     foreach ($customers as $customer) {
                    //         $customerCount++;
                    //         if ($customerCount <= $plan->max_customers) {
                    //             $customer->is_active = 1;
                    //             $customer->save();
                    //         } else {
                    //             $customer->is_active = 0;
                    //             $customer->save();
                    //         }
                    //     }
                    // }

                    $maxCustomers = $plan->max_customers;
                    $customers->each(
                        fn($customer, $index) =>
                        $customer->update(['is_active' => $maxCustomers == -1 || $index < $maxCustomers ? 1 : 0])
                    );


                    // if ($plan->max_venders == -1) {
                    //     foreach ($venders as $vender) {
                    //         $vender->is_active = 1;
                    //         $vender->save();
                    //     }
                    // } else {
                    //     $venderCount = 0;
                    //     foreach ($venders as $vender) {
                    //         $venderCount++;
                    //         if ($venderCount <= $plan->max_venders) {
                    //             $vender->is_active = 1;
                    //             $vender->save();
                    //         } else {
                    //             $vender->is_active = 0;
                    //             $vender->save();
                    //         }
                    //     }
                    // }

                    $maxVenders = $plan->max_venders;
                    $venders->each(
                        fn($vender, $index) =>
                        $vender->update(['is_active' => $maxVenders == -1 || $index < $maxVenders ? 1 : 0])
                    );


                    // if ($plan) {
                    //     if ($plan->duration != 'lifetime') {
                    //         $datetime1 = new \DateTime($user->plan_expire_date);
                    //         $datetime2 = new \DateTime(date('Y-m-d'));
                    //         //                    $interval  = $datetime1->diff($datetime2);
                    //         $interval = $datetime2->diff($datetime1);
                    //         $days     = $interval->format('%r%a');
                    //         if ($days <= 0) {
                    //             $user->assignPlan(1);

                    //             return redirect()->intended(RouteServiceProvider::HOME)->with('error', __('Your Plan is expired.'));
                    //         }
                    //     }

                    //     if ($user->trial_expire_date != null) {
                    //         if (\Auth::user()->trial_expire_date > date('Y-m-d')) {
                    //             $user->assignPlan(1);

                    //             return redirect()->intended(RouteServiceProvider::HOME)->with('error', __('Your Trial plan Expired.'));
                    //         }
                    //     }
                    // }

                    if ($plan) {
                        if ($plan->duration !== 'lifetime' && Carbon::parse($user->plan_expire_date)->isPast()) {
                            $user->assignPlan(1);
                            return redirect()->intended(RouteServiceProvider::HOME)
                                ->with('error', __('Your Plan is expired.'));
                        }

                        if ($user->trial_expire_date && Carbon::parse($user->trial_expire_date)->isPast()) {
                            $user->assignPlan(1);
                            return redirect()->intended(RouteServiceProvider::HOME)
                                ->with('error', __('Your Trial plan Expired.'));
                        }
                    }

                    return redirect()->route('dashboard')->with('error', 'Your plan expired limit is over, please upgrade your plan');
                }
            }
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }


    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function username()
    {
        return 'email';
    }


    public function showCustomerLoginForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        // \App::setLocale($lang);
        $this->setAppLocale($lang);
        return view('auth.customer_login', compact('lang'));
    }

    public function customerLogin(Request $request)
    {
        $customer = Customer::where('email', $request->email)->first();

        // Check if the customer is disabled
        if ($customer && !$customer->is_enable_login && $customer->type !== 'super admin') {
            return back()->with('status', __('Your Account is disabled.'));
        }

        // Validation rules
        $validation = [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ];

        // Add reCAPTCHA validation if enabled
        if (Utility::settings()['recaptcha_module'] === 'yes') {
            $validation['g-recaptcha-response'] = 'required|captcha';
        }

        $this->validate($request, $validation);

        // Attempt login
        if ($this->getGuard()->attempt($request->only('email', 'password'), $request->remember)) {

            // Check if the user is inactive and logout
            if (!$this->getGuard()->user()->is_active) {
                $this->getGuard()->logout();
            }

            // Get user IP address
            $ip = $request->ip();

            // Fetch location details via API
            $response = Http::get("http://ip-api.com/json/{$ip}")->json();

            if (isset($response['status']) && $response['status'] !== 'fail') {
                $whichbrowser = new \WhichBrowser\Parser($request->header('User-Agent'));

                if ($whichbrowser->device->type !== 'bot') {
                    $referrer = parse_url($request->header('Referer'));

                    $response += [
                        'browser_name' => $whichbrowser->browser->name ?? null,
                        'os_name' => $whichbrowser->os->name ?? null,
                        'browser_language' => substr($request->header('Accept-Language', ''), 0, 2),
                        'device_type' => get_device_type($request->header('User-Agent')),
                        'referrer_host' => $referrer['host'] ?? null,
                        'referrer_path' => $referrer['path'] ?? null,
                    ];

                    // Set timezone if available
                    if (!empty($response['timezone'])) {
                        date_default_timezone_set($response['timezone']);
                    }

                    // Save login details
                    LoginDetail::create([
                        'user_id' => optional($customer)->id,
                        'ip' => $ip,
                        'date' => now(),
                        'Details' => json_encode($response),
                        'type' => 'customer',
                        'created_by' => optional($customer)->created_by,
                    ]);
                }
            }

            return redirect()->route('customer.dashboard');
        }

        return $this->sendFailedLoginResponse($request);
    }


    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('These credentials do not match our records.')],
        ]);
    }

    public function showVenderLoginForm($lang = 'en')
    {
        $langList = Utility::langList();
        $lang = $langList[$lang] ?? Utility::getValByName('default_language');

        // \App::setLocale($lang);
        $this->setAppLocale($lang);

        return view('auth.vender_login', compact('lang'));
    }


    public function venderLogin(Request $request)
    {
        $vender = Vender::where('email', $request->email)->first();
        if ($vender != null && $vender->is_enable_login == 0 && $vender->type != 'super admin') {
            return redirect()->back()->with('status', __('Your Account is disable from vendor.'));
        }

        // ReCpatcha
        $settings = Utility::settings();

        if ($settings['recaptcha_module'] == 'yes') {
            $validation['g-recaptcha-response'] = 'required|captcha';
        } else {
            $validation = [];
        }
        $this->validate($request, $validation);

        $this->validate(
            $request,
            [
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]
        );
        if (\Auth::guard('vender')->attempt(
            [
                'email' => $request->email,
                'password' => $request->password,
            ],
            $request->get('remember')
        )) {
            if (\Auth::guard('vender')->user()->is_active == 0) {
                \Auth::guard('vender')->logout();
            }


            $ip = $_SERVER['REMOTE_ADDR']; // your ip address here

            // $ip = '49.36.83.154'; // This is static ip address

            $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
            if (isset($query['status']) &&  $query['status'] != 'fail') {
                $whichbrowser = new \WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
                if ($whichbrowser->device->type == 'bot') {
                    return;
                }
                $referrer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : null;

                /* Detect extra details about the user */
                $query['browser_name'] = $whichbrowser->browser->name ?? null;
                $query['os_name'] = $whichbrowser->os->name ?? null;
                $query['browser_language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
                $query['device_type'] = get_device_type($_SERVER['HTTP_USER_AGENT']);
                $query['referrer_host'] = !empty($referrer['host']);
                $query['referrer_path'] = !empty($referrer['path']);

                isset($query['timezone']) ? date_default_timezone_set($query['timezone']) : '';

                $json = json_encode($query);
                $vender = Vender::where('email', $request->email)->first();
                $login_detail = new LoginDetail();
                $login_detail->user_id = $vender->id;
                $login_detail->ip = $ip;
                $login_detail->date = date('Y-m-d H:i:s');
                $login_detail->Details = $json;
                $login_detail->type = 'vender';
                $login_detail->created_by = $vender->created_by;
                $login_detail->save();
            }
            return redirect()->route('vender.dashboard');
        }


        return $this->sendFailedLoginResponse($request);
    }

    public function showLoginForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        // \App::setLocale($lang);
        $this->setAppLocale($lang);
        return view('auth.login', compact('lang'));
    }

    public function showLinkRequestForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        // \App::setLocale($lang);
        $this->setAppLocale($lang);

        return view('auth.forgot-password', compact('lang'));
    }

    public function showCustomerLoginLang($lang = '')
    {
        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        // \App::setLocale($lang);
        $this->setAppLocale($lang);

        return view('auth.customer_login', compact('lang'));
    }

    public function showVenderLoginLang($lang = '')
    {
        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        // \App::setLocale($lang);
        $this->setAppLocale($lang);
        return view('auth.vender_login', compact('lang'));
    }

    //    ---------------------------------Customer ----------------q------------------_
    public function showCustomerLinkRequestForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        // \App::setLocale($lang);

        $this->setAppLocale($lang);
        return view('auth.customerEmail', compact('lang'));
    }

    public function postCustomerEmail(Request $request)
    {


        $request->validate(
            [
                'email' => 'required|email|exists:customers',
            ]
        );

        $token = \Str::random(60);

        DB::table('password_resets')->insert(
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        // dd(settings);

        Mail::send(
            'auth.customerVerify',
            ['token' => $token],
            function ($message) use ($request) {
                $settings = Utility::settings();

                $message->from($settings['mail_username'], $settings['mail_from_name']);
                $message->to($request->email);
                $message->subject('Reset Password Notification');
            }
        );

        return back()->with('status', 'We have e-mailed your password reset link!');
    }

    public function showResetForm(Request $request, $token = null)
    {

        $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->first();
        $lang             = !empty($default_language) ? $default_language->value : 'en';

        // \App::setLocale($lang);
        $this->setAppLocale($lang);

        return view('auth.passwords.reset')->with(
            [
                'token' => $token,
                'email' => $request->email,
                'lang' => $lang,
            ]
        );
    }

    public function getCustomerPassword($token)
    {

        return view('auth.customerReset', ['token' => $token]);
    }

    public function updateCustomerPassword(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email|exists:customers',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required',

            ]
        );

        $updatePassword = DB::table('password_resets')->where(
            [
                'email' => $request->email,
                'token' => $request->token,
            ]
        )->first();

        if (!$updatePassword) {
            return back()->withInput()->with('error', 'Invalid token!');
        }

        $user = Customer::where('email', $request->email)->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        return redirect('/login')->with('message', 'Your password has been changed.');
    }

    //    ----------------------------Vendor----------------------------------------------------
    public function showVendorLinkRequestForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        // \App::setLocale($lang);
        $this->setAppLocale($lang);
        return view('auth.vendorEmail', compact('lang'));
    }

    public function postVendorEmail(Request $request)
    {

        $request->validate(
            [
                'email' => 'required|email|exists:venders',
            ]
        );

        $token = \Str::random(60);

        DB::table('password_resets')->insert(
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        Mail::send(
            'auth.vendorVerify',
            ['token' => $token],
            function ($message) use ($request) {
                $settings = Utility::settings();
                $message->from($settings['mail_username'], $settings['mail_from_name']);
                $message->to($request->email);
                $message->subject('Reset Password Notification');
            }
        );

        return back()->with('status', 'We have e-mailed your password reset link!');
    }

    public function getVendorPassword($token)
    {

        return view('auth.vendorReset', ['token' => $token]);
    }

    public function updateVendorPassword(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email|exists:venders',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required',

            ]
        );

        $updatePassword = DB::table('password_resets')->where(
            [
                'email' => $request->email,
                'token' => $request->token,
            ]
        )->first();

        if (!$updatePassword) {
            return back()->withInput()->with('error', 'Invalid token!');
        }

        $user = Vender::where('email', $request->email)->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        return redirect('/login')->with('message', 'Your password has been changed.');
    }
}

function get_device_type($user_agent)
{
    $mobile_regex = '/(?:phone|windows\s+phone|ipod|blackberry|(?:android|bb\d+|meego|silk|googlebot) .+? mobile|palm|windows\s+ce|opera mini|avantgo|mobilesafari|docomo)/i';
    $tablet_regex = '/(?:ipad|playbook|(?:android|bb\d+|meego|silk)(?! .+? mobile))/i';
    if (preg_match_all($mobile_regex, $user_agent)) {
        return 'mobile';
    } else {
        if (preg_match_all($tablet_regex, $user_agent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
}
