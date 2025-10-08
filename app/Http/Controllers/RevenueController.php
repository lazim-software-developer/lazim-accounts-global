<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Revenue;
use App\Models\Utility;
use App\Models\Customer;
use App\Models\BankAccount;
use App\Models\Transaction;
use App\Traits\HelperTrait;
use App\Models\TransferType;
use Illuminate\Http\Request;
use App\Exports\RevenueExport;
// use App\Models\InvoicePayment;
use App\Models\TransactionLines;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\RevenueBankAllocation;
use App\Models\RevenueCustomerDetail;
use Illuminate\Support\Facades\Crypt;
// use Facade\FlareClient\Stacktrace\File;
use App\Models\ProductServiceCategory;
use Illuminate\Support\Facades\Validator;
// use App\Models\Mail\InvoicePaymentCreate;

class RevenueController extends Controller
{
    use HelperTrait;
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage revenue')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        try {

            $customer = Customer::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Owner', '');

            $account = BankAccount::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $category = ProductServiceCategory::where('created_by', '=', Auth::user()->creatorId())->where('type', '=', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');


            $query = Revenue::where('created_by', '=', Auth::user()->creatorId());
            if (str_contains($request->date, ' to ')) {
                $date_range = explode(' to ', $request->date);
                $query->whereBetween('date', $date_range);
            } elseif (!empty($request->date)) {

                $query->where('date', $request->date);
            }

            if (!empty($request->customer)) {
                $query->where('customer_id', '=', $request->customer);
            }
            if (!empty($request->account)) {
                $query->where('account_id', '=', $request->account);
            }

            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }

            if (!empty($request->transfer)) {
                $query->where('transfer_method', '=', $request->transfer);
            }
            $revenues = $query->get();

