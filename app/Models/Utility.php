<?php

namespace App\Models;

use Twilio\Rest\Client;
use App\Models\Language;
use Illuminate\Support\Str;
use App\Models\ReferralSetting;
use App\Mail\CommonEmailTemplate;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\ReferralTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

class Utility extends Model
{
    private static $taxes = NULL;
    private static $taxsData = NULL;
    private static $taxRateData = NULL;
    private static $adminSetting = NULL;
    private static $Setting = NULL;
    private static $languageSetting = NULL;
    private static $getRatingData = NULL;

    public static function settings($creatorId = null)
    {
        if (is_null(self::$Setting)) {

            $data = DB::table('settings');
            if (\Auth::check()) {
                $userId = \Auth::user()->creatorId();
                $data   = $data->where('created_by', '=', $userId);
            } else {
                $data = $data->where('created_by', '=', $creatorId);
            }
            $data     = $data->get();
            self::$Setting = $data;
        }
        $data = self::$Setting;
        $settings = [
            "site_currency" => "USD",
            "site_currency_symbol" => "",
            "site_currency_symbol_position" => "pre",
            "site_date_format" => "M j, Y",
            "site_time_format" => "g:i A",
            "company_name" => "",
            "company_address" => "",
            "company_city" => "",
            "company_state" => "",
            "company_zipcode" => "",
            "company_country" => "",
            "company_telephone" => "",
            "company_email" => "",
            "company_email_from_name" => "",
            "invoice_service_charge" => "",
            "vat_charge" => "",
            "invoice_prefix" => "#INVO",
            "journal_prefix" => "#JUR",
            "invoice_color" => "ffffff",
            "proposal_prefix" => "#PROP",
            "retainer_prefix" => "#RET",
            "proposal_color" => "ffffff",
            "retainer_color" => "ffffff",
            "bill_prefix" => "#BILL",
            "bill_color" => "ffffff",
            "proposal_logo" => "2_proposal_logo.png",
            "retainer_logo" => "2_retainer_logo.png",
            "invoice_logo" => "2_invoice_logo.png",
            "bill_logo" => "2_bill_logo.png",
            "customer_prefix" => "#CUST",
            "vender_prefix" => "#VEND",
            "contract_prefix" => "#CON",
            "contract_template" => 'template1',
            "footer_title" => "",
            "footer_notes" => "",
            "invoice_template" => "template1",
            "bill_template" => "template1",
            "proposal_template" => "template1",
            "retainer_template" => "template1",
            "registration_number" => "",
            "tax_number" => "on",
            "vat_number" => "",
            "default_language" => "en",
            "company_default_language" => "en",
            "enable_stripe" => "",
            "enable_paypal" => "",
            "paypal_mode" => "",
            "paypal_client_id" => "",
            "paypal_secret_key" => "",
            "stripe_key" => "",
            "stripe_secret" => "",
            "decimal_number" => "2",
            "tax_type" => "",
            "shipping_display" => "on",
            "journal_prefix" => "#JUR",
            "display_landing_page" => "on",
            "title_text" => "",
            "footer_text" => "",
            // 'gdpr_cookie' => " ",
            "enable_chatgpt" => "",
            "chatgpt_key" => "",
            "chatgpt_model_name" => "",
            'cookie_text' => "",
            "twilio_sid" => "",
            "twilio_token" => "",
            "twilio_from" => "",
            "enable_signup" => "on",
            "invoice_starting_number" => "1",
            "proposal_starting_number" => "1",
            "bill_starting_number" => "1",
            "dark_logo" => "logo-dark.png",
            "light_logo" => "logo-light.png",
            "company_logo_light" => "logo-light.png",
            "company_logo_dark" => "logo-dark.png",
            "company_favicon" => "",
            "cust_theme_bg" => "on",
            "cust_darklayout" => "off",
            "color" => 'theme-3',
            "SITE_RTL" => "off",
            'color_flag' => 'false',
            "retainer_starting_number" => "1",
            "storage_setting" => "",
            "local_storage_validation" => "",
            "local_storage_max_upload_size" => "",
            "s3_key" => "",
            "s3_secret" => "",
            "s3_region" => "",
            "s3_bucket" => "",
            "s3_url"    => "",
            "s3_endpoint" => "",
            "s3_max_upload_size" => "",
            "s3_storage_validation" => "",
            "wasabi_key" => "",
            "wasabi_secret" => "",
            "wasabi_region" => "",
            "wasabi_bucket" => "",
            "wasabi_url" => "",
            "wasabi_root" => "",
            "wasabi_max_upload_size" => "",
            "wasabi_storage_validation" => "",
            "email_verification" => "on",
            "meta_image" => "",
            'enable_cookie' => 'on',
            'necessary_cookies' => 'on',
            'cookie_logging' => 'on',
            'cookie_title' => 'We use cookies!',
            'cookie_description' => 'Hi, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it',
            'strictly_cookie_title' => 'Strictly necessary cookies',
            'strictly_cookie_description' => 'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
            'more_information_description' => 'For any queries in relation to our policy on cookies and your choices, please',
            "more_information_title" => "",
            'contactus_url' => '#',

            'mail_driver' => env('MAIL_MAILER'),
            'mail_host' => env('MAIL_HOST'),
            'mail_port' => env('MAIL_PORT'),
            'mail_username' => env('MAIL_USERNAME'),
            'mail_password' => env('MAIL_PASSWORD'),
            'mail_encryption' => env('MAIL_ENCRYPTION'),
            'mail_from_address' => env('MAIL_FROM_ADDRESS'),
            'mail_from_name' => env('MAIL_FROM_NAME'),

            'recaptcha_module' => '',
            'google_recaptcha_key' => '',
            'google_recaptcha_secret' => '',
        ];


        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    private static $cookie = null;

    public static function cookies()
    {
        if (is_null(self::$cookie)) {
            $data = DB::table('settings');
            if (\Auth::check()) {
                $userId = \Auth::user()->creatorId();
                $data = $data->where('created_by', '=', $userId);
            } else {
                $data = $data->where('created_by', '=', 1);
            }
            self::$cookie = $data->get();
        }
        $cookies = [
            'enable_cookie' => 'on',
            'necessary_cookies' => 'on',
            'cookie_logging' => 'on',
            'cookie_title' => 'We use cookies!',
            'cookie_description' => 'Hi, this website uses essential cookies to ensure its proper operation and tracking cookies to understand how you interact with it',
            'strictly_cookie_title' => 'Strictly necessary cookies',
            'strictly_cookie_description' => 'These cookies are essential for the proper functioning of my website. Without these cookies, the website would not work properly',
            'more_information_description' => 'For any queries in relation to our policy on cookies and your choices, please',
            "more_information_title" => "",
            'contactus_url' => '#',
        ];

        if (!is_null(self::$cookie)) {
            foreach (self::$cookie as $row) {
                if (array_key_exists($row->name, $cookies)) {
                    if ($row->value) {
                        $cookies[$row->name] = $row->value;
                    }
                }
            }
        }

        return $cookies;
    }


    private static $settingById = null;
    public static function settingsById($id)
    {
        if (is_null(self::$settingById)) {
            $data     = DB::table('settings');
            $data     = $data->where('created_by', '=', $id);
            $data     = $data->get();
            self::$settingById = $data;
        }
        $data = self::$settingById;

        $settings = [
            "site_currency" => "USD",
            "currency" => "USD",
            "currency_symbol" => "",
            "site_currency_symbol" => "",
            "site_currency_symbol_position" => "pre",
            "site_date_format" => "M j, Y",
            "site_time_format" => "g:i A",
            "company_name" => "",
            "company_address" => "",
            "company_city" => "",
            "company_state" => "",
            "company_zipcode" => "",
            "company_country" => "",
            "company_telephone" => "",
            "company_email" => "",
            "company_email_from_name" => "",
            "invoice_service_charge" => "",
            "vat_charge" => "",
            "invoice_prefix" => "#INVO",
            "journal_prefix" => "#JUR",
            "invoice_color" => "ffffff",
            "proposal_prefix" => "#PROP",
            "proposal_color" => "ffffff",
            "proposal_logo" => "2_proposal_logo.png",
            "retainer_logo" => "2_retainer_logo.png",
            "invoice_logo" => "2_invoice_logo.png",
            "bill_logo" => "2_bill_logo.png",
            "retainer_color" => "ffffff",
            "bill_prefix" => "#BILL",
            "bill_color" => "ffffff",
            "customer_prefix" => "#CUST",
            "vender_prefix" => "#VEND",
            "contract_prefix" => "#CON",
            "retainer_prefix" => "#RET",
            "footer_title" => "",
            "footer_notes" => "",
            "invoice_template" => "template1",
            "bill_template" => "template1",
            "proposal_template" => "template1",
            "retainer_template" => "template1",
            "contract_template" => "template1",
            "registration_number" => "",
            "vat_number" => "",
            "default_language" => "en",
            "enable_stripe" => "",
            "enable_paypal" => "",
            "paypal_mode" => "",
            "paypal_client_id" => "",
            "paypal_secret_key" => "",
            "stripe_key" => "",
            "stripe_secret" => "",
            "decimal_number" => "2",
            "tax_number" => "on",
            "tax_type" => "",
            "shipping_display" => "on",
            "journal_prefix" => "#JUR",
            "display_landing_page" => "on",
            "title_text" => "",
            // 'gdpr_cookie' => "off",
            'cookie_text' => "",
            "twilio_sid" => "",
            "twilio_token" => "",
            "twilio_from" => "",
            "dark_logo" => "logo-dark.png",
            "light_logo" => "logo-light.png",
            "company_logo_light" => "logo-light.png",
            "company_logo_dark" => "logo-dark.png",
            "company_favicon" => "",
            "SITE_RTL" => "off",
            "owner_signature" => "",
            "cust_darklayout" => "off",
            "footer_text" => "",

            'mail_driver' => env('MAIL_MAILER'),
            'mail_host' => env('MAIL_HOST'),
            'mail_port' => env('MAIL_PORT'),
            'mail_username' => env('MAIL_USERNAME'),
            'mail_password' => env('MAIL_PASSWORD'),
            'mail_encryption' => env('MAIL_ENCRYPTION'),
            'mail_from_address' => env('MAIL_FROM_ADDRESS'),
            'mail_from_name' => env('MAIL_FROM_NAME'),

        ];

        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    public static function flagOfCountry()
    {
        $arr = [
            'ar' => '🇦🇪 ar',
            'da' => '🇩🇰 da',
            'de' => '🇩🇪 de',
            'es' => '🇪🇸 es',
            'fr' => '🇫🇷 fr',
            'it' => '🇮🇹 it',
            'ja' => '🇯🇵 ja',
            'nl' => '🇳🇱 nl',
            'pl' => '🇵🇱 pl',
            'ru' => '🇷🇺 ru',
            'pt' => '🇵🇹 pt',
            'en' => '🇮🇳 en',
            'tr' => '🇹🇷 tr',
            'pt-br' => '🇵🇹 pt-br',
        ];
        return $arr;
    }

    public static function languagecreate()
    {
        $languages = Utility::langList();
        foreach ($languages as $key => $lang) {
            $languageExist = Language::where('code', $key)->first();
            if (empty($languageExist)) {
                $language = new Language();
                $language->code = $key;
                $language->fullname = $lang;
                $language->save();
            }
        }
    }

    public static function langList()
    {
        $languages = [
            "ar" => "Arabic",
            "zh" => "Chinese",
            "da" => "Danish",
            "de" => "German",
            "en" => "English",
            "es" => "Spanish",
            "fr" => "French",
            "he" => "Hebrew",
            "it" => "Italian",
            "ja" => "Japanese",
            "nl" => "Dutch",
            "pl" => "Polish",
            "pt" => "Portuguese",
            "ru" => "Russian",
            "tr" => "Turkish",
            "pt-br" => "Portuguese(Brazil)"
        ];
        return $languages;
    }

    public static function langSetting()
    {
        $data = DB::table('settings');
        $data = $data->where('created_by', '=', 1)->get();
        if (count($data) == 0) {
            $data = DB::table('settings')->where('created_by', '=', 1)->get();
        }
        $settings = [];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function languages()
    {
        if (self::$languageSetting == null) {
            $languages = Utility::langList();

            if (\Schema::hasTable('languages')) {
                $settings = Utility::langSetting();
                if (!empty($settings['disable_lang'])) {
                    $disabledlang = explode(',', $settings['disable_lang']);
                    $languages = Language::whereNotIn('code', $disabledlang)->pluck('fullName', 'code');
                } else {
                    $languages = Language::pluck('fullname', 'code');
                }
                self::$languageSetting = $languages;
            }
        }

        return self::$languageSetting;
    }

    // public static function languages()
    // {
    //     $dir     = base_path() . '/resources/lang/';
    //     $glob    = glob($dir . "*", GLOB_ONLYDIR);
    //     $arrLang = array_map(
    //         function ($value) use ($dir) {
    //             return str_replace($dir, '', $value);
    //         },
    //         $glob
    //     );
    //     $arrLang = array_map(
    //         function ($value) use ($dir) {
    //             return preg_replace('/[0-9]+/', '', $value);
    //         },
    //         $arrLang
    //     );
    //     $arrLang = array_filter($arrLang);

    //     return $arrLang;
    // }


    private static $storageSetting = null;
    public static function getStorageSetting()
    {
        if (self::$getRatingData == null) {
            $data = DB::table('settings');
            $data = $data->where('created_by', '=', 1);
            $data     = $data->get();
            self::$storageSetting = $data;
        }
        $data = self::$storageSetting;

        $settings = [
            "storage_setting" => "",
            "local_storage_validation" => "",
            "local_storage_max_upload_size" => "",
            "s3_key" => "",
            "s3_secret" => "",
            "s3_region" => "",
            "s3_bucket" => "",
            "s3_url"    => "",
            "s3_endpoint" => "",
            "s3_max_upload_size" => "",
            "s3_storage_validation" => "",
            "wasabi_key" => "",
            "wasabi_secret" => "",
            "wasabi_region" => "",
            "wasabi_bucket" => "",
            "wasabi_url" => "",
            "wasabi_root" => "",
            "wasabi_max_upload_size" => "",
            "wasabi_storage_validation" => "",
        ];

        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    public static function getValByName($key)
    {
        $setting = Utility::settings();
        if (!isset($setting[$key]) || empty($setting[$key])) {
            $setting[$key] = '';
        }

        return $setting[$key];
    }

    public static function getValByName1($key)
    {
        $setting = Utility::getGdpr();
        if (!isset($setting[$key]) || empty($setting[$key])) {
            $setting[$key] = '';
        }

        return $setting[$key];
    }

    public static function setEnvironmentValue(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str     = file_get_contents($envFile);
        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {
                $keyPosition       = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine           = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);
                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}='{$envValue}'\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}='{$envValue}'", $str);
                }
            }
        }
        $str = substr($str, 0, -1);
        $str .= "\n";
        if (!file_put_contents($envFile, $str)) {
            return false;
        }

