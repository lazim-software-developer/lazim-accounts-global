<?php

namespace App\Http\Controllers;

use Auth;
use File;
use Carbon\Carbon;
use App\Models\Bill;
use App\Models\Plan;
use App\Models\User;
use App\Models\Vender;
use App\Models\Utility;
use App\Models\BillAccount;
use App\Models\BillPayment;
use App\Models\BillProduct;
use App\Models\CustomField;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Exports\VenderExport;
use App\Imports\VenderImport;
use App\Models\BuildingVendor;
use App\Models\ChartOfAccount;
use App\Models\ProductService;
use App\Models\Mail\UserCreate;
use Illuminate\Validation\Rule;
use App\Models\ChartOfAccountType;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\ChartOfAccountParent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ChartOfAccountSubType;
use App\Models\ProductServiceCategory;
use Illuminate\Support\Facades\Storage;

class VenderController extends Controller
{
    public function dashboard()
    {
        $data['billChartData'] = \Auth::user()->billChartData();

        return view('vender.dashboard', $data);
    }

    public function syncVenderFile()
    {
        return view('vender.venderSync');
    }



    public function index()
    {
        if (\Auth::user()->can('manage vender')) {
            $buildingId = \Auth::user()->currentBuilding();
            $getVendors = BuildingVendor::where('building_id', $buildingId)->pluck('vendor_id');
            $venders = Vender::whereIn('id', $getVendors)->get();

            return view('vender.index', compact('venders'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function vendorPopup()
    {
        return view('vender.vendorPopup');
    }

    public function syncVender(Request $request)
    {
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
        if (!isset($validated['from_date']) || !isset($validated['to_date'])) {
            return redirect()->back()->with('error', __('From date and to date are required.'));
        }
        if ($validated['from_date'] > $validated['to_date']) {
            return redirect()->back()->with('error', __('From date cannot be greater than to date.'));
        }
        if ($validated['from_date'] == $validated['to_date']) {
            return redirect()->back()->with('error', __('From date and to date cannot be the same.'));
        }
        try {
            $connection = DB::connection(env('SECOND_DB_CONNECTION'));
            $buildingVendors = $connection->table('building_vendor')->where('building_id', $buildingId)->distinct()->pluck('vendor_id');
            $venders = $connection
                ->table('vendors')
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->whereIn('id', $buildingVendors)
                ->where('is_sync', 0)
                ->orderBy('created_at')
                ->get();
        } catch (\Exception $e) {
            Log::error('External database connection failed', [
                'error' => $e->getMessage(),
                'user_id' => $authUser->id,
                'building_id' => $buildingId
            ]);

            return redirect()
                ->back()
                ->with('error', __('Unable to connect to external database. Please try again later.'))
                ->withInput();
        }

        if ($venders->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', __('No venders found for the selected date range (:from to :to).', [
                    'from' => $fromDate->format('M j, Y'),
                    'to' => $toDate->format('M j, Y')
                ]))
                ->withInput();
        }
        $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->first();
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        foreach ($venders as $vender) {
            $connection = DB::connection(env('SECOND_DB_CONNECTION'));
            $venderData = $connection->table('vendors')->where('id', $vender->id)->first();
            $userDetail = $connection->table('users')->where('id', $venderData->owner_id)->first();
            if ($userDetail) {
                $vender = Vender::updateOrCreate([
                    'name' => $venderData->name,
                    'contact' => $userDetail->phone,
                    'email' => $userDetail->email,
                ], [
                    'vender_id' => $this->venderNumber(),
                    'tax_number' => null,
                    'password' => \Hash::make('Temp@123'),
                    'created_by' => $creatorId,
                    'is_active' => 1,
                    'is_enable_login' => 0,
                    'email_verified_at' => now(),
                    'billing_name' => $venderData->name,
                    'billing_country' => 'UAE',
                    'billing_state' => null,
                    'billing_city' => null,
                    'billing_phone' => $userDetail->phone,
                    'billing_zip' => null,
                    'billing_address' => $venderData->address_line_1,
                    'shipping_name' => $venderData->name,
                    'shipping_country' => 'UAE',
                    'shipping_state' => null,
                    'shipping_city' => null,
                    'shipping_phone' => $userDetail->phone,
                    'shipping_zip' => null,
                    'shipping_address' => $venderData->address_line_1,
                    'lang' => $default_language->value ?? null,
                    'balance' => 0,
                    'initial_balance' => 0,
                    'building_id' => $buildingId,
                    'is_mollak' => 1,
                ]);
                $this->BuildingVendor($venderData->id,$vender->id);
                $successCount++;
                $ids[] = $venderData->id;
                $role_r = Role::where('name', '=', 'vender')->firstOrFail();
                if ($role_r) {
                    $vender->assignRole($role_r);
                }
                $types = ChartOfAccountType::where('building_id', Auth::user()->currentBuilding())->where('name', 'Liabilities')->first();
                if (!$types) {
                    $types = ChartOfAccountType::updateOrCreate([
                        'name' => 'Liabilities',
                        'building_id' => Auth::user()->currentBuilding(),
                        'created_by' => $authUser->creatorId(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $accountTypes = ChartOfAccountSubType::where('type', $types->id)->where('name', 'Current Liabilities')->where('building_id', Auth::user()->currentBuilding())->first();
                if (!$accountTypes) {
                    $accountTypes = ChartOfAccountSubType::updateOrCreate([
                        'name' => 'Current Liabilities',
                        'type' => $types->id,
                        'building_id' => Auth::user()->currentBuilding(),
                        'created_by' => $authUser->creatorId(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                $AccountDetail = ChartOfAccount::where('building_id', $buildingId)->where('code', 2140)->first();
                if (!$AccountDetail) {
                    $AccountDetail = ChartOfAccount::updateOrCreate([
                        'name' => 'Sundry Creditors',
                        'code' => 2140,
                        'type' => $types->id,
                        'sub_type' => $accountTypes->id,
                        'parent' => 0,
                        'description' => 'Sundry Creditors',
                        'building_id' => $buildingId,
                        'created_by' => $authUser->creatorId(),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
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
                                'building_id' => $buildingId,
                                'code' => $AccountDetail->code,
                                'name' => $venderData->name,
                            ],
                            [
                                'type' => $types->id,
                                'sub_type' => $accountTypes->id,
                                'parent' => $parentAccount->id ?? 0,
                                'description' => 'Chart Of Account for Vender ID: ' . $venderData->id,
                                'is_enabled' => 1,
                                'is_sync' => 1,
                                'created_by' => $authUser->creatorId(),
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'initial_balance' => 0,
                            ]
                        );
                    } else {
                        Log::error('Failed to sync vender for vender ID: ' . $venderData->id, [
                            'building_id' => $buildingId,
                            'error' => 'Account Detail or Types not found for building ID: ' . $buildingId,
                            'user_id' => $authUser->id
                        ]);
                    }
                } else {
                    Log::error('Failed to sync vender for vender ID: ' . $venderData->id, [
                        'building_id' => $buildingId,
                        'error' => 'Account Detail or Types not found for building ID: ' . $buildingId,
                        'user_id' => $authUser->id
                    ]);
                }
            } else {
                Log::error('Unable to fetch user detail', [
                    'date' => date('Y-m-d H:i:s'),
                    'vendor_id' => $venderData->id,
                    'owner_id' => $venderData->owner_id
                ]);
            }
            // $this->syncBill($venderData, $vender, $buildingId);
        }
        $connection = DB::connection(env('SECOND_DB_CONNECTION'));
        $connection->table('vendors')->whereIn('id', $ids)->update(['is_sync' => 1]);
        return redirect()->back()->with(
            'success',
            __('Successfully synced :count venders.', ['count' => $successCount])
        );
    }
    protected function BuildingVendor($venderId,$id)
    {
        $connection = DB::connection(env('SECOND_DB_CONNECTION'));
        $venderData = $connection->table('building_vendor')->where('vendor_id', $venderId)->distinct('building_id')->pluck('building_id');
        foreach ($venderData as $value) {
            BuildingVendor::updateOrCreate(['building_id' => $value, 'vendor_id' => $id]);
        }
    }
    public function syncBill($venderData, $vender, $buildingId, $fromDate, $toDate)
    {
        $authUser=Auth::user();
        $secondDB = DB::connection(env('SECOND_DB_CONNECTION'));
        $invoices = $secondDB->table('invoices')->where('status', 'approved')->where('vendor_id', $venderData->id)
            ->whereBetween('date', [$fromDate, $toDate])->where('building_id', $buildingId)->where('is_sync', 0)->get();
        $successCount = 0;
        $ids = [];
        foreach ($invoices as $invoice) {
            $contractDetail = $secondDB->table('contracts')->where('id', $invoice->contract_id)->where('building_id', $buildingId)->first();
            if ($contractDetail) {
                $wdaDetails = $secondDB->table('wda')->where('contract_id', $invoice->contract_id)->where('vendor_id', $venderData->id)->where('building_id', $buildingId)->first();
                if ($wdaDetails) {
                    $serviceDetail = $secondDB->table('services')->where('id', $contractDetail->service_id)->first();
                    if ($serviceDetail) {
                        $subCategory = ProductService::where('name', $serviceDetail->name)->where('building_id', $buildingId)->first();
                        if (!$subCategory) {
                            $subCategory = ProductService::updateOrCreate(
                                [
                                    'name' => $serviceDetail->name,
                                    'building_id' => $buildingId,
                                    'created_by' => Auth::user()->creatorId(),
                                    'service_code' => 'A1.01',
                                ],
                                [
                                    'sku' => 'Cl846',
                                    'tax_id' => 1,
                                    'category_id' => 7,
                                    'unit_id' => 2,
                                    'type' => 'Service',
                                    'sale_chartaccount_id' => 1,
                                    'expense_chartaccount_id' => 25,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ]
                            );
                        }
                        if ($subCategory) {
                            $bill = Bill::updateOrCreate(
                                [
                                    'vender_id' => $vender->id,
                                    'building_id' => $buildingId,
                                    'category_id' => $subCategory->category_id,
                                    'created_by' => Auth::user()->creatorId(),
                                ],
                                [
                                    'bill_id' => $this->billNumber(Auth::user()->creatorId()),
                                    'bill_date' => $invoice->date,
                                    'due_date' => $invoice->date,
                                    'order_number' => $this->OrderNumber(Auth::user()->creatorId()),
                                    'status' => 0,
                                    'shipping_display' => 1,
                                    'send_date' => date('Y-m-d H:i:s'),
                                    'discount_apply' => 0,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                    'ref_number' => $invoice->invoice_number,
                                    'lazim_invoice_id' => null,
                                    'is_mollak' => 1,
                                    'total_amount' => $invoice->invoice_amount,
                                    'total_due' => $invoice->invoice_amount,
                                    'wda_document' => $wdaDetails->document ?? null,
                                    'wda_number' => $wdaDetails->wda_number ?? null,
                                ]
                            );
                            Utility::starting_number($bill->bill_id + 1, 'bill', Auth::user()->creatorId());  // changed added created_by
                            $actualAmount = ($invoice->invoice_amount / 21) * 20;
                            $chartOfAccount = ChartOfAccount::where('id', $subCategory->expense_chartaccount_id)->where('building_id', $buildingId)->first();
                            $billAccount = BillAccount::updateOrCreate([
                                'chart_account_id' => $chartOfAccount->id,
                                'ref_id' => $bill->id,
                                'type' => 'Bill',
                                'description' => 'Bill for Invoice ID: ' . $invoice->id,
                            ], [
                                'price' => round($actualAmount, 3),
                            ]);
                            $billProduct = BillProduct::updateOrCreate([
                                'bill_id' => $bill->id,
                                'product_id' => $subCategory->id,
                            ], [
                                'quantity' => 1,
                                'tax' => 1,
                                'discount' => 0,
                                'price' => round($actualAmount, 3),
                                'tax_amount' => round($invoice->invoice_amount - $actualAmount, 3),
                                'description' => 'Bill for Invoice ID: ' . $invoice->id,
                                'bill_account_id' => $billAccount->id,
                            ]);
                            Utility::total_quantity('plus', $billProduct->quantity, $billProduct->product_id);
                            $type = 'bill';
                            $type_id = $bill->id;
                            $productDescription = '1' . ' ' . __('quantity purchase in bill') . ' ' . Vender::billNumberFormat($bill->bill_id);  // changes Auth::user()->
                            Utility::addInvoiceProductStock($subCategory->id, 1, $type, $productDescription, $type_id, Auth::user()->creatorId());
                            BillAccount::updateOrCreate([
                                'chart_account_id' => $chartOfAccount->id,
                                'ref_id' => $bill->id,
                                'type' => 'Bill',
                            ], [
                                'vat_chart_of_account_id' => $vatLedger->id,
                                'vat_amount' => round($invoice->invoice_amount - $actualAmount, 3),
                                'total_amount' => $invoice->invoice_amount,
                            ]);
                        } else {
                            Log::error('Failed to sync bill for vender ID: ' . $venderData->id, [
                                'building_id' => $buildingId,
                                'error' => 'Product Service Not Found For Service ID: ' . $serviceDetail->name,
                                'user_id' => $authUser->id
                            ]);
                        }
                    } else {
                        Log::error('Failed to sync bill for vender ID: ' . $venderData->id, [
                            'building_id' => $buildingId,
                            'error' => 'Service not found for Contract & Service ID: ' . $contractDetail->service_id,
                            'user_id' => $authUser->id
                        ]);
                    }
                } else {
                    Log::error('Failed to sync bill for vender ID: ' . $venderData->id, [
                        'building_id' => $buildingId,
                        'error' => 'WDA not found for WDA ID: ' . $invoice->wda_id,
                        'user_id' => $authUser->id
                    ]);
                }
            } else {
                Log::error('Failed to sync bill for vender ID: ' . $venderData->id, [
                    'building_id' => $buildingId,
                    'error' => 'Contract not found for Contract ID: ' . $invoice->contract_id,
                    'user_id' => $authUser->id
                ]);
            }
            $successCount++;
            $ids[] = $invoice->id;
        }
        $connection = DB::connection(env('SECOND_DB_CONNECTION'));
        $connection->table('invoices')->whereIn('id', $ids)->update(['is_sync' => 1]);
        return $successCount;
    }

    public function billNumber($created_by = null)
    {
        $latest = Utility::getValByName('bill_starting_number');
        $latest = Bill::where('created_by', '=', $created_by ?? Auth::user()->creatorId())->orderByDesc('bill_id')->first();
        if (!$latest) {
            return 1;
        }

        return $latest->bill_id + 1;
        // return $latest;
    }

    public function OrderNumber($created_by = null)
    {
        $latestBill = Bill::where('created_by', '=', $created_by ?? Auth::user()->creatorId())->orderByDesc('id')->first();
        if (!$latestBill) {
            return 1;
        }

        return ($latestBill?->order_number ?? 0) + 1;
        // return $latest;
    }

    public function create()
    {
        if (\Auth::user()->can('create vender')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'vendor')->get();

            return view('vender.create', compact('customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create vender')) {
            $rules = [
                'name' => 'required',
                'contact' => 'required',
                'email' => 'required',
                Rule::unique('venders')->where(function ($query) {
                    return $query->where('created_by', \Auth::user()->creatorId());
                }),
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('vender.index')->with('error', $messages->first());
            }

            $enableLogin = 0;
            if (!empty($request->password_switch) && $request->password_switch == 'on') {
                $enableLogin = 1;
                $validator = \Validator::make(
                    $request->all(),
                    ['password' => 'required|min:6']
                );

                if ($validator->fails()) {
                    return redirect()->back()->with('error', $validator->errors()->first());
                }
            }
            $userpassword = $request->input('password');

            $objVendor = \Auth::user();
            $creator = User::find($objVendor->creatorId());
            $total_vendor = $objVendor->countVenders();
            $plan = Plan::find($creator->plan);

            $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->first();
            if ($total_vendor < $plan->max_venders || $plan->max_venders == -1) {
                $vender = new Vender();
                $vender->vender_id = $this->venderNumber();
                $vender->name = $request->name;
                $vender->contact = $request->contact;
                $vender->email = $request->email;
                $vender->tax_number = $request->tax_number;
                $request['password'] = !empty($userpassword) ? \Hash::make($userpassword) : null;
                if (!empty($request['password'])) {
                    $vender->password = $request['password'] ?? null;
                }
                $vender->created_by = \Auth::user()->creatorId();
                $vender->billing_name = $request->billing_name;
                $vender->billing_country = $request->billing_country;
                $vender->billing_state = $request->billing_state;
                $vender->billing_city = $request->billing_city;
                $vender->billing_phone = $request->billing_phone;
                $vender->billing_zip = $request->billing_zip;
                $vender->billing_address = $request->billing_address;
                $vender->shipping_name = $request->shipping_name;
                $vender->shipping_country = $request->shipping_country;
                $vender->shipping_state = $request->shipping_state;
                $vender->shipping_city = $request->shipping_city;
                $vender->shipping_phone = $request->shipping_phone;
                $vender->shipping_zip = $request->shipping_zip;
                $vender->shipping_address = $request->shipping_address;
                $vender->lang = !empty($default_language) ? $default_language->value : '';
                $vender->is_enable_login = $enableLogin;
                $vender->save();
                BuildingVendor::create(['building_id' => \Auth::user()->currentBuilding(), 'vendor_id' => $vender->id]);
                CustomField::saveData($vender, $request->customField);
            } else {
                return redirect()->back()->with('error', __('Your user limit is over, Please upgrade plan.'));
            }

            $role_r = Role::where('name', '=', 'vender')->firstOrFail();
            $vender->assignRole($role_r);  // Assigning role to user

            $uArr = [
                'email' => $vender->email,
                'password' => $request->password,
            ];
            try {
                $resp = Utility::sendEmailTemplate('user_created', [$vender->id => $vender->email], $uArr);
                // Mail::to($vender->email)->send(new UserCreate($vender));
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            // Twilio Notification
            $setting = Utility::settings(\Auth::user()->creatorId());
            if (isset($setting['vender_notification']) && $setting['vender_notification'] == 1) {
                $uArr = [
                    'vender_name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                ];
                Utility::send_twilio_msg($request->contact, 'new_vendor', $uArr);
            }

            // webhook
            $module = 'New Vendor';
            $webhook = Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($vender);
                // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if ($status == true) {
                    return redirect()->route('vender.index')->with('success', __('Vendor successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }

            return redirect()->route('vender.index')->with('success', __('Vendor successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function show($ids)
    {
        $id = \Crypt::decrypt($ids);
        $vendor = Vender::find($id);

        return view('vender.show', compact('vendor'));
    }

    public function edit($id)
    {
        if (\Auth::user()->can('edit vender')) {
            $vender = Vender::find($id);
            $vender->customField = CustomField::getData($vender, 'vendor');

            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'vendor')->get();

            return view('vender.edit', compact('vender', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, Vender $vender)
    {
        if (\Auth::user()->can('edit vender')) {
            $rules = [
                'name' => 'required',
                'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/',
            ];

            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('vender.index')->with('error', $messages->first());
            }

            $vender->name = $request->name;
            $vender->contact = $request->contact;
            $vender->email = $request->email;
            $vender->tax_number = $request->tax_number;
            $vender->created_by = \Auth::user()->creatorId();
            $vender->billing_name = $request->billing_name;
            $vender->billing_country = $request->billing_country;
            $vender->billing_state = $request->billing_state;
            $vender->billing_city = $request->billing_city;
            $vender->billing_phone = $request->billing_phone;
            $vender->billing_zip = $request->billing_zip;
            $vender->billing_address = $request->billing_address;
            $vender->shipping_name = $request->shipping_name;
            $vender->shipping_country = $request->shipping_country;
            $vender->shipping_state = $request->shipping_state;
            $vender->shipping_city = $request->shipping_city;
            $vender->shipping_phone = $request->shipping_phone;
            $vender->shipping_zip = $request->shipping_zip;
            $vender->shipping_address = $request->shipping_address;
            $vender->save();
            CustomField::saveData($vender, $request->customField);

            return redirect()->route('vender.index')->with('success', __('Vendor successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Vender $vender)
    {
        if (\Auth::user()->can('delete vender')) {
            if ($vender->created_by == \Auth::user()->creatorId()) {
                $vender->delete();

                return redirect()->route('vender.index')->with('success', __('Vendor successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function venderNumber()
    {
        $latest = Vender::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->vender_id + 1;
    }



    public function venderLogout(Request $request)
    {
        \Auth::guard('vender')->logout();

        $request->session()->invalidate();

        return redirect()->route('vender.login');
    }

    public function payment(Request $request)
    {
        if (\Auth::user()->can('manage vender payment')) {
            // $category = [
            //     'Bill' => 'Bill',
            //     'Deposit' => 'Deposit',
            //     'Sales' => 'Sales',
            // ];

            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 2)->get()->pluck('name', 'id');
            // $category->prepend('Bill', '');

            $query = Transaction::where('user_id', \Auth::user()->id)->where('created_by', \Auth::user()->creatorId())->where('user_type', 'Vender')->where('type', 'Payment');
            if (isset($request->date) && !empty($request->date)) {
                $time = strtotime($request->date);
                $month = date('m', $time);

                $query = $query->whereMonth('date', $month);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $payments = $query->get();

            return view('vender.payment', compact('payments', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function transaction(Request $request)
    {
        if (\Auth::user()->can('manage vender transaction')) {
            // $category = [
            //     'Bill' => 'Bill',
            //     'Deposit' => 'Deposit',
            //     'Sales' => 'Sales',
            // ];

            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 2)->get()->pluck('name', 'id');
            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Vender');
            if (isset($request->date) && !empty($request->date)) {
                $time = strtotime($request->date);
                $month = date('m', $time);

                $query = $query->whereMonth('date', $month);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $transactions = $query->get();

            return view('vender.transaction', compact('transactions', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function profile()
    {
        $userDetail = \Auth::user();
        $userDetail->customField = CustomField::getData($userDetail, 'vendor');
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'vendor')->get();

        return view('vender.profile', compact('userDetail', 'customFields'));
    }

    public function editprofile(Request $request)
    {
        $userDetail = \Auth::user();
        $user = Vender::findOrFail($userDetail['id']);

        $this->validate(
            $request,
            [
                'name' => 'required|max:120',
                // 'contact' => 'required',
                'email' => 'required|email|unique:users,email,' . $userDetail['id'],
            ]
        );

        if ($request->hasFile('profile')) {
            if (\Auth::guard('vender')->check()) {
                $file_path = $user['avatar'];
                $filenameWithExt = $request->file('profile')->getClientOriginalName();
                $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension = $request->file('profile')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                $settings = Utility::getStorageSetting();

                if ($settings['storage_setting'] == 'local') {
                    $dir = 'uploads/avatar/';
                } else {
                    $dir = 'uploads/avatar';
                }
                $image_path = $dir . $userDetail['avatar'];

                $url = '';
                // $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);
                // dd($path);
                $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);
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
                    $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension = $request->file('profile')->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    $settings = Utility::getStorageSetting();

                    if ($settings['storage_setting'] == 'local') {
                        $dir = 'uploads/avatar/';
                    } else {
                        $dir = 'uploads/avatar';
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

        return redirect()->back()->with(
            'success',
            __('Profile successfully updated.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '')
        );
    }

    public function editBilling(Request $request)
    {
        $userDetail = \Auth::user();
        $user = Vender::findOrFail($userDetail['id']);
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
        $user = Vender::findOrFail($userDetail['id']);
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
            $objUser = Auth::user();
            $request_data = $request->All();
            $current_password = $objUser->password;
            if (Hash::check($request_data['current_password'], $current_password)) {
                $user_id = Auth::User()->id;
                $obj_user = Vender::find($user_id);
                $obj_user->password = Hash::make($request_data['new_password']);;
                $obj_user->save();

                return redirect()->back()->with('success', __('Password successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Please enter correct current password.'));
            }
        } else {
            return redirect()->back()->with('error', __('Something is wrong.'));
        }
    }

    public function changeLanquage($lang)
    {
        $user = Auth::user();
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
        $name = 'vendor_' . date('Y-m-d i:h:s');
        $data = Excel::download(new VenderExport(), $name . '.xlsx');
        // dd($data);

        return $data;
    }

    public function importFile()
    {
        return view('vender.import');
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

        $vendors = (new VenderImport())->toArray(request()->file('file'))[0];

        // Check if the file is completely empty
        if (empty($vendors) || count($vendors) == 0) {
            return redirect()->back()->with('error', __('The file is empty and contains no data.'));
        }
        // Extract headers
        $fileHeaders = array_map('trim', $vendors[0]);
        // Expected headers
        $expectedHeaders = [
            'Name',
            'Email',
            'Password',
            'Contact',
            'Location',
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
            'Shipping Address',
            'Bank Name',
            'Account Number',
            'Swift Code',
            'IBAN',
            'Beneficiary Name',
            'Currency Name',
            'Company Name',
            'Company Location',
            'Company Store Name',
        ];
        // Check if there are no headings (e.g., all values are empty in the header row)
        $hasHeaders = !empty(array_filter($fileHeaders));
        // If no headings and no data after the first row, treat it as an empty file
        if (!$hasHeaders && count($vendors) == 1) {
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
        if (count($vendors) == 1) {
            return redirect()->back()->with('error', __('The file is empty no data found.'));
        }

        $totalCustomer = count($vendors) - 1;
        $errorArray = [];
        $errorRows = [];

        // Required fields indexes based on header order
        $requiredFieldsIndexes = [0, 1, 3];  // Corresponding to Name, Email, Contact

        for ($i = 1; $i <= count($vendors) - 1; $i++) {
            $vendor = $vendors[$i];

            // Check required fields
            foreach ($requiredFieldsIndexes as $index) {
                if (empty($vendor[$index])) {
                    $errorArray[] = [
                        'row' => $i + 1,  // adding 1 because $i is 0-based
                        'error' => __('The ' . $expectedHeaders[$index] . ' field is required at row ' . ($i + 1))
                    ];
                    $errorRows[] = $i + 1;
                    continue 2;  // Skip to the next iteration of the loop if a required field is missing
                }
            }

            $vendorByEmail = Vender::where('email', $vendor[1])->first();

            if (!empty($vendorByEmail)) {
                $vendorData = $vendorByEmail;
            } else {
                $vendorData = new Vender();
                $vendorData->vender_id = $this->venderNumber();
            }

            $vendorData->name = $vendor[0];
            $vendorData->email = $vendor[1];
            $vendorData->password = Hash::make($vendor[2]);
            $vendorData->contact = $vendor[3];
            $vendorData->is_active = 1;
            $vendorData->billing_name = $vendor[5];
            $vendorData->billing_country = $vendor[6];
            $vendorData->billing_state = $vendor[7];
            $vendorData->billing_city = $vendor[8];
            $vendorData->billing_phone = $vendor[9];
            $vendorData->billing_zip = $vendor[10];
            $vendorData->billing_address = $vendor[11];
            $vendorData->shipping_name = $vendor[12];
            $vendorData->shipping_country = $vendor[13];
            $vendorData->shipping_state = $vendor[14];
            $vendorData->shipping_city = $vendor[15];
            $vendorData->shipping_phone = $vendor[16];
            $vendorData->shipping_zip = $vendor[17];
            $vendorData->shipping_address = $vendor[18];
            $vendorData->balance = $vendor[19] ?? 0;
            $vendorData->lang = 'en';
            $vendorData->created_by = \Auth::user()->creatorId();

            if (empty($vendorData)) {
                $errorArray[] = $vendorData;
            } else {
                $vendorData->save();
                BuildingVendor::create(['building_id' => \Auth::user()->currentBuilding(), 'vendor_id' => $vendorData->id]);
            }
        }

        $errorRecord = [];
        if (empty($errorArray)) {
            $data['status'] = 'success';
            $data['msg'] = __('Record successfully imported');
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

    public function statement(Request $request, $id)
    {
        $vendor = Vender::find($id);
        $vendorDetail = Vender::findOrFail($vendor['id']);
        $settings = Utility::settings();

        $bill = Bill::where('created_by', '=', \Auth::user()->creatorId())->where('vender_id', '=', $vendor->id)->get()->pluck('id');

        $bill_payment = BillPayment::whereIn('bill_id', $bill);
        if (!empty($request->from_date) && !empty($request->until_date)) {
            $bill_payment->whereBetween('date', [$request->from_date, $request->until_date]);
            $data['from_date'] = $request->from_date;
            $data['until_date'] = $request->until_date;
        } else {
            $data['from_date'] = $request->from_date;
            $data['until_date'] = $request->until_date;
            $bill_payment->whereBetween('date', [$data['from_date'], $data['until_date']]);
        }
        $bill_payment = $bill_payment->get();

        $user = \Auth::user();
        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $img = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo.png'));

        return view('vender.statement', compact('vendor', 'vendorDetail', 'img', 'user', 'settings', 'bill_payment', 'data'));
    }

    public function venderPassword($id)
    {
        $eId = \Crypt::decrypt($id);
        $vender = Vender::find($eId);

        return view('vender.reset', compact('vender'));
    }

    public function vendorPasswordReset(Request $request, $id)
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

        $vender = Vender::where('id', $id)->first();
        $vender->forceFill([
            'password' => Hash::make($request->password),
            'is_enable_login' => 1,
        ])->save();

        return redirect()->route('vender.index')->with(
            'success',
            'Vender Password successfully updated.'
        );
    }
}
