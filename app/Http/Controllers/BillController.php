<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Bill;
use App\Models\User;
use App\Models\Vender;
use App\Models\Utility;
use App\Models\DebitNote;
use App\Exports\BillExport;
use App\Models\BankAccount;
use App\Models\BillAccount;
use App\Models\BillPayment;
use App\Models\BillProduct;
use App\Models\CustomField;
use App\Models\StockReport;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\ProductService;
use App\Models\TransactionLines;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Crypt;
use App\Models\ProductServiceCategory;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\VenderController;

class BillController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage bill')) {

            $vender = Vender::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $status = Bill::$statues;

            $query = Bill::where('created_by', '=', Auth::user()->creatorId());
            if (! empty($request->vender)) {
                $query->where('vender_id', '=', $request->vender);
            }

            if (str_contains($request->bill_date, ' to ')) {
                $date_range = explode(' to ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            } elseif (! empty($request->bill_date)) {

                $query->where('bill_date', $request->bill_date);
            }

            // if (!empty($request->bill_date)) {
            //     $date_range = explode(' to ', $request->bill_date);
            //     $query->whereBetween('bill_date', $date_range);
            // }

            if (! empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $bills = $query->get();

            return view('bill.index', compact('bills', 'vender', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create($vendorId)
    {

        if (Auth::user()->can('create bill')) {
            $customFields = CustomField::where('created_by', '=', Auth::user()->creatorId())->where('module', '=', 'bill')->get();
            $category = ProductServiceCategory::where('created_by', Auth::user()->creatorId())->where('type', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $bill_number = Auth::user()->billNumberFormat($this->billNumber());
            $venders = Vender::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('Select Vendor', '');

            // $product_services = ProductService::where('created_by', Auth::user()->creatorId())
            //     ->whereHas('unit', function ($query) {
            //         $query->where('name', 'Expense');
            //     })->get()->pluck('name', 'id');
            $product_services = ProductService::where('created_by', Auth::user()->creatorId())
                ->whereHas('unit', function ($query) {
                    $query->where('name', 'Expense');
                })
                ->get()
                ->mapWithKeys(function ($productService) {
                    return [
                        $productService->id => $productService->name.' - '.$productService->service_code,
                    ];
                });

            $product_services->prepend('Select Item', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', Auth::user()->creatorId())
                ->whereHas('types', function ($query) {
                    $query->where('name', 'Expenses');
                })
                ->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $subAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
            $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
            $subAccounts->where('chart_of_accounts.parent', '!=', 0);
            $subAccounts->where('chart_of_accounts.created_by', Auth::user()->creatorId());
            $subAccounts = $subAccounts->get()->toArray();
            $vatChargeId=DB::table('settings')->where('name', 'vat_charge')->first()->value;
            $vatCharge=DB::table('chart_of_accounts')->where('id', $vatChargeId)->first()->name;
            return view('bill.create', compact('venders', 'bill_number', 'product_services', 'category', 'customFields', 'vendorId', 'chartAccounts', 'subAccounts','vatCharge'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create bill')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'vender_id' => 'required',
                    'bill_date' => 'required',
                    'due_date' => 'required',
                    'category_id' => 'required',
                    'services' => 'required|array',
                ]
            );

            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->getMessageBag()->first());
            }
            $vatChargeId=DB::table('settings')->where('name', 'vat_charge')->first()->value;
            if(!$vatChargeId){
                return redirect()->back()->with('error', 'VAT Charge not found.');
            }
            $totalAmount = array_sum(array_column($request->services, 'amount')); 
            $vatChargeAmount = array_sum(array_column($request->services, 'tax_amount'));
            $totalAmount += $vatChargeAmount;           
            $created_by = Auth::user()->creatorId();
            $buildingId = Auth::user()->currentBuilding();
            $invoiceId = null;
            $bill = $this->createBill(
                $created_by,
                $buildingId,
                $invoiceId,
                $request->vender_id,
                $request->bill_date,
                $request->due_date,
                $request->category_id,
                $request->services,
                $request->order_number,
                isset($request->discount_apply),
                $request->customField,
                $request->description,
                $totalAmount
            );

            return redirect()->route('bill.index', $bill->id)->with('success', __('Bill successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function venderNumber()
    {
        $latest = Vender::where('created_by', '=', Auth::user()->creatorId())->latest()->first();
        if (! $latest) {
            return 1;
        }

        return $latest->customer_id + 1;
    }

    public function show($ids)
    {

        if (Auth::user()->can('show bill')) {
            try {
                $id = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Bill Not Found.'));
            }

            $id = Crypt::decrypt($ids);
            $bill = Bill::with('debitNote', 'payments.bankAccount', 'items.product.unit')->find($id);

            if (! empty($bill) && $bill->created_by == Auth::user()->creatorId()) {
                $billPayment = BillPayment::where('bill_id', $bill->id)->first();
                $vendor = $bill->vender;

                $item = $bill->items;
                $accounts = $bill->accounts;
                $items = [];
                if (! empty($item) && count($item) > 0) {
                    foreach ($item as $k => $val) {
                        if (! empty($accounts[$k])) {
                            $val['chart_account_id'] = $accounts[$k]['chart_account_id'];
                            $val['account_id'] = $accounts[$k]['id'];
                            $val['amount'] = $accounts[$k]['price'];
                        }
                        $items[] = $val;
                    }
                } else {

                    foreach ($accounts as $k => $val) {
                        $val1['chart_account_id'] = $accounts[$k]['chart_account_id'];
                        $val1['account_id'] = $accounts[$k]['id'];
                        $val1['amount'] = $accounts[$k]['price'];
                        $items[] = $val1;
                    }
                }

                $bill->customField = CustomField::getData($bill, 'bill');
                $customFields = CustomField::where('created_by', '=', Auth::user()->creatorId())->where('module', '=', 'bill')->get();

                return view('bill.view', compact('bill', 'vendor', 'items', 'billPayment', 'customFields'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($ids)
    {
        if (Auth::user()->can('edit bill')) {
            $id = Crypt::decrypt($ids);
            $bill = Bill::find($id);
            $category = ProductServiceCategory::where('created_by', Auth::user()->creatorId())->where('type', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $bill_number = Auth::user()->billNumberFormat($bill->bill_id);
            $venders = Vender::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
            // $product_services = ProductService::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $product_services = ProductService::where('created_by', Auth::user()->creatorId())

                ->get()
                ->mapWithKeys(function ($productService) {
                    return [
                        $productService->id => $productService->name.' - '.$productService->service_code,
                    ];
                });

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $subAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
            $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
            $subAccounts->where('chart_of_accounts.parent', '!=', 0);
            $subAccounts->where('chart_of_accounts.created_by', Auth::user()->creatorId());
            $subAccounts = $subAccounts->get()->toArray();

            $vatChargeId=DB::table('settings')->where('name', 'vat_charge')->first()->value;
            $vatCharge=DB::table('chart_of_accounts')->where('id', $vatChargeId)->first()->name;
            
            $BankAccounts=DB::table('bill_accounts')->where('ref_id', $bill->id)->orderBy('id', 'asc')->get();
            return view('bill.edit', compact('venders', 'product_services', 'bill', 'bill_number', 'category', 'chartAccounts', 'subAccounts','vatCharge','BankAccounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Bill $bill)
    {

        if (Auth::user()->can('edit bill')) {

            if ($bill->created_by == Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'vender_id' => 'required',
                        'bill_date' => 'required',
                        'due_date' => 'required',
                        'category_id' => 'required',
                        'services' => 'required|array',
                    ]
                );
    
                if ($validator->fails()) {
                    return redirect()->back()->with('error', $validator->getMessageBag()->first());
                }
                $vatChargeId=DB::table('settings')->where('name', 'vat_charge')->first()->value;
                if(!$vatChargeId){
                    return redirect()->back()->with('error', 'VAT Charge not found.');
                }
                $totalAmount = array_sum(array_column($request->services, 'amount')); 
                $vatChargeAmount = array_sum(array_column($request->services, 'tax_amount'));
                $totalAmount += $vatChargeAmount;           
                $created_by = Auth::user()->creatorId();
                $totalPaid = $bill->payments()->sum('amount');
                $totalDue = $totalAmount - $totalPaid;
                $bill->vender_id = $request->vender_id;
                $bill->bill_date = $request->bill_date;
                $bill->due_date = $request->due_date;
                $bill->order_number = $request->order_number;
                $bill->category_id = $request->category_id;
                $bill->total_amount = $totalAmount;
                $bill->total_due = $totalDue;
                $bill->save();
                TransactionLines::deleteAndRecalculateTransactionBalance($bill, 'Bill');
                TransactionLines::deleteAndRecalculateTransactionBalance($bill, 'Bill Account');
                $items = $request->services;
                BillAccount::where('ref_id', $bill->id)->delete();
                BillProduct::where('bill_id', $bill->id)->delete();
                foreach ($items as $product) {
                    if (! empty($product['chart_account_id'])) {
                        $billAccount = new BillAccount;
                        $billAccount->chart_account_id = $product['chart_account_id'];
                        $billAccount->price = $product['amount'] ?? 0;
                        $billAccount->description = $product['description'];
                        $billAccount->type = 'Bill';
                        $billAccount->ref_id = $bill->id;
                        $billAccount->vat_chart_of_account_id = $vatChargeId;
                        $billAccount->vat_amount = $product['tax_amount'] ?? 0;
                        $billAccount->total_amount = $product['amount']+$product['tax_amount'] ?? 0;
                        $billAccount->save();
                    }
                    if (! empty($product['item_id'])) {
                    $billProduct = new BillProduct;
                    $billProduct->bill_id = $bill->id;
                    $billProduct->product_id = $product['item_id'];
                    $billProduct->quantity = $product['quantity'];
                    $billProduct->tax = $product['tax_rate'];
                    $billProduct->discount = $product['discount'];
                    $billProduct->price = $product['unit_price'];
                    $billProduct->description = $product['description'];
                    $billProduct->tax_amount = $product['tax_amount'];
                    $billProduct->bill_account_id = $billAccount->id;
                    $billProduct->save();
                    }
        
                    Utility::total_quantity('plus', $billProduct->quantity, $billProduct->product_id);
        
                    // Product Stock Report
                    if (! empty($product['item_id'])) {
                        $type = 'bill';
                        $type_id = $bill->id;
                        $productDescription = $product['quantity'].' '.__('quantity purchase in bill').' '.Vender::billNumberFormat($bill->bill_id); // changes Auth::user()->
                        Utility::addInvoiceProductStock($product['item_id'], $product['quantity'], $type, $productDescription, $type_id, $created_by);
                    }
                }
                return redirect()->route('bill.index')->with('success', __('Bill successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Bill $bill)
    {
        if (Auth::user()->can('delete bill')) {
            if ($bill->created_by == Auth::user()->creatorId()) {
                $billpayments = $bill->payments;

                foreach ($billpayments as $key => $value) {
                    Utility::bankAccountBalance($value->account_id, $value->amount, 'credit');
                    $transaction = Transaction::where('payment_id', $value->id)->first();
                    $transaction->delete();

                    $billpayment = BillPayment::find($value->id)->first();
                    $billpayment->deleteVendorTransactionLine();
                    $billpayment->delete();
                }
                $bill->delete();

                if ($bill->vender_id != 0 && $bill->status != 0) {
                    $bill->deleteVendorTransactionLine();
                }
                BillProduct::where('bill_id', '=', $bill->id)->delete();

                DebitNote::where('bill', '=', $bill->id)->delete();

                // TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill')->delete();
                TransactionLines::deleteAndRecalculateTransactionBalance($bill, 'Bill');
                TransactionLines::deleteAndRecalculateTransactionBalance($bill, 'Bill Account');
                TransactionLines::deleteAndRecalculateTransactionBalance($bill, 'Bill Payment');
                // TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Account')->delete();
                // TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Payment')->delete();

                return redirect()->route('bill.index')->with('success', __('Bill successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function billNumber($created_by = null)
    {
        $latest = Utility::getValByName('bill_starting_number');
        $latest = Bill::where('created_by', '=', $created_by ?? Auth::user()->creatorId())->orderByDesc('bill_id')->first();
        if (! $latest) {
            return 1;
        }

        return $latest->bill_id + 1;
        // return $latest;
    }

    public function product(Request $request)
    {
        $data['product'] = $product = ProductService::find($request->product_id);
        $data['unit'] = ! empty($product->unit()->name) ? $product->unit()->name : 0;
        $data['taxRate'] = $taxRate = ! empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes'] = ! empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice = $product->purchase_price;
        $quantity = 1;
        $taxPrice = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function productDestroy(Request $request)
    {

        if (Auth::user()->can('delete bill product')) {
            $billProduct = BillProduct::find($request->id);
            $bill = Bill::find($billProduct->bill_id);

            $productService = ProductService::find($billProduct->product_id);

            TransactionLines::where('reference_sub_id', $productService->id)->where('reference', 'Bill')->delete();

            $accountIds = TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill')->pluck('account_id');
            foreach ($accountIds as $accountId) {
                TransactionLines::recalculateTransactionBalance($accountId, $bill->created_at);
            }

            BillProduct::where('id', '=', $request->id)->delete();
            BillAccount::where('id', '=', $request->account_id)->delete();
            $bill->updateVendorBalance();

            return redirect()->back()->with('success', __('Bill product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function sent($id)
    {
        if (Auth::user()->can('send bill')) {
            $bill = Bill::where('id', $id)->first();
            $bill->send_date = date('Y-m-d');
            $bill->status = 1;
            $bill->save();

            $vender = Vender::where('id', $bill->vender_id)->first();

            $bill->name = ! empty($vender) ? $vender->name : '';
            $bill->bill = Auth::user()->billNumberFormat($bill->bill_id);

            $billId = Crypt::encrypt($bill->id);
            $bill->url = route('bill.pdf', $billId);
            $vendorLedger = ChartOfAccount::where('name', $vender->name)->where('building_id', $bill->building_id)->first();
            if ($vendorLedger) {
                $data = [
                    'account_id' => $vendorLedger->id,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $bill->total_amount,
                    'reference' => 'Bill',
                    'reference_id' => $bill->id,
                    'reference_sub_id' => 0,
                    'date' => $bill->bill_date,
            ];
            Utility::addTransactionLines($data);
            }

            $bill->updateVendorBalance();

            $uArr = [
                'bill_name' => $bill->name,
                'bill_number' => $bill->bill,
                'bill_url' => $bill->url,
            ];

            $bill_products = BillProduct::where('bill_id', $bill->id)->get();
            foreach ($bill_products as $bill_product) {
                $product = ProductService::find($bill_product->product_id);
                $data = [
                    'account_id' => $bill_product->product_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $bill_product->price,
                    'reference' => 'Bill',
                    'reference_id' => $bill->id,
                    'reference_sub_id' => $product->id,
                    'date' => $bill->bill_date,
                ];
                Utility::addTransactionLines($data);
            }

            $vatChargeId=DB::table('settings')->where('name', 'vat_charge')->first()->value;
            $billTotalTax = BillProduct::where('bill_id', $bill->id)->sum('tax_amount');

            $data = [
                'account_id' => $vatChargeId,
                'transaction_type' => 'Debit',
                'transaction_amount' => $billTotalTax,
                'reference' => 'Bill',
                'reference_id' => $bill->id,
                'reference_sub_id' => $bill->items->pluck('tax')->join(','),
                'date' => $bill->bill_date,
            ];
            Utility::addTransactionLines($data, $bill->created_by, $bill->building_id);

            $bill_accounts = BillAccount::where('ref_id', $bill->id)->get();
            foreach ($bill_accounts as $bill_product) {
                $data = [
                    'account_id' => $bill_product->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $bill_product->price,
                    'reference' => 'Bill Account',
                    'reference_id' => $bill_product->ref_id,
                    'reference_sub_id' => $bill_product->id,
                    'date' => $bill->bill_date,
                ];
                Utility::addTransactionLines($data);
            }
            try {
                $resp = Utility::sendEmailTemplate('bill_sent', [$vender->id => $vender->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->back()->with('success', __('Bill successfully sent.').((isset($smtp_error)) ? '<br> <span class="text-danger">'.$smtp_error.'</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {

        if (Auth::user()->can('send bill')) {
            $bill = Bill::where('id', $id)->first();

            $vender = Vender::where('id', $bill->vender_id)->first();

            $bill->name = ! empty($vender) ? $vender->name : '';
            $bill->bill = Auth::user()->billNumberFormat($bill->bill_id);

            $billId = Crypt::encrypt($bill->id);
            $bill->url = route('bill.pdf', $billId);

            $uArr = [
                'bill_name' => $bill->name,
                'bill_number' => $bill->bill,
                'bill_url' => $bill->url,
            ];
            try {
                $resp = Utility::sendEmailTemplate('bill_sent', [$vender->id => $vender->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->back()->with('success', __('Bill successfully sent.').((isset($smtp_error)) ? '<br> <span class="text-danger">'.$smtp_error.'</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function payment($bill_id)
    {
        if (Auth::user()->can('create payment bill')) {
            $bill = Bill::where('id', $bill_id)->first();
            $venders = Vender::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('name', 'id');

            $categories = ProductServiceCategory::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $totalAmount = $bill->total_amount;
            $totalPaid = $bill->payments()->sum('amount');
            $totalDue = $totalAmount - $totalPaid;
            return view('bill.payment', compact('venders', 'categories', 'accounts', 'bill', 'totalAmount', 'totalPaid', 'totalDue'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function createPayment(Request $request, $bill_id)
    {
        if (Auth::user()->can('create payment bill')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'amount' => 'required',
                    'bank_details' => 'required|array',
                    'reference' => 'nullable|unique:bill_payments',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $bill = Bill::where('id', $bill_id)->first();
            $total = array_sum(array_column($request->bank_details, 'amount'));  
            if($total>$bill->total_due){
                return redirect()->back()->with('error', __('Payment amount is greater than amount.'));
            }            
            if (! isset($request->reference)) {
                $reference = crc32(BillPayment::latest()->first()?->id + 1);
            }
            if ($bill->status == 0) {
                $bill->send_date = date('Y-m-d');
                $bill->total_due = $bill->total_due-$total;
                $bill->save();
            }         
            $due=$bill->total_due-$total;
            if ($due <= 0) {
                $bill->status = 4;
                $bill->total_due = $bill->total_due-$total;
                $bill->save();
            } else {
                $bill->status = 3;
                $bill->total_due = $bill->total_due-$total;
                $bill->save();
            }
            foreach($request->bank_details as $bankaccount)
            {
                $billPayment = new BillPayment;
                $billPayment->bill_id = $bill_id;
                $billPayment->date = $request->date;
                $billPayment->amount = $bankaccount['amount'] ?? 0;
                $billPayment->account_id = $bankaccount['account_id'];
                $billPayment->payment_method = 0;
                $billPayment->reference = $request->reference ?? $reference;
                $billPayment->description = $request->description;
                $billPayment->building_id = Auth::user()->currentBuilding();
                if (! empty($request->add_receipt)) {
                    $fileName = time().'_'.$request->add_receipt->getClientOriginalName();
                    $fileContent = file_get_contents($request->add_receipt);
                    Storage::disk('s3')->put($fileName, $fileContent);
                    $billPayment->add_receipt = $fileName;
                }
                $billPayment->save();
                $billPayment->updateVendorBalance();

                
                $billPayment->user_id = $bill->vender_id;
                $billPayment->user_type = 'Vender';
                $billPayment->type = 'Partial';
                $billPayment->created_by = Auth::user()->id;
                $billPayment->payment_id = $billPayment->id;
                $billPayment->category = 'Bill';
                $billPayment->account = $billPayment->account_id;
                Transaction::addTransaction($billPayment);
                Utility::bankAccountBalance($bankaccount['account_id'], $bankaccount['amount'], 'debit');
            }
            $billPayments = BillPayment::where('bill_id', $bill->id)->get();
            foreach ($billPayments as $billPayment) {
                $accountId = BankAccount::find($billPayment->account_id);

                $data = [
                    'account_id' => $accountId->chart_account_id,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $billPayment->amount,
                    'reference' => 'Bill Payment',
                    'reference_id' => $bill->id,
                    'reference_sub_id' => $billPayment->id,
                    'date' => $billPayment->date,
                ];
                Utility::addTransactionLines($data);
            }
            $vender = Vender::where('id', $bill->vender_id)->first();
            $payment = new BillPayment;
            $payment->name = $vender['name'];
            $payment->method = '-';
            $payment->date = Auth::user()->dateFormat($request->date);
            $payment->amount = Auth::user()->priceFormat($total);
            $payment->bill = 'bill '.Auth::user()->billNumberFormat($billPayment->bill_id);

            $uArr = [
                'payment_name' => $payment->name,
                'payment_bill' => $payment->bill,
                'payment_amount' => $payment->amount,
                'payment_date' => $payment->date,
                'payment_method' => $payment->method,

            ];
            try {
                $resp = Utility::sendEmailTemplate('new_bill_payment', [$vender->id => $vender->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }
            //updating opening balance in lazim
            $connection = DB::connection('mysql_lazim');
            $lazim_Invoice = $connection->table('invoices')->where('id', $bill->lazim_invoice_id);
            Log::info('Lazim Invoice==>', [$lazim_Invoice->first()]);
            $totalAmount = BillPayment::where('bill_id', $bill->id)->sum('amount');
            Log::info('Lazim Invoice==>', [$totalAmount]);
            $invoice_amount = $lazim_Invoice->first()?->invoice_amount;
            if (isset($lazim_Invoice) && $invoice_amount > 0) {
                Log::info('Lazim Invoice==>', [$invoice_amount]);
                $lazim_Invoice->update([
                    'opening_balance' => $invoice_amount - $total < 0 ? 0 : $invoice_amount - $total,
                    'balance' => $invoice_amount - $total < 0 ? 0 : $invoice_amount - $total,
                    'payment' => $total,
                ]);
            }
            $vendorLedger = ChartOfAccount::where('name', $vender->name)->where('building_id', $bill->building_id)->first();
            if ($vendorLedger) {
                $data = [
                    'account_id' => $vendorLedger->id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $total,
                    'reference' => 'Bill Payment',
                    'reference_id' => $bill->id,
                    'reference_sub_id' => 0,
                    'date' => $bill->bill_date,
            ];
            Utility::addTransactionLines($data);
            }
            return redirect()->back()->with('success', __('Payment successfully added.').((isset($smtp_error)) ? '<br> <span class="text-danger">'.$smtp_error.'</span>' : ''));
        }
    }

    public function paymentDestroy(Request $request, $bill_id, $payment_id)
    {
        if (Auth::user()->can('delete payment bill')) {
            $payment = BillPayment::find($payment_id);
            BillPayment::where('id', '=', $payment_id)->delete();

            $bill = Bill::where('id', $bill_id)->first();

            $due = $bill->getDue();
            $total = $bill->getTotal();

            if ($due > 0 && $total != $due) {
                $bill->status = 3;
            } else {
                $bill->status = 2;
            }
            TransactionLines::where('reference_sub_id', $payment_id)->where('reference', 'Bill Payment')->delete();

            $payment->deleteVendorTransactionLine();

            Utility::bankAccountBalance($payment->account_id, $payment->amount, 'credit');

            $bill->save();

            $accountIds = TransactionLines::where('reference_id', $bill_id)->where('reference', 'Bill Payment')->pluck('account_id');
            foreach ($accountIds as $accountId) {
                TransactionLines::recalculateTransactionBalance($accountId, $bill->created_at);
            }

            $type = 'Partial';
            $user = 'Vender';
            Transaction::destroyTransaction($payment_id, $type, $user);

            return redirect()->back()->with('success', __('Payment successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function venderBill(Request $request)
    {
        if (Auth::user()->can('manage vender bill')) {

            $status = Bill::$statues;

            $query = Bill::where('vender_id', '=', Auth::user()->vender_id)->where('status', '!=', '0')->where('created_by', Auth::user()->creatorId());

            if (! empty($request->vender)) {
                $query->where('id', '=', $request->vender);
            }
            if (str_contains($request->bill_date, ' to ')) {
                $date_range = explode(' to ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            } elseif (! empty($request->bill_date)) {

                $query->where('bill_date', $request->bill_date);
            }

            // if (!empty($request->bill_date)) {
            //     $date_range = explode(' to ', $request->bill_date);
            //     $query->whereBetween('bill_date', $date_range);
            // }

            if (! empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $bills = $query->get();

            return view('bill.index', compact('bills', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function venderBillShow($id)
    {
        if (Auth::user()->can('show bill')) {
            $bill_id = Crypt::decrypt($id);
            $bill = Bill::where('id', $bill_id)->first();

            if ($bill->created_by == Auth::user()->creatorId()) {
                $vendor = $bill->vender;
                $iteams = $bill->items;
                $items = [];

                return view('bill.view', compact('bill', 'vendor', 'iteams', 'items'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function vender(Request $request)
    {
        $vender = Vender::where('id', '=', $request->id)->first();

        return view('bill.vender_detail', compact('vender'));
    }

    public function venderBillSend($bill_id)
    {
        return view('vender.bill_send', compact('bill_id'));
    }

    public function venderBillSendMail(Request $request, $bill_id)
    {

        $validator = \Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $email = $request->email;
        $bill = Bill::where('id', $bill_id)->first();

        $vender = Vender::where('id', $bill->vender_id)->first();
        $bill->name = ! empty($vender) ? $vender->name : '';
        $bill->bill = Auth::user()->billNumberFormat($bill->bill_id);

        $billId = Crypt::encrypt($bill->id);
        $bill->url = route('bill.pdf', $billId);

        $uArr = [
            'bill_name' => $bill->name,
            'bill_number' => $bill->bill,
            'bill_url' => $bill->url,
        ];
        try {
            $resp = Utility::sendEmailTemplate('vendor_bill_sent', [$vender->id => $vender->email], $uArr);
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        return redirect()->back()->with('success', __('Bill successfully sent.').((isset($smtp_error)) ? '<br> <span class="text-danger">'.$smtp_error.'</span>' : ''));
    }

    public function shippingDisplay(Request $request, $id)
    {
        $bill = Bill::find($id);

        if ($request->is_display == 'true') {
            $bill->shipping_display = 1;
        } else {
            $bill->shipping_display = 0;
        }
        $bill->save();

        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($bill_id)
    {
        if (Auth::user()->can('duplicate bill')) {
            $bill = Bill::where('id', $bill_id)->first();

            $duplicateBill = new Bill;
            $duplicateBill->bill_id = $this->billNumber();
            $duplicateBill->vender_id = $bill['vender_id'];
            $duplicateBill->bill_date = date('Y-m-d');
            $duplicateBill->due_date = $bill['due_date'];
            $duplicateBill->send_date = null;
            $duplicateBill->category_id = $bill['category_id'];
            $duplicateBill->order_number = $bill['order_number'];
            $duplicateBill->status = 0;
            $duplicateBill->shipping_display = $bill['shipping_display'];
            $duplicateBill->created_by = $bill['created_by'];
            $duplicateBill->save();

            if ($duplicateBill) {
                $billProduct = BillProduct::where('bill_id', $bill_id)->get();
                foreach ($billProduct as $product) {
                    $duplicateProduct = new BillProduct;
                    $duplicateProduct->bill_id = $duplicateBill->id;
                    $duplicateProduct->product_id = $product->product_id;
                    $duplicateProduct->quantity = $product->quantity;
                    $duplicateProduct->tax = $product->tax;
                    $duplicateProduct->discount = $product->discount;
                    $duplicateProduct->price = $product->price;
                    $duplicateProduct->save();
                }
            }

            return redirect()->back()->with('success', __('Bill duplicate successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function previewBill($template, $color)
    {
        $objUser = Auth::user();
        $settings = Utility::settings();
        $bill = new Bill;

        $vendor = new \stdClass;
        $vendor->email = '<Email>';
        $vendor->shipping_name = '<Vendor Name>';
        $vendor->shipping_country = '<Country>';
        $vendor->shipping_state = '<State>';
        $vendor->shipping_city = '<City>';
        $vendor->shipping_phone = '<Vendor Phone Number>';
        $vendor->shipping_zip = '<Zip>';
        $vendor->shipping_address = '<Address>';
        $vendor->billing_name = '<Vendor Name>';
        $vendor->billing_country = '<Country>';
        $vendor->billing_state = '<State>';
        $vendor->billing_city = '<City>';
        $vendor->billing_phone = '<Vendor Phone Number>';
        $vendor->billing_zip = '<Zip>';
        $vendor->billing_address = '<Address>';
        $vendor->sku = 'Test123';

        $totalTaxPrice = 0;
        $taxesData = [];
        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item = new \stdClass;
            $item->name = 'Item '.$i;
            $item->quantity = 1;
            $item->tax = 5;
            $item->discount = 50;
            $item->price = 100;

            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice = 10;
                $totalTaxPrice += $taxPrice;
                $itemTax['name'] = 'Tax '.$k;
                $itemTax['rate'] = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[] = $itemTax;

                // $taxPrice         = 10;
                // $totalTaxPrice    += $taxPrice;
                // $itemTax['name']  = 'Tax ' . $k;
                // $itemTax['rate']  = '10 %';
                // $itemTax['price'] = '$10';
                // $itemTaxes[]      = $itemTax;
                if (array_key_exists('Tax '.$k, $taxesData)) {
                    $taxesData['Tax '.$k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax '.$k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[] = $item;
        }

        $bill->bill_id = 1;
        $bill->bill_date = date('Y-m-d H:i:s');
        $bill->due_date = date('Y-m-d H:i:s');
        $bill->itemData = $items;

        $bill->totalTaxPrice = 60;
        $bill->totalQuantity = 3;
        $bill->totalRate = 300;
        $bill->totalDiscount = 10;
        $bill->taxesData = $taxesData;
        $bill->customField = [];
        $customFields = [];

        $preview = 1;
        $color = '#'.$color;
        $font_color = Utility::getFontColor($color);
        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $bill_logo = Utility::getValByName('bill_logo');
        if (isset($bill_logo) && ! empty($bill_logo)) {

            $img = Utility::get_file($bill_logo);

            $img = Utility::get_file('bill_logo/').$bill_logo;

            // $img = asset(\Storage::url('bill_logo/') . $bill_logo);
        } else {
            $img = asset($logo.'/'.(isset($company_logo) && ! empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        return view('bill.templates.'.$template, compact('bill', 'preview', 'color', 'img', 'settings', 'vendor', 'font_color', 'customFields'));
    }

    public function bill($bill_id)
    {
        $settings = Utility::settings();
        $billId = Crypt::decrypt($bill_id);

        $bill = Bill::where('id', $billId)->first();
        $data = DB::table('settings');
        $data = $data->where('created_by', '=', $bill->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $vendor = $bill->vender;

        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];
        $items = [];

        foreach ($bill->items as $product) {

            $item = new \stdClass;
            $item->name = ! empty($product->product) ? $product->product->name : '';
            $item->quantity = $product->quantity;
            $item->tax = $product->tax;
            $item->discount = $product->discount;
            $item->price = $product->price;
            $item->description = $product->description;

            $totalQuantity += $item->quantity;
            $totalRate += $item->price;
            $totalDiscount += $item->discount;

            $taxes = Utility::tax($product->tax);
            $itemTaxes = [];
            if (! empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name'] = $tax->name;
                    $itemTax['rate'] = $tax->rate.'%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[] = $itemTax;

                    if (array_key_exists($tax->name, $taxesData)) {
                        $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                    } else {
                        $taxesData[$tax->name] = $taxPrice;
                    }
                }

                $item->itemTax = $itemTaxes;
            } else {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $bill->itemData = $items;
        $bill->totalTaxPrice = $totalTaxPrice;
        $bill->totalQuantity = $totalQuantity;
        $bill->totalRate = $totalRate;
        $bill->totalDiscount = $totalDiscount;
        $bill->taxesData = $taxesData;
        $bill->customField = CustomField::getData($bill, 'bill');
        $customFields = [];
        if (! empty(Auth::user())) {
            $customFields = CustomField::where('created_by', '=', Auth::user()->creatorId())->where('module', '=', 'bill')->get();
        }

        //Set your logo
        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($bill->created_by);
        $bill_logo = $settings_data['bill_logo'];
        if (isset($bill_logo) && ! empty($bill_logo)) {
            // $img = Utility::get_file('bill_logo') . $bill_logo;
            $img = asset($logo.'/'.(isset($company_logo) && ! empty($company_logo) ? $company_logo : 'logo-dark.png'));

            // $img = asset(\Storage::url('bill_logo/') . $bill_logo);
            // $img = Utility::get_file($bill_logo);
        } else {
            $img = asset($logo.'/'.(isset($company_logo) && ! empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($bill) {
            $color = '#'.$settings['bill_color'];
            $font_color = Utility::getFontColor($color);

            return view('bill.templates.'.$settings['bill_template'], compact('bill', 'color', 'settings', 'vendor', 'img', 'font_color', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function saveBillTemplateSettings(Request $request)
    {
        $user = Auth::user();
        $post = $request->all();
        unset($post['_token']);

        if ($request->bill_logo) {
            $request->validate(
                [
                    'bill_logo' => 'image',
                ]
            );

            $dir = 'bill_logo/';
            $bill_logo = $user->id.'_bill_logo.png';
            $validation = [
                'mimes:'.'png',
                'max:'.'20480',
            ];

            $path = Utility::upload_file($request, 'bill_logo', $bill_logo, $dir, $validation);
            if ($path['flag'] == 1) {
                $retainer_logo = $path['url'];
            } else {
                return redirect()->back()->with('error', __($path['msg']));
            }

            // $path                 = $request->file('bill_logo')->storeAs('/bill_logo', $bill_logo);
            $post['bill_logo'] = $bill_logo;
        }

        if (isset($post['bill_template']) && (! isset($post['bill_color']) || empty($post['bill_color']))) {
            $post['bill_color'] = 'ffffff';
        }

        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    Auth::user()->creatorId(),
                ]
            );
        }

        return redirect()->back()->with('success', __('Bill Setting updated successfully'));
    }

    public function items(Request $request)
    {
        $items = BillProduct::where('bill_id', $request->bill_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }

    public function paybill($bill_id)
    {

        if (! empty($bill_id)) {
            $id = \Illuminate\Support\Facades\Crypt::decrypt($bill_id);

            $bill = bill::where('id', $id)->first();

            if (! is_null($bill)) {

                $settings = Utility::settings();

                $items = [];
                $totalTaxPrice = 0;
                $totalQuantity = 0;
                $totalRate = 0;
                $totalDiscount = 0;
                $taxesData = [];

                foreach ($bill->items as $item) {
                    $totalQuantity += $item->quantity;
                    $totalRate += $item->price;
                    $totalDiscount += $item->discount;
                    $taxes = Utility::tax($item->tax);

                    $itemTaxes = [];
                    foreach ($taxes as $tax) {
                        if (! empty($tax)) {
                            $taxPrice = Utility::taxRate($tax->rate, $item->price, $item->quantity);
                            $totalTaxPrice += $taxPrice;
                            $itemTax['tax_name'] = $tax->tax_name;
                            $itemTax['tax'] = $tax->tax.'%';
                            $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                            $itemTaxes[] = $itemTax;

                            if (array_key_exists($tax->name, $taxesData)) {
                                $taxesData[$itemTax['tax_name']] = $taxesData[$tax->tax_name] + $taxPrice;
                            } else {
                                $taxesData[$tax->tax_name] = $taxPrice;
                            }
                        } else {
                            $taxPrice = Utility::taxRate(0, $item->price, $item->quantity);
                            $totalTaxPrice += $taxPrice;
                            $itemTax['tax_name'] = 'No Tax';
                            $itemTax['tax'] = '';
                            $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                            $itemTaxes[] = $itemTax;

                            if (array_key_exists('No Tax', $taxesData)) {
                                $taxesData[$tax->tax_name] = $taxesData['No Tax'] + $taxPrice;
                            } else {
                                $taxesData['No Tax'] = $taxPrice;
                            }
                        }
                    }
                    $item->itemTax = $itemTaxes;
                    $items[] = $item;
                }
                $bill->items = $items;
                $bill->totalTaxPrice = $totalTaxPrice;
                $bill->totalQuantity = $totalQuantity;
                $bill->totalRate = $totalRate;
                $bill->totalDiscount = $totalDiscount;
                $bill->taxesData = $taxesData;
                $ownerId = $bill->created_by;
                $company_setting = Utility::settingById($ownerId);
                $payment_setting = Utility::bill_payment_settings($ownerId);

                $users = User::where('id', $bill->created_by)->first();

                if (! is_null($users)) {
                    \App::setLocale($users->lang);
                } else {
                    $users = User::where('type', 'owner')->first();
                    \App::setLocale($users->lang);
                }

                $bill = bill::where('id', $id)->first();
                $customer = $bill->customer;
                $iteams = $bill->items;
                $company_payment_setting = Utility::getCompanyPaymentSetting($bill->created_by);

                return view('bill.billpay', compact('bill', 'iteams', 'company_setting', 'users', 'payment_setting'));
            } else {
                return abort('404', 'The Link You Followed Has Expired');
            }
        } else {
            return abort('404', 'The Link You Followed Has Expired');
        }
    }

    public function pdffrombill($id)
    {
        $settings = Utility::settings();

        $billId = Crypt::decrypt($id);
        $bill = bill::where('id', $billId)->first();

        $data = \DB::table('settings');
        $data = $data->where('created_by', '=', $bill->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $user = new User;
        $user->name = $bill->name;
        $user->email = $bill->contacts;
        $user->mobile = $bill->contacts;

        $user->bill_address = $bill->billing_address;
        $user->bill_zip = $bill->billing_postalcode;
        $user->bill_city = $bill->billing_city;
        $user->bill_country = $bill->billing_country;
        $user->bill_state = $bill->billing_state;

        $user->address = $bill->shipping_address;
        $user->zip = $bill->shipping_postalcode;
        $user->city = $bill->shipping_city;
        $user->country = $bill->shipping_country;
        $user->state = $bill->shipping_state;

        $items = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];

        foreach ($bill->items as $product) {
            $item = new \stdClass;
            $item->name = $product->item;
            $item->quantity = $product->quantity;
            $item->tax = ! empty($product->taxs) ? $product->taxs->rate : '';
            $item->discount = $product->discount;
            $item->price = $product->price;

            $totalQuantity += $item->quantity;
            $totalRate += $item->price;
            $totalDiscount += $item->discount;

            $taxes = \Utility::tax($product->tax);
            $itemTaxes = [];
            foreach ($taxes as $tax) {
                $taxPrice = \Utility::taxRate($tax->rate, $item->price, $item->quantity);
                $totalTaxPrice += $taxPrice;

                $itemTax['name'] = $tax->tax_name;
                $itemTax['rate'] = $tax->rate.'%';
                $itemTax['price'] = \App\Models\Utility::priceFormat($settings, $taxPrice);
                $itemTaxes[] = $itemTax;

                if (array_key_exists($tax->tax_name, $taxesData)) {
                    $taxesData[$tax->tax_name] = $taxesData[$tax->tax_name] + $taxPrice;
                } else {
                    $taxesData[$tax->tax_name] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[] = $item;
        }

        $bill->items = $items;
        $bill->totalTaxPrice = $totalTaxPrice;
        $bill->totalQuantity = $totalQuantity;
        $bill->totalRate = $totalRate;
        $bill->totalDiscount = $totalDiscount;
        $bill->taxesData = $taxesData;

        //Set your logo
        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($bill->created_by);
        $bill_logo = $settings_data['bill_logo'];
        if (isset($bill_logo) && ! empty($bill_logo)) {
            $img = asset(\Storage::url('bill_logo/').$bill_logo);
        } else {
            $img = asset($logo.'/'.(isset($company_logo) && ! empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($bill) {
            $color = '#'.$settings['bill_color'];
            $font_color = Utility::getFontColor($color);

            return view('bill.templates.'.$settings['bill_template'], compact('bill', 'user', 'color', 'settings', 'img', 'font_color'));
        } else {
            return redirect()->route('pay.billpay', \Illuminate\Support\Facades\Crypt::encrypt($billId))->with('error', __('Permission denied.'));
        }
    }

    public function export()
    {
        $name = 'customer_'.date('Y-m-d i:h:s');
        $data = Excel::download(new BillExport, $name.'.xlsx');

        return $data;
    }

    public function createBillFromLazim(Request $request)
    {
        $orderNumber = null;
        $discountApply = false;
        $customField = [];
        $description = null;

        return $this->createBill(
            $request->created_by,
            $request->buildingId,
            $request->invoiceId,
            $request->venderId,
            $request->billDate,
            $request->dueDate,
            $request->categoryId,
            $request->items,
            $orderNumber,
            $discountApply,
            $customField,
            $description,
            $request->chartAccountId
        );
    }

    public function createBill($created_by, $buildingId, $invoiceId, $venderId, $billDate, $dueDate, $categoryId, $items, $orderNumber = null, $discountApply = false, $customField = [], $description = null, $totalAmount = null)
    {

        $reference = crc32(Bill::latest()->first()?->id + 1);
        $bill = new Bill;
        $bill->bill_id = $this->billNumber($created_by);
        $bill->vender_id = $venderId;
        $bill->bill_date = $billDate;
        $bill->status = 0;
        $bill->due_date = $dueDate;
        $bill->category_id = $categoryId;
        $bill->order_number = $orderNumber ?? 0;
        $bill->discount_apply = $discountApply ? 1 : 0;
        $bill->created_by = $created_by ?? Auth::user()->creatorId();
        $bill->building_id = $buildingId;
        $bill->lazim_invoice_id = $invoiceId;
        $bill->ref_number = $reference;
        $bill->total_amount = $totalAmount;
        $bill->total_due = $totalAmount;

        $bill->save();
        Utility::starting_number($bill->bill_id + 1, 'bill', $created_by); // changed added created_by
        if (!empty($customField)) {
            CustomField::saveData($bill, $customField);
        }
        $vatChargeId=DB::table('settings')->where('name', 'vat_charge')->first()->value;
        foreach ($items as $product) {
            if (! empty($product['chart_account_id'])) {
                $billAccount = new BillAccount;
                $billAccount->chart_account_id = $product['chart_account_id'];
                $billAccount->price = $product['amount'] ?? 0;
                $billAccount->description = $product['description'] ?? null;
                $billAccount->type = 'Bill';
                $billAccount->ref_id = $bill->id;
                $billAccount->vat_chart_of_account_id = $vatChargeId;
                $billAccount->vat_amount = $product['tax_amount'] ?? 0;
                $billAccount->total_amount = $product['amount']+$product['tax_amount'] ?? 0;
                $billAccount->save();
            }
            if (! empty($product['item_id'])) {
            $billProduct = new BillProduct;
            $billProduct->bill_id = $bill->id;
            $billProduct->product_id = $product['item_id'];
            $billProduct->quantity = $product['quantity'];
            $billProduct->tax = 1;
            $billProduct->discount = $product['discount'];
            $billProduct->price = $product['unit_price'];
            $billProduct->description = $product['description'] ?? null;
            $billProduct->tax_amount = $product['tax_amount'];
            $billProduct->bill_account_id = $billAccount->id;
            $billProduct->save();
            }

            Utility::total_quantity('plus', $billProduct->quantity, $billProduct->product_id);

            // Product Stock Report
            if (! empty($product['item_id'])) {
                $type = 'bill';
                $type_id = $bill->id;
                $productDescription = $product['quantity'].' '.__('quantity purchase in bill').' '.Vender::billNumberFormat($bill->bill_id); // changes Auth::user()->
                Utility::addProductStock($product['item_id'], $product['quantity'], $type, $productDescription, $type_id, $created_by);
                // $total_amount += ($billProduct->quantity * $billProduct->price) + $billTotal;
            }
        }

        $this->sendBillNotifications($bill, $venderId, $created_by); // changed added created_by

        return $bill;
    }

    private function sendBillNotifications($bill, $venderId, $created_by) // changed added created_by
    {
        $setting = Utility::settings();
        $billId = Crypt::encrypt($bill->id);
        $bill->url = route('bill.pdf', $billId);
        $vendor = Vender::find($venderId);

        if (isset($setting['bill_notification']) && $setting['bill_notification'] == 1) {
            $uArr = [
                'bill_name' => $vendor->name,
                'bill_number' => Vender::billNumberFormat($bill->bill_id), // changed Auth::user()->
                'bill_url' => $bill->url,
            ];
            Utility::send_twilio_msg($vendor->contact, 'new_bill', $uArr, $created_by); // changed send_twilio_msg($vendor->contact, 'new_bill', $uArr)
        }

        // Webhook
        $module = 'New Bill';
        $webhook = Utility::webhookSetting($module, $created_by); // changed ebhookSetting($module) // changed added created_by

        if ($webhook) {
            $parameter = json_encode($bill);
            Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
        }
    }
    public function billPopup($id)
    {
        return view('bill.billPopup', compact('id'));
    }
    public function syncBill(Request $request,$id)
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
        if (!isset($validated['from_date']) || !isset($validated['to_date'])) {
            return redirect()->back()->with('error', __('From date and to date are required.'));
        }
        if ($validated['from_date'] > $validated['to_date']) {
            return redirect()->back()->with('error', __('From date cannot be greater than to date.'));
        }
        if ($validated['from_date'] == $validated['to_date']) {
            return redirect()->back()->with('error', __('From date and to date cannot be the same.'));
        }
        $vender = Vender::find($id);
        $connection = DB::connection(env('SECOND_DB_CONNECTION'));
        $venderData = $connection->table('vendors')->where('name', $vender->name)->first();
        $buildingId=Auth::user()->currentBuilding();
        $vendorController = new VenderController();
        $result = $vendorController->syncBill($venderData, $vender, $buildingId,$fromDate,$toDate);
        if($result>0){
            return redirect()->back()->with('success', __('Bill synced successfully.'));
        }
        return redirect()->back()->with('error', __('No new bill found to sync.'));
    }
}
