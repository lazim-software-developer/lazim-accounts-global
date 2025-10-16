<?php

namespace App\Http\Controllers;

use Auth;
use File;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Revenue;
use App\Models\Utility;
use App\Models\Customer;
use App\Models\BankAccount;
use App\Models\CustomField;
use App\Models\Transaction;
use App\Models\CustomerFlat;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\InvoicePayment;
use App\Models\InvoiceProduct;
use App\Models\InvoiceRevenue;
use App\Models\ProductService;
use App\Exports\CustomerExport;
use App\Imports\CustomerImport;
use App\Models\Mail\UserCreate;
use Illuminate\Validation\Rule;
use App\Models\TransactionLines;
use App\Models\ChartOfAccountType;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use App\Jobs\AddTransactionLinesJob;
use App\Models\ChartOfAccountParent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ChartOfAccountSubType;
use App\Models\RevenueBankAllocation;
use App\Models\RevenueCustomerDetail;
use Illuminate\Support\Facades\Crypt;
use App\Models\ProductServiceCategory;
use Illuminate\Support\Facades\Storage;
use App\Models\StakeholderTransactionLine;

class CustomerController extends Controller
{

    public function dashboard()
    {
        $data['invoiceChartData'] = \Auth::user()->invoiceChartData();

        return view('customer.dashboard', $data);
    }

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage customer')) {
            // $customerids = DB::table('customer_flat')->where('building_id',\Auth::user()->currentBuilding())->distinct()->pluck('customer_id');
            $customers = Customer::where('created_by', \Auth::user()->creatorId())->where(function ($query) {
                $query->whereNotIn('type', ['Tenant'])
                    ->orWhereNull('type');
            });

            if ($request->has('payment_status') && $request->payment_status != 'all') {
                $customerIds = [];

                foreach ($customers->get() as $customer) {

                    $due = $customer->customerTotaldue($customer->id);
                    $overDue = $customer->customerOverdue($customer->id);

                    if ($request->payment_status == 'due' && $due > 0) {
                        $customerIds[] = $customer->id;
                    } elseif ($request->payment_status == 'overdue' && $overDue > 0) {
                        $customerIds[] = $customer->id;
                    } elseif ($request->payment_status == 'paid' && $due <= 0 && $overDue <= 0) {
                        $customerIds[] = $customer->id;
                    }
                }

                $customers = $customers->whereIn('id', $customerIds);
            }
            $customers = $customers->get();