            return view('revenue.index', compact('revenues', 'customer', 'account', 'category'));
        } catch (\Exception $e) {
            Log::error('####### RevenueController -> index() #######  ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong');
        }
    }


    public function create()
    {
        if (Auth::user()->can('create revenue')) {
            $customers = Customer::where('created_by', '=', Auth::user()->creatorId())->get()->mapWithKeys(function ($customer) {
                return [
                    $customer->id => $customer->property_number . ' - ' . $customer->name
                ];
            });
            $customers->prepend('Select Owner', '');
            $categories = ProductServiceCategory::where('created_by', '=', Auth::user()->creatorId())->where('type', '=', 'income')->get()->pluck('name', 'id');
            $categories->prepend('Select Category', '');
            $accounts   = BankAccount::select('*', DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $flats = DB::connection('mysql_lazim')->table('flats')->where('building_id', Auth::user()->currentBuilding())->get()->pluck('property_number', 'id');

            return view('revenue.create', compact('customers', 'categories', 'accounts', 'flats'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {
        // dd($request->all());
        if (!Auth::user()->can('create revenue')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'customer_details' => 'required|array',
            'bank_details' => 'required|array',
            'category_id' => 'required',
            'add_receipt' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:2048',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $customerDetailArray = $request->customer_details;
        $bankDetailArray = $request->bank_details;
        $customerIds = array_map('strval', array_map('trim', array_column($customerDetailArray, 'customer_id')));
        $customerIds = json_encode(array_values($customerIds));

        $totalAdjustedAmount = array_reduce($customerDetailArray, function ($carry, $customer) {
            $amount = $customer['adjusted_amount'] ?? null;
            return $carry + (is_numeric($amount) ? $amount : 0);
        }, 0);

        $totalBankAmount = array_reduce($bankDetailArray, function ($carry, $bank) {
            $amount = $bank['amount'] ?? null;
            return $carry + (is_numeric($amount) ? $amount : 0);
        }, 0);
        // Add the total for the new invoice being added
        // $totalReceiptAmount = array_sum($request->amount);
        $totalReceiptAmount = $totalAdjustedAmount;
        $totalAdjustedAmount = $totalBankAmount;

        if ($totalAdjustedAmount > $totalReceiptAmount) {
            return redirect()->back()
                ->with('error', 'The total invoice amount is greater than receipt amount');
        }
        DB::beginTransaction();
        try {
            $result = $this->createReceipt(
                $request->date,
                $totalReceiptAmount,
                $request->bank_details[0]['account_id'], // Default to first account for compatibility
                $request->category_id,
                $customerIds,
                $request->reference,
                $request->description,
                $request->add_receipt,
                $customerDetailArray,
            );


            if ($result['status'] == 'error') {
                Log::error("####### RevenueController.php->store:155 ############   " . $result['message']);
                return redirect()->back()->with('error', $result['message']);
            }

            $revenue = $result['revenue'];

            foreach ($customerDetailArray as $index => $customer) {
                RevenueCustomerDetail::create([
                    'revenue_id' => $revenue->id,
                    'customer_id' => $customer['customer_id'],
                    'invoice_number' => $customer['invoice_number'],
                    'amount' => $customer['adjusted_amount'],
                    'reference_type' => $customer['reference_type'],
                    'reference_details' => $customer['ref_details'],
                ]);
            }
            // dd($customerDetailArray);
            // Store bank allocations
            foreach ($bankDetailArray as $index => $bank) {
                $amount = $bank['amount'];
                $vatApplicable = $bank['vat_applicable'] ?? false;
                $vatAmount = $vatApplicable ? ($amount * 0.05) : 0; // 5% VAT
                $netAmount = $amount - $vatAmount;

                RevenueBankAllocation::create([
                    'revenue_id' => $revenue->id,
                    'bank_account_id' => $bank['account_id'],
                    'amount' => $netAmount,
                ]);

                Utility::bankAccountBalance($bank['account_id'], $netAmount, 'credit');
                $account = BankAccount::find($bank['account_id']);
                $data = [
                    'account_id' => $account->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $netAmount,
                    'reference' => 'Revenue',
                    'reference_id' => $revenue->id,
                    'reference_sub_id' => 0,
                    'date' => $revenue->date,
                ];
                Utility::addTransactionLines($data);

                if ($vatAmount > 0) {
                    Transaction::create([
                        'amount' => $vatAmount,
                        'type' => 'VAT',
                        'category' => 'VAT Payable',
                        'user_id' => $revenue->customer_id,
                        'user_type' => 'Customer',
                        'account' => $bank['account_id'],
                        'date' => $revenue->date,
                        'created_by' => Auth::user()->creatorId(),
                    ]);
                }
            }
            foreach ($customerDetailArray as $key => $invoice) {
                $adjustedAmount = $invoice['adjusted_amount'];
                if (isset($invoice['invoice_number']) && !empty($invoice['invoice_number'])) {
                    DB::table('invoice_revenue')->insert([
                        'invoice_number' => $invoice['invoice_number'],
                        'revenue_id' => $revenue->id,
                        'adjusted_amount' => $adjustedAmount,
                    ]);

                    $totalTransfer = DB::table('invoice_revenue')
                        ->where('invoice_number', $invoice['invoice_number'])->sum('adjusted_amount');
                    $invoice = Invoice::find($invoice['invoice_number']);
                    $status = $totalTransfer >= $invoice->getTotal() ? 4 : 3;

                    $invoice->update(['status' => $status]);
                }
            }

            // Step 2: OAM DB me receipt insert karo
            $customer = Customer::find($customerDetailArray[0]['customer_id']);
            $flatId   = $customer?->flat_id ?? null;

            // Building ke owner_association_id nikalna (jaise invoices me kiya tha)
            $ownerAssociationId = DB::connection(env('SECOND_DB_CONNECTION'))
                ->table('buildings')
                ->where('id', $revenue->building_id)
                ->value('owner_association_id');

            $oaReceiptData = [
                'revenue_id'                => $revenue->id,  // primary invoice id ya receipt->id
                'invoice_number'            => $revenue->invoice_number,  // primary invoice id ya receipt->id
                'transaction_method'        => $revenue->payment_method ?? 0,  // primary invoice id ya receipt->id  // primary invoice id ya receipt->id
                'receipt_number'            => $revenue->receipt_number ?? ('REC-' . $revenue->id),
                'receipt_date'              => $revenue->date,
                'receipt_period'            => $revenue->receipt_period, // agar tum calculate karte ho toh fill karo
                'record_source'             => 'ACCOUNTING', // source system ka naam
                'receipt_amount'            => $revenue->amount,
                'receipt_created_date'      => $revenue->created_at,
                'transaction_reference'     => $request->reference,
                'payment_mode'              => $request->payment_method ?? 0,
                'payment_status'            => 'Success', // ya 'Pending' tumhare logic ke hisaab se
                'from_date'                 => "2025-09-12",
                'to_date'                   => "2025-09-12",
                'building_id'               => $revenue->building_id,
                'flat_id'                   => $flatId,
                'created_at'                => $revenue->created_at,
                'updated_at'                => $revenue->updated_at,
                'processed'                 => 0,
                'owner_association_id'      => $ownerAssociationId,
                'is_sync'                   => 1, // mark synced
            ];

            DB::connection(env('SECOND_DB_CONNECTION'))
                ->table('oam_receipts')
                ->insert($oaReceiptData);
            // dd($oaReceiptData);
            // dd($oaReceiptData);
            // foreach ($request->reference_type as $key => $referenceType) { //TODO please review Dillip sir
            //     $invoiceId = $request->invoice_id[$key] ?? 0;
            //     $adjustedAmount = $request->adjusted_amount[$key];

            //     DB::table('invoice_revenue')->insert([
            //         'invoice_id' => $invoiceId,
            //         'revenue_id' => $revenue->id,
            //         'adjusted_amount' => $adjustedAmount,
            //     ]);

            //     // Invoice update sirf tab karo jab invoiceId actual ho
            //     if ($invoiceId) {
            //         $totalTransfer = DB::table('invoice_revenue')->where('invoice_id', $invoiceId)->sum('adjusted_amount');
            //         $invoice = Invoice::find($invoiceId);
            //         $status = $totalTransfer >= $invoice->getTotal() ? 4 : 3;
            //         $invoice->update(['status' => $status]);
            //     }
            // }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("####### RevenueController.php->store:216 ############   " . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('revenue.index')->with('success', $result['message']);
    }


    public function edit(Revenue $revenue)
    {
        if (Auth::user()->can('edit revenue')) {

            // $customers = Customer::where('id', $revenue->customer_id)->get()->pluck('name', 'id');
            // $customers->prepend('--', 0);
            $customers = Customer::where('created_by', Auth::user()->creatorId())
                ->get()
                ->mapWithKeys(function ($customer) {
                    return [
                        $customer->id => $customer->property_number . ' - ' . $customer->name
                    ];
                });
            $customers->prepend('Select Owner', '');
            $categories = ProductServiceCategory::where('created_by', '=', Auth::user()->creatorId())->where('type', '=', 'income')->get()->pluck('name', 'id');
            $accounts   = BankAccount::select('*', DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $flats = DB::connection('mysql_lazim')->table('flats')->where('building_id', Auth::user()->currentBuilding())->get()->pluck('property_number', 'id');
            $invoice_revenue = DB::table('invoice_revenue')->where('revenue_id', $revenue->id);
            $revenueCustomerIds = explode(',', trim($revenue->customer_id, '[]'));
            $revenueCustomerIds = array_map('intval', array_map('trim', $revenueCustomerIds));
            // $allInvoices = Invoice::where('created_by', '=', Auth::user()->creatorId())
            //     ->whereIn('customer_id', json_decode($revenue->customer_id))
            //     ->where('status', '!=', '4')
            //     ->select('invoices.id', 'invoices.issue_date', 'invoices.invoice_id')
            //     ->get()
            //     ->mapWithKeys(function ($invoice) {
            //         $total = $invoice->getTotal(); // Calculate the total using the method
            //         return [
            //             $invoice->id => AUth::user()->invoiceNumberFormat($invoice->invoice_id) . ' - ' . $total,
            //         ];
            //     });
            $allInvoices = Invoice::where('created_by', '=', Auth::user()->creatorId())
                ->whereIn('customer_id', json_decode($revenue->customer_id))
                ->where('status', '!=', 4)
                ->select('invoices.id', 'invoices.issue_date', 'invoices.invoice_number') // 👈 invoice_number use karo
                ->get()
                ->mapWithKeys(function ($invoice) {
                    $total = $invoice->getTotal();
                    return [
                        $invoice->id => $invoice->invoice_number . ' - ' . $total, // 👈 direct column se
                    ];
                });

            //Prepend the placeholder option
            // dd($allInvoices);

            $allInvoices = $allInvoices->prepend('Select an invoice', '');


            $adjusted_amount = $invoice_revenue->first()?->adjusted_amount;
            $invoices = Invoice::select('invoices.*', 'invoice_revenue.adjusted_amount')
                // ->where('status','!=',4)
                ->join('invoice_revenue', 'invoices.id', '=', 'invoice_revenue.invoice_number')
                ->where('invoice_revenue.revenue_id', $revenue->id)
                ->get();

            $invoicesList = Invoice::whereIn('id', DB::table('invoice_revenue')->where('revenue_id', $revenue->id)
                ->pluck('invoice_number'))->get();
            $bankAllocations = $revenue->bankAllocations;
            $customerDetails = RevenueCustomerDetail::where('revenue_id', $revenue->id)->get();
            // dd($customers->toArray(), $revenue->toArray(), $customerDetails->toArray(), $bankAllocations->toArray());
            return view('revenue.edit', compact('customers', 'categories', 'accounts', 'revenue', 'allInvoices', 'invoices', 'bankAllocations', 'customerDetails'));

            // return view('revenue.edit', compact('customers', 'categories', 'accounts', 'revenue', 'flats', 'allInvoices', 'adjusted_amount', 'invoices', 'invoicesList'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, Revenue $revenue)
    {
        // dd($request->all())->toArray();
        if (!Auth::user()->can('edit revenue')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'customer_details' => 'required|array',
            'bank_details' => 'required|array',
            'category_id' => 'required',
            'add_receipt' => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }
        $customerDetailArray = $request->customer_details;
        $bankDetailArray = $request->bank_details;
        $customerIds = array_map('strval', array_map('trim', array_column($customerDetailArray, 'customer_id')));
        $customerIds = json_encode(array_values($customerIds));

        $totalAdjustedAmount = array_reduce($customerDetailArray, function ($carry, $customer) {
            $amount = $customer['adjusted_amount'] ?? null;
            return $carry + (is_numeric($amount) ? $amount : 0);
        }, 0);

        $totalBankAmount = array_reduce($bankDetailArray, function ($carry, $bank) {
            $amount = $bank['amount'] ?? null;
            return $carry + (is_numeric($amount) ? $amount : 0);
        }, 0);
        // Add the total for the new invoice being added
        // $totalReceiptAmount = array_sum($request->amount);
        $totalReceiptAmount = $totalAdjustedAmount;
        $totalAdjustedAmount = $totalBankAmount;
        // $totalReceiptAmount = array_sum($request->amount);
        // $totalAdjustedAmount = array_sum($request->adjusted_amount);

        if ($totalAdjustedAmount > $totalReceiptAmount) {
            return redirect()->back()->with('error', 'The total invoice amount exceeds the receipt amount.');
        }

        DB::beginTransaction();
        try {
            // Reverse existing balances
            Utility::userBalance('customer', $revenue->customer_id, $revenue->amount, 'debit');
            foreach ($revenue->bankAllocations as $allocation) {
                Utility::bankAccountBalance($allocation->bank_account_id, $allocation->amount, 'debit');
            }

            // Update revenue
            $revenue->update([
                'date' => $request->date,
                'amount' => $totalReceiptAmount,
                'category_id' => $request->category_id,
                'customer_id' => $customerIds,
                'reference' => $request->reference,
                'description' => $request->description,
                'is_attend' => 1,
            ]);
            // dd($customerDetailArray);
            RevenueCustomerDetail::where('revenue_id', $revenue->id)->delete();
            foreach ($customerDetailArray as $index => $customer) {
                RevenueCustomerDetail::create([
                    'revenue_id' => $revenue->id,
                    'customer_id' => $customer['customer_id'],
                    'invoice_number' => $customer['invoice_number'],
                    'amount' => $customer['adjusted_amount'],
                    'reference_type' => $customer['reference_type'],
                    'reference_details' => $customer['ref_details'],
                ]);
            }


            // Update bank allocations
            $revenue->bankAllocations()->delete();
            foreach ($bankDetailArray as $index => $bank) {
                $amount = $bank['amount'];
                $vatApplicable = $request->vat_applicable[$index] ?? false;
                $vatAmount = $vatApplicable ? ($amount * 0.05) : 0;
                $netAmount = $amount - $vatAmount;

                RevenueBankAllocation::updateOrCreate([
                    'revenue_id' => $revenue->id,
                    'bank_account_id' => $bank['account_id']
                ], [
                    'amount' => $netAmount,
                ]);

                Utility::bankAccountBalance($bank['account_id'], $netAmount, 'credit');
            }

            // Update invoice payments
            DB::table('invoice_revenue')->where('revenue_id', $revenue->id)->delete();
            foreach ($customerDetailArray as $key => $invoice) {
                if (isset($invoice['invoice_number']) && !empty($invoice['invoice_number'])) {
                    $adjustedAmount = $invoice['adjusted_amount'];
                    DB::table('invoice_revenue')->insert([
                        'invoice_number' => $invoice['invoice_number'],
                        'revenue_id' => $revenue->id,
                        'adjusted_amount' => $adjustedAmount,
                    ]);

                    $totalTransfer = DB::table('invoice_revenue')->where('invoice_number', $invoice['invoice_number'])->sum('adjusted_amount');
                    $invoice = Invoice::find($invoice['invoice_number']);
                    $status = $totalTransfer >= $invoice->getTotal() ? 4 : 3;
                    $invoice->update(['status' => $status]);
                }
                $revenue->updateRevenueCustomerBalance($invoice['customer_id'], $invoice['adjusted_amount'], $revenue->id, $revenue->date);
            }

            // $revenue->updateCustomerBalance();
            Transaction::editTransaction($revenue);

            /**
             * ✅ OAM Receipt Update
             */
            $customer = Customer::find($customerDetailArray[0]['customer_id']);
            $flatId   = $customer?->flat_id ?? null;

            // Building ka owner_association_id nikalna
            $ownerAssociationId = DB::connection(env('SECOND_DB_CONNECTION'))
                ->table('buildings')
                ->where('id', $revenue->building_id)
                ->value('owner_association_id');

            $oaReceiptData = [
                // 'invoice_number'       => $revenue->invoice_number,
                // 'transaction_method'   => $revenue->payment_method ?? 0,
                'receipt_number'       => $revenue->receipt_number ?? ('REC-' . $revenue->id),
                'receipt_date'         => $revenue->date,
                'receipt_period'       => $revenue->receipt_period,
                'record_source'        => 'ACCOUNTING',
                'receipt_amount'       => $revenue->amount,
                'receipt_created_date' => $revenue->created_at,
                'transaction_reference' => $request->reference,
                'payment_mode'         => $request->payment_method ?? 0,
                'payment_status'       => 'Success',
                'from_date'            => now()->toDateString(),
                'to_date'              => now()->toDateString(),
                'building_id'          => $revenue->building_id,
                'flat_id'              => $flatId,
                'updated_at'           => now(),
                'processed'            => 0,
                'owner_association_id' => $ownerAssociationId,
                'is_sync'              => 1,
            ];

            // Update instead of insert
            DB::connection(env('SECOND_DB_CONNECTION'))
                ->table('oam_receipts')
                ->where('receipt_number', $revenue->receipt_number)
                ->update($oaReceiptData);
            // dd($oaReceiptData);

            DB::commit();
            return redirect()->route('revenue.index')->with('success', __('Revenue successfully updated.'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RevenueController::update: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong.');
        }
        // if (Auth::user()->can('edit revenue')) {

        //     $validator = \Validator::make(
        //         $request->all(),
        //         [
        //             'date' => 'required',
        //             'amount' => 'required',
        //             'account_id' => 'required',
        //             'category_id' => 'required',
        //             'invoice_id' => 'required',
        //             'adjusted_amount' => 'required',
        //         ]
        //     );
        //     if ($validator->fails()) {
        //         $messages = $validator->getMessageBag();

        //         return redirect()->back()->with('error', $messages->first());
        //     }
        //     $existingInvoices = DB::table('invoice_revenue')
        //         ->where('revenue_id', $revenue->id)
        //         ->pluck('invoice_id')
        //         ->toArray();

        //     $customer = Customer::where('id', $request->customer_id)->first();
        //     if (!empty($customer)) {
        //         Utility::userBalance('customer', $revenue->customer_id, $revenue->amount, 'debit');
        //     }

        //     Utility::bankAccountBalance($revenue->account_id, $revenue->amount, 'debit');

        //     if (!empty($customer)) {
        //         Utility::userBalance('customer', $customer->id, $request->amount, 'credit');
        //     }

        //     Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

        //     $revenue->date           = $request->date;
        //     $revenue->amount         = $request->amount;
        //     $revenue->account_id     = $request->account_id;
        //     $revenue->customer_id    = $request->customer_id;
        //     $revenue->category_id    = $request->category_id;
        //     $revenue->payment_method = 0;
        //     $revenue->reference      = $request->reference;
        //     $revenue->description    = $request->description;


        //     if (!empty($request->add_receipt)) {
        //         // if($revenue->add_receipt)
        //         // {
        //         //     $image_path =  app_path("uploads/revenue/{$revenue->add_receipt}");
        //         //     if (File::exists($image_path)) {
        //         //         //File::delete($image_path);
        //         //         unlink($image_path);
        //         //     }
        //         // }

        //         if ($revenue->add_receipt) {

        //             $file_path = 'uploads/revenue/' . $revenue->add_receipt;
        //             $image_size = $request->file('add_receipt')->getSize();

        //             $result = Utility::updateStorageLimit(Auth::user()->creatorId(), $image_size);

        //             if ($result == 1) {

        //                 Utility::changeStorageLimit(Auth::user()->creatorId(), $file_path);
        //                 $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
        //                 $revenue->add_receipt = $fileName;
        //                 $path = storage_path('uploads/revenue/' . $revenue->add_receipt);
        //                 if (file_exists($path)) {
        //                     \File::delete($path);
        //                 }


        //                 // $request->add_receipt->storeAs('uploads/revenue', $fileName);

        //                 $dir        = 'uploads/revenue';
        //                 $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
        //                 // $request->add_receipt  = $path['url'];
        //                 if ($path['flag'] == 0) {
        //                     return redirect()->back()->with('error', __($path['msg']));
        //                 }
        //             } else {
        //                 return redirect()->back()->with('error', $result);
        //             }
        //         }
        //         // $revenue->save();


        //     }

        //     $revenue->save();
        //     $revenue->updateCustomerBalance();

        //     foreach ($request->invoice_id as $key => $invoiceId) {
        //         $adjustedAmount = $request->adjusted_amount[$key];

        //         if (!in_array($invoiceId, $existingInvoices)) {
        //             DB::table('invoice_revenue')->insert([
        //                 'invoice_id'      => $invoiceId,
        //                 'revenue_id'      => $revenue->id,
        //                 'adjusted_amount' => $adjustedAmount ?? 0,
        //             ]);
        //         } else {
        //             DB::table('invoice_revenue')
        //                 ->where(['invoice_id' => $invoiceId, 'revenue_id' => $revenue->id])
        //                 ->increment('adjusted_amount', $adjustedAmount ?? 0);
        //         }

        //         // Update the invoice status based on the total transfer
        //         $status       = 1;
        //         $totalTransfer = DB::table('invoice_revenue')
        //             ->where('invoice_id', $invoiceId)
        //             ->sum('adjusted_amount');
        //         $invoice = Invoice::find($invoiceId);

        //         if ($totalTransfer < $invoice->getTotal()) {
        //             $status = 3; // Partial transfer
        //         } elseif ($totalTransfer >= $invoice->getTotal()) {
        //             $status = 4; // Fully paid
        //         }

        //         Invoice::where('id', $invoiceId)
        //             ->update(['status' => $status]);
        //     }



        //     $category            = ProductServiceCategory::where('id', $request->category_id)->first();
        //     $revenue->category   = $category->name;
        //     $revenue->transfer_id = $revenue->id;
        //     $revenue->type       = 'Revenue';
        //     $revenue->account    = $request->account_id;
        //     Transaction::editTransaction($revenue);

        //     $accountId = BankAccount::find($revenue->account_id);
        //     $data = [
        //         'account_id' => $accountId->chart_account_id,
        //         'transaction_type' => 'Debit',
        //         'transaction_amount' => $revenue->amount,
        //         'reference' => 'Revenue',
        //         'reference_id' => $revenue->id,
        //         'reference_sub_id' => 0,
        //         'date' => $revenue->date,
        //     ];
        //     Utility::addTransactionLines($data);

        //     return redirect()->route('revenue.index')->with('success', __('Revenue successfully updated.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
        // } else {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }


    public function destroy(Revenue $revenue)
    {
        if (!Auth::user()->can('delete revenue')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        DB::beginTransaction();
        try {
            $revenue->bankAllocations()->delete();
            $revenue->delete();
            // ... existing cleanup logic ...
            DB::commit();
            return redirect()->route('revenue.index')->with('success', __('Revenue successfully deleted.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Something went wrong.');
        }
        // if (Auth::user()->can('delete revenue')) {
        //     if (!empty($revenue->add_receipt)) {
        //         $file_path = 'uploads/revenue/' . $revenue->add_receipt;
        //         $result = Utility::changeStorageLimit(Auth::user()->creatorId(), $file_path);

        //         if (file_exists($file_path)) {
        //             \File::delete($file_path);
        //         }
        //     }

        //     if ($revenue->created_by == Auth::user()->creatorId()) {
        //         // TransactionLines::where('reference_id',$revenue->id)->where('reference','Revenue')->delete();
        //         TransactionLines::deleteAndRecalculateTransactionBalance($revenue, 'Revenue');

        //         $revenue->deleteCustomerTransactionLine();
        //         $revenue->delete();
        //         $type = 'Revenue';
        //         $user = 'Customer';
        //         Transaction::destroyTransaction($revenue->id, $type, $user);
        //         if ($revenue->customer_id != 0) {
        //             Utility::userBalance('customer', $revenue->customer_id, $revenue->amount, 'debit');
        //         }
        //         Utility::bankAccountBalance($revenue->account_id, $revenue->amount, 'debit');

        //         return redirect()->route('revenue.index')->with('success', __('Revenue successfully deleted.'));
        //     } else {
        //         return redirect()->back()->with('error', __('Permission denied.'));
        //     }
        // } else {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    public function export($date = null)
    {
        $name = 'revenue_' . date('Y-m-d i:h:s');
        $data = Excel::download(new RevenueExport($date), $name . '.xlsx');

        return $data;
    }

    public function createReceipt($date, $amount, $accountId, $categoryId, $customerId = null, $reference = null, $description = null, $addReceipt = null, $customerDetailArray = null)
    {
        // dd($date, $amount, $accountId, $categoryId, $customerId, $reference, $description, $addReceipt);
        $validator = Validator::make(
            compact('date', 'amount', 'accountId', 'categoryId'),
            [
                'date' => 'required',
                'amount' => 'required',
                'accountId' => 'required',
                'categoryId' => 'required',
            ]
        );

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->getMessageBag()->first()
            ];
        }

        try {
            DB::beginTransaction();

            $reference = (int) $reference ? (int) $reference : crc32(Revenue::latest()->first()?->id + 1);

            $revenue = new Revenue();
            $revenue->date = $date;
            $revenue->amount = $amount;
            $revenue->account_id = $accountId;
            $revenue->customer_id = $customerId;
            $revenue->category_id = $categoryId;
            $revenue->payment_method = 0;
            $revenue->reference = $reference;
            $revenue->description = $description;
            $revenue->building_id = \Auth::user()->currentBuilding();
            $revenue->is_attend = 1;

            if ($addReceipt instanceof \Illuminate\Http\UploadedFile) {
                $image_size = $addReceipt->getSize();
                $result = Utility::updateStorageLimit(Auth::user()->creatorId(), $image_size);

                if ($result == 1) {

                    $fileName = time() . "_" . $addReceipt->getClientOriginalName();
                    // $revenue->add_receipt = $fileName;
                    $dir = 'uploads/revenue';
                    // dd($addReceipt);
                    $path = Utility::upload_file_v2(['add_receipt' => $addReceipt], 'add_receipt', $fileName, $dir, []);
                    if ($path['flag'] == 0) {
                        return [
                            'status' => 'error',
                            'message' => __($path['msg'])
                        ];
                    }
                    $revenue->add_receipt = $path['url'];
                } else {
                    return [
                        'status' => 'error',
                        'message' => $result
                    ];
                }
            }
            $revenue->created_by = Auth::user()->creatorId();
            $revenue->save();
            $category = ProductServiceCategory::find($categoryId);

            foreach ($customerDetailArray as $index => $customer) {
                $revenue->updateRevenueCustomerBalance($customer['customer_id'], $customer['adjusted_amount'], $revenue->id, $revenue->date);
                $revenue->transfer_id = $revenue->id;
                $revenue->type = 'Revenue';
                $revenue->category = $category->name;
                $revenue->user_id = $customer['customer_id'];
                $revenue->user_type = 'Customer';
                $revenue->account = $accountId;
                Transaction::addTransaction($revenue);

                if ($customer['customer_id']) {
                    $customer = Customer::find($customer['customer_id']);
                    Utility::userBalance('customer', $customer->id, $revenue->amount, 'credit');
                }

                if (isset($customer) && !empty($customer->email)) {
                    $uArr = [
                        'transfer_name' => $customer->name,
                        'transfer_amount' => Auth::user()->priceFormat($amount),
                        'invoice_number' => $revenue->type,
                        'transfer_date' => Auth::user()->dateFormat($date),
                        'transfer_dueAmount' => '-',
                    ];

                    try {
                        Utility::sendEmailTemplate('new_invoice_transfer', [$customer->id => $customer->email], $uArr);
                    } catch (\Exception $e) {
                        $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
                    }
                }

                $setting = Utility::settings(Auth::user()->creatorId());
                if (isset($setting['revenue_notification']) && $setting['revenue_notification'] == 1 && isset($customer)) {
                    $uArr = [
                        'transfer_name' => $customer->name,
                        'transfer_amount' => Auth::user()->priceFormat($amount),
                        'transfer_date' => Auth::user()->dateFormat($date),
                        'user_name' => Auth::user()->name,
                    ];
                    Utility::send_twilio_msg($customer->contact, 'new_revenue', $uArr);
                }
            }

            $module = 'New Revenue';
            $webhook = Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($revenue);
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if (!$status) {
                    return [
                        'status' => 'error',
                        'message' => __('Webhook call failed.')
                    ];
                }
            }

            return [
                'status' => 'success',
                'message' => __('Revenue successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''),
                'revenue' => $revenue,
            ];

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("####### RevenueController.php->createReceipt:692 ############   " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    // public function createInvoice($date, $amount, $accountId, $categoryId, $customerId = null, $reference = null, $description = null, $addReceipt = null)
    // {
    //     $validator = \Validator::make(
    //         compact('date', 'amount', 'accountId', 'categoryId'),
    //         [
    //             'date' => 'required',
    //             'amount' => 'required',
    //             'accountId' => 'required',
    //             'categoryId' => 'required',
    //         ]
    //     );

    //     if ($validator->fails()) {
    //         return [
    //             'status' => 'error',
    //             'message' => $validator->getMessageBag()->first()
    //         ];
    //     }

    //     $reference = (int) $reference ? (int) $reference : crc32(Revenue::latest()->first()?->id + 1);

    //     $revenue = new Revenue();
    //     $revenue->date = $date;
    //     $revenue->amount = $amount;
    //     $revenue->account_id = $accountId;
    //     $revenue->customer_id = $customerId;
    //     $revenue->category_id = $categoryId;
    //     $revenue->payment_method = 0;
    //     $revenue->reference = $reference;
    //     $revenue->description = $description;

    //     if ($addReceipt) {
    //         $image_size = $addReceipt->getSize();
    //         $result = Utility::updateStorageLimit(Auth::user()->creatorId(), $image_size);

    //         if ($result == 1) {
    //             $fileName = time() . "_" . $addReceipt->getClientOriginalName();
    //             $revenue->add_receipt = $fileName;
    //             $dir = 'uploads/revenue';
    //             $path = Utility::upload_file(compact('addReceipt'), 'add_receipt', $fileName, $dir, []);

    //             if ($path['flag'] == 0) {
    //                 return [
    //                     'status' => 'error',
    //                     'message' => __($path['msg'])
    //                 ];
    //             }
    //         } else {
    //             return [
    //                 'status' => 'error',
    //                 'message' => $result
    //             ];
    //         }
    //     }

    //     $revenue->created_by = Auth::user()->creatorId();
    //     $revenue->save();

    //     $revenue->updateCustomerBalance();

    //     $category = ProductServiceCategory::find($categoryId);
    //     $revenue->transfer_id = $revenue->id;
    //     $revenue->type = 'Revenue';
    //     $revenue->category = $category->name;
    //     $revenue->user_id = $customerId;
    //     $revenue->user_type = 'Customer';
    //     $revenue->account = $accountId;
    //     Transaction::addTransaction($revenue);

    //     if ($customerId) {
    //         $customer = Customer::find($customerId);
    //         Utility::userBalance('customer', $customer->id, $revenue->amount, 'credit');
    //     }

    //     Utility::bankAccountBalance($accountId, $amount, 'credit');

    //     $account = BankAccount::find($revenue->account_id);
    //     $data = [
    //         'account_id' => $account->chart_account_id,
    //         'transaction_type' => 'Debit',
    //         'transaction_amount' => $revenue->amount,
    //         'reference' => 'Revenue',
    //         'reference_id' => $revenue->id,
    //         'reference_sub_id' => 0,
    //         'date' => $revenue->date,
    //     ];
    //     Utility::addTransactionLines($data);

    //     if (isset($customer) && !empty($customer->email)) {
    //         $uArr = [
    //             'transfer_name' => $customer->name,
    //             'transfer_amount' => Auth::user()->priceFormat($amount),
    //             'invoice_number' => $revenue->type,
    //             'transfer_date' => Auth::user()->dateFormat($date),
    //             'transfer_dueAmount' => '-',
    //         ];

    //         try {
    //             Utility::sendEmailTemplate('new_invoice_transfer', [$customer->id => $customer->email], $uArr);
    //         } catch (\Exception $e) {
    //             $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
    //         }
    //     }

    //     $setting = Utility::settings(Auth::user()->creatorId());
    //     if (isset($setting['revenue_notification']) && $setting['revenue_notification'] == 1 && isset($customer)) {
    //         $uArr = [
    //             'transfer_name' => $customer->name,
    //             'transfer_amount' => Auth::user()->priceFormat($amount),
    //             'transfer_date' => Auth::user()->dateFormat($date),
    //             'user_name' => Auth::user()->name,
    //         ];
    //         Utility::send_twilio_msg($customer->contact, 'new_revenue', $uArr);
    //     }

    //     $module = 'New Revenue';
    //     $webhook = Utility::webhookSetting($module);
    //     if ($webhook) {
    //         $parameter = json_encode($revenue);
    //         $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
    //         if (!$status) {
    //             return [
    //                 'status' => 'error',
    //                 'message' => __('Webhook call failed.')
    //             ];
    //         }
    //     }

    //     return [
    //         'status' => 'success',
    //         'message' => __('Revenue successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''),
    //         'revenue' => $revenue,
    //     ];
    // }

    public function getAdjustedAmount(Request $request)
    {
        $invoiceId = $request->input('invoice_id');
        $revenueId = $request->input('revenue_id');

        $invoice = Invoice::where('id', $invoiceId)->first();
        // Fetch the adjusted amount from the database
        if ($revenueId) {
            $amount = DB::table('invoice_revenue')
                ->where(['invoice_id' => $invoiceId, 'revenue_id' => $revenueId])
                ->value('adjusted_amount');
        }
        // Log::info($invoice->getDue());
        // Return the adjusted amount as a JSON response
        return response()->json([
            'adjusted_amount' => $amount ?? null,
            'maxValue'        => $invoice->getDue(),
        ]);
    }
    public function getInvoiceStatus(Request $request)
    {
        $invoiceIds = $request->input('invoice_ids');
        // Convert the string into an array
        $invoiceIdsArray = explode(',', $invoiceIds);

        $invoices   = Invoice::whereIn('id', $invoiceIdsArray)->get();

        $statuses = [];
        foreach ($invoices as $invoice) {
            $statuses[$invoice->id] = $invoice->status == 4;
        }
        return response()->json($statuses);
    }
    // public function getInvoicesByCustomer($customerId)
    // {

    //     $invoices = Invoice::where('customer_id', $customerId)
    //         ->where('status', '!=', 4)
    //         ->select('id', 'invoice_id')
    //         ->get()
    //         ->mapWithKeys(function ($invoice) {
    //             $total = $invoice->getTotal(); // Calculate the total using the method
    //             return [
    //                 $invoice->id => Auth::user()->invoiceNumberFormat($invoice->invoice_id) . ' - ' . $total,
    //             ];
    //         });
    //     // dd($customerId);
    //     return response()->json($invoices);
    // }

    public function getInvoicesByCustomer($customerId)
    {
        $invoices = Invoice::where('customer_id', $customerId)
            ->where('status', '!=', 4)
            ->select('id', 'invoice_number') // 👈 ab invoice_number lenge
            ->get()
            ->mapWithKeys(function ($invoice) {
                $total = $invoice->getTotal();
                return [
                    $invoice->id => $invoice->invoice_number . ' - ' . $total, // 👈 direct use
                ];
            });

        return response()->json($invoices);
    }

    public function show($id)
    {
        $revenue = Revenue::findOrFail(Crypt::decrypt($id));
        $bankAllocations = $revenue->bankAllocations;
        $transferTypes = TransferType::where('transferable_id', $revenue->id)
            ->where('transferable_type', Revenue::class)
            ->get();
        return view('revenue.show', compact('revenue', 'transferTypes', 'bankAllocations'));
    }

    public function deleteTransferType($id)
    {
        $transferType = TransferType::findOrFail($id);
        $transferType->delete();

        return redirect()->back()->with('success', __('Transfer method deleted successfully.'));
    }

    public function transfer($id)
    {
        $revenue = Revenue::find($id);
        $transferMethods = ['online' => 'Online', 'cheque' => 'Cheque', 'cash' => 'Cash'];
        return view('revenue.payment', compact('revenue', 'transferMethods'));
    }

    public function updateTransfer(Request $request, $id)
    {
        $revenue = Revenue::find($id);

        // Create a new transfer type entry
        $transferType = new TransferType();
        $transferType->transfer_type = $request->transfer_method;
        $transferType->reference_number = $request->reference_number;
        $transferType->date = $request->transfer_date;
        $transferType->transferable_id = $revenue->id;
        $transferType->transferable_type = Revenue::class;

        $transferType->save();

        // Success message set karna
        session()->flash('success', 'Transfer method updated successfully.');

        return redirect()->route('revenue.index');
    }
}
