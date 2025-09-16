<?php

namespace App\Http\Controllers\Api\Tally;

use App\Http\Controllers\Controller;
use App\Http\Resources\LedgerResource;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\BillProduct;
use App\Models\Budget;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\DebitNote;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Revenue;
use App\Models\TallyAcknowledgement;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TallyIntigrationController extends Controller
{
    // private $voucherType = "Sales"; // old
    private $voucherType = "Sales ERP Web";
    public function getReceiptVouchers(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "fromDate" => "required|date",
                "toDate" => "required|date",
                "building_id" => "required"
            ]);

            $ref_number = TallyAcknowledgement::where('subtype', 'receipt')->where('building_id', $request->building_id)->pluck('voucher_number');
            // Fetch revenues within the specified date range
            $revenues = Revenue::whereBetween('date', [$request->fromDate, $request->toDate])
                ->where('building_id', $request->building_id)->get();
            $responseData = [];

            foreach ($revenues as $revenue) {
                $data = [];
                $data["voucherDate"] = User::dateFormat($revenue->date);
                $data["voucherNumber"] = (int) $revenue->reference; // Assuming `id` as voucher number, modify as needed
                $data["narration"] = "Reference: " . ($revenue->reference ?? '');
                $data["voucherType"] = "Receipt";

                // Ledger details
                $data["voucherDetail"] = [];
                $paymentDetails = [];
                $ref_details = [];

                $transferMethod = array_rand(['e-fund Transfer', 'Cheque', 'DD', 'Others']);
                $paymentDetails[] = [
                    // 'transfer_method' => $transferMethod,
                    // 'cheque_dd_number' => in_array($transferMethod, ['Cheque', 'DD']) ? strval(rand(100000000, 999999999)) : '',
                    // 'cheque_dd_date' => in_array($transferMethod, ['Cheque', 'DD']) ? date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')) : '',
                    'transfer_method' => $revenue->transaction_method ?? '',
                    'cheque_dd_number' => $revenue->transaction_number ?? '',
                    'cheque_dd_date' => $revenue->transaction_date ?? '',
                    'amount' => $revenue->amount,
                ];
                $invoiceRevenues = \DB::table('invoice_revenue')->where('revenue_id', $revenue->id)->get();

                foreach ($invoiceRevenues as $invoiceRevenue) {
                    $invoice = Invoice::where('id', $invoiceRevenue->invoice_id)->first();

                    if ($invoice) {
                        $ref_details[] = [
                            'reference_type' => 'Agst Ref',
                            'reference_number' => $invoice->reference,
                            'reference_amount' => (float) $invoiceRevenue->adjusted_amount,
                        ];
                    }
                }

                $data["voucherDetail"]["credit"] = [
                    "ledgerName" => !empty($revenue->customer) ? $revenue->customer->name : '',
                    "transactionType" => "Credit",
                    "amount" => $revenue->amount,
                    "reference_details" => $ref_details,
                ];

                $data["voucherDetail"]["debit"] = [
                    "ledgerName" => !empty($revenue->bankAccount) ? $revenue->bankAccount->chartAccount->name : '',
                    "transactionType" => "Debit",
                    "amount" => $revenue->amount,
                    "payment_deatils" => $paymentDetails,
                ];

                $responseData[] = $data;
            }

            // Return the response in JSON format
            return response()->json([
                "result" => "success",
                "total_vouchers" => count($responseData),
                "vouchers" => $responseData
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getGroups(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "building_id" => "required"
            ]);

            $types = ChartOfAccountType::where('building_id', '=', $request->building_id)->get();

            $accountSubTypes = ChartOfAccountSubType::whereIn('type', $types->pluck('id'))
                ->where('building_id', '=', $request->building_id)
                ->whereNull('parent_id')
                ->get();

            $groups = [];
            foreach ($accountSubTypes as $subType) {
                $group = [
                    "name" => $subType->name,
                    // "parent" => "", // passed as parent
                    "parent" => !empty($subType->parent->name) ? $subType->parent->name
                        : $subType->typeObj->name, // passed as parent
                    "type" => $subType->typeObj->name
                ];
                $groups[] = $group;

                foreach ($subType->childSubTypes as $subSubType) {
                    $group = [
                        "name" => $subSubType->name,
                        "parent" => $subType->name,
                        "type" => $subType->typeObj->name
                    ];

                    $groups[] = $group;
                }
            }

            return response()->json([
                "result" => "success",
                "total" => count($groups),
                "data" => $groups
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getCostCategory(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "building_id" => "required"
            ]);

            $expenseType = ChartOfAccountType::where('building_id', '=', $request->building_id)
                ->where("name", "Expenses")->first();

            $accountIds = ChartOfAccount::where('type', $expenseType->id)
                ->where('building_id', '=', $request->building_id)
                ->pluck("id");

            $categories = ProductServiceCategory::where('type', "expense")
                ->whereIn("chart_account_id", $accountIds)
                ->where('building_id', '=', $request->building_id)
                ->get();

            $costCategories = [];
            foreach ($categories as $category) {
                $costCategories[]['name'] = $category->name;
            }

            return response()->json([
                "result" => "success",
                "total" => count($costCategories),
                "data" => $costCategories
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getCostCentre(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "building_id" => "required"
            ]);

            $expenseType = ChartOfAccountType::where('building_id', '=', $request->building_id)
                ->where("name", "Expenses")->first();

            $accountIds = ChartOfAccount::where('type', $expenseType->id)
                ->where('building_id', '=', $request->building_id)
                ->pluck("id");

            $categories = ProductServiceCategory::where('type', "expense")
                ->whereIn("chart_account_id", $accountIds)
                ->where('building_id', '=', $request->building_id)
                ->get();

            $costCentres = [];
            foreach ($categories as $category) {
                foreach ($category->productServices as $service) {
                    $costCentre = [
                        "name" => $service->name,
                        "costCategory" => $category->name,
                        "parentCostCentre" => '',
                    ];

                    $costCentres[] = $costCentre;
                }
            }

            return response()->json([
                "result" => "success",
                "total" => count($costCentres),
                "data" => $costCentres
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getLedgers(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "building_id" => "required"
            ]);

            $types = ChartOfAccountType::where('building_id', '=', $request->building_id);

            $accountSubTypes = ChartOfAccountSubType::whereIn('type', $types->pluck('id'))
                ->where('building_id', '=', $request->building_id)
                ->get()
                ->pluck('id');

            $accounts = ChartOfAccount::whereIn('type', $accountSubTypes)
                ->where('building_id', '=', $request->building_id)
                ->get();

            $ledgers = [];
            foreach ($accounts as $account) {
                $ledger = [
                    "name" => $account->name,
                    "parent" => $account->subType->name,
                    'iban_number' => 'DE' . rand(1000000000, 9999999999),
                    'bank_account_number' => strval(rand(1000000000, 9999999999)),
                    'branch' => 'Branch ' . chr(rand(65, 90)) . rand(1, 100),
                    'bank_holder_name' => fake()->name,
                    'bank_name' => 'Bank ' . chr(rand(65, 90)) . rand(1, 100),
                    'bank_code' => rand(100, 999),
                ];

                $ledgers[] = (object) $ledger;
            }

            $owners = Customer::where('building_id', '=', $request->building_id)->get();

            foreach ($owners as $owner) {
                $ledger = [
                    'name' => $owner->name,
                    'parent' => 'Sundry Debtors',
                    'mobile_number' => $owner->billing_phone,
                    'pobox' => $owner->billing_zip,
                    'address' => $owner->billing_address,
                    'email' => $owner->email,
                    'opening_balance' => $owner->balance,
                    'iban_number' => 'DE' . rand(1000000000, 9999999999),
                    'bank_account_number' => strval(rand(1000000000, 9999999999)),
                    'branch' => 'Branch ' . chr(rand(65, 90)) . rand(1, 100),
                    'bank_holder_name' => fake()->name,
                    'bank_name' => 'Bank ' . chr(rand(65, 90)) . rand(1, 100),
                    'bank_code' => rand(100, 999),

                ];

                $ledgers[] = (object) $ledger;
            }

            $vendors = Vender::where('building_id', '=', $request->building_id)->get();

            foreach ($vendors as $vendor) {
                $ledger = [
                    'name' => $vendor->name,
                    'parent' => 'Sundry Creditors',
                    'mobile_number' => $vendor->billing_phone,
                    'pobox' => $vendor->billing_zip,
                    'address' => $vendor->billing_address,
                    'email' => $vendor->email,
                    'opening_balance' => $vendor->balance,
                    // 'iban_number' => 'DE' . rand(10000000000000000000, 99999999999999999999),
                    'iban_number' => 'DE' . random_int(100000000000000000, 999999999999999999),
                    'bank_account_number' => strval(rand(1000000000, 9999999999)),
                    'branch' => 'Branch ' . chr(rand(65, 90)) . rand(1, 100),
                    'bank_holder_name' => fake()->name,
                    'bank_name' => 'Bank ' . chr(rand(65, 90)) . rand(1, 100),
                    'bank_code' => rand(100, 999),
                    'vat_type' => $vendor->tax_number ? 'Regular' : 'Unregistered',
                    'date_of_vat_registration' => $vendor->tax_number ? date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')) : '',
                    'trn' => $vendor->tax_number ?? '',
                    'bill_by_bill' => 'Yes',
                    'alias' => chr(rand(65, 90)) . rand(1, 100),
                ];

                $ledgers[] = (object) $ledger;
            }

            return response()->json([
                "result" => "success",
                "total" => count($ledgers),
                "data" => LedgerResource::collection(collect($ledgers))
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function acknowledgements(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "building_id" => "required",
                'type' => 'required|in:master,voucher',
                'subtype' => 'required|' . ($request->type == 'master' ? 'in:group,ledger,costcategory,costcentre,budget' : 'in:sales,receipt,purchase,payment,contra,credit_note,debit_note,journal'),
                'voucherNumber' => $request->type == 'voucher' ? 'required' : '',
                'name' => $request->type == 'master' ? 'required' : '',
                'date' => 'required|date'
            ]);

            // Insert the validated data into the acknowledgements table
            TallyAcknowledgement::create([
                'building_id' => $request->building_id,
                'type' => $request->type,
                'subtype' => $request->subtype,
                'voucher_number' => $request->type == 'voucher' ? $request->voucherNumber : null,
                'name' => $request->type == 'master' ? $request->name : null,
                'date' => $request->date,
            ]);

            return response()->json([
                "result" => "success",
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getSalesVouchers(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "fromDate" => "required|date",
                "toDate" => "required|date",
                "building_id" => "required"
            ]);

            $ref_number = TallyAcknowledgement::where('subtype', 'sales')
                ->where('building_id', $request->building_id)
                ->pluck('voucher_number');
            // Fetch invoices within the specified date range
            $invoices = Invoice::whereBetween('issue_date', [$request->fromDate, $request->toDate])
                ->where('building_id', $request->building_id)->get();
            $responseData = [];

            foreach ($invoices as $invoice) {
                $data = [];
                $data["voucherDate"] = User::dateFormat($invoice->issue_date);
                $data["voucherNumber"] = $invoice->ref_number;
                $data["narration"] = "Reference: " . ($invoice->ref_number ?? '');
                //$data["voucherType"] = "Sales";
                $data["voucherType"] = $this->voucherType;
                // Ledger details
                $data["voucherDetail"] = [];
                $ref_details = [];
                $ref_details[] = [
                    'reference_type' => 'New Ref',
                    'reference_number' => $invoice->ref_number,
                    'reference_amount' => $invoice->getTotal()
                ];
                $data["voucherDetail"]["partyLedger"] = [
                    "ledgerName" => !empty($invoice->customer) ? $invoice->customer->name : '',
                    "transactionType" => "Debit",
                    "amount" => $invoice->getTotal(),
                    "reference_details" => $ref_details,
                ];

                $data["voucherDetail"]["incomeLedger"] = [];
                foreach ($invoice->items as $item) {
                    $data["voucherDetail"]["incomeLedger"][] = [
                        "ledgerName" => ChartOfAccount::find($item->product->sale_chartaccount_id)?->name,
                        "transactionType" => "Credit",
                        "amount" => $item->price,
                    ];
                    $data["voucherDetail"]["incomeLedger"][] = [
                        "ledgerName" => 'VAT 5%',
                        "transactionType" => "Credit",
                        "amount" => $item->price * 0.05,
                    ];
                }

                $responseData[] = $data;
            }

            // Return the response in JSON format
            return response()->json([
                "result" => "success",
                "total_vouchers" => count($responseData),
                "vouchers" => $responseData
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getPurchaseVouchers(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "fromDate" => "required|date",
                "toDate" => "required|date",
                "building_id" => "required"
            ]);

            $ref_number = TallyAcknowledgement::where('subtype', 'purchase')->where('building_id', $request->building_id)->pluck('voucher_number');
            // Fetch Bills within the specified date range
            $bills = Bill::whereBetween('bill_date', [$request->fromDate, $request->toDate])
                ->where('building_id', $request->building_id)->get();
            $responseData = [];

            foreach ($bills as $bill) {
                $data = [];
                $data["voucherDate"] = User::dateFormat($bill->bill_date);
                $data["voucherNumber"] = $bill->ref_number;
                $data["narration"] = "Reference: " . ($bill->ref_number ?? '');
                $data["voucherType"] = "Purchase";

                // Ledger details
                $data["voucherDetail"] = [];
                $ref_details = [];
                $ref_details[] = [
                    'reference_type' => 'New Ref',
                    'reference_number' => $bill->ref_number,
                    'reference_amount' => $bill->getTotal()
                ];
                $data["voucherDetail"]["partyLedger"] = [
                    "ledgerName" => !empty($bill->vender) ? $bill->vender->name : '',
                    "transactionType" => "Credit",
                    "amount" => $bill->getTotal(),
                    "reference_details" => $ref_details,
                ];

                foreach ($bill->items as $item) {
                    $data["voucherDetail"]["expenseLedger"][] = [
                        "ledgerName" => ChartOfAccount::find($item->product->expense_chartaccount_id)->name,
                        "costCategory" => $item->product->category->name,
                        "costCenter" => $item->product->name,
                        "transactionType" => "Debit",
                        "amount" => $item->price
                    ];
                }


                $responseData[] = $data;
            }

            // Return the response in JSON format
            return response()->json([
                "result" => "success",
                "total_vouchers" => count($responseData),
                "vouchers" => $responseData
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getPaymentVouchers(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "fromDate" => "required|date",
                "toDate" => "required|date",
                "building_id" => "required"
            ]);

            $ref_number = TallyAcknowledgement::where('subtype', 'payment')
                //->where('building_id', $request->building_id)
                ->pluck('voucher_number');
            // Fetch revenues within the specified date range
            $payments = Payment::whereBetween('date', [$request->fromDate, $request->toDate])
                // ->where('building_id', $request->building_id) // commented by karan as no column is exist
                ->get();
            $responseData = [];

            foreach ($payments as $payment) {
                $data = [];
                $data["voucherDate"] = User::dateFormat($payment->date);
                $data["voucherNumber"] = $payment->reference;
                $data["narration"] = "Reference: " . ($payment->reference ?? '');
                $data["voucherType"] = "Payment";

                // Ledger details
                $data["voucherDetail"] = [];
                $paymentDetails = [];
                $transferMethod = array_rand(['e-fund Transfer', 'Cheque', 'DD', 'Others']);
                $paymentDetails[] = [
                    'transfer_method' => $transferMethod,
                    'cheque_dd_number' => in_array($transferMethod, ['Cheque', 'DD']) ? strval(rand(100000000, 999999999)) : '',
                    'cheque_dd_date' => in_array($transferMethod, ['Cheque', 'DD']) ? date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')) : '',
                    'amount' => $payment->amount,
                ];

                $ref_details = [];
                $ref_details[] = [
                    'reference_type' => 'New Ref',
                    'reference_number' => $payment->reference,
                    'reference_amount' => $payment->amount
                ];
                $data["voucherDetail"]["debit"] = [
                    "ledgerName" => !empty($payment->vender) ? $payment->vender->name : '',
                    "transactionType" => "Debit",
                    "amount" => $payment->amount,
                    "reference_details" => $ref_details,
                ];


                $data["voucherDetail"]["credit"] = [
                    "ledgerName" => $payment->bankAccount->chartAccount->name,
                    "transactionType" => "Credit",
                    "amount" => $payment->amount,
                    "payment_deatils" => $paymentDetails,
                ];

                $responseData[] = $data;
            }

            // Return the response in JSON format
            return response()->json([
                "result" => "success",
                "total_vouchers" => count($responseData),
                "vouchers" => $responseData
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getContraVouchers(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "fromDate" => "required|date",
                "toDate" => "required|date",
                "building_id" => "required"
            ]);

            $ref_number = TallyAcknowledgement::where('subtype', 'contra')->where('building_id', $request->building_id)->pluck('voucher_number');
            // Fetch revenues within the specified date range
            $transfers = Transfer::whereBetween('date', [$request->fromDate, $request->toDate])
                ->where('created_by', $request->building_id)->get();
            $responseData = [];

            foreach ($transfers as $transfer) {
                $data = [];
                $data["voucherDate"] = User::dateFormat($transfer->date);
                $data["voucherNumber"] = $transfer->reference;
                $data["narration"] = "Reference: " . ($transfer->reference ?? '');
                $data["voucherType"] = "Contra";

                //getting bill item
                // Ledger details
                $data["voucherDetail"] = [];
                $paymentDetails = [];
                $transferMethod = array_rand(['e-fund Transfer', 'Cheque', 'DD', 'Others']);
                $paymentDetails[] = [
                    'transfer_method' => $transferMethod,
                    'cheque_dd_number' => in_array($transferMethod, ['Cheque', 'DD']) ? strval(rand(100000000, 999999999)) : '',
                    'cheque_dd_date' => in_array($transferMethod, ['Cheque', 'DD']) ? date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')) : '',
                    'amount' => $transfer->amount,
                ];

                $data["voucherDetail"]["debit"] = [
                    "ledgerName" => $transfer->toBankAccount->chartAccount?->name,
                    "transactionType" => "Debit",
                    "amount" => $transfer->amount,
                    "payment_deatils" => $paymentDetails,
                ];

                $data["voucherDetail"]["credit"] = [
                    "ledgerName" => $transfer->fromBankAccount->chartAccount?->name,
                    "transactionType" => "Credit",
                    "amount" => $transfer->amount,
                    "payment_deatils" => $paymentDetails,
                ];

                $responseData[] = $data;
            }

            // Return the response in JSON format
            return response()->json([
                "result" => "success",
                "total_vouchers" => count($responseData),
                "vouchers" => $responseData
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getCreditNoteVouchers(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "fromDate" => "required|date",
                "toDate" => "required|date",
                "building_id" => "required"
            ]);

            $ref_number = TallyAcknowledgement::where('subtype', 'credit_note')->where('building_id', $request->building_id)->pluck('voucher_number');
            // Fetch revenues within the specified date range
            $creditNotes = CreditNote::select('credit_notes.*')->whereBetween('date', [$request->fromDate, $request->toDate])
                ->join('invoices', "invoices.id", "=", "credit_notes.invoice")
                ->where('invoices.created_by', $request->building_id)->get();
            $responseData = [];

            foreach ($creditNotes as $creditNote) {
                $data = [];
                $data["voucherDate"] = User::dateFormat($creditNote->date);
                $data["voucherNumber"] = $creditNote->reference;
                $data["narration"] = "Reference: " . ($creditNote->reference ?? '');
                $data["voucherType"] = "Credit_Note";

                //getting bill item
                // Ledger details
                $data["voucherDetail"] = [];
                $ref_details = [
                    'reference_type' => 'New Ref',
                    'reference_number' => $creditNote->reference,
                    'reference_amount' => $creditNote->amount
                ];
                $data["voucherDetail"]["partyLedger"] = [
                    "ledgerName" => $creditNote->customer->name,
                    "transactionType" => "Credit",
                    "amount" => $creditNote->amount,
                    "reference_details" => $ref_details,
                ];

                $amount = 0;
                $data["voucherDetail"]["incomeLedger"] = [];
                foreach ($creditNote->invoice->items as $item) {
                    $amount += $item->price;
                    $data["voucherDetail"]["incomeLedger"][] = [
                        "ledgerName" => ChartOfAccount::find($item->product->sale_chartaccount_id)->name,
                        "transactionType" => "Debit",
                        "amount" => $item->price
                    ];
                    if ($amount > $creditNote->amount) {
                        break;
                    }
                }

                $responseData[] = $data;
            }

            // Return the response in JSON format
            return response()->json([
                "result" => "success",
                "total_vouchers" => count($responseData),
                "vouchers" => $responseData
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getDebitNoteVouchers(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "fromDate" => "required|date",
                "toDate" => "required|date",
                "building_id" => "required"
            ]);

            $ref_number = TallyAcknowledgement::where('subtype', 'debit_note')->where('building_id', $request->building_id)->pluck('voucher_number');
            // Fetch revenues within the specified date range
            $debitNotes = DebitNote::select('debit_notes.*')->whereBetween('date', [$request->fromDate, $request->toDate])
                ->join('bills', "bills.id", "=", "debit_notes.bill")
                ->where('bills.created_by', $request->building_id)->get();
            $responseData = [];

            foreach ($debitNotes as $debitNote) {
                $data = [];
                $data["voucherDate"] = User::dateFormat($debitNote->date);
                $data["voucherNumber"] = $debitNote->reference;
                $data["narration"] = "Reference: " . ($debitNote->reference ?? '');
                $data["voucherType"] = "Debit_Note";

                //getting bill item
                // Ledger details
                $data["voucherDetail"] = [];
                $ref_details = [
                    'reference_type' => 'New Ref',
                    'reference_number' => $debitNote->reference,
                    'reference_amount' => $debitNote->amount
                ];
                $data["voucherDetail"]["partyLedger"] = [
                    "ledgerName" => $debitNote->vendor->name,
                    "transactionType" => "Debit",
                    "amount" => $debitNote->amount,
                    "reference_details" => $ref_details,
                ];

                $amount = 0;
                $data["voucherDetail"]["expenseLedger"] = [];
                foreach ($debitNote->bill->items as $item) {
                    $amount += $item->price;
                    $data["voucherDetail"]["expenseLedger"][] = [
                        "ledgerName" => ChartOfAccount::find($item->product->expense_chartaccount_id)->name,
                        "transactionType" => "Credit",
                        "costCategory" => $item->product->category->name,
                        "costCenter" => $item->product->name,
                        "amount" => $item->price
                    ];
                    if ($amount > $debitNote->amount) {
                        break;
                    }
                }

                $responseData[] = $data;
            }

            // Return the response in JSON format
            return response()->json([
                "result" => "success",
                "total_vouchers" => count($responseData),
                "vouchers" => $responseData
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getJournalVouchers(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "fromDate" => "required|date",
                "toDate" => "required|date",
                "building_id" => "required"
            ]);

            $ref_number = TallyAcknowledgement::where('subtype', 'journal')->where('building_id', $request->building_id)->pluck('voucher_number');
            // Fetch revenues within the specified date range
            $journals = JournalEntry::whereBetween('date', [$request->fromDate, $request->toDate])
                ->where('created_by', $request->building_id)->get();
            $responseData = [];

            foreach ($journals as $journal) {
                $data = [];
                $data["voucherDate"] = User::dateFormat($journal->date);
                $data["voucherNumber"] = $journal->reference;
                $data["narration"] = "Reference: " . ($journal->reference ?? '');
                $data["voucherType"] = "Journal";

                // Ledger details
                $data["voucherDetail"] = [];
                $data["voucherDetail"]['debit'] = [];
                $data["voucherDetail"]['credit'] = [];
                foreach ($journal->accounts as $item) {
                    $type = !empty((float) $item->credit) ? 'credit' : 'debit';
                    $data["voucherDetail"][$type][] = [
                        "ledgerName" => $item->accounts->name,
                        "transactionType" => ucwords($type),
                        "amount" => $item->$type
                    ];
                }

                $responseData[] = $data;
            }

            // Return the response in JSON format
            return response()->json([
                "result" => "success",
                "total_vouchers" => count($responseData),
                "vouchers" => $responseData
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }

    public function getBudget(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                "building_id" => "required"
            ]);

            // Fetch revenues within the specified date range
            $budgets = Budget::where('created_by', $request->building_id)->where('period', 'yearly')->get();
            $responseData = [];

            foreach ($budgets as $budget) {
                $data = [];
                $data["name"] = $budget->name;
                $data["period_details"] = [];
                $data["period_details"]['period_from'] = date('Y-m-d H:i:s', strtotime($budget->from . '-01-01 00:00:01'));
                $data["period_details"]['period_to'] = date('Y-m-d H:i:s', strtotime($budget->from . '-12-31 23:59:59'));

                $data['income_data'] = [];
                $data['expense_data'] = [];
                $budget->expense_data = json_decode($budget->expense_data, true);;

                if ($budget->expense_data) {
                    $i = 0;
                    foreach ($budget->expense_data as $categoryId => $categoryBudget) {
                        $category = ProductServiceCategory::find($categoryId);
                        $data['expense_data'][$i]['categoryName'] = $category->name;

                        $j = 0;
                        foreach ($categoryBudget as $serviceId => $serviceBudget) {
                            $service = ProductService::find($serviceId);
                            $data['expense_data'][$i]['data'][$j] = [
                                "costCentre" => $service->name,
                                'credit' => (float) 0,
                                'debit' => (float) $serviceBudget['Jan-Dec']
                            ];
                            $j++;
                        }

                        $i++;
                    }
                }

                $data['income_data'][] = [
                    "categoryName" => "Service Charges",
                    'data' => [
                        "costCentre" => $service->name,
                        'credit' => (float) 100,
                        'debit' => (float) 0,
                    ]
                ];

                $responseData[] = $data;
            }

            // Return the response in JSON format
            return response()->json([
                "result" => "success",
                "total" => count($responseData),
                "data" => $responseData
            ]);
        } catch (ValidationException $e) {
            // Return validation error messages
            return response()->json([
                'result' => 'error',
                'errors' => $e->errors()
            ], 200);
        }
    }
}