        return true;
    }

    public static function templateData()
    {
        $arr              = [];
        $arr['colors']    = [
            '003580',
            '666666',
            '6676ef',
            'f50102',
            'f9b034',
            'fbdd03',
            'c1d82f',
            '37a4e4',
            '8a7966',
            '6a737b',
            '050f2c',
            '0e3666',
            '3baeff',
            '3368e6',
            'b84592',
            'f64f81',
            'f66c5f',
            'fac168',
            '46de98',
            '40c7d0',
            'be0028',
            '2f9f45',
            '371676',
            '52325d',
            '511378',
            '0f3866',
            '48c0b6',
            '297cc0',
            'ffffff',
            '000',
        ];
        $arr['templates'] = [
            "template1" => "New York",
            "template2" => "Toronto",
            "template3" => "Rio",
            "template4" => "London",
            "template5" => "Istanbul",
            "template6" => "Mumbai",
            "template7" => "Hong Kong",
            "template8" => "Tokyo",
            "template9" => "Sydney",
            "template10" => "Paris",
        ];

        return $arr;
    }

    public static function priceFormat($settings, $price)
    {
        $decimal_number = Utility::getValByName('decimal_number') ? Utility::getValByName('decimal_number') : 0;
        return (($settings['site_currency_symbol_position'] == "pre") ? $settings['site_currency_symbol'] : '') . number_format($price, $decimal_number) . (($settings['site_currency_symbol_position'] == "post") ? $settings['site_currency_symbol'] : '');
    }

    public static function currencySymbol($settings)
    {
        return $settings['site_currency_symbol'];
    }

    public static function dateFormat($settings, $date)
    {
        return date($settings['site_date_format'], strtotime($date));
    }

    public static function timeFormat($settings, $time)
    {
        return date($settings['site_time_format'], strtotime($time));
    }

    public static function invoiceNumberFormat($settings, $number)
    {
        $settings = Utility::settings();
        return $settings["invoice_prefix"] . sprintf("%05d", $number);
    }

    public static function proposalNumberFormat($settings, $number)
    {
        return $settings["proposal_prefix"] . sprintf("%05d", $number);
    }

    public static function retainerNumberFormat($settings, $number)
    {
        $settings = Utility::settings();
        return $settings["retainer_prefix"] . sprintf("%05d", $number);
    }

    public static function customerProposalNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["proposal_prefix"] . sprintf("%05d", $number);
    }

    public static function customerRetainerNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["retainer_prefix"] . sprintf("%05d", $number);
    }

    public static function customerInvoiceNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["invoice_prefix"] . sprintf("%05d", $number);
    }

    public static function billNumberFormat($settings, $number)
    {
        return $settings["bill_prefix"] . sprintf("%05d", $number);
    }

    public static function vendorBillNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["bill_prefix"] . sprintf("%05d", $number);
    }
    public static function contractNumberFormat($settings, $number)
    {
        return $settings["contract_prefix"] . sprintf("%05d", $number);
    }

    public static function getTax($tax)
    {
        if (self::$taxes == null) {
            $tax = Tax::find($tax);
            self::$taxes = $tax;
        }
        return self::$taxes;
    }

    public static function tax($taxes)
    {

        $taxArr = explode(',', $taxes);
        $taxes  = [];
        foreach ($taxArr as $tax) {
            $taxes[] = Tax::find($tax);
        }

        return $taxes;
    }


    // public static function tax($taxes)
    // {
    //     if (self::$taxsData == null) {
    //         $taxArr = explode(',', $taxes);
    //         $taxes  = [];
    //         foreach ($taxArr as $tax) {
    //             $taxes[] = self::getTax($tax);
    //         }
    //         self::$taxsData = $taxes;
    //     }

    //     return self::$taxsData;
    // }

    // public static function taxRate($taxRate, $price, $quantity, $discount)
    // {


    //     return ($taxRate / 100) * ($price * $quantity - $discount);
    // }
    // public static function taxRate($taxRate, $price, $quantity)
    // {

    //     return ($taxRate / 100) * ($price * $quantity);
    // }



    public static function taxRate($taxRate, $price, $quantity, $discount = 0)
    {

        //        return ($taxRate / 100) * (($price-$discount) * $quantity);
        return (($price * $quantity) - $discount) * ($taxRate / 100);
    }



    // public static function totalTaxRate($taxes)
    // {

    //     if (self::$taxRateData == null) {
    //         $taxArr  = explode(',', $taxes);
    //         $taxRate = 0;
    //         foreach ($taxArr as $tax) {
    //             $tax     = self::getTax($tax);
    //             $taxRate += !empty($tax->rate) ? $tax->rate : 0;
    //         }
    //         self::$taxRateData = $taxRate;
    //     }
    //     return self::$taxRateData;
    // }

    public static function totalTaxRate($taxes)
    {

        $taxArr  = explode(',', $taxes);
        $taxRate = 0;

        foreach ($taxArr as $tax) {

            $tax     = Tax::find($tax);
            $taxRate += !empty($tax->rate) ? $tax->rate : 0;
        }

        return $taxRate;
    }

    public static function userBalance($users, $id, $amount, $type)
    {
        if ($users == 'customer') {
            $user = Customer::find($id);
        } else {
            $user = Vender::find($id);
        }
        if (!empty($user)) {
            if ($type == 'credit') {
                $oldBalance  = $user->balance;
                $userBalance = $oldBalance + $amount;
                $user->balance = $userBalance;
                $user->save();
            } elseif ($type == 'debit') {
                $oldBalance    = $user->balance;
                $userBalance = $oldBalance - $amount;
                $user->balance = $userBalance;
                $user->save();
            }
        }
    }

    public static function updateUserBalance($users, $id, $amount, $type)
    {
        if ($users == 'customer') {
            $user = Customer::find($id);
        } else {
            $user = Vender::find($id);
        }

        if (!empty($user)) {
            if ($type == 'credit') {
                $oldBalance    = $user->balance;
                $userBalance = $oldBalance - $amount;
                $user->balance = $userBalance;
                $user->save();
            } elseif ($type == 'debit') {
                $oldBalance    = $user->balance;
                $userBalance = $oldBalance + $amount;
                $user->balance = $userBalance;
                $user->save();
            }
        }
    }

    public static function bankAccountBalance($id, $amount, $type)
    {
        $bankAccount = BankAccount::find($id);
        if ($bankAccount) {
            if ($type == 'credit') {
                $oldBalance                   = $bankAccount->opening_balance;
                $bankAccount->opening_balance = $oldBalance + $amount;
                $bankAccount->save();
                Log::info('Bank Account Balance credit Updated: ' . $bankAccount->opening_balance);
            } elseif ($type == 'debit') {
                $oldBalance                   = $bankAccount->opening_balance;
                $bankAccount->opening_balance = $oldBalance - $amount;
                $bankAccount->save();
                Log::info('Bank Account Balance debit Updated: ' . $bankAccount->opening_balance);
            }
        }
    }

    // get font-color code accourding to bg-color
    public static function hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        $rgb = array(
            $r,
            $g,
            $b,
        );

        //return implode(",", $rgb); // returns the rgb values separated by commas
        return $rgb; // returns an array with the rgb values
    }

    public static function getFontColor($color_code)
    {
        $rgb = self::hex2rgb($color_code);
        $R   = $G = $B = $C = $L = $color = '';

        $R = (floor($rgb[0]));
        $G = (floor($rgb[1]));
        $B = (floor($rgb[2]));

        $C = [
            $R / 255,
            $G / 255,
            $B / 255,
        ];

        for ($i = 0; $i < count($C); ++$i) {
            if ($C[$i] <= 0.03928) {
                $C[$i] = $C[$i] / 12.92;
            } else {
                $C[$i] = pow(($C[$i] + 0.055) / 1.055, 2.4);
            }
        }

        $L = 0.2126 * $C[0] + 0.7152 * $C[1] + 0.0722 * $C[2];

        if ($L > 0.179) {
            $color = 'black';
        } else {
            $color = 'white';
        }

        return $color;
    }


    public static function delete_directory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!self::delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }

        return rmdir($dir);
    }


    public static function getCompanyPaymentSettingWithOutAuth($user_id)
    {
        // dd($user_id)
        $data     = \DB::table('company_payment_settings');
        $settings = [];
        $data     = $data->where('created_by', '=', $user_id);
        $data     = $data->get();
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    // public static function getAdminPaymentSetting()
    // {
    //     if (self::$adminSetting == null) {

    //         $data     = \DB::table('admin_payment_settings');
    //         $settings = [];
    //         if (\Auth::check()) {
    //             $user_id = 1;
    //             $data    = $data->where('created_by', '=', $user_id);
    //         }
    //         self::$adminSetting = $data;
    //         foreach ($data as $row) {
    //             $settings[$row->name] = $row->value;
    //         }
    //     }

    //     return self::$adminSetting;
    // }

    public static function getAdminPaymentSetting()
    {
        if (self::$adminSetting === null) {
            $settings = [];

            if (\Auth::check()) {
                $user_id = 1; // You may want to replace this with the actual user's ID.
                $data = \DB::table('admin_payment_settings')
                    ->where('created_by', $user_id)
                    ->get();

                foreach ($data as $row) {
                    $settings[$row->name] = $row->value;
                }
            }

            self::$adminSetting = $settings;
        }

        return self::$adminSetting;
    }


    public static function getCompanyPaymentSetting($user_id)
    {
        $data     = \DB::table('company_payment_settings');
        $settings = [];
        $data    = $data->where('created_by', '=', $user_id);
        $data = $data->get();
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function getCompanyPayment()
    {

        $data     = \DB::table('company_payment_settings');
        $settings = [];
        if (\Auth::check()) {
            $user_id = \Auth::user()->creatorId();
            $data    = $data->where('created_by', '=', $user_id);
        }
        $data = $data->get();
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    public static function getNonAuthCompanyPaymentSetting($id)
    {

        $data     = \DB::table('company_payment_settings');
        $settings = [];
        $data     = $data->where('created_by', '=', $id);

        $data = $data->get();
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    public static function error_res($msg = "", $args = array())
    {
        $msg       = $msg == "" ? "error" : $msg;
        $msg_id    = 'error.' . $msg;
        $converted = \Lang::get($msg_id, $args);
        $msg       = $msg_id == $converted ? $msg : $converted;
        $json      = array(
            'flag' => 0,
            'msg' => $msg,
        );

        return $json;
    }

    public static function success_res($msg = "", $args = array())
    {
        $msg       = $msg == "" ? "success" : $msg;
        $msg_id    = 'success.' . $msg;
        $converted = \Lang::get($msg_id, $args);
        $msg       = $msg_id == $converted ? $msg : $converted;
        $json      = array(
            'flag' => 1,
            'msg' => $msg,
        );

        return $json;
    }

    // get date format
    public static function getDateFormated($date, $time = false)
    {
        if (!empty($date) && $date != '0000-00-00') {
            if ($time == true) {
                return date("d M Y H:i A", strtotime($date));
            } else {
                return date("d M Y", strtotime($date));
            }
        } else {
            return '';
        }
    }


    public static function invoice_payment_settings($id)
    {
        $data = [];

        $user = User::where(['id' => $id])->first();
        if (!is_null($user)) {
            $data = DB::table('admin_payment_settings');
            $data->where('created_by', '=', $id);
            $data = $data->get();
            //dd($data);
        }

        $res = [];

        foreach ($data as $key => $value) {
            $res[$value->name] = $value->value;
        }

        return $res;
    }

    public static function bill_payment_settings($id)
    {
        $data = [];

        $user = User::where(['id' => $id])->first();
        if (!is_null($user)) {
            $data = DB::table('admin_payment_settings');
            $data->where('created_by', '=', $id);
            $data = $data->get();
            //dd($data);
        }

        $res = [];

        foreach ($data as $key => $value) {
            $res[$value->name] = $value->value;
        }

        return $res;
    }


    public static function settingById($id)
    {
        $data     = DB::table('settings')->where('created_by', '=', $id)->get();
        $settings = [
            "site_currency" => "USD",
            "site_currency_symbol" => "",
            "site_currency_symbol_position" => "pre",
            "site_date_format" => "M j, Y",
            "site_time_format" => "g:i A",
            "company_name" => "",
            "company_address" => "",
            "company_city" => "",
            "company_state" => "",
            "company_zipcode" => "",
            "company_country" => "",
            "company_telephone" => "",
            "company_email" => "",
            "invoice_service_charge" => "",
            "vat_charge" => "",
            "company_email_from_name" => "",
            "invoice_prefix" => "#INVO",
            "journal_prefix" => "#JUR",
            "invoice_color" => "ffffff",
            "proposal_prefix" => "#PROP",
            "proposal_color" => "ffffff",
            "proposal_logo" => " ",
            "retainer_logo" => " ",
            "invoice_logo" => " ",
            "bill_logo" => " ",
            "retainer_color" => "ffffff",
            "bill_prefix" => "#BILL",
            "bill_color" => "ffffff",
            "customer_prefix" => "#CUST",
            "vender_prefix" => "#VEND",
            "footer_title" => "",
            "footer_notes" => "",
            "invoice_template" => "template1",
            "bill_template" => "template1",
            "proposal_template" => "template1",
            "retainer_template" => "template1",
            "registration_number" => "",
            "vat_number" => "",
            "default_language" => "en",
            "enable_stripe" => "",
            "enable_paypal" => "",
            "paypal_mode" => "",
            "paypal_client_id" => "",
            "paypal_secret_key" => "",
            "stripe_key" => "",
            "stripe_secret" => "",
            "decimal_number" => "2",
            "tax_number" => "on",
            "tax_type" => "",
            "shipping_display" => "on",
            "journal_prefix" => "#JUR",
            "display_landing_page" => "on",
            "title_text" => "",
            // 'gdpr_cookie' => "off",
            'cookie_text' => "",
            "invoice_starting_number" => "1",
            "proposal_starting_number" => "1",
            "retainer_starting_number" => "1",
            "bill_starting_number" => "1",
        ];

        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }



    public static function addNewData()
    {
        \Artisan::call('cache:forget spatie.permission.cache');
        \Artisan::call('cache:clear');
        $usr = \Auth::user();

        $arrPermissions = [
            'manage budget planner',
            'create budget planner',
            'edit budget planner',
            'delete budget planner',
            'view budget planner',
            'stock report',
            'manage contract',
            'manage customer contract',
            'create contract',
            'edit contract',
            'delete contract',
            'show contract',
            'duplicate contract',
            'delete attachment',
            'delete comment',
            'delete notes',
            'contract description',
            'upload attachment',
            'add comment',
            'add notes',
            'send contract mail',
            'manage retainer',


        ];
        foreach ($arrPermissions as $ap) {
            // check if permission is not created then create it.
            $permission = Permission::where('name', 'LIKE', $ap)->first();
            if (empty($permission)) {
                Permission::create(['name' => $ap]);
            }
        }
        $companyRole = Role::where('name', 'LIKE', 'company')->first();

        $companyPermissions   = $companyRole->getPermissionNames()->toArray();
        $companyNewPermission = [
            'manage budget planner',
            'create budget planner',
            'edit budget planner',
            'delete budget planner',
            'view budget planner',
            'stock report',
            'manage contract',
            'manage customer contract',
            'create contract',
            'edit contract',
            'delete contract',
            'show contract',
            'duplicate contract',
            'delete attachment',
            'delete comment',
            'delete notes',
            'contract description',
            'upload attachment',
            'add comment',
            'add notes',
            'send contract mail',
            'manage retainer',
        ];
        foreach ($companyNewPermission as $op) {
            // check if permission is not assign to owner then assign.
            if (!in_array($op, $companyPermissions)) {
                $permission = Permission::findByName($op);
                $companyRole->givePermissionTo($permission);
            }
        }
    }

    // Twilio Notification
    public static function send_twilio_msg($to, $slug, $obj, $user_id = null)
    {
        // dd($user_id);
        $notification_template = NotificationTemplates::where('slug', $slug)->first();

        if (!empty($notification_template) && !empty($obj)) {
            if (!empty($user_id)) {
                $user = User::find($user_id);
            } else {
                $user = \Auth::user();
            }
            $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', $user->lang)->where('created_by', '=', $user->id)->first();

            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', $user->lang)->first();
            }
            if (empty($curr_noti_tempLang)) {
                $curr_noti_tempLang       = NotificationTemplateLangs::where('parent_id', '=', $notification_template->id)->where('lang', 'en')->first();
            }
            if (!empty($curr_noti_tempLang) && !empty($curr_noti_tempLang->content)) {
                $msg = self::replaceVariable($curr_noti_tempLang->content, $obj);
            }
        }
        // dd($msg);
        if (isset($msg)) {
            $settings      = Utility::settings($user->id);
            $account_sid   = $settings['twilio_sid'];
            $auth_token    = $settings['twilio_token'];
            $twilio_number = $settings['twilio_from'];
            try {
                $client        = new Client($account_sid, $auth_token);
                $client->messages->create($to, [
                    'from' => $twilio_number,
                    'body' => $msg,
                ]);
            } catch (\Exception $e) {
            }
            //  dd('SMS Sent Successfully.');
        }
    }

    // inventory management (Quantity)

    public static function total_quantity($type, $quantity, $product_id)
    {

        $product = ProductService::find($product_id);

        if (($product->type == 'Product')) {
            $pro_quantity = $product->quantity;

            if ($type == 'minus') {
                $product->quantity = $pro_quantity - $quantity;
            } else {
                $product->quantity = $pro_quantity + $quantity;
            }

            $product->save();
        }
    }

    public static function starting_number($id, $type, $createdBy = null)
    {

        if ($type == 'invoice') {
            $data = DB::table('settings')->where('created_by', $createdBy ?? \Auth::user()->creatorId())->where('name', 'invoice_starting_number')->update(array('value' => $id));
        } elseif ($type == 'proposal') {
            $data = DB::table('settings')->where('created_by', $createdBy ?? \Auth::user()->creatorId())->where('name', 'proposal_starting_number')->update(array('value' => $id));
        } elseif ($type == 'retainer') {
            $data = DB::table('settings')->where('created_by', $createdBy ?? \Auth::user()->creatorId())->where('name', 'retainer_starting_number')->update(array('value' => $id));
        } elseif ($type == 'bill') {
            $data = DB::table('settings')->where('created_by', $createdBy ?? \Auth::user()->creatorId())->where('name', 'bill_starting_number')->update(array('value' => $id));
        }


        return $data;
    }


    //add quantity in product stock
    public static function addProductStock($product_id, $quantity, $type, $description, $type_id, $createdBy = null)
    {

        $stocks             = new StockReport();
        $stocks->product_id = $product_id;
        $stocks->quantity     = $quantity;
        $stocks->type = $type;
        $stocks->type_id = $type_id;
        $stocks->description = $description;
        $stocks->created_by = $createdBy ?? \Auth::user()->creatorId();
        $stocks->save();
    }
    //add quantity in product stock
    public static function addInvoiceProductStock($product_id, $quantity, $type, $description, $type_id, $createdBy = null)
    {
        StockReport::updateOrCreate(
            [
                'type_id' => $type_id,
                'product_id' => $product_id,
            ],
            [
                'quantity' => $quantity,
                'type' => $type,
                'description' => $description,
                'created_by' => $createdBy ?? \Auth::user()->creatorId(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public static function mode_layout()
    {
        $data = DB::table('settings');
        $data = $data->where('created_by', '=', 1);
        $data     = $data->get();
        $settings = [
            "cust_darklayout" => "off",
            "cust_theme_bg" => "off",
            "color" => 'theme-3'
        ];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function colorset()
    {
        if (\Auth::user()) {
            if (\Auth::user()->type == 'super admin') {
                $user = \Auth::user();
                $setting = DB::table('settings')->where('created_by', $user->id)->pluck('value', 'name')->toArray();
            } else {
                $setting = DB::table('settings')->where('created_by', \Auth::user()->creatorId())->pluck('value', 'name')->toArray();
            }
        } else {
            $user = User::where('type', 'super admin')->first();
            $setting = DB::table('settings')->where('created_by', $user->id)->pluck('value', 'name')->toArray();
        }
        if (!isset($setting['color'])) {
            $setting = Utility::settings();
        }
        return $setting;
    }

    public static function admin_color()
    {
        if (\Auth::user()) {
            if (\Auth::user()->type == 'super admin') {
                $user = \Auth::user();
                $setting = DB::table('settings')->where('created_by', $user->id)->pluck('value', 'name')->toArray();
            } else {
                $setting = DB::table('settings')->where('created_by', \Auth::user()->created_by)->pluck('value', 'name')->toArray();
            }
        } else {
            $user = User::where('type', 'super admin')->first();
            $setting = DB::table('settings')->where('created_by', $user->id)->pluck('value', 'name')->toArray();
        }
        if (!isset($setting['color'])) {
            $setting = Utility::settings();
        }
        return $setting;
    }

    public static function get_superadmin_logo()
    {
        $is_dark_mode = self::getValByName('cust_darklayout');
        $setting = DB::table('settings')->where('created_by', '1')->pluck('value', 'name')->toArray();
        $is_dark_mode = isset($setting['cust_darklayout']) ? $setting['cust_darklayout'] : $is_dark_mode;

        if (\Auth::user() && \Auth::user()->type != 'super admin') {
            if ($is_dark_mode == 'on') {
                return Utility::getValByName('company_logo_light');
            } else {
                return Utility::getValByName('company_logo_dark');
            }
        } else {
            if ($is_dark_mode == 'on') {
                return 'logo-light.png';
            } else {
                return 'logo-dark.png';
            }
        }
    }

    public static function get_company_logo()
    {
        $is_dark_mode = self::getValByName('cust_darklayout');
        if ($is_dark_mode == 'on') {
            $logo = self::getValByName('cust_darklayout');
            return Utility::getValByName('company_logo_light');
        } else {
            return Utility::getValByName('company_logo_dark');
        }
    }

    public static function GetLogo()
    {
        $setting = Utility::colorset();
        if (\Auth::user() && \Auth::user()->type != 'super admin') {
            if ($setting['cust_darklayout'] == 'on') {
                return Utility::getValByName('company_logo_light');
            } else {
                return Utility::getValByName('company_logo_dark');
            }
        } else {
            if ($setting['cust_darklayout'] == 'on') {
                return Utility::getValByName('logo_light');
            } else {
                return Utility::getValByName('logo_dark');
            }
        }
    }


    public static function getLayoutsSetting()
    {
        $data = DB::table('settings');

        if (\Auth::check()) {

            $data = $data->where('created_by', '=', \Auth::user()->creatorId())->get();
            // dd($data);
            if (count($data) == 0) {
                $data = DB::table('settings')->where('created_by', '=', 1)->get();
            }
        } else {
            $data = $data->where('created_by', '=', 1)->get();
        }
        $settings = [
            "cust_theme_bg" => "on",
            "cust_darklayout" => "off",
            "color" => "theme-3",
            "SITE_RTL" => "off",
        ];

        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }

        return $settings;
    }

    // used for replace email variable (parameter 'template_name','id(get particular record by id for data)')
    public static function replaceVariable($content, $obj)
    {
        $arrVariable = [
            '{payment_name}',
            '{payment_bill}',
            '{payment_amount}',
            '{payment_date}',
            '{payment_method}',
            '{invoice_name}',
            '{invoice_number}',
            '{invoice_url}',
            '{bill_name}',
            '{bill_number}',
            '{bill_url}',
            '{payment_dueAmount}',
            '{proposal_name}',
            '{proposal_number}',
            '{proposal_url}',
            '{app_name}',
            '{company_name}',
            '{app_url}',
            '{email}',
            '{password}',
            '{contract_customer}',
            '{contract_subject}',
            '{contract_start_date}',
            '{contract_end_date}',
            '{contract_type}',
            '{contract_value}',
            '{retainer_name}',
            '{retainer_number}',
            '{retainer_url}',
            '{customer_name}',
            '{due_amount}',
            '{invoice_category}',
            '{vender_name}',
            '{user_name}',
            '{type}',
            '{company_email}'



        ];
        $arrValue    = [
            'payment_name' => '-',
            'payment_bill' => '-',
            'payment_amount' => '-',
            'payment_date' => '-',
            'payment_method' => '-',
            'invoice_name' => '-',
            'invoice_number' => '-',
            'invoice_url' => '-',
            'bill_name' => '-',
            'bill_number' => '-',
            'bill_url' => '-',
            'payment_dueAmount' => '-',
            'proposal_name' => '-',
            'proposal_number' => '-',
            'proposal_url' => '-',
            'app_name' => '-',
            'company_name' => '-',
            'app_url' => '-',
            'email' => '-',
            'password' => '-',
            'contract_customer' => '-',
            'contract_subject' => '-',
            'contract_start_date' => '-',
            'contract_end_date' => '-',
            'contract_type' => '-',
            'contract_value' => '-',
            'retainer_name' => '-',
            'retainer_number' => '-',
            'retainer_url' => '-',
            'customer_name' => '-',
            'due_amount' => '-',
            'invoice_category' => '-',
            'retainer_url' => '-',
            'vender_name' => '-',
            'user_name' => '-',
            'type' => '-',
            "company_email" => '-'



        ];

        foreach ($obj as $key => $val) {
            $arrValue[$key] = $val;
        }

        $settings = Utility::settings();
        $company_name = $settings['company_name'];

        $arrValue['app_name']     =  env('APP_NAME');
        $arrValue['company_name'] = self::settings()['company_name'];
        $arrValue['app_url']      = '<a href="' . env('APP_URL') . '" target="_blank">' . env('APP_URL') . '</a>';

        return str_replace($arrVariable, array_values($arrValue), $content);
    }


    public static function getSMTPDetails($user_id)
    {
        $settings = self::settingsById($user_id);
        $smtpDetail = config(
            [
                'mail.driver' => $settings['mail_driver'],
                'mail.host' => $settings['mail_host'],
                'mail.port' => $settings['mail_port'],
                'mail.encryption' => $settings['mail_encryption'],
                'mail.username' => $settings['mail_username'],
                'mail.password' => $settings['mail_password'],
                'mail.from.address' => $settings['mail_from_address'],
                'mail.from.name' => $settings['mail_from_name'],
            ]
        );

        return $smtpDetail;
    }

    public static function sendEmailTemplate($emailTemplate, $mailTo, $obj)
    {
        $usr = \Auth::user();
        //Remove Current Login user Email don't send mail to them
        if ($usr->user_type != 'super admin') {
            unset($mailTo[$usr->id]);

            $mailTo = array_values($mailTo);

            if ($usr->user_type != 'super admin') {
                // find template is exist or not in our record
                $template = EmailTemplate::where('slug', $emailTemplate)->first();

                if (isset($template) && !empty($template)) {
                    // check template is active or not by company

                    $is_active = UserEmailTemplate::where('template_id', '=', $template->id)->first();

                    if ($template->id == 1) {
                        $is_active->is_active = 1;
                    }

                    if ($is_active->is_active == 1) {

                        // get email content language base
                        $content = EmailTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', $usr->lang)->first();

                        $content->from = $template->from;


                        if ($usr->user_type == 'super admin') {
                            $settings = Utility::settings();
                        } else {
                            $setting = self::settings();
                            if (empty($setting['mail_driver'])) {
                                $setting = self::settingsById(1);
                            }
                            $settings = $setting;
                        }

                        config([
                            'mail.default'                   => isset($settings['mail_driver'])       ? $settings['mail_driver']       : '',
                            'mail.mailers.smtp.host'         => isset($settings['mail_host'])         ? $settings['mail_host']         : '',
                            'mail.mailers.smtp.port'         => isset($settings['mail_port'])         ? $settings['mail_port']         : '',
                            'mail.mailers.smtp.encryption'   => isset($settings['mail_encryption'])   ? $settings['mail_encryption']   : '',
                            'mail.mailers.smtp.username'     => isset($settings['mail_username'])     ? $settings['mail_username']     : '',
                            'mail.mailers.smtp.password'     => isset($settings['mail_password'])     ? $settings['mail_password']     : '',
                            'mail.from.address'              => isset($settings['mail_from_address']) ? $settings['mail_from_address'] : '',
                            'mail.from.name'                 => isset($settings['mail_from_name'])    ? $settings['mail_from_name']    : '',
                        ]);
                        if (!empty($content->content)) {
                            $content->content = self::replaceVariable($content->content, $obj);
                            // send email
                            try {
                                Mail::to($mailTo)->send(new CommonEmailTemplate($content, $settings, $mailTo[0]));
                            } catch (\Exception $e) {
                                $error = __('E-Mail has been not sent due to SMTP configuration');
                            }

                            if (isset($error)) {
                                $arReturn = [
                                    'is_success' => false,
                                    'error' => $error,
                                ];
                            } else {
                                $arReturn = [
                                    'is_success' => true,
                                    'error' => false,
                                ];
                            }
                        } else {
                            $arReturn = [
                                'is_success' => false,
                                'error' => __('Mail not send, email is empty'),
                            ];
                        }

                        return $arReturn;
                    } else {
                        return [
                            'is_success' => true,
                            'error' => false,
                        ];
                    }
                } else {
                    return [
                        'is_success' => false,
                        'error' => __('Mail not send, email not found'),
                    ];
                }
            }
        } else {
            $mailTo = array_values($mailTo);

            $template = EmailTemplate::where('slug', $emailTemplate)->first();

            $content = EmailTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', 'en')->first();

            $content->from = $template->from;
            $settings = Utility::settings();

            config(
                [
                    'mail.driver'       => isset($settings['mail_driver']) ? $settings['mail_driver'] : '',
                    'mail.host'         => isset($settings['mail_host']) ? $settings['mail_host'] : '',
                    'mail.port'         => isset($settings['mail_port']) ? $settings['mail_port'] : '',
                    'mail.encryption'   => isset($settings['mail_encryption']) ? $settings['mail_encryption'] : '',
                    'mail.username'     => isset($settings['mail_username']) ? $settings['mail_username'] : '',
                    'mail.password'     => isset($settings['mail_password']) ? $settings['mail_password'] : '',
                    'mail.from.address' => isset($settings['mail_from_address']) ? $settings['mail_from_address'] : '',
                    'mail.from.name'    => isset($settings['mail_from_name']) ? $settings['mail_from_name'] : '',
                ]
            );

            if (!empty($content->content)) {

                $content->content = self::replaceVariable($content->content, $obj);

                try {
                    Mail::to($mailTo)->send(new CommonEmailTemplate($content, $settings, $mailTo[0]));
                } catch (\Exception $e) {


                    $error = __('E-Mail has been not sent due to SMTP configuration');
                }
            }
        }
    }

    public static function sendUserEmailTemplate($emailTemplate, $mailTo, $obj)
    {
        $usr = Auth::user();
        //Remove Current Login user Email don't send mail to them
        // unset($mailTo[$usr->id]);
        $mailTo = array_values($mailTo);

        // find template is exist or not in our record
        $template = EmailTemplate::where('name', 'LIKE', $emailTemplate)->first();
        if (isset($template) && !empty($template)) {
            // check template is active or not by company

            $is_active = UserEmailTemplate::where('template_id', '=', $template->id)->where('user_id', '=', $usr->creatorId())->first();

            if ($is_active->is_active == 1) {

                $settings = self::settingsById(1);

                // get email content language base
                $content = EmailTemplateLang::where('parent_id', '=', $template->id)->where('lang', 'LIKE', $usr->lang)->first();
                $content->from = $template->from;
                if (!empty($content->content)) {
                    $content->content = self::replaceVariable($content->content, $obj);
                    // send email
                    try {
                        config(
                            [
                                'mail.driver' => $settings['mail_driver'],
                                'mail.host' => $settings['mail_host'],
                                'mail.port' => $settings['mail_port'],
                                'mail.encryption' => $settings['mail_encryption'],
                                'mail.username' => $settings['mail_username'],
                                'mail.password' => $settings['mail_password'],
                                'mail.from.address' => $settings['mail_from_address'],
                                'mail.from.name' => $settings['mail_from_name'],
                            ]
                        );
                        Mail::to($mailTo)->send(new CommonEmailTemplate($content, $settings));
                    } catch (\Exception $e) {
                        $error = $e->getMessage();
                    }

                    if (isset($error)) {
                        $arReturn = [
                            'is_success' => false,
                            'error' => $error,
                        ];
                    } else {
                        $arReturn = [
                            'is_success' => true,
                            'error' => false,
                        ];
                    }
                } else {
                    $arReturn = [
                        'is_success' => false,
                        'error' => __('Mail not send, email is empty'),
                    ];
                }

                return $arReturn;
            } else {
                return [
                    'is_success' => true,
                    'error' => false,
                ];
            }
        } else {
            return [
                'is_success' => false,
                'error' => __('Mail not send, email not found'),
            ];
        }
    }

    // Make Entry in email_tempalte_lang table when create new language
    // makeEmailLang

    public static function newLangEmailTemp($lang)
    {
        $template = EmailTemplate::all();
        foreach ($template as $t) {
            $default_lang                 = EmailTemplateLang::where('parent_id', '=', $t->id)->where('lang', 'LIKE', 'en')->first();
            $emailTemplateLang            = new EmailTemplateLang();
            $emailTemplateLang->parent_id = $t->id;
            $emailTemplateLang->lang      = $lang;
            $emailTemplateLang->subject   = $default_lang->subject;
            $emailTemplateLang->content   = $default_lang->content;
            $emailTemplateLang->save();
        }
    }

    // Email Template Modules Function END

    public static function upload_file($request, $key_name, $name, $path, $custom_validation = [])
    {
        try {
            $settings = Utility::getStorageSetting();
            // dd($settings);
            if (!empty($settings['storage_setting'])) {
                if ($settings['storage_setting'] == 'wasabi') {

                    config(
                        [
                            'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                            'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                            'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                            'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                            'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com'
                        ]
                    );

                    $max_size = !empty($settings['wasabi_max_upload_size']) ? $settings['wasabi_max_upload_size'] : '2048';
                    $mimes =  !empty($settings['wasabi_storage_validation']) ? $settings['wasabi_storage_validation'] : '';
                } else if ($settings['storage_setting'] == 's3') {
                    config(
                        [
                            'filesystems.disks.s3.key' => $settings['s3_key'],
                            'filesystems.disks.s3.secret' => $settings['s3_secret'],
                            'filesystems.disks.s3.region' => $settings['s3_region'],
                            'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                            'filesystems.disks.s3.use_path_style_endpoint' => false,
                        ]
                    );
                    $max_size = !empty($settings['s3_max_upload_size']) ? $settings['s3_max_upload_size'] : '2048';
                    $mimes =  !empty($settings['s3_storage_validation']) ? $settings['s3_storage_validation'] : '';
                } else {
                    $max_size = !empty($settings['local_storage_max_upload_size']) ? $settings['local_storage_max_upload_size'] : '2048';

                    $mimes =  !empty($settings['local_storage_validation']) ? $settings['local_storage_validation'] : '';
                }


                $file = $request->$key_name;

                if (count($custom_validation) > 0) {

                    $validation = $custom_validation;
                } else {

                    $validation = [
                        'mimes:' . $mimes,
                        'max:' . $max_size,
                    ];
                }

                $validator = \Validator::make($request->all(), [
                    $key_name => $validation
                ]);

                // dd($mimes,$max_size);

                if ($validator->fails()) {
                    $res = [
                        'flag' => 0,
                        'msg' => $validator->messages()->first(),
                    ];
                    return $res;
                } else {

                    $name = $name;

                    // if($settings['storage_setting']=='local'){

                    //     \Storage::disk()->putFileAs(
                    //         $path,
                    //         $request->file($key_name),
                    //         $name
                    //     );
                    //     $path = $path.$name;
                    //     // dd($path);
                    // }
                    if ($settings['storage_setting'] == 'local') {
                        $request->$key_name->move(storage_path($path), $name);
                        $path = $path . $name;
                    } else if ($settings['storage_setting'] == 'wasabi') {

                        $path = \Storage::disk('wasabi')->putFileAs(
                            $path,
                            $file,
                            $name
                        );

                        // $path = $path.$name;


                    } else if ($settings['storage_setting'] == 's3') {

                        $path = \Storage::disk('s3')->putFileAs(
                            $path,
                            $file,
                            $name
                        );

                        // $path = $path.$name;
                    }


                    $res = [
                        'flag' => 1,
                        'msg'  => 'success',
                        'url'  => $path
                    ];
                    return $res;
                }
            } else {
                $res = [
                    'flag' => 0,
                    'msg' => __('Please set proper configuration for storage.'),
                ];
                return $res;
            }
        } catch (\Exception $e) {
            // dd($e);
            $res = [
                'flag' => 0,
                'msg' => $e->getMessage(),
            ];
            return $res;
        }
    }

    public static function upload_file_v2($request, $key_name, $name, $path, $custom_validation = [])
    {
        try {
            $settings = Utility::getStorageSetting();

            if (!empty($settings['storage_setting'])) {
                if ($settings['storage_setting'] == 'wasabi') {
                    config([
                        'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                        'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                        'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                        'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                        'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com'
                    ]);
                    $max_size = !empty($settings['wasabi_max_upload_size']) ? $settings['wasabi_max_upload_size'] : '2048';
                    $mimes = !empty($settings['wasabi_storage_validation']) ? $settings['wasabi_storage_validation'] : '';
                } else if ($settings['storage_setting'] == 's3') {
                    config([
                        'filesystems.disks.s3.key' => $settings['s3_key'],
                        'filesystems.disks.s3.secret' => $settings['s3_secret'],
                        'filesystems.disks.s3.region' => $settings['s3_region'],
                        'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                        'filesystems.disks.s3.use_path_style_endpoint' => false,
                    ]);
                    $max_size = !empty($settings['s3_max_upload_size']) ? $settings['s3_max_upload_size'] : '2048';
                    $mimes = !empty($settings['s3_storage_validation']) ? $settings['s3_storage_validation'] : '';
                } else {
                    $max_size = !empty($settings['local_storage_max_upload_size']) ? $settings['local_storage_max_upload_size'] : '2048';
                    $mimes = !empty($settings['local_storage_validation']) ? $settings['local_storage_validation'] : '';
                }

                $file = $request[$key_name] ?? null;

                if (!$file instanceof \Illuminate\Http\UploadedFile) {
                    return [
                        'flag' => 0,
                        'msg' => __('Invalid file provided.')
                    ];
                }

                $validation = count($custom_validation) > 0 ? $custom_validation : [
                    'mimes:' . $mimes,
                    'max:' . $max_size,
                ];

                $validator = \Validator::make([$key_name => $file], [$key_name => $validation]);

                if ($validator->fails()) {
                    return [
                        'flag' => 0,
                        'msg' => $validator->messages()->first(),
                    ];
                }

                if ($settings['storage_setting'] == 'local') {
                    $file->move(storage_path($path), $name);
                    $path = $path . '/' . $name;
                } else if ($settings['storage_setting'] == 'wasabi') {
                    $path = \Storage::disk('wasabi')->putFileAs($path, $file, $name);
                } else if ($settings['storage_setting'] == 's3') {
                    $path = \Storage::disk('s3')->putFileAs($path, $file, $name);
                }

                return [
                    'flag' => 1,
                    'msg' => 'success',
                    'url' => $path
                ];
            }

            return [
                'flag' => 0,
                'msg' => __('Please set proper configuration for storage.'),
            ];
        } catch (\Exception $e) {
            return [
                'flag' => 0,
                'msg' => $e->getMessage(),
            ];
        }
    }


    public static function get_file($path)
    {
        $settings = Utility::getStorageSetting();

        try {
            if ($settings['storage_setting'] == 'wasabi') {
                config(
                    [
                        'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                        'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                        'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                        'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                        'filesystems.disks.wasabi.endpoint' => 'https://s3.' . $settings['wasabi_region'] . '.wasabisys.com'
                    ]
                );
            } elseif ($settings['storage_setting'] == 's3') {
                config(
                    [
                        'filesystems.disks.s3.key' => $settings['s3_key'],
                        'filesystems.disks.s3.secret' => $settings['s3_secret'],
                        'filesystems.disks.s3.region' => $settings['s3_region'],
                        'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                        'filesystems.disks.s3.use_path_style_endpoint' => false,
                    ]
                );
            }

            return \Storage::disk($settings['storage_setting'])->url($path);
        } catch (\Throwable $th) {
            return '';
        }
    }

    public static function getData()
    {
        $data = DB::table('settings')->where('created_by', 1)->get();

        return $data;
    }

    private static $seoSetting = null;

    public static function getSeoSetting()
    {
        if (is_null(self::$seoSetting)) {
            $data = DB::table('settings');
            $data = $data->where('created_by', '=', 1);

            $data     = $data->get();
            self::$seoSetting = $data;
        }
        $data = self::$seoSetting;
        $settings = [
            "meta_keywords" => "",
            "meta_image" => "",
            "meta_description" => ""
        ];
        foreach ($data as $row) {
            $settings[$row->name] = $row->value;
        }
        return $settings;
    }

    public static function webhookSetting($module, $user_id = null)
    {

        if (!empty($user_id)) {
            $user = User::find($user_id);
        } else {
            $user = \Auth::user();
        }
        $webhook = Webhook::where('module', $module)->where('created_by', '=', $user?->id)->first();
        if (!empty($webhook)) {
            $url = $webhook->url;
            $method = $webhook->method;
            $reference_url  = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $data['method'] = $method;
            $data['reference_url'] = $reference_url;
            $data['url'] = $url;
            return $data;
        }
        return false;
    }



    public static function WebhookCall($url = null, $parameter = null, $method = 'POST')
    {

        if (!empty($url) && !empty($parameter)) {
            try {

                $curlHandle = curl_init($url);
                curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $parameter);
                curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                $curlResponse = curl_exec($curlHandle);
                curl_close($curlHandle);
                if (empty($curlResponse)) {
                    return true;
                } else {
                    return false;
                }
            } catch (\Throwable $th) {
                return false;
            }
        } else {
            return false;
        }
    }

    public static $rates;
    public static $data;


    public static function getTaxData()
    {
        $data = [];
        if (self::$rates == null) {
            $rates = Tax::get();
            self::$rates = $rates;
            foreach (self::$rates as $rate) {
                $data[$rate->id]['id'] = $rate->id;
                $data[$rate->id]['name'] = $rate->name;
                $data[$rate->id]['rate'] = $rate->rate;
                $data[$rate->id]['created_by'] = $rate->created_by;
            }
            self::$data = $data;
        }
        return self::$data;
    }

    public static function getAccountData($account_id, $start_date = null, $end_date = null)
    {

        if (!empty($start_date) && !empty($end_date)) {
            $start = $start_date;
            $end = $end_date;
        } else {
            $start = date('Y-m-01');
            $end = date('Y-m-t');
        }

        $transactionData = DB::table('transaction_lines')
            ->where('transaction_lines.created_by', \Auth::user()->creatorId())
            ->where('transaction_lines.account_id', $account_id)
            ->whereBetween('transaction_lines.date', [$start, $end])
            ->leftJoin('invoices', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'invoices.id')
                    ->whereIn('transaction_lines.reference', ['Invoice Payment', 'Invoice']);
            })
            ->leftJoin('bills', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'bills.id')
                    ->whereIn('transaction_lines.reference', ['Bill', 'Bill Payment', 'Bill Account', 'Expense', 'Expense Account', 'Expense Payment']);
            })
            ->leftJoin('revenues', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'revenues.id')
                    ->whereIn('transaction_lines.reference', ['Revenue']);
            })
            ->leftJoin('payments', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'payments.id')
                    ->whereIn('transaction_lines.reference', ['Payment']);
            })
            ->leftJoin('bank_accounts', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'bank_accounts.id')
                    ->whereIn('transaction_lines.reference', ['Bank Account']);
            })
            ->leftJoin('customers as revenues_customers', 'revenues.customer_id', '=', 'revenues_customers.id')
            ->leftJoin('venders as payments_venders', 'payments.vender_id', '=', 'payments_venders.id')
            ->leftJoin('customers', 'invoices.customer_id', '=', 'customers.id')
            ->leftJoin('venders', 'bills.vender_id', '=', 'venders.id')
            ->leftJoin('chart_of_accounts', 'transaction_lines.account_id', '=', 'chart_of_accounts.id')
            ->select(
                'transaction_lines.*',
                'invoices.customer_id as customer_id',
                'bills.vender_id as vendor_id',
                'chart_of_accounts.name as account_name',
                DB::raw("COALESCE(customers.name, venders.name , revenues_customers.name , payments_venders.name, bank_accounts.holder_name) as user_name"),
                DB::raw("COALESCE(invoices.invoice_id, bills.bill_id) as ids"),
            )
            ->get();

        return $transactionData;
    }

    public static function get_device_type($user_agent)
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

    public static function GetCacheSize()
    {
        $file_size = 0;
        foreach (\File::allFiles(storage_path('/framework')) as $file) {
            $file_size += $file->getSize();
        }
        $file_size = number_format($file_size / 1000000, 4);
        return $file_size;
    }


    public static function updateStorageLimit($company_id, $image_size)
    {
        $image_size = number_format($image_size / 1048576, 2);
        $user   = User::find($company_id);
        $plan   = Plan::find($user->plan);
        $total_storage = $user->storage_limit + $image_size;


        if ($plan->storage_limit <= $total_storage && $plan->storage_limit > 1) {
            $error = __('Plan storage limit is over so please upgrade the plan.');
            return $error;
        } else {
            $user->storage_limit = $total_storage;
        }

        $user->save();
        return 1;
    }

    public static function changeStorageLimit($company_id, $file_path)
    {

        $files =  \File::glob(storage_path($file_path));
        $fileSize = 0;
        foreach ($files as $file) {
            $fileSize += \File::size($file);
        }

        $image_size = number_format($fileSize / 1048576, 2);
        $user   = User::find($company_id);
        $plan   = Plan::find($user->plan);
        $total_storage = $user->storage_limit - $image_size;
        $user->storage_limit = $total_storage;
        $user->save();

        $status = false;
        foreach ($files as $key => $file) {
            if (\File::exists($file)) {
                $status = \File::delete($file);
            }
        }

        return true;
    }

    public static function getChatGPTSettings()
    {
        $user = User::find(\Auth::user()->creatorId());
        $plan = \App\Models\Plan::find($user->plan);
        return $plan;
    }

    public static function getAccountBalance($account_id, $start_date = null, $end_date = null)
    {
        if (!empty($start_date) && !empty($end_date)) {
            $start = $start_date;
            $end = $end_date;
        } else {
            $start = date('Y-m-01');
            $end = date('Y-m-t');
        }

        // foreach ($types as $type) {
        $total = TransactionLines::select(
            'chart_of_accounts.id',
            'chart_of_accounts.code',
            'chart_of_accounts.name',
            \DB::raw('sum(transaction_lines.debit) as totalDebit'),
            \DB::raw('sum(transaction_lines.credit) as totalCredit')
        );
        $total->leftjoin('chart_of_accounts', 'transaction_lines.account_id', 'chart_of_accounts.id');
        $total->leftjoin('chart_of_account_types', 'chart_of_accounts.type', 'chart_of_account_types.id');
        $total->where('transaction_lines.created_by', \Auth::user()->creatorId());
        $total->where('transaction_lines.account_id', $account_id);
        $total->where('transaction_lines.date', '>=', $start);
        $total->where('transaction_lines.date', '<=', $end);
        $total->groupBy('account_id');
        $total = $total->get()->toArray();

        $balance = 0;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($total as $key => $record) {
            $totalDebit = $record['totalDebit'];
            $totalCredit = $record['totalCredit'];
        }

        $balance += $totalCredit - $totalDebit;

        return $balance;
    }
    public static function getNewChartOfAccountBalance($account_id, $start_date = null, $end_date = null)
    {
        if (!empty($start_date) && !empty($end_date)) {
            $start = $start_date;
            $end = $end_date;
        } else {
            $start = date('Y-m-01');
            $end = date('Y-m-t');
        }
        $accountDetails = ChartOfAccount::where('id', $account_id)->first()->name ?? '';
        if($accountDetails)
        {
            $accountDetails = explode('-', $accountDetails);
            if(count($accountDetails) == 2)
            {
                $customerDetails = Customer::where('property_number', $accountDetails[0])->where('name', $accountDetails[1])->first()->id ?? '';
                $lastClosingBalance = StakeholderTransactionLine::where('customer_id', $customerDetails)
                ->where('created_by', \Auth::user()->creatorId())
                ->whereBetween('date', [$start_date, $end_date])
                ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->value('closing_balance');
            }
        }
        return $lastClosingBalance ?? 0;
    }

    public static function getBankOpeningBalance($account_id, $start_date = null)
    {
        $bankOpeningBalance = BankAccount::where('created_by', \Auth::user()->creatorId())
            ->where('chart_account_id', $account_id)
            ->value('opening_balance');

        $transactionsBeforeStart = TransactionLines::where('created_by', \Auth::user()->creatorId())
            ->where('account_id', $account_id)
            ->where('date', '<', $start_date)
            ->selectRaw('SUM(credit) as totalCredit, SUM(debit) as totalDebit')
            ->first();


        $openingBalanceAdjustment = ($transactionsBeforeStart->totalCredit ?? 0) - ($transactionsBeforeStart->totalDebit ?? 0);


        return $bankOpeningBalance - $openingBalanceAdjustment;
    }

    public static function addTransactionLines($data, $created_by = null, $buildingId = null)
    {
        Log::info('Adding transaction lines: ' . json_encode($data));
        Log::info('Adding transaction lines created_by: ' . $created_by);
        Log::info('Adding transaction lines buildingId: ' . $buildingId);
        $existingTransaction = TransactionLines::where('reference_id', $data['reference_id'])
            ->where('account_id', $data['account_id'])
            ->where('reference_sub_id', $data['reference_sub_id'])
            ->where('reference', $data['reference'])
            ->first();

        $chartOfAccount = ChartOfAccount::find($data['account_id']);
        $olderAccountId = null;
        if ($existingTransaction) {
            $transactionLines = $existingTransaction;
            $olderAccountId = ($chartOfAccount->id == $existingTransaction->account_id) ? null : $existingTransaction->account_id;
        } else {
            $transactionLines = new TransactionLines();
        }
        $transactionLines->account_id = $data['account_id'];
        $transactionLines->reference = $data['reference'];
        $transactionLines->reference_id = $data['reference_id'];
        $transactionLines->reference_sub_id = $data['reference_sub_id'];
        $transactionLines->date = $data['date'];

        $previousTransaction = TransactionLines::where('account_id', $data['account_id'])
            ->when($transactionLines->id, function ($q) use ($transactionLines) {
                $q->where('id', '<', $transactionLines->id);
            })
            ->orderBy('id', 'desc')
            ->first();

        $transactionLines->opening_balance = $previousTransaction ? $previousTransaction->closing_balance : $chartOfAccount->initial_balance;
        $transactionLines->closing_balance = self::getClosingBalance($chartOfAccount->types->name, $data['transaction_type'], $data['transaction_amount'], $transactionLines->opening_balance);

        if ($data['transaction_type'] == "Credit") {
            $transactionLines->credit = $data['transaction_amount'];
            $transactionLines->debit = 0;
        } else {
            $transactionLines->credit = 0;
            $transactionLines->debit = $data['transaction_amount'];
        }
        $transactionLines->created_by = $created_by ?? \Auth::user()->creatorId();
        $transactionLines->building_id = $buildingId ?? \Auth::user()->currentBuilding();
        $transactionLines->save();
        Log::info('Transaction Line Created: ' . json_encode($transactionLines));
        $recalculateTransactionBalance = TransactionLines::recalculateTransactionBalance($chartOfAccount->id, $transactionLines->created_at);
        Log::info('Transaction Line Recalculated: ' . json_encode($recalculateTransactionBalance));
        if ($olderAccountId) {
            TransactionLines::recalculateTransactionBalance($olderAccountId, $transactionLines->created_at);
        }
    }

    public static function billInvoiceData($array, $request, $yearList)
    {
        $billsum = [];
        foreach ($array as $category => $categoryData) {
            $billchartArr = [];
            foreach ($yearList as $key => $value) {

                if ($request->period === 'quarterly') {
                    for ($i = 0; $i < 12; $i += 3) {
                        $invoicequarterArr = array_slice($categoryData[$key], $i, 3);
                        $billchartArr[] = array_sum($invoicequarterArr);
                    }
                } elseif ($request->period === 'half-yearly') {
                    for ($i = 0; $i < 12; $i += 6) {
                        $InvoicehalfYearArr = array_slice($categoryData[$key], $i, 6);
                        $billchartArr[] = array_sum($InvoicehalfYearArr);
                    }
                } elseif ($request->period === 'yearly') {
                    for ($i = 0; $i < 12; $i += 12) {
                        $invoiceyearArr = array_slice($categoryData[$key], $i, 12);
                        $billchartArr[] = array_sum($invoiceyearArr);
                    }
                } else {
                    // Monthly
                    $billchartArr = $categoryData[$key];
                }
            }

            $billdata = [
                "category" => $category,
                "data" => $billchartArr,
            ];

            $billsum[] = $billdata;
        }
        return $billsum;
    }

    public static function revenuePaymentData($category, $categoryData, $request, $yearList)
    {

        $chartArr = [];
        foreach ($yearList as $key => $value) {
            if ($request->period === 'quarterly') {
                for ($i = 0; $i < 12; $i += 3) {
                    $quarterArr = array_slice($categoryData[$key], $i, 3);
                    $chartArr[] = array_sum($quarterArr);
                }
            } elseif ($request->period === 'half-yearly') {
                for ($i = 0; $i < 12; $i += 6) {
                    $halfYearArr = array_slice($categoryData[$key], $i, 6);
                    $chartArr[] = array_sum($halfYearArr);
                }
            } elseif ($request->period === 'yearly') {

                for ($i = 0; $i < 12; $i += 12) {
                    $yearArr = array_slice($categoryData[$key], $i, 12);
                    $chartArr[] = array_sum($yearArr);
                }
            } else {
                $chartArr = $categoryData[$key];
                $billchartArr = $categoryData[$key];
            }
        }

        $chartdata = [
            "category" => $category,
            "data" => $chartArr,
        ];

        return $chartdata;
    }

    public static function billData($billArray, $request, $yearList)
    {
        $billsum = [];
        foreach ($billArray as $category => $categoryData) {
            $billchartArr = [];
            foreach ($yearList as $key => $value) {
                if ($request->period === 'quarterly') {
                    for ($i = 0; $i < 12; $i += 3) {
                        $invoicequarterArr = array_slice($categoryData[$key], $i, 3);
                        $billchartArr[] = array_sum($invoicequarterArr);
                    }
                } elseif ($request->period === 'half-yearly') {
                    for ($i = 0; $i < 12; $i += 6) {
                        $InvoicehalfYearArr = array_slice($categoryData[$key], $i, 6);
                        $billchartArr[] = array_sum($InvoicehalfYearArr);
                    }
                } elseif ($request->period === 'yearly') {
                    for ($i = 0; $i < 12; $i += 12) {
                        $invoiceyearArr = array_slice($categoryData[$key], $i, 12);
                        $billchartArr[] = array_sum($invoiceyearArr);
                    }
                } else {
                    // Monthly
                    $billchartArr = $categoryData[$key];
                }
            }
            $billdata = [
                "category" => $category,
                "data" => $billchartArr,
            ];
            $billsum[] = $billdata;
        }
        return $billsum;
    }
    public static function expenseData($category, $categoryData, $request, $yearList)
    {
        $chartArr = [];
        foreach ($yearList as $key => $value) {
            if ($request->period === 'quarterly') {
                for ($i = 0; $i < 12; $i += 3) {
                    $quarterArr = array_slice($categoryData[$key], $i, 3);
                    $chartArr[] = array_sum($quarterArr);
                }
            } elseif ($request->period === 'half-yearly') {
                for ($i = 0; $i < 12; $i += 6) {
                    $halfYearArr = array_slice($categoryData[$key], $i, 6);
                    $chartArr[] = array_sum($halfYearArr);
                }
            } elseif ($request->period === 'yearly') {
                for ($i = 0; $i < 12; $i += 12) {
                    $yearArr = array_slice($categoryData[$key], $i, 12);
                    $chartArr[] = array_sum($yearArr);
                }
            } else {
                $chartArr = $categoryData[$key];
            }
        }
        $chartdata = [
            "category" => $category,
            "data" => $chartArr,
        ];
        return $chartdata;
    }
    public static function totalData($billArr, $expenseArr, $request, $yearList)
    {
        $chartExpenseArr = [];
        foreach ($yearList as $year) {
            if ($request->period === 'quarterly') {
                for ($i = 0; $i < 12; $i += 3) {
                    $quarterbillArr = array_slice($billArr[$year], $i, 3);
                    $quarterexpenseArr = array_slice($expenseArr[$year], $i, 3);
                    $chartbillArr[$year][$i] = array_sum($quarterbillArr);
                    $chartexpenseArr[$year][$i] = array_sum($quarterexpenseArr);
                }
            } elseif ($request->period === 'half-yearly') {
                for ($i = 0; $i < 12; $i += 6) {
                    $halfYearBillArr = array_slice($billArr[$year], $i, 6);
                    $halfYearExpenseArr = array_slice($expenseArr[$year], $i, 6);
                    $chartbillArr[$year][$i] = array_sum($halfYearBillArr);
                    $chartexpenseArr[$year][$i] = array_sum($halfYearExpenseArr);
                }
            } elseif ($request->period === 'yearly') {
                for ($i = 0; $i < 12; $i += 12) {
                    $YearBillArr = array_slice($billArr[$year], $i, 12);
                    $YearExpenseArr = array_slice($expenseArr[$year], $i, 12);
                    $chartbillArr[$year][$i] = array_sum($YearBillArr);
                    $chartexpenseArr[$year][$i] = array_sum($YearExpenseArr);
                }
            } else {
                for ($i = 1; $i <= 12; $i++) {
                    $chartbillArr[$year][] = $billArr[$year][$i];
                    $chartexpenseArr[$year][] = $expenseArr[$year][$i];
                }
            }
        }
        if (isset($chartexpenseArr) && isset($chartbillArr)) {
            foreach ($chartexpenseArr as $year => $values) {
                if (isset($chartbillArr[$year])) {
                    $chartExpenseArr[] = array_map(function ($a, $b) {
                        return $a + $b;
                    }, $chartexpenseArr[$year], $chartbillArr[$year]);
                } else {
                    $chartExpenseArr[$year] = $values;
                }
            }
        }
        return $chartExpenseArr;
    }

    public static function totalSum($array, $request, $yearList)
    {

        $totalArr = [];
        foreach ($yearList as $year) {
            if ($request->period === 'quarterly') {
                for ($i = 0; $i < 12; $i += 3) {
                    $quarterArr = array_slice($array[$year], $i, 3);
                    $totalArr[$year][$i] = array_sum($quarterArr);
                }
            } elseif ($request->period === 'half-yearly') {
                for ($i = 0; $i < 12; $i += 6) {
                    $halfYearArr = array_slice($array[$year], $i, 6);
                    $totalArr[$year][$i] = array_sum($halfYearArr);
                }
            } elseif ($request->period === 'yearly') {
                for ($i = 0; $i < 12; $i += 12) {
                    $YearArr = array_slice($array[$year], $i, 12);
                    $totalArr[$year][$i] = array_sum($YearArr);
                }
            } else {
                for ($i = 1; $i <= 12; $i++) {
                    $totalArr[] = $array[$year][$i];
                }
            }
        }
        return $totalArr;
    }

    public static $chartOfAccountType = [
        'assets' => 'Assets',
        'liabilities' => 'Liabilities',
        // 'equity' => 'Equity',
        'income' => 'Income',
        // 'costs of goods sold' => 'Costs of Goods Sold',
        'expenses' => 'Expenses',

    ];

    public static $chartOfAccountSubType = array(
        "assets" => array(
            '1' => 'Current Asset',
            '2' => 'Fixed Assets',
            '3' => 'Investments',
            '4' => 'Misc. Expenses (Asset)'
        ),
        "liabilities" => array(
            '1' => 'Branch / Divisions',
            '2' => 'Capital Account',
            '3' => 'Current Liabilities',
            // '4' => 'Retained Earnings',
        ),
        // "equity" => array(
        //     '1' => 'Owners Equity',
        // ),
        "income" => array(
            '1' => 'Direct Income',
            '2' => 'OSales Account',
        ),
        // "costs of goods sold" => array(
        //     '1' => 'Costs of Goods Sold',
        // ),
        "expenses" => array(
            '1' => 'Indirect Expenses',
            '2' => 'Purchase Accounts',
        ),

    );

    public static function chartOfAccountTypeData($company_id, $building_id = null)
    {
        $chartOfAccountTypes = Self::$chartOfAccountType;
        Log::info("Utility chartOfAccountTypeData 2545 chartOfAccountTypes ---------".json_encode($chartOfAccountTypes));
        foreach ($chartOfAccountTypes as $k => $type) {

            $accountType = ChartOfAccountType::create(
                [
                    'name' => $type,
                    'created_by' => $company_id,
                    'building_id' => $building_id
                ]
            );
            Log::info("Utility chartOfAccountTypeData 2545-----------".json_encode($accountType));
            $chartOfAccountSubTypes = Self::$chartOfAccountSubType;
            Log::info("Utility chartOfAccountTypeData 2549 chartOfAccountSubTypes ---------".json_encode($chartOfAccountSubTypes));
            foreach ($chartOfAccountSubTypes[$k] as $subType) {
                $accountSubType = ChartOfAccountSubType::create(
                                        [
                                            'name' => $subType,
                                            'type' => $accountType->id,
                                            'created_by' => $company_id,
                                            'building_id' => $building_id
                                        ]
                                    );
                Log::info("Utility chartOfAccountTypeData 2559 ----------------------".json_encode($accountSubType));
            }
        }
    }

    public static $chartOfAccount = array(
        [
            'code' => '1060',
            'name' => 'Deposits (Asset)',
            'type' => 1,
            'sub_type' => 1,
        ],
        [
            'code' => '1065',
            'name' => 'Loans & Advances (Asset)',
            'type' => 1,
            'sub_type' => 1,
        ],
        [
            'code' => '1070',
            'name' => 'VAT Receivable 5%',
            'type' => 1, // Assets
            'sub_type' => 1, // Current Asset
        ],
        [
            'code' => '1200',
            'name' => 'Management company',
            'type' => 1,
            'sub_type' => 1,
        ],
        [
            'code' => '1205',
            'name' => 'Stock-in-Hand',
            'type' => 1,
            'sub_type' => 1,
        ],
        [
            'code' => '1206',
            'name' => 'Sundry Debtors',
            'type' => 1,
            'sub_type' => 1,
        ],
        [
            'code' => '1207',
            'name' => 'General Fund - Bank Account',
            'type' => 1,
            'sub_type' => 1,
        ],
        [
            'code' => '1208',
            'name' => 'Reserve Fund - Bank Account',
            'type' => 1,
            'sub_type' => 1,
        ],
        [
            'code' => '1209',
            'name' => 'General Fund + Reserve Fund - Bank Account',
            'type' => 1,
            'sub_type' => 1,
        ],
        [
            'code' => '1210',
            'name' => 'Cash',
            'type' => 1,
            'sub_type' => 1,
        ],
        [
            'code' => '2100',
            'name' => 'Reserves & Surplus',
            'type' => 2,
            'sub_type' => 2,
        ],
        [
            'code' => '2105',
            'name' => 'General Fund',
            'type' => 2,
            'sub_type' => 2,
        ],
        [
            'code' => '2110',
            'name' => 'Reserve Fund',
            'type' => 2,
            'sub_type' => 2,
        ],
        [
            'code' => '2120',
            'name' => 'Duties & Taxes',
            'type' => 2,
            'sub_type' => 3,
        ],
        [
            'code' => '2125',
            'name' => 'VAT Payable 5%',
            'type' => 2, // Liabilities
            'sub_type' => 3, // Current Liabilities
        ],
        [
            'code' => '2130',
            'name' => 'Provisions',
            'type' => 2,
            'sub_type' => 3,
        ],
        [
            'code' => '2140',
            'name' => 'Sundry Creditors',
            'type' => 2,
            'sub_type' => 3,
        ],
        [
            'code' => '4010',
            'name' => 'Legal Fee Reimbursement',
            'type' => 3,
            'sub_type' => 1,
        ],
        [
            'code' => '4020',
            'name' => 'Service Charges {year} (Non Tax) - General Fund',
            'type' => 3,
            'sub_type' => 1,
        ],
        [
            'code' => '4430',
            'name' => 'Service Charges {year} (Non Tax) - Reserve Fund',
            'type' => 3,
            'sub_type' => 1,
        ],
        [
            'code' => '4435',
            'name' => 'Service Charges {year} (Non Tax) - Gen & Res Fund',
            'type' => 3,
            'sub_type' => 1,
        ],
        [
            'code' => '4440',
            'name' => 'Service Charges {year} (Tax) - Gen & Res Fund',
            'type' => 3, // earlier 4
            'sub_type' => 1,
        ],
        [
            'code' => '4450',
            'name' => 'Service Charges {year}  Non Tax',
            'type' => 3,
            'sub_type' => 1,
        ],

        [
            'code' => '5005',
            'name' => 'Services (Non Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5006',
            'name' => 'Services (Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5007',
            'name' => 'Maintenance (Non Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5008',
            'name' => 'Maintenance (Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5009',
            'name' => 'Improvement (Non Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5010',
            'name' => 'Utilities (Non Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5011',
            'name' => 'Utilities (Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5012',
            'name' => 'Management Services (Non Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5013',
            'name' => 'Management Services (Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5014',
            'name' => 'Insurance (Non Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5014',
            'name' => 'Insurance (Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5015',
            'name' => 'Master Community (Non Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5016',
            'name' => 'Shared Services (Non Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5017',
            'name' => 'Reserve Fund',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5018',
            'name' => 'Adjustments (Non Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
        [
            'code' => '5019',
            'name' => 'Adjustments (Taxable)',
            'type' => 4,
            'sub_type' => 1,
        ],
    );

    public static $chartOfAccount1 = array(

        [
            'code' => '1060',
            'name' => 'Deposits (Asset)',
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '1065',
            'name' => 'Loans & Advances (Asset)',
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '1070',
            'name' => 'VAT Receivable 5%', //Added At 19-04-2025
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '1200',
            'name' => 'Management company',
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '1205',
            'name' => 'Stock-in-Hand',
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '1206',
            'name' => 'Sundry Debtors',
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '1207',
            'name' => 'General Fund - Bank Account',
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '1208',
            'name' => 'Reserve Fund - Bank Account',
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '1209',
            'name' => 'General Fund + Reserve Fund - Bank Account',
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '1210',
            'name' => 'Cash',
            'type' => 'Assets',
            'sub_type' => 'Current Asset',
        ],
        [
            'code' => '2100',
            'name' => 'Reserves & Surplus',
            'type' => 'Liabilities',
            'sub_type' => 'Capital Account',
        ],
        [
            'code' => '2105',
            'name' => 'General Fund',
            'type' => 'Liabilities',
            'sub_type' => 'Capital Account',
        ],
        [
            'code' => '2110',
            'name' => 'Reserve Fund',
            'type' => 'Liabilities',
            'sub_type' => 'Capital Account',
        ],
        [
            'code' => '2120',
            'name' => 'Duties & Taxes',
            'type' => 'Liabilities',
            'sub_type' => 'Current Liabilities',
        ],
        [
            'code' => '2125',
            'name' => 'VAT Payable 5%',//Added At 19-04-2025
            'type' => 'Liabilities',
            'sub_type' => 'Current Liabilities',
        ],
        [
            'code' => '2130',
            'name' => 'Accrued Franchise Tax',
            'type' => 'Liabilities',
            'sub_type' => 'Current Liabilities',
        ],
        [
            'code' => '2140',
            'name' => 'Sundry Creditors',
            'type' => 'Liabilities',
            'sub_type' => 'Current Liabilities',
        ],
        [
            'code' => '4010',
            'name' => 'Legal Fee Reimbursement',
            'type' => 'Income',
            'sub_type' => 'Direct Income',
        ],
        [
            'code' => '4020',
            'name' => 'Service Charges {year} (Non Tax) - General Fund',
            'type' => 'Income',
            'sub_type' => 'Direct Income',
        ],
        [
            'code' => '4430',
            'name' => 'Service Charges {year} (Non Tax) - Reserve Fund',
            'type' => 'Income',
            'sub_type' => 'Direct Income',
        ],
        [
            'code' => '4435',
            'name' => 'Service Charges {year} (Non Tax) - Gen & Res Fund',
            'type' => 'Income',
            'sub_type' => 'Direct Income',
        ],
        [
            'code' => '4440',
            'name' => 'Service Charges {year} (Tax) - Gen & Res Fund',
            'type' => 'Income',
            'sub_type' => 'Direct Income',
        ],
        [
            'code' => '4450',
            'name' => 'Service Charges {year}  Non Tax',
            'type' => 'Income',
            'sub_type' => 'Direct Income',
        ],
        [
            'code' => '5005',
            'name' => 'Services (Non Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5006',
            'name' => 'Services (Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5007',
            'name' => 'Maintenance (Non Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5008',
            'name' => 'Maintenance (Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5009',
            'name' => 'Improvement (Non Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5010',
            'name' => 'Utilities (Non Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5011',
            'name' => 'Utilities (Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5012',
            'name' => 'Management Services (Non Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5012',
            'name' => 'Management Services (Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5013',
            'name' => 'Insurance (Non Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5014',
            'name' => 'Insurance (Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5015',
            'name' => 'Master Community (Non Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5016',
            'name' => 'Shared Services (Non Taxable)',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],
        [
            'code' => '5017',
            'name' => 'Reserve Fund',
            'type' => 'Expenses',
            'sub_type' => 'Indirect Expenses',
        ],

    );

    // Add categories
    // Fetch all categories from Lazim DB
    public static $subcategories = array(
        [
            'name' => '3rd Party Requirement, Inspection And Certification',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Additional Services',
            'type' => 'expense',
            'chart_account_type' => 'Utilities (Taxable)'
        ],
        [
            'name' => 'Adjustments',
            'type' => 'expense',
            'chart_account_type' => 'Management Services (Taxable)'
        ],
        [
            'name' => 'Bank Charges',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Beaches',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Civil & Architectural',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Communication Charges / Postal Charges',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Community Events',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Community Improvement',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Community Management Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Concierge Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Dewa Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'District Cooling Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Fire Related Provisions',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Gas Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Government Entities Fees',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Health, Safety & Environment Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Hotel Operational Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Infrastructure Maintenance',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Insurance Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'IT SERVICES',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Landscaping Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Marine And Lakes Maintenance',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Master Community Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'MEP Maintenance Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Mosque Management',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Pest Control Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Professional Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Recreation & Community Facilities',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'RECREATION AND COMMUNITY SERVICES',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Reserved Fund',
            'type' => 'expense',
            'chart_account_type' => 'Maintenance (Non Taxable)'
        ],
        [
            'name' => 'Revenue',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Road Maintenance',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Security Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Sewerage Charges',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Specialized System And Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'TELECOMMUNICATION',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Unexpected & Contingency Cost',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Waste Management Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ],
        [
            'name' => 'Water Treatment & Test Services',
            'type' => 'expense',
            'chart_account_type' => 'Services (Taxable)'
        ]
    );

    // Add services
    public static $services = array(
        [
            'name' => 'AC Duct Cleaning',
            'code' => 'A1.20',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Beach cleaning',
            'code' => 'A1.15',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Car Park Cleaning',
            'code' => 'A1.06',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning Breakwater',
            'code' => 'A1.09',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning Common Area',
            'code' => 'A1.01',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning Corridors And Staircase And Hallway',
            'code' => 'A1.02',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning Lakes And Waters',
            'code' => 'A1.10',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning Machinery And Equipment',
            'code' => 'A1.04',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning Parking Pathway',
            'code' => 'A1.17',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning Public Parking',
            'code' => 'A1.19',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning Roads',
            'code' => 'A1.16',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleaning signals and street lights',
            'code' => 'A1.14',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Cleanings public parks',
            'code' => 'A1.18',
            'subcategory' => 'Landscaping Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Consumable And Material (Like Bins Etc..)',
            'code' => 'A1.03',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Facade Cleaning',
            'code' => 'A1.05',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Garbage Chute Cleaning',
            'code' => 'A1.21',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Road Sweeping',
            'code' => 'A1.11',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Signage And Street Furniture',
            'code' => 'A1.12',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Special Cleaning Task',
            'code' => 'A1.07',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Sump Pit and Drain line Cleaning',
            'code' => 'A1.22',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Tunnel And Underpass Cleaning',
            'code' => 'A1.13',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Variable Allowance For Incidentals-Cleaning',
            'code' => 'A1.08',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Campaigns',
            'code' => 'A10.02',
            'subcategory' => 'Community Events',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Events',
            'code' => 'A10.01',
            'subcategory' => 'Community Events',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Miscellaneous Expense',
            'code' => 'A10.03',
            'subcategory' => 'Community Events',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'DCD Fees',
            'code' => 'A11.02',
            'subcategory' => 'Government Entities Fees',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'DPS Fees',
            'code' => 'A11.03',
            'subcategory' => 'Government Entities Fees',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'RERA Fees',
            'code' => 'A11.01',
            'subcategory' => 'Government Entities Fees',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Health Club Facilities Services',
            'code' => 'A13.01',
            'subcategory' => 'RECREATION AND COMMUNITY SERVICES',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Communication For Security',
            'code' => 'A2.06',
            'subcategory' => 'Security Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Equipment/Machinery and Vehicles',
            'code' => 'A2.05',
            'subcategory' => 'Security Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Lifeguard',
            'code' => 'A2.02',
            'subcategory' => 'Security Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Marine Guards',
            'code' => 'A2.04',
            'subcategory' => 'Security Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Security Command and Control Center',
            'code' => 'A2.08',
            'subcategory' => 'Security Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Security Patrol',
            'code' => 'A2.03',
            'subcategory' => 'Security Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Security Services',
            'code' => 'A2.01',
            'subcategory' => 'Security Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Variable Allowance for Incidentals',
            'code' => 'A2.07',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Concierge Services',
            'code' => 'A3.01',
            'subcategory' => 'Hotel Operational Services',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Late Payment Fees',
            'code' => 'JK1.12',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Legal Fees-Rev',
            'code' => 'JK1.15',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Master Community Charges',
            'code' => 'JK1.02',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Miscellaneous Income',
            'code' => 'JK1.13',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Other Income',
            'code' => 'JK1.16',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Rental Of Common Area',
            'code' => 'JK1.06',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Revenue From Business Center',
            'code' => 'JK1.03',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Revenue From Gym',
            'code' => 'JK1.04',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Shared Services Income',
            'code' => 'JK1.01',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'The Club Revenue',
            'code' => 'JK1.05',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)'
        ],
        [
            'name' => 'Surplus / Deficit Derived From Service Charges (Gf) For The Previous Year',
            'code' => 'JL1.01',
            'subcategory' => 'Adjustments',
            'chart_account' => 'Management Services (Taxable)'
        ],
        [
            'name' => 'Surplus Derived From Other Income From The Previous Year',
            'code' => 'JL1.02',
            'subcategory' => 'Adjustments',
            'chart_account' => 'Management Services (Taxable)'
        ],
        [
            'name' => 'Meter Installation',
            'code' => 'JN1.03',
            'subcategory' => 'Additional Services',
            'chart_account' => 'Utilities (Taxable)'
        ],
        [
            'name' => 'Parking',
            'code' => 'JN1.04',
            'subcategory' => 'Additional Services',
            'chart_account' => 'Utilities (Taxable)'
        ],
        [
            'name' => 'Unit A/C (Charges)',
            'code' => 'JN1.01',
            'subcategory' => 'Additional Services',
            'chart_account' => 'Utilities (Taxable)'
        ],
        [
            'name' => 'Unit A/C (Sq.Ft)',
            'code' => 'JN1.02',
            'subcategory' => 'Additional Services',
            'chart_account' => 'Utilities (Taxable)'
        ],
        [
            'name' => 'General Pest Control Service',
            'subcategory' => 'Pest Control Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A4.01'
        ],
        [
            'name' => 'Garden or excess waste collection',
            'subcategory' => 'Waste Management Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A5.03'
        ],
        [
            'name' => 'Waste Recycling Services',
            'subcategory' => 'Waste Management Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A5.05'
        ],
        [
            'name' => 'Waste collection and bin removal',
            'subcategory' => 'Waste Management Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A5.01'
        ],
        [
            'name' => 'Bank Charges',
            'subcategory' => 'Bank Charges',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A7.03'
        ],
        [
            'name' => 'BTU Meter Reading',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.12'
        ],
        [
            'name' => 'Budget Review Fee',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.10'
        ],
        [
            'name' => 'Building Condition Survey',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.06'
        ],
        [
            'name' => 'Consultancy Fees',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.02'
        ],
        [
            'name' => 'Financial Audit fees',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.01'
        ],

        [
            'name' => 'Health And Safety Reports',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.07'
        ],
        [
            'name' => 'Insurance Valuation',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.08'
        ],
        [
            'name' => 'Legal Fees',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.03'
        ],
        [
            'name' => 'Property Inspection',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.05'
        ],
        [
            'name' => 'Quality Assurance Compliance',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.09'
        ],
        [
            'name' => 'Reserve Fund Study',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.11'
        ],
        [
            'name' => 'Service Charge Modelling',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.13'
        ],
        [
            'name' => 'Survey Fees',
            'subcategory' => 'Professional Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A8.04'
        ],
        [
            'name' => 'Courier and Postage',
            'subcategory' => 'Communication Charges / Postal Charges',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A9.01'
        ],
        [
            'name' => 'Notices/Circulars Etc.',
            'subcategory' => 'Communication Charges / Postal Charges',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A9.02'
        ],
        [
            'name' => 'Website And Other Communication Tools',
            'subcategory' => 'Communication Charges / Postal Charges',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A9.03'
        ],
        [
            'name' => 'Co Detection System',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.07'
        ],
        [
            'name' => 'Cradle Maintenance',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.10'
        ],
        [
            'name' => 'Domestic Water System',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.08'
        ],
        [
            'name' => 'Hvac System',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.04'
        ],
        [
            'name' => 'Intelligent Lighting Control System',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.01'
        ],
        [
            'name' => 'LED Maintenance',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.12'
        ],
        [
            'name' => 'MEP Services',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.13'
        ],
        [
            'name' => 'Pumps And Motors',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.03'
        ],
        [
            'name' => 'Sliding/Revolving And Exit Doors',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.06'
        ],
        [
            'name' => 'Spare Parts, Consumables And Material',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.09'
        ],
        [
            'name' => 'VFD',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.02'
        ],
        [
            'name' => 'Ventilation And Extract System',
            'subcategory' => 'MEP Maintenance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B1.05'
        ],
        [
            'name' => 'Environmental Management',
            'subcategory' => 'Health, Safety & Environment Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B10.02'
        ],
        [
            'name' => 'Fire Drill',
            'subcategory' => 'Health, Safety & Environment Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B10.05'
        ],
        [
            'name' => 'First Aid Supplies',
            'subcategory' => 'Health, Safety & Environment Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B10.01'
        ],
        [
            'name' => 'Public Health',
            'subcategory' => 'Health, Safety & Environment Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B10.04'
        ],
        [
            'name' => 'Recycling Programme',
            'subcategory' => 'Health, Safety & Environment Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B10.03'
        ],
        [
            'name' => 'Hardware/Software License Fees',
            'subcategory' => 'IT SERVICES',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B11.01'
        ],

        [
            'name' => 'Hardware/Software Maintenance Contracts',
            'subcategory' => 'IT SERVICES',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B11.02'
        ],
        [
            'name' => 'Amenities Maintenance Services',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.15'
        ],
        [
            'name' => 'Basketball Court Maintenance',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.06'
        ],
        [
            'name' => 'Business Center Other Operating Expense',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.11'
        ],
        [
            'name' => 'Children Play Area',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.13'
        ],
        [
            'name' => 'Community Recreational - Changing Room , Parks , etc',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.10'
        ],
        [
            'name' => 'Football Playground maintenance',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.08'
        ],
        [
            'name' => 'Gym Other Operating Expense',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.12'
        ],
        [
            'name' => 'Light Control System Maintenance',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.03'
        ],
        [
            'name' => 'Other Recreational Facilities (Channel And Internet Services Etc.)',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.16'
        ],
        [
            'name' => 'Public Facilities Civil Maintenance',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.02'
        ],
        [
            'name' => 'Public Parking Maintenance',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.01'
        ],
        [
            'name' => 'Sauna , Steam',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.09'
        ],
        [
            'name' => 'Subscription',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.17'
        ],
        [
            'name' => 'Swimming Pool Maintenance',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.05'
        ],
        [
            'name' => 'Swimming Pool Water Treatment And Testing',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.04'
        ],
        [
            'name' => 'Tennis Court Maintenance',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.07'
        ],
        [
            'name' => 'Tv And Av Systems',
            'subcategory' => 'Recreation & Community Facilities',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B12.14'
        ],
        [
            'name' => 'Minor Av Equipment Replacement And Repair',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.21'
        ],
        [
            'name' => 'Minor Equipment Replacement And Repair',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.02'
        ],
        [
            'name' => 'R And M Air-Conditioning',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.20'
        ],
        [
            'name' => 'R And M Bmu',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.16'
        ],
        [
            'name' => 'R And M Building (General)',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.04'
        ],
        [
            'name' => 'R And M Duct Cleaning',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.17'
        ],
        [
            'name' => 'R And M Electrical',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.03'
        ],
        [
            'name' => 'R And M Fire System',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.19'
        ],
        [
            'name' => 'R And M Floor/Wall Covering',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.07'
        ],
        [
            'name' => 'R And M Furniture',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.08'
        ],
        [
            'name' => 'R And M Kitchen Equipment',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.13'
        ],
        [
            'name' => 'R And M Light Bulbs Replacements',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.02'
        ],
        [
            'name' => 'R And M Mechanical Supplies',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.06'
        ],
        [
            'name' => 'R And M Painting And Decoration',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.09'
        ],
        [
            'name' => 'R And M Plumbing',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.12'
        ],
        [
            'name' => 'R And M Refrigeration',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.14'
        ],
        [
            'name' => 'R And M Signage',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.10'
        ],
        [
            'name' => 'R And M Spare Parts For Operation',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.15'
        ],
        [
            'name' => 'R And M Tank Cleaning',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.18'
        ],
        [
            'name' => 'R And M Tools',
            'subcategory' => 'Unexpected & Contingency Cost',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B13.05'
        ],
        [
            'name' => 'Bmu Certification',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.02'
        ],
        [
            'name' => 'Building Pipe Disinfection',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.09'
        ],
        [
            'name' => 'Calorifiers Testing',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.12'
        ],
        [
            'name' => 'DPS CCTV Certification',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.10'
        ],
        [
            'name' => 'Electrical Thermal Imaging',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.01'
        ],
        [
            'name' => 'Elevator Certificate',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.03'
        ],
        [
            'name' => 'Fire And Safety Certificate',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.04'
        ],
        [
            'name' => 'Gas System Certification',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.11'
        ],
        [
            'name' => 'Lightning Protection Test',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.05'
        ],
        [
            'name' => 'Pressure Vessel Test',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.06'
        ],
        [
            'name' => 'Water Tank Cleaning',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.07'
        ],
        [
            'name' => 'Water Testing',
            'subcategory' => '3rd Party Requirement, Inspection And Certification',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B14.08'
        ],
        [
            'name' => 'Crisis Management Expenses',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.11'
        ],
        [
            'name' => 'Infrastructure Repair and Maintenance Contract',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.03'
        ],
        [
            'name' => 'Lakes And Floating Fountain Maintenance Works',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.09'
        ],
        [
            'name' => 'Rain Planning Expenses',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.10'
        ],
        [
            'name' => 'Reactive Maintenance For Stp',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.02'
        ],
        [
            'name' => 'Sewage Treatment Plant Maintenance',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.01'
        ],
        [
            'name' => 'Tankering Services For Stps',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.05'
        ],
        [
            'name' => 'Underground Networks And Infrastructure',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.04'
        ],
        [
            'name' => 'Variable Allowance for Incidentals STP',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.06'
        ],
        [
            'name' => 'Variable Allowance for Incidentals/ Planned',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.07'
        ],
        [
            'name' => 'Variable Allowance for water feature and fountains Incidentals',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.12'
        ],
        [
            'name' => 'Water Feature Maintenance',
            'subcategory' => 'Infrastructure Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B2.08'
        ],
        [
            'name' => 'Bridges And Ramps Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.04'
        ],
        [
            'name' => 'Maintenance - Bridges And Ramps',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.01'
        ],
        [
            'name' => 'Maintenance - Storm Water Drainage',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.10'
        ],
        [
            'name' => 'Mass Transit Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.05'
        ],
        [
            'name' => 'Parking Areas Maintenances',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.06'
        ],
        [
            'name' => 'Pathways And Crossing Repairs And Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.17'
        ],
        [
            'name' => 'Railing On Main Street Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.14'
        ],
        [
            'name' => 'Revetments And Quay Walls',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'b3.07'
        ],
        [
            'name' => 'Road Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.02'
        ],
        [
            'name' => 'Road Marking And Signage Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.18'
        ],
        [
            'name' => 'Roads And Roundabout Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.08'
        ],
        [
            'name' => 'Roadway Pavement Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.12'
        ],
        [
            'name' => 'Rumps Repairs And Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.16'
        ],
        [
            'name' => 'Speed Humps Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.13'
        ],
        [
            'name' => 'Stock And Spare Parts',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.09'
        ],
        [
            'name' => 'Street Furniture Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.15'
        ],
        [
            'name' => 'Temporary Works And Plant Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.11'
        ],
        [
            'name' => 'Traffic Control Devices maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.03'
        ],
        [
            'name' => 'Tunnel Civil Maintenance',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.20'
        ],
        [
            'name' => 'Tunnels Mep Services',
            'subcategory' => 'Road Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B3.19'
        ],
        [
            'name' => 'Ad Hoc Maintenance',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.02'
        ],
        [
            'name' => 'Breakwater Maintenance',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.14'
        ],
        [
            'name' => 'Craft And Equipment',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.09'
        ],
        [
            'name' => 'Foreshore maintenance',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.04'
        ],
        [
            'name' => 'Interconnecting Pipe Maintenance',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.03'
        ],
        [
            'name' => 'Irrigation Lakes Maintenance',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.05'
        ],
        [
            'name' => 'Lakes And Floating Fountain Maintenance Works',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.13'
        ],
        [
            'name' => 'Lakes Maintenance',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.01'
        ],
        [
            'name' => 'Liner Maintenance',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.06'
        ],
        [
            'name' => 'Marine Maintenance',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.08'
        ],
        [
            'name' => 'Pollution Control',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.10'
        ],
        [
            'name' => 'Pumping Station Maintenance',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.07'
        ],
        [
            'name' => 'Seawater Monitoring',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.11'
        ],
        [
            'name' => 'Water Dredging',
            'subcategory' => 'Marine And Lakes Maintenance',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B4.12'
        ],
        [
            'name' => 'Beach Asset And Equipments Maintenance',
            'subcategory' => 'Beaches',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B5.04'
        ],
        [
            'name' => 'Beach Wall Maintenance',
            'subcategory' => 'Beaches',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B5.01'
        ],
        [
            'name' => 'Sand Replenishment',
            'subcategory' => 'Beaches',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B5.02'
        ],
        [
            'name' => 'Access Control System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.10'
        ],
        [
            'name' => 'Aircraft Warning Light',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.13'
        ],
        [
            'name' => 'BTU Meter Maintenance',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.26'
        ],
        [
            'name' => 'Bms System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.01'
        ],
        [
            'name' => 'Bmu System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.02'
        ],
        [
            'name' => 'CCTV System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.11'
        ],
        [
            'name' => 'Central Dish Maintenance',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.25'
        ],
        [
            'name' => 'Chiller Maintenance',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.15'
        ],
        [
            'name' => 'Communication For Bms System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.18'
        ],
        [
            'name' => 'Emergency Central Battery System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.08'
        ],
        [
            'name' => 'Emergency Lighting Maintenance',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.23'
        ],
        [
            'name' => 'Festive Lights Maintenance',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.22'
        ],
        [
            'name' => 'Fire Alarm System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.06'
        ],
        [
            'name' => 'Fire Fighting System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.04'
        ],
        [
            'name' => 'Fm 200',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.05'
        ],
        [
            'name' => 'Fuel Supply',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.16'
        ],
        [
            'name' => 'Garbage Chute System And Compactors',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.19'
        ],
        [
            'name' => 'Gas System Maintenance',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.24'
        ],
        [
            'name' => 'Gate Barrier System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.12'
        ],
        [
            'name' => 'Generator Service',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.07'
        ],
        [
            'name' => 'Intercom/Home Automation System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.09'
        ],
        [
            'name' => 'Lift, Elevator And Escalators',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.03'
        ],
        [
            'name' => 'Pa System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.14'
        ],
        [
            'name' => 'Seasonal Festive Lighting/ Area Activation',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.21'
        ],
        [
            'name' => 'Traffic Signal Maintenance',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B6.20'
        ],
        [
            'name' => 'Indoor Plants Maintenance',
            'subcategory' => 'Landscaping Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B8.01'
        ],
        [
            'name' => 'Irrigation Pump Station',
            'subcategory' => 'Landscaping Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B8.03'
        ],
        [
            'name' => 'Irrigation System Maintenance',
            'subcategory' => 'Landscaping Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B8.06'
        ],

        [
            'name' => 'Landscaping Improvements And Contingency',
            'subcategory' => 'Landscaping Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B8.08'
        ],
        [
            'name' => 'Landscaping Maintenance',
            'subcategory' => 'Landscaping Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B8.05'
        ],
        [
            'name' => 'Soft Landscaping',
            'subcategory' => 'Landscaping Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B8.02'
        ],
        [
            'name' => 'Water Feature',
            'subcategory' => 'Landscaping Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B8.04'
        ],
        [
            'name' => 'Chilled Water Treatment And Testing',
            'subcategory' => 'Water Treatment & Test Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B9.02'
        ],
        [
            'name' => 'Domestic Water Treatment And Testing',
            'subcategory' => 'Water Treatment & Test Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B9.01'
        ],
        [
            'name' => 'Lake Water Cleaning, Treatment And Testing',
            'subcategory' => 'Water Treatment & Test Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B9.04'
        ],
        [
            'name' => 'Water Feature Treatment And Testing',
            'subcategory' => 'Water Treatment & Test Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'B9.03'
        ],
        [
            'name' => 'Gymnasium Upgrade',
            'subcategory' => 'Community Improvement',
            'chart_account' => 'Services (Taxable)',
            'code' => 'C1.03'
        ],
        [
            'name' => 'Improvements related to health and safety',
            'subcategory' => 'Community Improvement',
            'chart_account' => 'Services (Taxable)',
            'code' => 'C1.08'
        ],
        [
            'name' => 'Improvements required by Statutory requirements (such as Dubai Municipality)',
            'subcategory' => 'Community Improvement',
            'chart_account' => 'Services (Taxable)',
            'code' => 'C1.07'
        ],
        [
            'name' => 'Other',
            'subcategory' => 'Community Improvement',
            'chart_account' => 'Services (Taxable)',
            'code' => 'C1.05'
        ],
        [
            'name' => 'Building Amenities',
            'subcategory' => 'Hotel Operational Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'D1.01'
        ],
        [
            'name' => 'Cleaning Services',
            'subcategory' => 'Hotel Operational Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'D1.03'
        ],
        [
            'name' => 'MEP Services',
            'subcategory' => 'Hotel Operational Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'D1.05'
        ],
        [
            'name' => 'Security Services',
            'subcategory' => 'Hotel Operational Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'D1.04'
        ],
        [
            'name' => 'Chiller Charges',
            'subcategory' => 'Dewa Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E1.02'
        ],
        [
            'name' => 'Electricity Charges',
            'subcategory' => 'Dewa Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E1.01'
        ],
        [
            'name' => 'Sewerage Charges',
            'subcategory' => 'Dewa Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E1.03'
        ],
        [
            'name' => 'Water Charges',
            'subcategory' => 'Dewa Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E1.04'
        ],
        [
            'name' => 'District Cooling Capacity Charges',
            'subcategory' => 'District Cooling Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E2.01'
        ],
        [
            'name' => 'District Cooling Consumption Charges',
            'subcategory' => 'District Cooling Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E2.02'
        ],
        [
            'name' => 'Gas Charges',
            'subcategory' => 'Gas Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E3.01'
        ],
        [
            'name' => 'Sewerage Charges',
            'subcategory' => 'Sewerage Charges',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E4.01'
        ],
        [
            'name' => 'Internet Services',
            'subcategory' => 'TELECOMMUNICATION',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E5.02'
        ],
        [
            'name' => 'Telephone Services',
            'subcategory' => 'TELECOMMUNICATION',
            'chart_account' => 'Services (Taxable)',
            'code' => 'E5.01'
        ],
        [
            'name' => 'Management Fees',
            'subcategory' => 'Community Management Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'F1.01'
        ],
        [
            'name' => 'Fidelity Guarantee',
            'subcategory' => 'Insurance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'G1.03'
        ],
        [
            'name' => 'Property All Risk',
            'subcategory' => 'Insurance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'G1.01'
        ],
        [
            'name' => 'Public Liability Risk',
            'subcategory' => 'Insurance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'G1.02'
        ],
        [
            'name' => 'Terrorism Insurance',
            'subcategory' => 'Insurance Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'G1.04'
        ],
        [
            'name' => 'Fire Related Provisions',
            'subcategory' => 'Fire Related Provisions',
            'chart_account' => 'Services (Taxable)',
            'code' => 'I1.01'
        ],
        [
            'name' => 'Waste Skip Collection',
            'subcategory' => 'Waste Management Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JA5.02'
        ],
        [
            'name' => 'CCTV System',
            'subcategory' => 'Specialized System And Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JB6.11'
        ],
        [
            'name' => 'Concierge Services',
            'subcategory' => 'Hotel Operational Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JD1.02'
        ],
        [
            'name' => 'Access(Proximity) Card/Transponders',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.07'
        ],
        [
            'name' => 'Bounced Cheque Fee',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.08'
        ],
        [
            'name' => 'Community Violation Fee',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.14'
        ],
        [
            'name' => 'Direct Marketing',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.09'
        ],
        [
            'name' => 'Insurance Reimbursement',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.10'
        ],
        [
            'name' => 'Interest On Call Accounts',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.11'
        ],
        [
            'name' => 'Late Payment Fees',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.12'
        ],
        [
            'name' => 'Legal Fees-Rev',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.15'
        ],
        [
            'name' => 'Master Community Charges',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.02'
        ],
        [
            'name' => 'Miscellaneous Income',
            'subcategory' => 'Revenue',
            'chart_account' => 'Services (Taxable)',
            'code' => 'JK1.13'
        ],

        [
            'name' => 'Printing',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'A9.04'
        ],
        [
            'name' => 'Master Community Charges',
            'subcategory' => 'Cleaning Services',
            'chart_account' => 'Services (Taxable)',
            'code' => 'H1.01'
        ],
        [
            'name' => 'Surplus / Deficit  Derived From Service Charges (Gf) For The Previous Year',
            'subcategory' => 'Adjustments',
            'chart_account' => 'Management Services (Taxable)',
            'code' => 'L1.01'
        ],
        [
            'name' => 'Reserved Fund',
            'subcategory' => 'Reserved Fund',
            'chart_account' => 'Maintenance (Taxable)',
            'code' => 'M1.01'
        ]
    );

    public static function updateService($user_id, $building_id)
    {
        $services = Self::$services;

        foreach ($services as $service) {
            $category = ProductServiceCategory::where(['name' => $service['subcategory'], 'building_id' => $building_id])->first()->id;
            $chartOfAccountId = ChartOfAccount::where(['name' => $service['chart_account'], 'building_id' => $building_id])
                ->first()->id;
            ProductService::create(
                [
                    'name' => $service['name'],
                    'sku' => substr($service['name'], 0, 2) . rand(100, 999),
                    'sale_price' => 0,
                    'purchase_price' => 0,
                    'quantity' => 0,
                    'tax_id' => Tax::where(['name' => 'VAT', 'building_id' => $building_id])->first()->id,
                    'category_id' => $category,
                    'unit_id' => ProductServiceUnit::where(['name' => 'Expense', 'building_id' => $building_id])->first()->id,
                    'type' => 'Service',
                    'expense_chartaccount_id' => $chartOfAccountId,
                    'created_by' => $user_id,
                    'building_id' => $building_id,
                    'service_code' => $service['code']
                ]
            );
        }
    }

    public static function updateCategory($user_id, $building_id)
    {
        $subcategories = Self::$subcategories;
        Log::info("Utility updateCategory 4892 subcategories ---------".json_encode($subcategories));
        foreach ($subcategories as $subcategory) {
            $category = ChartOfAccount::where(['name' => $subcategory['chart_account_type'], 'building_id' => $building_id])->first()->id;
            Log::info("Utility updateCategory 4895 category ---------". json_encode($category));
            $productServiceCategory = ProductServiceCategory::create(
                [
                    'name' => $subcategory['name'],
                    'type' => $subcategory['type'],
                    'chart_account_id' => $category,
                    'created_by' => $user_id,
                    'building_id' => $building_id
                ]
            );
            Log::info("Utility updateCategory 4905 productServiceCategory ---------".json_encode($productServiceCategory));
        }
    }


    // chart of account for new company
    public static function chartOfAccountData1($user, $building_id = null)
    {
        $chartOfAccounts = Self::$chartOfAccount1;
        Log::info("Utility chartOfAccountData1 4912 chartOfAccounts ---------".json_encode($chartOfAccounts));

        foreach ($chartOfAccounts as $account) {

            $type = ChartOfAccountType::where('created_by', $user)->where('name', $account['type'])
                ->where('building_id', $building_id)->first();
            $sub_type = ChartOfAccountSubType::where('type', $type->id)->where('name', $account['sub_type'])
                ->where('building_id', $building_id)->first();

            $account['name'] = str_replace('{year}', date("Y"), $account['name']);
            Log::info("Utility chartOfAccountData1 4922----------account ----------------------".json_encode($account));
            Log::info("Utility chartOfAccountData1 4923----------type ----------------------".json_encode($type));
            Log::info("Utility chartOfAccountData1 4924----------sub_type ----------------------".json_encode($sub_type));

           $chartOfAccount = ChartOfAccount::create(
                                [
                                    'code' => $account['code'],
                                    'name' => $account['name'],
                                    'type' => $type->id,
                                    'sub_type' => $sub_type->id,
                                    'is_enabled' => 1,
                                    'created_by' => $user,
                                    'building_id' => $building_id
                                ]
                            );
            Log::info("Utility chartOfAccountData1 4937----------chartOfAccount ----------------------".json_encode($chartOfAccount));
        }
    }

    public static function chartOfAccountData($user)
    {
        $chartOfAccounts = Self::$chartOfAccount;
        foreach ($chartOfAccounts as $account) {
            $account['name'] = str_replace('{year}', date("Y"), $account['name']);
            ChartOfAccount::create(
                [
                    'code' => $account['code'],
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'sub_type' => $account['sub_type'],
                    'is_enabled' => 1,
                    'created_by' => $user->id,
                ]
            );
        }
    }

    public static function check_file($path)
    {
        if (!empty($path)) {

            $settings = Utility::settings();
            if ($settings['storage_setting'] == 'local' || $settings['storage_setting'] == null) {

                return Storage::disk($settings['storage_setting'])->exists($path);
            } else {

                if ($settings['storage_setting'] == 's3') {
                    config(
                        [
                            'filesystems.disks.s3.key' => $settings['s3_key'],
                            'filesystems.disks.s3.secret' => $settings['s3_secret'],
                            'filesystems.disks.s3.region' => $settings['s3_region'],
                            'filesystems.disks.s3.bucket' => $settings['s3_bucket'],
                            'filesystems.disks.s3.url' => $settings['s3_url'],
                            'filesystems.disks.s3.endpoint' => $settings['s3_endpoint'],
                        ]
                    );
                } else if ($settings['storage_setting'] == 'wasabi') {
                    config(
                        [
                            'filesystems.disks.wasabi.key' => $settings['wasabi_key'],
                            'filesystems.disks.wasabi.secret' => $settings['wasabi_secret'],
                            'filesystems.disks.wasabi.region' => $settings['wasabi_region'],
                            'filesystems.disks.wasabi.bucket' => $settings['wasabi_bucket'],
                            'filesystems.disks.wasabi.root' => $settings['wasabi_root'],
                            'filesystems.disks.wasabi.endpoint' => $settings['wasabi_url'],
                            'filesystems.disks.wasabi.use_path_style_endpoint' => false
                        ]
                    );
                }

                try {
                    return Storage::disk($settings['storage_setting'])->exists($path);
                } catch (\Exception $e) {
                    return 0;
                }
            }
        } else {
            return 0;
        }
    }

    public static function getCurrencySymbol($key)
    {
        $data = DB::table('admin_payment_settings');

        if (Auth::check()) {
            $data->where('name', $key)->where('created_by', '=', Auth::user()->creatorId());
        } else {
            $data->where('name', $key)->where('created_by', '=', 1);
        }
        return $data->pluck('value')->first();
    }

    public static function generateReferralCode()
    {
        do {
            $referralCode = rand(100000, 999999);
        } while (User::where('referral_code', $referralCode)->exists());

        return $referralCode;
    }

    public static function referralTransaction($plan, $company = '')
    {
        if ($company != '') {
            $objUser = $company;
        } else {
            $objUser = \Auth::user();
        }

        $user = ReferralTransaction::where('company_id', $objUser->id)->first();

        $referralSetting = ReferralSetting::where('created_by', 1)->first();

        if ($objUser->used_referral_code != 0 && $user == null && (isset($referralSetting) && $referralSetting->is_enable == 1)) {
            $transaction         = new ReferralTransaction();
            $transaction->company_id    = $objUser->id;
            $transaction->plan_id       = $plan->id;
            $transaction->plan_price    = $plan->price;
            $transaction->commission    = $referralSetting->percentage;
            $transaction->referral_code = $objUser->used_referral_code;
            $transaction->save();
        }
    }

    public static function generateUniqueEmail($number, $baseEmail = 'user@lazim.ae')
    {
        // Hash or encode the number to make it unrecognizable
        $encodedNumber = base64_encode($number);

        // Remove any padding or special characters from the base64 encoding
        $encodedNumber = str_replace(['+', '/', '='], '', $encodedNumber);

        // Optionally, you can take a substring to shorten the encoded number
        $encodedNumber = Str::limit($encodedNumber, 8, '');

        // Insert the encoded number into the base email
        $uniqueEmail = str_replace('user', 'user' . $encodedNumber, $baseEmail);

        return $uniqueEmail;
    }

    public static function getVenderTransactionLines($account_id = null, $start_date = null, $end_date = null)
    {

        if (!empty($start_date) && !empty($end_date)) {
            $start = $start_date;
            $end = $end_date;
        } else {
            $start = date('Y-m-01');
            $end = date('Y-m-t');
        }
        // dd(\Auth::user()->creatorId());

        $transactionData = DB::table('transaction_lines')
            ->where('transaction_lines.created_by', \Auth::user()->creatorId())
            ->when(!empty($account_id), function ($query) use ($account_id) {
                return $query->where('transaction_lines.account_id', $account_id);
            })
            ->whereBetween('transaction_lines.date', [$start, $end])
            ->leftJoin('bills', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'bills.id')
                    ->whereIn('transaction_lines.reference', ['Bill', 'Bill Payment', 'Bill Account', 'Expense', 'Expense Account', 'Expense Payment']);
            })
            ->leftJoin('payments', function ($join) {
                $join->on('transaction_lines.reference_id', '=', 'payments.id')
                    ->whereIn('transaction_lines.reference', ['Payment']);
            })
            ->leftJoin('venders as payments_venders', 'payments.vender_id', '=', 'payments_venders.id')
            ->leftJoin('venders', 'bills.vender_id', '=', 'venders.id')
            ->leftJoin('chart_of_accounts', 'transaction_lines.account_id', '=', 'chart_of_accounts.id')
            ->where(function ($query) {
                $query->whereNotNull('bills.id')
                    ->orWhereNotNull('payments.id');
            })
            ->select(
                'transaction_lines.*',
                'bills.vender_id as vendor_id',
                'chart_of_accounts.name as account_name',
                DB::raw("COALESCE(venders.name , payments_venders.name) as user_name"),
                DB::raw("COALESCE(bills.bill_id) as ids"),
            )
            ->get();

        return $transactionData;
    }

    public static function updateUserTransactionLine($users, $id, $amount, $type, $reference, $referenceId, $date = 'Y-m-d H:i:s', $reference_sub_id = null)
    {
        $existingTransaction = StakeholderTransactionLine::where('reference_id',  $referenceId)->where('reference', $reference)
            ->when($reference_sub_id, function ($q) use ($reference_sub_id) {
                $q->where('reference_sub_id', $reference_sub_id);
            })
            ->first();

        if ($users == 'vendor') {
            $userColumn = 'vender_id';
            $userModel = Vender::class;
        } else {
            $userColumn = 'customer_id';
            $userModel = Customer::class;
        }

        if ($existingTransaction) {
            $olderUserId = ($id == $existingTransaction->$userColumn) ? null : $existingTransaction->$userColumn;

            $transactionLines = $existingTransaction;
            $transactionLines->updated_by = \Auth::user()->creatorId();
        } else {
            $transactionLines = new StakeholderTransactionLine();
            $transactionLines->created_by = \Auth::user()->creatorId();
            $transactionLines->building_id = \Auth::user()->currentBuilding();
        }


        $previousTransaction = StakeholderTransactionLine::where($userColumn, $id)
            ->when($transactionLines->id, function ($q) use ($transactionLines) {
                $q->where('id', '<', $transactionLines->id);
            })
            ->orderBy('id', 'desc')
            ->first();

        $transactionLines->opening_balance = $previousTransaction ? $previousTransaction->closing_balance : $userModel::find($id)->initial_balance;

        if ($type == "credit") {
            $transactionLines->credit = $amount;
            $transactionLines->debit = 0;
            $transactionLines->closing_balance = self::getClosingBalance($userModel::ACCOUNT_TYPE, $type, $amount, $transactionLines->opening_balance);
        } else {
            $transactionLines->credit = 0;
            $transactionLines->debit = $amount;
            $transactionLines->closing_balance = self::getClosingBalance($userModel::ACCOUNT_TYPE, $type, $amount, $transactionLines->opening_balance);
        }

        $data = [
            'reference' => $reference,
            'reference_id' => $referenceId,
            'reference_sub_id' => $reference_sub_id,
            $userColumn => $id,
            'date' => $date
        ];

        $transactionLines->fill($data);
        $transactionLines->save();

        StakeholderTransactionLine::recalculateStakeholderBalances($userColumn, $id, $transactionLines->created_at);
        if (!empty($olderUserId)) {
            StakeholderTransactionLine::recalculateStakeholderBalances($userColumn, $olderUserId, $transactionLines->created_at);
        }
    }

    public static function getClosingBalance($category, $type, $amount, $openingBalance)
    {
        $isCredit = strtolower($type) == 'credit';

        if (in_array(strtolower($category), ChartOfAccountType::DEBIT_ACCOUNT_TYPE)) {
            return $isCredit ? $openingBalance - $amount : $openingBalance + $amount;
        } else {
            return $isCredit ? $openingBalance + $amount : $openingBalance - $amount;
        }
    }
}