            return view('customer.index', compact('customers'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create customer')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

            return view('customer.create', compact('customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function store(Request $request)
    {

        if (\Auth::user()?->can('create customer') || $request->created_by_lazim) {


            $rules = [
                'name' => 'required',
                'contact' => 'required',
                'email' => 'required',
                // Rule::unique('customers')->where(function ($query) use($request){
                //     return $query->where('created_by', $request->created_by ?? \Auth::user()->creatorId());
                // }),

            ];


            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('customer.index')->with('error', $messages->first());
            }

            $enableLogin       = 0;
            if (!empty($request->password_switch) && $request->password_switch == 'on') {
                $enableLogin   = 1;
                $validator = \Validator::make(
                    $request->all(),
                    ['password' => 'required|min:6']
                );

                if ($validator->fails()) {
                    return redirect()->back()->with('error', $validator->errors()->first());
                }
            }
            $userpassword               = $request->input('password');

            $objCustomer    = $request->created_by ? User::find($request->created_by) : \Auth::user();
            $creator        = User::find($request->created_by ?? $objCustomer->creatorId());
            $total_customer = $objCustomer->countCustomers();
            $plan           = Plan::find($creator->plan);

            $default_language = DB::table('settings')->select('value')->where('name', 'company_default_language')->first();
            // if ($total_customer < $plan->max_customers || $plan->max_customers == -1) {
            $customer                  = new Customer();
            $customer->customer_id     = $request->customer_id ??  $this->customerNumber();
            $customer->name            = $request->name;
            $customer->contact         = $request->contact;
            $customer->email           = $request->email;
            $customer->tax_number      = $request->tax_number;
            $customer->type            = $request->type;

            $request['password'] = !empty($userpassword) ? \Hash::make($userpassword) : null;

            $customer->created_by      = $request->created_by ?? \Auth::user()?->creatorId();
            $customer->billing_name    = $request->billing_name;
            $customer->billing_country = $request->billing_country;
            $customer->billing_state   = $request->billing_state;
            $customer->billing_city    = $request->billing_city;
            $customer->billing_phone   = $request->billing_phone;
            $customer->billing_zip     = $request->billing_zip;
            $customer->billing_address = $request->billing_address;
            if (!empty($request['password'])) {
                $customer->password        = $request['password'] ?? null;
            }
            $customer->shipping_name    = $request->shipping_name;
            $customer->shipping_country = $request->shipping_country;
            $customer->shipping_state   = $request->shipping_state;
            $customer->shipping_city    = $request->shipping_city;
            $customer->shipping_phone   = $request->shipping_phone;
            $customer->shipping_zip     = $request->shipping_zip;
            $customer->shipping_address = $request->shipping_address;
            $customer->created_by_lazim = $request->created_by_lazim ?? false;
            $customer->flat_id          = $request->flat_id;
            $customer->building_id      = $request->building_id ?? \Auth::user()?->currentBuilding();

            $customer->is_enable_login =  $enableLogin;

            $customer->lang = !empty($default_language) ? $default_language->value : 'en';
            $customer->save();
            CustomField::saveData($customer, $request->customField);

            $types = ChartOfAccountType::where('building_id', \Auth::user()->currentBuilding())->where('name', 'Assets')->first();
                if (! $types) {
                    $types = ChartOfAccountType::updateOrCreate([
                        'name' => 'Assets',
                        'building_id' => \Auth::user()->currentBuilding(),
                        'created_by' => \Auth::user()->creatorId(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }

            $accountTypes = ChartOfAccountSubType::where('type', $types->id)->where('building_id', \Auth::user()->currentBuilding())->first();
            if (! $accountTypes) {
                $accountTypes = ChartOfAccountSubType::updateOrCreate([
                    'name' => 'Current Asset',
                    'type' => $types->id,
                    'building_id' => \Auth::user()->currentBuilding(),
                    'created_by' => \Auth::user()->creatorId(),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
            $AccountDetail = ChartOfAccount::where('building_id', \Auth::user()->currentBuilding())->where('code', 1206)->first();
                if (! $AccountDetail) {
                    $AccountDetail = ChartOfAccount::updateOrCreate([
                        'name' => 'Sundry Debtors',
                        'code' => 1206,
                        'type' => $types->id,
                        'sub_type' => $accountTypes->id,
                        'parent' => 0,
                        'description' => 'Sundry Debtors',
                        'is_enabled' => 1,
                        'building_id' => \Auth::user()->currentBuilding(),
                        'created_by' => \Auth::user()->creatorId(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'initial_balance' => 0,
                    ]);
                }
                $parentAccount = ChartOfAccountParent::where('account', $AccountDetail->id)->first();
                if (! $parentAccount) {
                    $parentAccount = ChartOfAccountParent::updateOrCreate([
                        'name' => $AccountDetail->name,
                        'sub_type' => $accountTypes->id,
                        'type' => $types->id,
                        'account' => $AccountDetail->id,
                        'created_by' => \Auth::user()->creatorId(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $parentAccountChildCount = ChartOfAccount::where('parent', $parentAccount->id)
                            ->where('building_id', $customer->building_id)
                            ->count();
                        $accountCode = intval($AccountDetail->code . ($parentAccountChildCount + 1));
                        $chartOfAccount = ChartOfAccount::updateOrCreate(
                            [
                                'building_id' => $customer->building_id,
                                'code' => $accountCode,
                                'name' => $customer->name,
                            ],
                            [
                                'type' => $types->id,
                                'sub_type' => $accountTypes->id,
                                'parent' => $parentAccount->id,
                                'description' => 'Sundry Debtors',
                                'is_enabled' => 1,
                                'building_id' => $customer->building_id,
                                'created_by' => \Auth::user()->creatorId(),
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'initial_balance' => 0,
                            ]
                        );

            // } else {
            //     return redirect()->back()->with('error', __('Your user limit is over, Please upgrade plan.'));
            // }


            $role_r = Role::where('name', '=', 'customer')->firstOrFail();
            $customer->assignRole($role_r);

            $uArr = [
                'email' => $customer->email,
                'password' => $request->password,
            ];

            try {
                $resp = Utility::sendEmailTemplate('user_created', [$customer->id => $customer->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }


            //Twilio Notification
            $setting  = Utility::settings($request->created_by ?? \Auth::user()->creatorId());
            if (isset($setting['customer_notification']) && $setting['customer_notification'] == 1) {
                $uArr = [
                    'customer_name' => $request->name,
                    'email'  => $request->email,
                    'password'  =>  $request->password,
                ];
                Utility::send_twilio_msg($request->contact, 'new_customer', $uArr);
            }

            // webhook
            $module = 'New Customer';
            $webhook =  Utility::webhookSetting($module, $creator->id);

            if ($webhook) {
                $parameter = json_encode($customer);
                // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);


                if ($status == true) {
                    return redirect()->route('customer.index')->with('success', __('Customer successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }
            if ($request->created_by_lazim) {
                return '';
            }
            return redirect()->route('customer.index')->with('success', __('Owner successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function show($ids)
    {

        $id       = \Crypt::decrypt($ids);
        $customer = Customer::find($id);
        return view('customer.show', compact('customer'));
    }


    public function edit($id)
    {
        if (\Auth::user()->can('edit customer')) {
            $customer              = Customer::find($id);
            $customer->customField = CustomField::getData($customer, 'customer');

            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

            return view('customer.edit', compact('customer', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, Customer $customer)
    {

        if (\Auth::user()->can('edit customer')) {

            $rules = [
                'name' => 'required',
                // 'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
                'email' => 'required|email|unique:customers,email,' . $customer->id,
            ];


            $validator = \Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('customer.index')->with('error', $messages->first());
            }

            $AccountDetail = ChartOfAccount::where('building_id', \Auth::user()->currentBuilding())->where('code', 1206)->first();
            $parentAccount = ChartOfAccountParent::where('account', $AccountDetail->id)->first();
            $parentAccountChildCount = ChartOfAccount::where('parent', $parentAccount->id)
                            ->where('building_id', $customer->building_id)
                            ->count();
            $accountCode = intval($AccountDetail->code . ($parentAccountChildCount));
            $chartOfAccount = ChartOfAccount::where('building_id', \Auth::user()->currentBuilding())->where('created_by', \Auth::user()->creatorId())->where('code', $accountCode)->where('name', $customer->name)->first();
            if ($chartOfAccount) {
                $chartOfAccount->name = $request->name;
                $chartOfAccount->save();
            }

            $customer->name             = $request->name;
            $customer->contact          = $request->contact;
            $customer->email            = $request->email;
            $customer->tax_number       = $request->tax_number;
            $customer->created_by       = \Auth::user()->creatorId();
            $customer->billing_name     = $request->billing_name;
            $customer->billing_country  = $request->billing_country;
            $customer->billing_state    = $request->billing_state;
            $customer->billing_city     = $request->billing_city;
            $customer->billing_phone    = $request->billing_phone;
            $customer->billing_zip      = $request->billing_zip;
            $customer->billing_address  = $request->billing_address;
            $customer->shipping_name    = $request->shipping_name;
            $customer->shipping_country = $request->shipping_country;
            $customer->shipping_state   = $request->shipping_state;
            $customer->shipping_city    = $request->shipping_city;
            $customer->shipping_phone   = $request->shipping_phone;
            $customer->shipping_zip     = $request->shipping_zip;
            $customer->shipping_address = $request->shipping_address;
            $customer->save();

            CustomField::saveData($customer, $request->customField);

            return redirect()->route('customer.index')->with('success', __('Owner successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Customer $customer)
    {
        if (\Auth::user()->can('delete customer')) {
            if ($customer->created_by == \Auth::user()->creatorId()) {
                $customer->delete();

                return redirect()->route('customer.index')->with('success', __('Customer successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function customerNumber()
    {
        $latest = Customer::where('created_by', '=', \Auth::user()->creatorId())->orderByDesc('customer_id')->first();
        if (!$latest) {
            return 1;
        }

        return $latest->customer_id + 1;
    }

    public function customerLogout(Request $request)
    {
        \Auth::guard('customer')->logout();

        $request->session()->invalidate();

        return redirect()->route('customer.login');
    }

    public function payment(Request $request)
    {

        if (\Auth::user()->can('manage customer payment')) {
            // $category = [
            //     'Invoice' => 'Invoice',
            //     'Deposit' => 'Deposit',
            //     'Sales' => 'Sales',
            // ];

            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 2)->get()->pluck('name', 'id');
            // $category->prepend('Bill', '');

            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Customer')->where('type', 'Payment');
            if (isset($request->date) && !empty($request->date)) {
                $time = strtotime($request->date);
                $month = date("m", $time);

                $query = $query->whereMonth('date', $month);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $payments = $query->get();

            return view('customer.payment', compact('payments', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function transaction(Request $request)
    {
        if (\Auth::user()->can('manage customer payment')) {
            $category = [
                'Invoice' => 'Invoice',
                'Retainer' => 'Retainer',
                // 'Sales' => 'Sales',
            ];

            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Customer');

            if (isset($request->date) && !empty($request->date)) {
                $time = strtotime($request->date);
                $month = date("m", $time);

                $query = $query->whereMonth('date', $month);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $transactions = $query->get();

            return view('customer.transaction', compact('transactions', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function profile()
    {
        $userDetail              = \Auth::user();
        $userDetail->customField = CustomField::getData($userDetail, 'customer');
        $customFields            = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'customer')->get();

        return view('customer.profile', compact('userDetail', 'customFields'));
    }

    public function editprofile(Request $request)
    {
        $userDetail = \Auth::user();
        $user       = Customer::findOrFail($userDetail['id']);

        $this->validate(
            $request,
            [
                'name' => 'required|max:120',
                // 'contact' => 'required',
                'email' => 'required|email|unique:users,email,' . $userDetail['id'],
            ]
        );

        if ($request->hasFile('profile')) {
            if (\Auth::guard('customer')->check()) {
                $file_path = $user['avatar'];
                $filenameWithExt = $request->file('profile')->getClientOriginalName();
                $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension       = $request->file('profile')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                $settings = Utility::getStorageSetting();

                if ($settings['storage_setting'] == 'local') {
                    $dir        = 'uploads/avatar/';
                } else {
                    $dir        = 'uploads/avatar';
                }
                $image_path = $dir . $userDetail['avatar'];

                $url = '';
                // $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);
                // dd($path);
                $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);
                // dd($path);
                if ($path['flag'] == 1) {
                    $url = $path['url'];
                } else {
                    return redirect()->route('profile', \Auth::user()->id)->with('error', __($path['msg']));
                }
            } else {
                $file_path = $user['avatar'];
                $image_size = $request->file('profile')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                if ($result == 1) {

                    Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                    $filenameWithExt = $request->file('profile')->getClientOriginalName();
                    $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension       = $request->file('profile')->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    $settings = Utility::getStorageSetting();

                    if ($settings['storage_setting'] == 'local') {
                        $dir        = 'uploads/avatar/';
                    } else {
                        $dir        = 'uploads/avatar';
                    }
                    $image_path = $dir . $userDetail['avatar'];

                    $url = '';
                    // $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);
                    // dd($path);
                    $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);
                    // dd($path);
                    if ($path['flag'] == 1) {
                        $url = $path['url'];
                    } else {
                        return redirect()->route('profile', \Auth::user()->id)->with('error', __($path['msg']));
                    }
                } else {
                    return redirect()->back()->with('error', $result);
                }
            }
        }

        if (!empty($request->profile)) {
            $user['avatar'] = $fileNameToStore;
        }
        $user['name']    = $request['name'];
        $user['email']   = $request['email'];
        $user['contact'] = $request['contact'];
        $user->save();
        CustomField::saveData($user, $request->customField);

        return redirect()->back()->with(
            'success',
            __('Profile successfully updated.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '')
        );
    }

    public function editBilling(Request $request)
    {
        $userDetail = \Auth::user();
        $user       = Customer::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'billing_name' => 'required',
                'billing_country' => 'required',
                'billing_state' => 'required',
                'billing_city' => 'required',
                'billing_phone' => 'required',
                'billing_zip' => 'required',
                'billing_address' => 'required',
            ]
        );
        $input = $request->all();
        $user->fill($input)->save();

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function editShipping(Request $request)
    {
        $userDetail = \Auth::user();
        $user       = Customer::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'shipping_name' => 'required',
                'shipping_country' => 'required',
                'shipping_state' => 'required',
                'shipping_city' => 'required',
                'shipping_phone' => 'required',
                'shipping_zip' => 'required',
                'shipping_address' => 'required',
            ]
        );
        $input = $request->all();
        $user->fill($input)->save();

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function updatePassword(Request $request)
    {
        if (Auth::Check()) {
            $request->validate(
                [
                    'current_password' => 'required',
                    'new_password' => 'required|min:6',
                    'confirm_password' => 'required|same:new_password',
                ]
            );
            $objUser          = Auth::user();
            $request_data     = $request->All();
            $current_password = $objUser->password;
            if (Hash::check($request_data['current_password'], $current_password)) {
                $user_id            = Auth::User()->id;
                $obj_user           = Customer::find($user_id);
                $obj_user->password = Hash::make($request_data['new_password']);;
                $obj_user->save();

                return redirect()->back()->with('success', __('Password updated successfully.'));
            } else {
                return redirect()->back()->with('error', __('Please enter correct current password.'));
            }
        } else {
            return redirect()->back()->with('error', __('Something is wrong.'));
        }
    }

    public function changeLanquage($lang)
    {
        $user       = Auth::user();
        $user->lang = $lang;
        $user->save();
        if ($user->lang == 'ar' || $user->lang == 'he') {
            $value = 'on';
        } else {
            $value = 'off';
        }
        if ($user->type == 'super admin') {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $value,
                    'SITE_RTL',
                    $user->creatorId(),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        } else {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $value,
                    'SITE_RTL',
                    $user->creatorId(),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        }

        return redirect()->back()->with('success', __('Language change successfully.'));
    }

    public function export()
    {
        $name = 'resident_' . date('Y-m-d i:h:s');
        $data = Excel::download(new CustomerExport(), $name . '.xlsx');

        return $data;
    }

    public function importFile()
    {
        return view('customer.import');
    }
    public function syncFile()
    {
        return view('customer.residentSync');
    }
    public function InvoicePopup($id)
    {
        return view('customer.invoicePopup', compact('id'));
    }
    public function BulkInvoicePopup()
    {
        return view('customer.bulkInvoicePopup');
    }
    public function BulkReceiptPopup()
    {
        return view('customer.bulkReceiptPopup');
    }
    public function ReceiptPopup($id)
    {
        return view('customer.receiptPopup', compact('id'));
    }
    public function syncResident(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        try {
            $fromDate = Carbon::parse($request->from_date)->startOfDay();
            $toDate = Carbon::parse($request->to_date)->endOfDay();

            $connection = DB::connection(env('SECOND_DB_CONNECTION'));
            $authUser = Auth::user();
            $buildingId = Auth::user()->currentBuilding();
            // Get units within date range
            $units = $connection->table('flats')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->where('building_id', $buildingId)
                ->get();

            if ($units->isEmpty()) {
                return redirect()->back()->with('error', __('No new units found in the specified date range.'));
            }

            // Get next customer ID
            $customerId = $this->getNextCustomerId($authUser->creatorId());

            $syncedCount = 0;
            $skippedCount = 0;

            DB::transaction(function () use ($units, $connection, $authUser, &$customerId, &$syncedCount, &$skippedCount, $fromDate, $toDate) {
                $ids = [];
                foreach ($units as $unit) {
                    try {
                        $residentData = $this->prepareResidentData($unit, $connection, $authUser, $customerId);

                        if (!$residentData) {
                            $skippedCount++;
                            continue;
                        }

                        $customer = Customer::updateOrCreate(
                            [
                                'created_by' => $authUser->creatorId(),
                                'flat_id' => $unit->id,
                                'building_id' => $unit->building_id,
                                'property_number' => $unit->property_number,
                            ],
                            $residentData
                        );

                        DB::table('sync_flat_histories')->insert([
                            'flat_id' => $unit->id,
                            'customer_id' => $customer->id,
                            'building_id' => $unit->building_id,
                            'sync_by' => $authUser->creatorId(),
                            'sync_date' => $fromDate . ' To ' . $toDate,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $types = ChartOfAccountType::where('building_id', Auth::user()->currentBuilding())->where('name', 'Assets')->first();
                        if (!$types) {
                            $types = ChartOfAccountType::updateOrCreate([
                                'name' => 'Assets',
                                'building_id' => Auth::user()->currentBuilding(),
                                'created_by' => $authUser->creatorId(),
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                        $accountTypes = ChartOfAccountSubType::where('type', $types->id)->where('building_id', Auth::user()->currentBuilding())->first();
                        if (!$accountTypes) {
                            $accountTypes = ChartOfAccountSubType::updateOrCreate([
                                'name' => 'Current Asset',
                                'type' => $types->id,
                                'building_id' => Auth::user()->currentBuilding(),
                                'created_by' => $authUser->creatorId(),
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                        $AccountDetail = ChartOfAccount::where('building_id', $unit->building_id)->where('code', 1206)->first();
                        if (!$AccountDetail) {
                            $AccountDetail = ChartOfAccount::updateOrCreate([
                                'name' => 'Sundry Debtors',
                                'code' => 1206,
                                'type' => $types->id,
                                'sub_type' => $accountTypes->id,
                                'parent' => 0,
                                'description' => 'Sundry Debtors',
                                'is_enabled' => 1,
                                'building_id' => Auth::user()->currentBuilding(),
                                'created_by' => $authUser->creatorId(),
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'initial_balance' => 0,
                            ]);
                        }
                        if ($AccountDetail && $types && $accountTypes) {
                            $parentAccount = ChartOfAccountParent::where('account', $AccountDetail->id)->first();
                            if (!$parentAccount) {
                                $parentAccount = ChartOfAccountParent::updateOrCreate([
                                    'name' => $AccountDetail->name,
                                    'sub_type' => $accountTypes->id,
                                    'type' => $types->id,
                                    'account' => $AccountDetail->id,
                                    'created_by' => $authUser->creatorId(),
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]);
                            }
                            if ($parentAccount) {
                                $chartOfAccount = ChartOfAccount::updateOrCreate(
                                    [
                                        'building_id' => $unit->building_id,
                                        'code' => $AccountDetail->code,
                                        'name' => $customer->property_number . '-' . $customer->name,
                                    ],
                                    [
                                        'type' => $types->id,
                                        'sub_type' => $accountTypes->id,
                                        'parent' => $parentAccount->id ?? 0,
                                        'description' => 'Chart Of Account for Flat ID: ' . $unit->id,
                                        'is_enabled' => 1,
                                        'is_sync' => 1,
                                        'created_by' => $authUser->creatorId(),
                                        'created_at' => date('Y-m-d H:i:s'),
                                        'updated_at' => date('Y-m-d H:i:s'),
                                        'initial_balance' => 0,
                                    ]
                                );
                            } else {
                                Log::error('Failed to sync resident for unit: ' . $unit->id, [
                                    'building_id' => $unit->building_id,
                                    'error' => 'Account Detail or Types not found for building ID: ' . $unit->building_id,
                                    'user_id' => $authUser->id
                                ]);
                            }
                        } else {
                            Log::error('Failed to sync resident for unit: ' . $unit->id, [
                                'building_id' => $unit->building_id,
                                'error' => 'Account Detail or Types not found for building ID: ' . $unit->building_id,
                                'user_id' => $authUser->id
                            ]);
                        }
                        $customerId++;
                        $syncedCount++;
                        $ids[] = $unit->id;
                    } catch (\Exception $e) {
                        Log::error('Failed to sync resident for unit: ' . $unit->id, [
                            'unit_id' => $unit->id,
                            'error' => $e->getMessage(),
                            'user_id' => $authUser->id
                        ]);
                        $skippedCount++;
                    }
                }
                DB::connection(env('SECOND_DB_CONNECTION'))->table('flats')->whereIn('id', $ids)->update(['is_sync' => 1]);
            });
            $message = __('Residents sync completed. Synced: :synced, Skipped: :skipped', [
                'synced' => $syncedCount,
                'skipped' => $skippedCount
            ]);

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Resident sync failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->only(['from_date', 'to_date'])
            ]);

            return redirect()->back()->with('error', __('Failed to sync residents. Please try again.'));
        }
    }
    private function getNextCustomerId($creatorId)
    {
        $customer = Customer::where('created_by', $creatorId)
            ->orderByDesc('customer_id')
            ->first();

        return $customer ? $customer->customer_id + 1 : 1;
    }
    private function prepareResidentData($unit, $connection, $authUser, $customerId)
    {
        // Get building data
        $building = $connection->table('buildings')
            ->where('id', $unit->building_id)
            ->first();

        if (!$building) {
            return null;
        }

        // Get flat owner data
        $flatOwner = $connection->table('flat_owner')
            ->where('flat_id', $unit->id)
            ->first();

        if (!$flatOwner) {
            return null;
        }

        // Get owner data
        $ownerData = $connection->table('apartment_owners')
            ->where('id', $flatOwner->owner_id)
            ->first();

        if (!$ownerData) {
            return null;
        }

        $name = $ownerData->name;
        $email = $ownerData->email;
        $contact = $ownerData->mobile;
        $address = $building->address_line1 . ', ' . $building->area;

        return [
            'customer_id' => $customerId,
            'name' => $name,
            'email' => $email,
            'contact' => $contact,
            'type' => 'Owner',
            'lang' => 'en',
            'is_enable_login' => 0,
            'billing_name' => $name,
            'billing_country' => 'UAE',
            'billing_city' => 'Dubai',
            'billing_phone' => $contact,
            'billing_address' => $address,
            'shipping_name' => $name,
            'shipping_country' => 'UAE',
            'shipping_city' => 'Dubai',
            'shipping_phone' => $contact,
            'shipping_address' => $address,
            'created_by_lazim' => true,
            'flat_id' => $unit->id,
            'building_id' => $building->id,
            'property_number' => $unit->property_number,
            'created_by' => $authUser->creatorId(),
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    public function syncInvoice(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
                'customer_id' => 'required',
            ]);
            if (!isset($validated['from_date']) || !isset($validated['to_date'])) {
                return redirect()->back()->with('error', __('From date and to date are required.'));
            }
            if ($validated['from_date'] > $validated['to_date']) {
                return redirect()->back()->with('error', __('From date cannot be greater than to date.'));
            }
            if ($validated['from_date'] == $validated['to_date']) {
                return redirect()->back()->with('error', __('From date and to date cannot be the same.'));
            }

            $fromDate = Carbon::parse($validated['from_date'])->startOfDay();
            $toDate = Carbon::parse($validated['to_date'])->endOfDay();
            $authUser = Auth::user();
            $creatorId = $authUser->creatorId();

            // Pre-fetch required data
            $requiredData = $this->getRequiredSyncData($creatorId, $validated['customer_id']);
            if ($requiredData === null) {
                return redirect()->back()->with('error', __('Required data not found for sync operation.'));
            }
            $FlatId = Customer::find($validated['customer_id'])->flat_id;
            if ($FlatId === null) {
                return redirect()->back()->with('error', __('No flat found for this customer.'));
            }
            // Get invoices from external connection
            $invoices = $this->fetchExternalInvoices($fromDate, $toDate, $authUser->building_id, $FlatId);
            if ($invoices->isEmpty()) {
                return redirect()->back()->with('error', __('No invoices found for the selected date range.'));
            }

            $ServiceCharge = DB::table('settings')->where('name', 'invoice_service_charge')->where('created_by', $creatorId)->first();
            if ($ServiceCharge === null) {
                // return redirect()->back()->with('error', __('No service charge found for this building.')); ## TODO In correct message
                return redirect()->back()->with('error', __('No service charge ledger found for this building.'));
            }
            // Process invoices in transaction
            $result = $this->processInvoicesSync($invoices, $requiredData, $creatorId, $ServiceCharge);

            return $this->buildSyncResponse($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            $this->logSyncError($e, $request);
            return redirect()->back()->with('error', __('Failed to sync invoices. Please try again.'));
        }
    }

    public function syncBulkInvoice(Request $request)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);
            if (!isset($validated['from_date']) || !isset($validated['to_date'])) {
                return redirect()->back()->with('error', __('From date and to date are required.'));
            }
            if ($validated['from_date'] > $validated['to_date']) {
                return redirect()->back()->with('error', __('From date cannot be greater than to date.'));
            }
            if ($validated['from_date'] == $validated['to_date']) {
                return redirect()->back()->with('error', __('From date and to date cannot be the same.'));
            }
            $fromDate = Carbon::parse($validated['from_date'])->startOfDay();
            $toDate = Carbon::parse($validated['to_date'])->endOfDay();
            $authUser = Auth::user();
            $creatorId = \Auth::user()->creatorId();

            // Get invoices from external connection
            $invoices = $this->fetchExternalBulkInvoices($fromDate, $toDate, $authUser->building_id);
            if ($invoices->isEmpty()) {
                return redirect()->back()->with('error', __('No invoices found for the selected date range.'));
            }

            $ServiceCharge = DB::table('settings')->where('name', 'invoice_service_charge')->where('created_by', $creatorId)->first();
            if ($ServiceCharge === null) {
                return redirect()->back()->with('error', __('No service charge found for this building.'));
            }
            // Process invoices in transaction
            $result = $this->processBulkInvoicesSync($invoices, $creatorId, $ServiceCharge);

            return $this->buildSyncResponse($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            $this->logSyncError($e, $request);
            return redirect()->back()->with('error', __('Failed to sync invoices. Please try again.'));
        }
    }

    /**
     * Get all required data for sync operation
     */
    private function getRequiredSyncData(int $creatorId, int $customerId): ?array
    {
        try {
            // Get service charges category
            $categoryId = DB::table('product_service_categories')
                ->where('name', 'Service Charges')
                ->value('id');

            if (!$categoryId) {
                return null;
            }

            // Get customer
            $customer = DB::table('customers')->find($customerId);
            if (!$customer) {
                return null;
            }

            return [
                'category_id' => $categoryId,
                'customer' => $customer,
            ];
        } catch (\Exception $e) {
            \Log::error('Error fetching required sync data: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Fetch invoices from external connection
     */
    private function fetchExternalInvoices(Carbon $fromDate, Carbon $toDate, int $buildingId, int $flatId)
    {
        $connection = DB::connection(env('SECOND_DB_CONNECTION'));
        return $connection->table('oam_invoices')
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->where('building_id', $buildingId)
            ->where('flat_id', $flatId)
            ->orderBy('invoice_date')
            ->get();
    }
    /**
     * Fetch invoices from external connection
     */
    private function fetchExternalBulkInvoices(Carbon $fromDate, Carbon $toDate, int $buildingId)
    {
        $connection = DB::connection(env('SECOND_DB_CONNECTION'));
        return $connection->table('oam_invoices')
            ->whereBetween('invoice_date', [$fromDate, $toDate])
            ->where('building_id', $buildingId)
            ->orderBy('invoice_date')
            ->get();
    }

    /**
     * Process invoices synchronization
     */
    private function processInvoicesSync($invoices, array $requiredData, int $creatorId, $ServiceCharge): array
    {
        $syncedCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::transaction(function () use ($invoices, $requiredData, $creatorId, &$syncedCount, &$errorCount, &$errors, $ServiceCharge) {
            foreach ($invoices as $invoice) {
                try {
                    $this->syncSingleInvoice($invoice, $requiredData, $creatorId, $ServiceCharge);
                    $ids[] = $invoice->id;
                    $syncedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessage = "Invoice ID {$invoice->id}: " . $e->getMessage();
                    $errors[] = $errorMessage;
                    \Log::error("Sync error - " . $errorMessage);
                }
            }
            $connection = DB::connection(env('SECOND_DB_CONNECTION'));
            $connection->table('oam_invoices')
                ->whereIn('id', $ids)
                ->update(['is_sync' => 1]);
        });

        return [
            'synced' => $syncedCount,
            'errors' => $errorCount,
            'error_details' => $errors,
        ];
    }
    private function processBulkInvoicesSync($invoices, int $creatorId, $ServiceCharge): array
    {
        $syncedCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::transaction(function () use ($invoices, $creatorId, &$syncedCount, &$errorCount, &$errors, $ServiceCharge) {
            foreach ($invoices as $invoice) {
                try {
                    $this->syncInvoiceForBuilding($invoice, $creatorId, $ServiceCharge);
                    $ids[] = $invoice->id;
                    $syncedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessage = "Invoice ID {$invoice->id}: " . $e->getMessage();
                    $errors[] = $errorMessage;
                    \Log::error("Sync error - " . $errorMessage);
                }
            }
            $connection = DB::connection(env('SECOND_DB_CONNECTION'));
            $connection->table('oam_invoices')
                ->whereIn('id', $ids)
                ->update(['is_sync' => 1]);
        });

        return [
            'synced' => $syncedCount,
            'errors' => $errorCount,
            'error_details' => $errors,
        ];
    }

    private function syncInvoiceForBuilding(object $invoice, int $creatorId, $ServiceCharge): void
    {
        $customer = Customer::where('building_id', $invoice->building_id)->where('flat_id', $invoice->flat_id)->first()->id;
        $categoryId = DB::table('product_service_categories')
            ->where('name', 'Service Charges')
            ->value('id');
        // not condition should be handled first
        if ($customer) {
            $attributes = [
                'building_id' => $invoice->building_id,
                'flat_id' => $invoice->flat_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $customer,
                'invoice_period' => $invoice->invoice_period,
            ];

            $values = [
                'issue_date' => $invoice->invoice_date,
                'due_date' => $invoice->invoice_due_date,
                'send_date' => $invoice->invoice_date,
                'category_id' => $categoryId,
                'ref_number' => $invoice->invoice_number,
                'is_mollak' => 1,
                'status' => 0,
                'shipping_display' => true,
                'discount_apply' => false,
                'invoice_pdf_link' => $invoice->invoice_pdf_link ?? null,
                'invoice_detail_link' => $invoice->invoice_detail_link ?? null,
                'payment_url' => $invoice->payment_url ?? null,
                'created_by' => $creatorId,
                'updated_at' => now(),
            ];

            // update condition should be handled first

            // Use firstOrCreate for better control
            $invoiceRecord = Invoice::firstOrCreate($attributes, array_merge($values, ['created_at' => now()]));

            // Update if it already exists
            if (!$invoiceRecord->wasRecentlyCreated) {
                $invoiceRecord->update($values);
            }
            $ServiceChargeAmount = (($invoice->invoice_amount / 21) * 20);
            InvoiceProduct::updateOrInsert(
                [
                    'invoice_id' => $invoiceRecord->id,
                    'product_id' => $ServiceCharge->value,
                ],
                [
                    'quantity' => 1.00,
                    'tax' => 1,
                    'price' => $ServiceChargeAmount ?? 0,
                    'description' => sprintf(
                        'Service charge invoice for flat ID: %s (Period: %s)',
                        $invoice->flat_id,
                        $invoice->invoice_period ?? 'N/A'
                    ),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            // Update the total quantity of the product
            Utility::total_quantity('minus', 1, $ServiceCharge->value);
            // Create a product stock report
            $user = $creatorId == null ? Auth::user() : User::find($creatorId);
            $type = 'invoice';
            $typeId = $invoiceRecord->id;
            $description = '1 quantity sold in invoice' . ' ' . $invoiceRecord->ref_number;
            Utility::addInvoiceProductStock($ServiceCharge->value, 1, $type, $description, $typeId, $creatorId);
            $requiredData = [
                'customer' => $customer,
                'category_id' => $categoryId,
            ];
            $this->sentInvoice($invoiceRecord, $invoice, $requiredData, $creatorId, $ServiceCharge);
        } else {
            Log::info('Customer not found for building_id: ' . $invoice->building_id . ' and flat_id: ' . $invoice->flat_id . ' and Invoice Number: ' . $invoice->invoice_number);
        }
    }

    /**
     * Sync a single invoice with all related records
     */
    private function syncSingleInvoice(object $invoice, array $requiredData, int $creatorId, $ServiceCharge): void
    {
        // Generate unique reference number
        $refNumber = $this->generateInvoiceRefNumber($invoice);

        // Create/update main invoice
        $invoiceRecord = $this->createOrUpdateInvoice($invoice, $requiredData, $creatorId, $refNumber);

        // Create/update invoice product
        $this->createOrUpdateInvoiceProduct($invoiceRecord, $invoice, $ServiceCharge);
        // Create transaction lines
        $this->sentInvoice($invoiceRecord, $invoice, $requiredData, $creatorId, $ServiceCharge);
    }

    /**
     * Generate unique invoice reference number
     */
    private function generateInvoiceRefNumber(object $invoice): string
    {
        return $invoice->invoice_number;
    }

    /**
     * Create or update invoice record
     */
    private function createOrUpdateInvoice(object $invoice, array $requiredData, int $creatorId, string $refNumber): object
    {
        $attributes = [
            'building_id' => $creatorId,
            'flat_id' => $invoice->flat_id,
            'invoice_id' => $invoice->id,
            'customer_id' => $requiredData['customer']->id,
            'invoice_period' => $invoice->invoice_period,
        ];

        $values = [
            'issue_date' => $invoice->invoice_date,
            'due_date' => $invoice->invoice_due_date,
            'send_date' => $invoice->invoice_date,
            'category_id' => $requiredData['category_id'],
            'ref_number' => $refNumber,
            'is_mollak' => 1,
            'status' => 0,
            'shipping_display' => true,
            'discount_apply' => false,
            'invoice_pdf_link' => $invoice->invoice_pdf_link ?? null,
            'invoice_detail_link' => $invoice->invoice_detail_link ?? null,
            'payment_url' => $invoice->payment_url ?? null,
            'created_by' => $creatorId,
            'updated_at' => now(),
        ];

        // Use firstOrCreate for better control
        $invoiceRecord = Invoice::firstOrCreate($attributes, array_merge($values, ['created_at' => now()]));

        // Update if it already exists
        if (!$invoiceRecord->wasRecentlyCreated) {
            Log::info('Invoice already exists with ID: ' . $invoiceRecord->id . ' for Invoice Number: ' . $refNumber);
            $invoiceRecord->update($values);
        }
        return $invoiceRecord;
    }

    /**
     * Create or update invoice product
     */
    private function createOrUpdateInvoiceProduct(object $invoiceRecord, object $invoice, $ServiceCharge): void
    {

        $ServiceChargeAmount = (($invoice->invoice_amount / 21) * 20); // invoice received including vat need to take entry without Vat
        InvoiceProduct::updateOrInsert(
            [
                'invoice_id' => $invoiceRecord->id,
                'product_id' => $ServiceCharge->value,
            ],
            [
                'quantity' => 1.00,
                'tax' => 1,
                'price' => $ServiceChargeAmount ?? 0,
                'description' => sprintf(
                    'Service charge invoice for flat ID: %s (Period: %s)',
                    $invoice->flat_id,
                    $invoice->invoice_period ?? 'N/A'
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
        // Update the total quantity of the product
        Utility::total_quantity('minus', 1, $ServiceCharge->value);
        // Create a product stock report
        $creatorId = Auth::user()->currentBuilding();
        $user = $creatorId == null ? Auth::user() : User::find($creatorId);
        $type = 'invoice';
        $typeId = $invoiceRecord->id;
        $description = '1 quantity sold in invoice' . ' ' . $invoiceRecord->ref_number;
        Utility::addInvoiceProductStock($ServiceCharge->value, 1, $type, $description, $typeId, $creatorId);
    }

    /**
     * Create transaction lines
     */
    private function sentInvoice(object $invoiceRecord, object $invoice, array $requiredData, int $creatorId, $ServiceCharge): void
    {
        $invoice = Invoice::where('id', $invoiceRecord->id)->first();
        $invoice->send_date = date('Y-m-d');
        $invoice->status = 1;
        $invoice->save();

        $customer = Customer::where('id', $invoiceRecord->customer_id)->first();
        $invoice->name = ! empty($customer) ? $customer->name : '';
        $invoice->invoice = $invoiceRecord->ref_number;

        $invoiceId = Crypt::encrypt($invoiceRecord->id);
        $invoice->url = route('invoice.pdf', $invoiceId);

        // Utility::updateUserBalance('customer', $customer->id, $invoice->getTotal(), 'credit');

        $invoice_products = InvoiceProduct::where('invoice_id', $invoiceRecord->id)->get();
        foreach ($invoice_products as $invoice_product) {
            $product = ProductService::find($invoice_product->product_id);
            $totalTaxPrice = 0;
            if ($invoice_product->tax != null) {
                $taxes = \App\Models\Utility::tax($invoice_product->tax);
                foreach ($taxes as $tax) {
                    $taxPrice = \App\Models\Utility::taxRate($tax->rate, $invoice_product->price, $invoice_product->quantity, $invoice_product->discount);
                    $totalTaxPrice += $taxPrice;
                }
            }

            $itemAmount = ($invoice_product->price * $invoice_product->quantity) - ($invoice_product->discount);

            $data = [
                'account_id' => $product->sale_chartaccount_id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $itemAmount,
                'reference' => 'Invoice',
                'reference_id' => $invoiceRecord->id,
                'reference_sub_id' => $product->id,
                'date' => $invoiceRecord->issue_date,
            ];
            dispatch(new AddTransactionLinesJob($data, $creatorId, $invoiceRecord->building_id));
            // Utility::addTransactionLines($data, $creatorId, $invoiceRecord->building_id);
        }
        $vatAccount = ChartOfAccount::where('name', 'VAT Payable 5%')->where('created_by', '=', Auth::user()->creatorId())->first(); // TODO TAX
        $invoiceTotalTax = $invoice->getTotalTax();
        $data = [
            'account_id' => $vatAccount->id,
            'transaction_type' => 'Credit',
            'transaction_amount' => $invoiceTotalTax,
            'reference' => 'Invoice',
            'reference_id' => $invoice->id,
            'reference_sub_id' => $invoice->items->pluck('tax')->join(','),
            'date' => $invoice->issue_date,
        ];
        dispatch(new AddTransactionLinesJob($data, $creatorId, $invoice->building_id));
        // Utility::addTransactionLines($data, $creatorId, $invoice?->building_id);

        $uArr = [
            'invoice_name' => $invoice->name,
            'invoice_number' => $invoice->invoice,
            'invoice_url' => $invoice->url,
        ];
        $invoice->updateCustomerBalance();
    }

    /**
     * Build sync response message
     */
    private function buildSyncResponse(array $result): \Illuminate\Http\RedirectResponse
    {
        $message = __('Invoice sync completed.');

        if ($result['synced'] > 0) {
            $message .= " " . __(':count invoices synced successfully.', ['count' => $result['synced']]);
        }

        if ($result['errors'] > 0) {
            $message .= " " . __(':count invoices failed to sync.', ['count' => $result['errors']]);

            // Log detailed errors for admin review
            \Log::warning('Invoice sync completed with errors:', $result['error_details']);
        }

        $type = $result['errors'] > 0 ? 'warning' : 'success';
        return redirect()->back()->with($type, $message);
    }

    /**
     * Log sync error with context
     */
    private function logSyncError(\Exception $e, Request $request): void
    {
        \Log::error('Invoice sync failed: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'building_id' => Auth::user()->building_id ?? null,
            'from_date' => $request->from_date ?? null,
            'to_date' => $request->to_date ?? null,
            'customer_id' => $request->customer_id ?? null,
            'trace' => $e->getTraceAsString(),
        ]);
    }
    public function syncReceipt(Request $request)
    {
        try {
            // Validate input with custom error messages
            $validated = $request->validate([
                'from_date' => 'required|date|before_or_equal:today',
                'to_date' => 'required|date|after_or_equal:from_date|before_or_equal:today',
                'customer_id' => 'required|exists:customers,id',
            ], [
                'from_date.before_or_equal' => 'From date cannot be in the future.',
                'to_date.after_or_equal' => 'To date must be after or equal to from date.',
                'to_date.before_or_equal' => 'To date cannot be in the future.',
                'customer_id.exists' => 'Selected customer does not exist.',
            ]);

            $fromDate = Carbon::parse($validated['from_date'])->startOfDay();
            $toDate = Carbon::parse($validated['to_date'])->endOfDay();
            $authUser = Auth::user();
            $creatorId = $authUser->creatorId();
            $buildingId = Auth::user()->currentBuilding();
            $customerId = $validated['customer_id'];
            if (!isset($validated['from_date']) || !isset($validated['to_date'])) {
                return redirect()->back()->with('error', __('From date and to date are required.'));
            }
            if ($validated['from_date'] > $validated['to_date']) {
                return redirect()->back()->with('error', __('From date cannot be greater than to date.'));
            }
            if ($validated['from_date'] == $validated['to_date']) {
                return redirect()->back()->with('error', __('From date and to date cannot be the same.'));
            }
            // Check date range limitation (optional business rule)
            // if ($fromDate->diffInDays($toDate) > 365) {
            //     return redirect()->back()
            //         ->with('error', __('Date range cannot exceed 365 days. Please select a smaller range.'))
            //         ->withInput();
            // }

            // Pre-fetch required data with better error handling
            $requiredData = $this->getRequiredSyncData($creatorId, $customerId);
            if (!$requiredData) {
                return redirect()->back()
                    ->with('error', __('Unable to find required customer or building data. Please contact support.'))
                    ->withInput();
            }

            $FlatId = Customer::find($customerId)->flat_id;
            if ($FlatId === null) {
                return redirect()->back()
                    ->with('error', __('Flat not found for this customer.'))
                    ->withInput();
            }

            // Pre-fetch account and category data to avoid repetitive queries
            $generalFundAccount = BankAccount::where('building_id', $buildingId)
                ->where('holder_name', 'General Fund')
                ->first();
            // Pre-fetch account and category data to avoid repetitive queries
            $reserveFundAccount = BankAccount::where('building_id', $buildingId)
                ->where('holder_name', 'Reserve Fund')
                ->first();

            $serviceChargesCategory = ProductServiceCategory::where('name', 'Service Charges')
                ->where('building_id', $buildingId)
                ->first();

            if (!$generalFundAccount) {
                return redirect()->back()
                    ->with('error', __('General Fund bank account not found. Please set up your bank accounts first.'))
                    ->withInput();
            }

            if (!$reserveFundAccount) {
                return redirect()->back()
                    ->with('error', __('Reserve Fund bank account not found. Please set up your bank accounts first.'))
                    ->withInput();
            }

            if (!$serviceChargesCategory) {
                return redirect()->back()
                    ->with('error', __('Service Charges category not found. Please set up your service categories first.'))
                    ->withInput();
            }

            // Get receipts from external connection with better error handling
            try {
                $connection = DB::connection(env('SECOND_DB_CONNECTION'));
                $receipts = $connection->table('oam_receipts')
                    ->whereBetween('receipt_date', [$fromDate, $toDate])
                    ->where('building_id', $buildingId)
                    ->where('flat_id', $FlatId)
                    ->where('is_sync', 0)
                    ->orderBy('receipt_date')
                    ->get();
            } catch (\Exception $e) {
                Log::error('External database connection failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $authUser->id,
                    'building_id' => $buildingId
                ]);

                return redirect()->back()
                    ->with('error', __('Unable to connect to external database. Please try again later.'))
                    ->withInput();
            }

            if ($receipts->isEmpty()) {
                return redirect()->back()
                    ->with('error', __('No receipts found for the selected date range (:from to :to).', [
                        'from' => $fromDate->format('M j, Y'),
                        'to' => $toDate->format('M j, Y')
                    ]))
                    ->withInput();
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            // Process receipts in a database transaction
            DB::beginTransaction();

            try {
                foreach ($receipts as $receipt) {
                    try {
                        // Validate receipt data
                        if (!$this->validateReceiptData($receipt)) {
                            $errorCount++;
                            $errors[] = "Receipt {$receipt->receipt_number}: Invalid data";
                            continue;
                        }

                        // Create or update revenue record
                        $revenue = Revenue::updateOrCreate(
                            [
                                'customer_id' => json_encode([$customerId]),
                                'flat_id' => $receipt->flat_id,
                                'building_id' => $buildingId,
                                'created_by' => $creatorId,
                                'receipt_period' => $receipt->receipt_period,
                                'reference' => $receipt->receipt_number,
                            ],
                            [
                                'transaction_date' => $receipt->receipt_created_date,
                                'transaction_method' => $receipt->payment_mode,
                                'transaction_number' => $receipt->transaction_reference,
                                'date' => $receipt->receipt_date,
                                'amount' => $receipt->receipt_amount,
                                'account_id' => $generalFundAccount->id,
                                'category_id' => $serviceChargesCategory->id,
                                'payment_method' => 0,
                                'is_mollak' => 1,
                                'updated_at' => now(),
                            ]
                        );


                        // Update customer balance
                        $revenue->updateRevenueCustomerBalance($customerId, $receipt->receipt_amount, $revenue->id, $receipt->receipt_date);

                        $revenue->transfer_id = $revenue->id;
                        $revenue->type = 'Revenue';
                        $revenue->category = $serviceChargesCategory->name;
                        $revenue->user_id = $customerId;
                        $revenue->user_type = 'Customer';
                        $revenue->account = $generalFundAccount->id;
                        Transaction::addTransaction($revenue);

                        if ($customerId) {
                            $customer = Customer::find($customerId);
                            Utility::userBalance('customer', $customer->id, $revenue->amount, 'credit');
                        }
                        if ($receipt->payment_mode == 'Noqodi Payment') {
                            $PaymentDetail = json_decode($receipt->noqodi_info);
                            RevenueBankAllocation::updateOrCreate([
                                'revenue_id' => $revenue->id,
                                'bank_account_id' => $generalFundAccount->id,
                                'amount' => $PaymentDetail->generalFundAmount,
                            ]);
                            Utility::bankAccountBalance($generalFundAccount->id, $PaymentDetail->generalFundAmount, 'credit');
                            RevenueBankAllocation::updateOrCreate([
                                'revenue_id' => $revenue->id,
                                'bank_account_id' => $reserveFundAccount->id,
                                'amount' => $PaymentDetail->reservedFundAmount,
                            ]);
                            Utility::bankAccountBalance($reserveFundAccount->id, $PaymentDetail->reservedFundAmount, 'credit');
                        } else {
                            ##TODO  Q1. Why we are deducting vat from payment? Q2.why we are not deducting vat from noqodi payment?
                            // $vatAmount = $receipt->receipt_amount ? ($receipt->receipt_amount * 0.05) : 0; // 5% VAT
                            // $netAmount = $receipt->receipt_amount - $vatAmount;
                            $netAmount = $receipt->receipt_amount;
                            RevenueBankAllocation::updateOrCreate([
                                'revenue_id' => $revenue->id,
                                'bank_account_id' => $generalFundAccount->id,
                                'amount' => $netAmount,
                            ]);
                            Utility::bankAccountBalance($generalFundAccount->id, $netAmount, 'credit');
                        }

                        if ($receipt->payment_mode == 'Noqodi Payment') {
                            $PaymentDetail = json_decode($receipt->noqodi_info);
                            $gneralaccount = BankAccount::find($generalFundAccount->id);
                            $data1 = [
                                'account_id' => $gneralaccount->chart_account_id,
                                'transaction_type' => 'Debit',
                                'transaction_amount' => $PaymentDetail->generalFundAmount,
                                'reference' => 'Revenue',
                                'reference_id' => $revenue->id,
                                'reference_sub_id' => 0,
                                'date' => $revenue->date,
                            ];
                            dispatch(new AddTransactionLinesJob($data1, $creatorId, $buildingId));
                            // Utility::addTransactionLines($data);
                            $reserveaccount = BankAccount::find($reserveFundAccount->id);
                            $data2 = [
                                'account_id' => $reserveaccount->chart_account_id,
                                'transaction_type' => 'Debit',
                                'transaction_amount' => $PaymentDetail->reservedFundAmount,
                                'reference' => 'Revenue',
                                'reference_id' => $revenue->id,
                                'reference_sub_id' => 0,
                                'date' => $revenue->date,
                            ];
                            dispatch(new AddTransactionLinesJob($data2, $creatorId, $buildingId));
                            // Utility::addTransactionLines($data);
                        } else {
                            $account = BankAccount::find($generalFundAccount->id);
                            $data3 = [
                                'account_id' => $account->chart_account_id,
                                'transaction_type' => 'Debit',
                                'transaction_amount' => $netAmount,
                                'reference' => 'Revenue',
                                'reference_id' => $revenue->id,
                                'reference_sub_id' => 0,
                                'date' => $revenue->date,
                            ];
                            dispatch(new AddTransactionLinesJob($data3, $creatorId, $buildingId));
                            // Utility::addTransactionLines($data);
                        }

                        // if ($vatAmount > 0) {
                        //     Transaction::updateOrCreate([
                        //         'amount' => $vatAmount,
                        //         'type' => 'VAT',
                        //         'category' => 'VAT Payable',
                        //         'user_id' => $revenue->customer_id,
                        //         'user_type' => 'Customer',
                        //         'account' => $generalFundAccount->id,
                        //         'date' => $revenue->date,
                        //         'created_by' => Auth::user()->creatorId(),
                        //     ]);
                        // }
                        if ($receipt->payment_mode == 'Noqodi Payment') {
                            $PaymentDetail = json_decode($receipt->noqodi_info);
                            $invoiceId = Invoice::where([
                                'building_id' => $receipt->building_id,
                                'flat_id' => $receipt->flat_id,
                                'ref_number' => $PaymentDetail->invoiceNumber
                            ])->first()?->id;
                        } else {
                            $invoiceId = Invoice::where([
                                'building_id' => $receipt->building_id,
                                'flat_id' => $receipt->flat_id,
                                'invoice_period' => $receipt->receipt_period
                            ])->first()?->id;
                        }
                        if ($invoiceId) {
                            InvoiceRevenue::updateOrCreate([
                                'invoice_id' => $invoiceId,
                                'revenue_id' => $revenue->id,
                            ], [
                                'adjusted_amount' => $receipt->receipt_amount,
                            ]);
                            $totalTransfer = DB::table('invoice_revenue')->where('invoice_id', $invoiceId)->sum('adjusted_amount');
                            $invoice = Invoice::find($invoiceId);
                            $status = $totalTransfer >= $invoice->getTotal() ? 4 : 3;
                            $invoice->update(['status' => $status]);
                            $revenueCustomerDetail = RevenueCustomerDetail::updateOrCreate(
                                [
                                    'revenue_id' => $revenue->id,
                                    'customer_id' => $customerId,
                                    'invoice_id' => $invoiceId,
                                ],
                                [
                                    'reference_type' => 'against_ref',
                                    'amount' => $receipt->receipt_amount,
                                    'reference_details' => null,
                                ]
                            );
                        } else {
                            $revenueCustomerDetail = RevenueCustomerDetail::updateOrCreate(
                                [
                                    'revenue_id' => $revenue->id,
                                    'customer_id' => $customerId,
                                ],
                                [
                                    'reference_type' => 'new_ref',
                                    'amount' => $receipt->receipt_amount,
                                    'reference_details' => null,
                                ]
                            );
                        }
                        $successCount++;
                        $ids[] = $receipt->id;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $errors[] = "Receipt {$receipt->receipt_number}: {$e->getMessage()}";

                        Log::error('Receipt sync error', [
                            'receipt_number' => $receipt->receipt_number,
                            'error' => $e->getMessage(),
                            'user_id' => $authUser->id,
                            'building_id' => $buildingId
                        ]);
                    }
                }
                if (isset($ids) && !empty($ids)) {
                    DB::connection(env('SECOND_DB_CONNECTION'))->table('oam_receipts')->whereIn('id', $ids)->update(['is_sync' => 1]);
                }
                DB::commit();

                // Generate appropriate response based on results
                if ($successCount > 0 && $errorCount === 0) {
                    return redirect()->back()->with(
                        'success',
                        __('Successfully synced :count receipts.', ['count' => $successCount])
                    );
                } elseif ($successCount > 0 && $errorCount > 0) {
                    $message = __('Partially completed: :success receipts synced, :errors failed.', [
                        'success' => $successCount,
                        'errors' => $errorCount
                    ]);

                    if (count($errors) <= 5) {
                        $message .= ' Errors: ' . implode('; ', $errors);
                    }

                    return redirect()->back()->with('warning', $message);
                } else {
                    return redirect()->back()->with(
                        'error',
                        __('Failed to sync any receipts. Errors: :errors', [
                            'errors' => implode('; ', array_slice($errors, 0, 5))
                        ])
                    );
                }
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('Receipt sync transaction failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $authUser->id,
                    'building_id' => $buildingId,
                    'date_range' => "{$fromDate} to {$toDate}"
                ]);

                return redirect()->back()
                    ->with('error', __('Sync operation failed. All changes have been rolled back.'))
                    ->withInput();
            }
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Receipt sync general error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', __('An unexpected error occurred. Please try again or contact support.'))
                ->withInput();
        }
    }

    public function syncBulkReceipt(Request $request)
    {
        try {
            // Validate input with custom error messages
            $validated = $request->validate([
                'from_date' => 'required|date|before_or_equal:today',
                'to_date' => 'required|date|after_or_equal:from_date|before_or_equal:today',
            ], [
                'from_date.before_or_equal' => 'From date cannot be in the future.',
                'to_date.after_or_equal' => 'To date must be after or equal to from date.',
                'to_date.before_or_equal' => 'To date cannot be in the future.',
            ]);

            $fromDate = Carbon::parse($validated['from_date'])->startOfDay();
            $toDate = Carbon::parse($validated['to_date'])->endOfDay();
            $authUser = Auth::user();
            $creatorId = $authUser->creatorId();
            $buildingId = Auth::user()->currentBuilding();
            // Check date range limitation (optional business rule)
            if (!isset($validated['from_date']) || !isset($validated['to_date'])) {
                return redirect()->back()->with('error', __('From date and to date are required.'));
            }
            if ($validated['from_date'] > $validated['to_date']) {
                return redirect()->back()->with('error', __('From date cannot be greater than to date.'));
            }
            if ($validated['from_date'] == $validated['to_date']) {
                return redirect()->back()->with('error', __('From date and to date cannot be the same.'));
            }
            // if ($fromDate->diffInDays($toDate) > 365) {
            //     return redirect()->back()
            //         ->with('error', __('Date range cannot exceed 365 days. Please select a smaller range.'))
            //         ->withInput();
            // }

            // Pre-fetch account and category data to avoid repetitive queries
            $generalFundAccount = BankAccount::where('building_id', $buildingId)
                ->where('holder_name', 'General Fund')
                ->first();
            // Pre-fetch account and category data to avoid repetitive queries
            $reserveFundAccount = BankAccount::where('building_id', $buildingId)
                ->where('holder_name', 'Reserve Fund')
                ->first();

            $serviceChargesCategory = ProductServiceCategory::where('name', 'Service Charges')
                ->where('building_id', $buildingId)
                ->first();

            if (!$generalFundAccount) {
                return redirect()->back()
                    ->with('error', __('General Fund bank account not found. Please set up your bank accounts first.'))
                    ->withInput();
            }

            if (!$reserveFundAccount) {
                return redirect()->back()
                    ->with('error', __('Reserve Fund bank account not found. Please set up your bank accounts first.'))
                    ->withInput();
            }

            if (!$serviceChargesCategory) {
                return redirect()->back()
                    ->with('error', __('Service Charges category not found. Please set up your service categories first.'))
                    ->withInput();
            }

            // Get receipts from external connection with better error handling
            try {
                $connection = DB::connection(env('SECOND_DB_CONNECTION'));
                $receipts = $connection->table('oam_receipts')
                    ->whereBetween('receipt_date', [$fromDate, $toDate])
                    ->where('building_id', $buildingId)
                    ->where('is_sync', 0)
                    ->orderBy('receipt_date')
                    ->get();
            } catch (\Exception $e) {
                Log::error('External database connection failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $authUser->id,
                    'building_id' => $buildingId
                ]);

                return redirect()->back()
                    ->with('error', __('Unable to connect to external database. Please try again later.'))
                    ->withInput();
            }
            if ($receipts->isEmpty()) {
                return redirect()->back()
                    ->with('error', __('No receipts found for the selected date range (:from to :to).', [
                        'from' => $fromDate->format('M j, Y'),
                        'to' => $toDate->format('M j, Y')
                    ]))
                    ->withInput();
            }

            $successCount = 0;
            $errorCount = 0;
            $customerCount = 0;
            $errors = [];

            // Process receipts in a database transaction
            DB::beginTransaction();

            try {
                foreach ($receipts as $receipt) {
                    try {
                        // Validate receipt data
                        if (!$this->validateReceiptData($receipt)) {
                            $errorCount++;
                            $errors[] = "Receipt {$receipt->receipt_number}: Invalid data";
                            continue;
                        }
                        $customerid = Customer::where('flat_id', $receipt->flat_id)->where('building_id', $receipt->building_id)->first();
                        if ($customerid) {
                            // Create or update revenue record
                            $revenue = Revenue::updateOrCreate(
                                [
                                    'customer_id' => trim(json_encode([$customerid->id]), '"'),
                                    'building_id' => $buildingId,
                                    'created_by' => $creatorId,
                                    'receipt_period' => $receipt->receipt_period,
                                    'reference' => $receipt->receipt_number,
                                ],
                                [
                                    'transaction_date' => $receipt->receipt_created_date,
                                    'transaction_method' => $receipt->payment_mode,
                                    'transaction_number' => $receipt->transaction_reference,
                                    'date' => $receipt->receipt_date,
                                    'amount' => $receipt->receipt_amount,
                                    'account_id' => $generalFundAccount->id,
                                    'category_id' => $serviceChargesCategory->id,
                                    'payment_method' => 0,
                                    'is_mollak' => 1,
                                    'updated_at' => now(),
                                ]
                            );

                            // Update customer balance
                            $revenue->updateRevenueCustomerBalance($customerid->id, $receipt->receipt_amount, $revenue->id, $receipt->receipt_date);

                            $revenue->transfer_id = $revenue->id;
                            $revenue->type = 'Revenue';
                            $revenue->category = $serviceChargesCategory->name;
                            $revenue->user_id = $customerid->id;
                            $revenue->user_type = 'Customer';
                            $revenue->account = $generalFundAccount->id;
                            Transaction::addTransaction($revenue);

                            if ($customerid->id) {
                                $customer = Customer::find($customerid->id);
                                Utility::userBalance('customer', $customer->id, $revenue->amount, 'credit');
                            }
                            if ($receipt->payment_mode == 'Noqodi Payment') {
                                $PaymentDetail = json_decode($receipt->noqodi_info);
                                RevenueBankAllocation::updateOrCreate([
                                    'revenue_id' => $revenue->id,
                                    'bank_account_id' => $generalFundAccount->id,
                                    'amount' => $PaymentDetail->generalFundAmount,
                                ]);
                                Utility::bankAccountBalance($generalFundAccount->id, $PaymentDetail->generalFundAmount, 'credit');
                                RevenueBankAllocation::updateOrCreate([
                                    'revenue_id' => $revenue->id,
                                    'bank_account_id' => $reserveFundAccount->id,
                                    'amount' => $PaymentDetail->reservedFundAmount,
                                ]);
                                Utility::bankAccountBalance($reserveFundAccount->id, $PaymentDetail->reservedFundAmount, 'credit');
                            } else {
                                $netAmount = $receipt->receipt_amount;
                                RevenueBankAllocation::updateOrCreate([
                                    'revenue_id' => $revenue->id,
                                    'bank_account_id' => $generalFundAccount->id,
                                    'amount' => $netAmount,
                                ]);
                                Utility::bankAccountBalance($generalFundAccount->id, $netAmount, 'credit');
                            }

                            if ($receipt->payment_mode == 'Noqodi Payment') {
                                $PaymentDetail = json_decode($receipt->noqodi_info);
                                $gneralaccount = BankAccount::find($generalFundAccount->id);
                                $data1 = [
                                    'account_id' => $gneralaccount->chart_account_id,
                                    'transaction_type' => 'Debit',
                                    'transaction_amount' => $PaymentDetail->generalFundAmount,
                                    'reference' => 'Revenue',
                                    'reference_id' => $revenue->id,
                                    'reference_sub_id' => 0,
                                    'date' => $revenue->date,
                                ];
                                dispatch(new AddTransactionLinesJob($data1, $creatorId, $buildingId));
                                // Utility::addTransactionLines($data);
                                $reserveaccount = BankAccount::find($reserveFundAccount->id);
                                $data2 = [
                                    'account_id' => $reserveaccount->chart_account_id,
                                    'transaction_type' => 'Debit',
                                    'transaction_amount' => $PaymentDetail->reservedFundAmount,
                                    'reference' => 'Revenue',
                                    'reference_id' => $revenue->id,
                                    'reference_sub_id' => 0,
                                    'date' => $revenue->date,
                                ];
                                dispatch(new AddTransactionLinesJob($data2, $creatorId, $buildingId));
                                // Utility::addTransactionLines($data);
                            } else {
                                $account = BankAccount::find($generalFundAccount->id);
                                $data3 = [
                                    'account_id' => $account->chart_account_id,
                                    'transaction_type' => 'Debit',
                                    'transaction_amount' => $netAmount,
                                    'reference' => 'Revenue',
                                    'reference_id' => $revenue->id,
                                    'reference_sub_id' => 0,
                                    'date' => $revenue->date,
                                ];
                                dispatch(new AddTransactionLinesJob($data3, $creatorId, $buildingId));
                                // Utility::addTransactionLines($data);
                            }

                            if ($receipt->payment_mode == 'Noqodi Payment') {
                                $PaymentDetail = json_decode($receipt->noqodi_info);
                                $invoiceId = Invoice::where([
                                    'building_id' => $receipt->building_id,
                                    'flat_id' => $receipt->flat_id,
                                    'ref_number' => $PaymentDetail->invoiceNumber
                                ])->first()?->id;
                            } else {
                                $invoiceId = Invoice::where([
                                    'building_id' => $receipt->building_id,
                                    'flat_id' => $receipt->flat_id,
                                    'invoice_period' => $receipt->receipt_period
                                ])->first()?->id;
                            }
                            if ($invoiceId) {
                                InvoiceRevenue::updateOrCreate([
                                    'invoice_id' => $invoiceId,
                                    'revenue_id' => $revenue->id,
                                ], [
                                    'adjusted_amount' => $receipt->receipt_amount,
                                ]);
                                $totalTransfer = DB::table('invoice_revenue')->where('invoice_id', $invoiceId)->sum('adjusted_amount');
                                $invoice = Invoice::find($invoiceId);
                                $status = $totalTransfer >= $invoice->getTotal() ? 4 : 3;
                                $invoice->update(['status' => $status]);
                                $revenueCustomerDetail = RevenueCustomerDetail::updateOrCreate(
                                    [
                                        'revenue_id' => $revenue->id,
                                        'customer_id' => $customerId,
                                        'invoice_id' => $invoiceId,
                                    ],
                                    [
                                        'reference_type' => 'against_ref',
                                        'amount' => $receipt->receipt_amount,
                                        'reference_details' => null,
                                    ]
                                );
                            } else {
                                $revenueCustomerDetail = RevenueCustomerDetail::updateOrCreate(
                                    [
                                        'revenue_id' => $revenue->id,
                                        'customer_id' => $customerId,
                                    ],
                                    [
                                        'reference_type' => 'new_ref',
                                        'amount' => $receipt->receipt_amount,
                                        'reference_details' => null,
                                    ]
                                );
                            }
                            $ids[] = $receipt->id;
                            $successCount++;
                        } else {
                            $customerCount++;
                            Log::info('Receipt sync skipped', [
                                'receipt_number' => $receipt->receipt_number,
                                'flat_id' => $receipt->flat_id,
                                'building_id' => $buildingId,
                                'reason' => 'Customer not found'
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errorCount++;
                        $errors[] = "Receipt {$receipt->receipt_number}: {$e->getMessage()}";

                        Log::error('Receipt sync error', [
                            'receipt_number' => $receipt->receipt_number,
                            'error' => $e->getMessage(),
                            'user_id' => $authUser->id,
                            'building_id' => $buildingId
                        ]);
                    }
                }
                if (isset($ids) && !empty($ids)) {
                    DB::connection(env('SECOND_DB_CONNECTION'))->table('oam_receipts')->whereIn('id', $ids)->update(['is_sync' => 1]);
                }
                DB::commit();

                // Generate appropriate response based on results
                if ($successCount > 0 && $errorCount === 0) {
                    return redirect()->back()->with(
                        'success',
                        __('Successfully synced :count receipts.', ['count' => $successCount])
                    );
                } elseif ($successCount > 0 && $errorCount > 0) {
                    $message = __('Partially completed: :success receipts synced, :errors failed.', [
                        'success' => $successCount,
                        'errors' => $errorCount
                    ]);

                    if (count($errors) <= 5) {
                        $message .= ' Errors: ' . implode('; ', $errors);
                    }

                    return redirect()->back()->with('warning', $message);
                } elseif ($customerCount > 0 && $errorCount === 0) {
                    return redirect()->back()->with(
                        'error',
                        __('Customer not found for synced :count receipts Please Check Logs.', ['count' => $customerCount])
                    );
                } else {
                    return redirect()->back()->with(
                        'error',
                        __('Failed to sync any receipts. Errors: :errors', [
                            'errors' => implode('; ', array_slice($errors, 0, 5))
                        ])
                    );
                }
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('Receipt sync transaction failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $authUser->id,
                    'building_id' => $buildingId,
                    'date_range' => "{$fromDate} to {$toDate}"
                ]);

                return redirect()->back()
                    ->with('error', __('Sync operation failed. All changes have been rolled back.'))
                    ->withInput();
            }
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Receipt sync general error', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', __('An unexpected error occurred. Please try again or contact support.'))
                ->withInput();
        }
    }

    /**
     * Validate receipt data before processing
     */
    private function validateReceiptData($receipt): bool
    {
        return !empty($receipt->receipt_number)
            && !empty($receipt->receipt_amount)
            && !empty($receipt->receipt_date)
            && !empty($receipt->flat_id)
            && is_numeric($receipt->receipt_amount)
            && $receipt->receipt_amount > 0;
    }
    public function import(Request $request)
    {

        $rules = [
            'file' => 'required|mimes:csv,txt,xls,xlsx',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $customers = (new CustomerImport())->toArray(request()->file('file'))[0];

        // Check if the file is completely empty
        if (empty($customers) || count($customers) == 0) {
            return redirect()->back()->with('error', __('The file is empty and contains no data.'));
        }
        // Extract headers
        $fileHeaders = array_map('trim', $customers[0]);
        // Expected headers
        $expectedHeaders = [
            'Name',
            'Email',
            'Password',
            'Contact',
            'Billing Name',
            'Billing Country',
            'Billing State',
            'Billing City',
            'Billing Phone',
            'Billing Zip',
            'Billing Address',
            'Shipping Name',
            'Shipping Country',
            'Shipping State',
            'Shipping City',
            'Shipping Phone',
            'Shipping Zip',
            'Shipping Address'
        ];
        // Check if there are no headings (e.g., all values are empty in the header row)
        $hasHeaders = !empty(array_filter($fileHeaders));
        // If no headings and no data after the first row, treat it as an empty file
        if (!$hasHeaders && count($customers) == 1) {
            return redirect()->back()->with('error', __('The file does not contain any headings or data.'));
        }
        // If there are no valid headings but data exists, return an error
        if (!$hasHeaders) {
            return redirect()->back()->with('error', __('The file is missing the required headings.'));
        }
        // Validate if the headers match the expected format
        if ($fileHeaders !== $expectedHeaders) {
            return redirect()->back()->with('error', __('The file headers do not match the expected format.'));
        }
        // Validate if the headers match the expected format
        if (count($customers) == 1) {
            return redirect()->back()->with('error', __('The file is empty no data found.'));
        }

        $totalCustomer = count($customers) - 1;
        $errorArray    = [];
        $errorRows = [];
        $customer_id = $this->customerNumber();

        // Required fields indexes based on header order
        $requiredFieldsIndexes = [0, 1, 3]; // Corresponding to Name, Email, Contact

        for ($i = 1; $i <= count($customers) - 1; $i++) {
            $cust_id = $customer_id++;
            $customer = $customers[$i];

            // Check required fields
            foreach ($requiredFieldsIndexes as $index) {
                if (empty($customer[$index])) {
                    $errorArray[] = [
                        'row' => $i + 1, // adding 1 because $i is 0-based
                        'error' => __('The ' . $expectedHeaders[$index] . ' field is required at row ' . ($i + 1))
                    ];
                    $errorRows[] = $i + 1;
                    continue 2; // Skip to the next iteration of the loop if a required field is missing
                }
            }

            $customerByEmail = Customer::where('email', $customer[1])->first();
            if (!empty($customerByEmail)) {
                $customerData = $customerByEmail;
            } else {
                $customerData = new Customer();
                $customerData->customer_id      = $this->customerNumber();
            }
            //            dd($customer);

            $customerData->name             = $customer[0];
            $customerData->email            = $customer[1];
            $customerData->password         = Hash::make($customer[2]);
            $customerData->contact          = $customer[3];
            $customerData->billing_name     = $customer[4];
            $customerData->billing_country  = $customer[5];
            $customerData->billing_state    = $customer[6];
            $customerData->billing_city     = $customer[7];
            $customerData->billing_phone    = $customer[8];
            $customerData->billing_zip      = $customer[9];
            $customerData->billing_address  = $customer[10];
            $customerData->shipping_name    = $customer[11];
            $customerData->shipping_country = $customer[12];
            $customerData->shipping_state   = $customer[13];
            $customerData->shipping_city    = $customer[14];
            $customerData->shipping_phone   = $customer[15];
            $customerData->shipping_zip     = $customer[16];
            $customerData->shipping_address = $customer[17];
            $customerData->lang             = 'en';
            $customerData->is_active        = 1;
            $customerData->created_by       = \Auth::user()->creatorId();

            if (empty($customerData)) {
                $errorArray[] = $customerData;
            } else {
                $customerData->save();

                $role_r = Role::where('name', '=', 'customer')->firstOrFail();
                $customerData->assignRole($role_r);
            }
        }

        $errorRecord = [];
        if (empty($errorArray)) {
            $data['status'] = 'success';
            $data['msg']    = __('Record successfully imported');
        } else {
            $data['status'] = 'error';
            $data['msg'] = count($errorArray) . ' ' . __('records failed out of' . ' ' . $totalCustomer . ' ' . 'records. Errors found in rows: ' . implode(', ', array_unique($errorRows)));


            foreach ($errorArray as $errorData) {
                $errorRecord[] = 'Row ' . $errorData['row'] . ': ' . $errorData['error'];
            }

            \Session::put('errorArray', $errorRecord);
        }

        return redirect()->back()->with($data['status'], $data['msg']);
    }
    public function previewInvoice()
    {

        $objUser  = \Auth::user();
        $settings = Utility::settings();

        $invoice  = new Invoice();

        $customer                   = new \stdClass();
        $customer->email            = '<Email>';
        $customer->shipping_name    = '<Customer Name>';
        $customer->shipping_country = '<Country>';
        $customer->shipping_state   = '<State>';
        $customer->shipping_city    = '<City>';
        $customer->shipping_phone   = '<Customer Phone Number>';
        $customer->shipping_zip     = '<Zip>';
        $customer->shipping_address = '<Address>';
        $customer->billing_name     = '<Customer Name>';
        $customer->billing_country  = '<Country>';
        $customer->billing_state    = '<State>';
        $customer->billing_city     = '<City>';
        $customer->billing_phone    = '<Customer Phone Number>';
        $customer->billing_zip      = '<Zip>';
        $customer->billing_address  = '<Address>';
        $invoice->sku               = 'Test123';

        $totalTaxPrice = 0;
        $taxesData     = [];

        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item           = new \stdClass();
            $item->name     = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax      = 5;
            $item->discount = 50;
            $item->price    = 100;

            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice         = 10;
                $totalTaxPrice    += $taxPrice;
                $itemTax['name']  = 'Tax ' . $k;
                $itemTax['rate']  = '10 %';
                $itemTax['price'] = '$10';
                $itemTaxes[]      = $itemTax;
                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[]       = $item;
        }

        $invoice->invoice_id = 1;
        $invoice->issue_date = date('Y-m-d H:i:s');
        $invoice->due_date   = date('Y-m-d H:i:s');
        $invoice->itemData   = $items;

        $invoice->totalTaxPrice = 60;
        $invoice->totalQuantity = 3;
        $invoice->totalRate     = 300;
        $invoice->totalDiscount = 10;
        $invoice->taxesData     = $taxesData;
        $invoice->customField   = [];
        $customFields           = [];

        $preview    = 1;


        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));

        return view('customer.show', compact('invoice', 'preview', 'img', 'settings', 'customer', 'customFields'));
    }

    public function statement(Request $request, $id)
    {
        $customer = Customer::find($id);
        $settings = Utility::settings();
        $customerDetail       = Customer::findOrFail($customer['id']);
        $invoice   = Invoice::where('created_by', '=', \Auth::user()->creatorId())->where('customer_id', '=', $customer->id)->get()->pluck('id');
        // $invoice_payment = StakeholderTransactionLine::where('customer_id', $customer->id)
        //     ->where('created_by', \Auth::user()->creatorId())
        //     ->where('building_id', \Auth::user()->building_id);
        if (!empty($request->from_date) && !empty($request->until_date)) {
            //     $invoice_payment->whereBetween('date',  [$request->from_date, $request->until_date]);

            $data['from_date']  = $request->from_date;
            $data['until_date'] = $request->until_date;
        } else {
            $data['from_date']  = date('Y-m-t');
            $data['until_date'] = date('Y-m-t');
            //     $invoice_payment->whereBetween('date',  [$data['from_date'], $data['until_date']]);
        }
        $invoice_payment = StakeholderTransactionLine::getCustomerStatement(
            $customer->id,
            $data['from_date'],
            $data['until_date']
        );

        // $invoice_payment = $invoice_payment->orderBy('date')->get();
        //        dd($invoice_payment);
        $user = \Auth::user();
        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));

        $invoice_id = Invoice::where('created_by', '=', \Auth::user()->creatorId())->where('customer_id', '=', $customer->id)->first();
        // dd($customer->id, $invoice_id);

        if (!empty($invoice_id)) {
            $invoice_total = Invoice::find($invoice_id->id);
            $invoicePayment = InvoicePayment::where('invoice_id', $invoice_total->id)->first();
            $customer = $invoice_total->customer;
            $iteams   = $invoice_total->items;

            $invoice_total->customField = CustomField::getData($invoice_total, 'invoice');
        } else {
            $invoice_total = 0;
            $invoicePayment = 0;
        }
        // dump($invoice_payment);exit;
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'invoice')->get();

        return view('customer.statement', compact('customer', 'img', 'user', 'customerDetail', 'invoice_payment', 'settings', 'data', 'invoice_total'));
    }

    public function customerPassword($id)
    {
        $eId        = \Crypt::decrypt($id);
        $customer = Customer::find($eId);

        return view('customer.reset', compact('customer'));
    }

    public function customerPasswordReset(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'password' => 'required|confirmed|same:password_confirmation',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }


        $customer                 = Customer::where('id', $id)->first();
        $customer->forceFill([
            'password' => Hash::make($request->password),
            'is_enable_login' => 1,
        ])->save();

        return redirect()->route('customer.index')->with(
            'success',
            'Customer Password successfully updated.'
        );
    }
}
