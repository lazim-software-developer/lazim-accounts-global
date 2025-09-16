<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Revenue;
use App\Models\Utility;
use App\Models\Customer;
use App\Models\CreditNote;
use App\Models\BankAccount;
use App\Models\CustomField;
use App\Models\StockReport;
use App\Models\Transaction;
use App\Traits\HelperTrait;
use Illuminate\Http\Request;
use App\Exports\InvoiceExport;
use App\Models\ChartOfAccount;
use App\Models\InvoicePayment;
use App\Models\InvoiceProduct;
use App\Models\ProductService;
use App\Models\TransactionLines;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Crypt;
use App\Models\ProductServiceCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['invoice', 'payinvoice', 'export']]);
    }

    public function index(Request $request)
    {
        if (Auth::user()->can('manage invoice')) {
            $customer = Customer::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Owner', '');

            $status = Invoice::$statues;

            $query = Invoice::where('created_by', Auth::user()->creatorId());

            if (! empty($request->customer)) {
                $query->where('customer_id', '=', $request->customer);
            }

            if (str_contains($request->issue_date, ' to ')) {
                $date_range = explode(' to ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            } elseif (! empty($request->issue_date)) {

                $query->where('issue_date', $request->issue_date);
            }

            // if(!empty($request->issue_date))
            // {
            //     $date_range = explode(' to ', $request->issue_date);
            //     $query->whereBetween('issue_date', $date_range);
            // }

            if (! empty($request->status)) {

                $query->where('status', '=', $request->status);
            }

            $invoices = $query->get();

            return view('invoice.index', compact('invoices', 'customer', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function create($customerId)
    {
        if (Auth::user()->can('create invoice')) {
            $customFields = CustomField::where('created_by', '=', Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
            $invoice_number = Auth::user()->invoiceNumberFormat($this->invoiceNumberData());
            $customers = Customer::where('created_by', Auth::user()->creatorId())
                ->get()
                ->mapWithKeys(function ($customer) {
                    return [
                        $customer->id => $customer->property_number . ' - ' . $customer->name
                    ];
                });
            $customers->prepend('Select Owner', '');
            $categoryData = ProductServiceCategory::where('created_by', Auth::user()->creatorId())->where('type', 'income')->get();

            $category = $categoryData->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $product_services = ProductService::where('created_by', Auth::user()->creatorId())
                ->whereIn('category_id', $categoryData->pluck('id'))
                ->where('name', 'like', '%' . now()->year . '%')
                ->pluck('name', 'id');

            $product_services->prepend('--', '');

            return view('invoice.create', compact('customers', 'invoice_number', 'product_services', 'category', 'customFields', 'customerId'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function invoiceNumber() // not using
    {
        $latest = Utility::getValByName('invoice_starting_number');
        if ($latest == null) {
            return 1;
        } else {
            return $latest;
        }
    }

    public function customer(Request $request)
    {
        $customer = Customer::where('id', '=', $request->id)->first();

        return view('invoice.customer_detail', compact('customer'));
    }

    public function product(Request $request)
    {

        $data['product'] = $product = ProductService::find($request->product_id);
        $data['unit'] = (! empty($product->unit)) ? $product->unit->name : '';
        $data['taxRate'] = $taxRate = ! empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes'] = ! empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice = ! empty($product->sale_price) ? $product->sale_price : 0;
        $quantity = 1;
        $taxPrice = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function store(Request $request)
    {
        // dd($request->all())->toArray();
        if (!Auth::user()->can('create invoice')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = Validator::make(
            $request->all(),
            [
                'customer_id' => 'required',
                'issue_date' => 'required',
                'due_date' => 'required',
                'category_id' => 'required',
                'items' => 'required',
            ]
        );

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->getMessageBag()->first());
        }


        try {
            // START transaction for both DB
            DB::beginTransaction();
            DB::connection(env('SECOND_DB_CONNECTION'))->beginTransaction();

            $customer = Customer::findOrFail($request->customer_id);
            $flatId = $customer->flat_id ?? null;

            // Step 1: Create invoice in primary DB
            $invoice = $this->CreateInvoice(
                $request->customer_id,
                $request->issue_date,
                $request->due_date,
                $request->category_id,
                $request->items,
                $request->ref_number,
                isset($request->discount_apply),
                $request->customField,
                Auth::user()->currentBuilding(),
                $flatId,
            );

            // dd($invoice);

            // Step 2: Try to mark invoice as sent
            $sentResponse = $this->sent($invoice->id, Auth::user());

            $invoice->refresh();

            // Agar sent method fail hua toh rollback aur exit
            if (
                $sentResponse instanceof RedirectResponse &&
                $sentResponse->getSession()->get('error')
            ) {

                DB::rollBack();
                DB::connection(env('SECOND_DB_CONNECTION'))->rollBack();
                return redirect()->back()->with('error', 'Invoice could not be created. Transaction rolled back.');
            }

            $typeName = ProductServiceCategory::where('id', $request->category_id)->value('name');

            $transactionLine = $invoice->getCustomerTransactionLine();
            $previousBalance = $transactionLine ? $transactionLine->opening_balance : 0;
            $invoiceAmount   = $transactionLine ? $transactionLine->debit : 0;

            // Invoice status ko int se string me map karo
            $invoiceStatus = Invoice::$statues[$invoice->status] ?? 'Unknown';

            $ownerAssociationId = DB::connection(env('SECOND_DB_CONNECTION'))
                ->table('buildings')
                ->where('id', $invoice->building_id)   // invoice ka building_id
                ->value('owner_association_id');

            $oaInvoiceData = [
                'building_id'           => $invoice->building_id,
                'flat_id'               => optional($invoice->customer)->flat_id ?? 0,
                'invoice_number'        => $invoice->id,
                'invoice_id'            => $invoice->id,
                'invoice_date'          => $invoice->issue_date,
                'invoice_status'        => $invoiceStatus,
                'due_amount'            => optional($invoice->customer)->balance ?? 0,
                'previous_balance'      => $previousBalance,
                'invoice_due_date'      => $invoice->due_date,
                'invoice_pdf_link'      => $invoice->invoice_pdf_link,
                'type'                  => $typeName,
                'invoice_amount'        => $invoiceAmount,
                'owner_association_id'  => $ownerAssociationId,
                'created_at'            => now(),
                'updated_at'            => now(),
            ];
            DB::connection(env('SECOND_DB_CONNECTION'))->table('oam_invoices')->insert($oaInvoiceData);
            // dd($oaInvoiceData);
            // Step 4: Final commit in both databases
            DB::commit();
            DB::connection(env('SECOND_DB_CONNECTION'))->commit();

            return redirect()->route('invoice.index', $invoice->id)->with('success', __('Invoice successfully created and sent.'));
        } catch (\Exception $e) {
            DB::rollBack();
            DB::connection(env('SECOND_DB_CONNECTION'))->rollBack();
            Log::error('####### InvoiceController -> store() #######  ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function invoiceImport(Request $request)
    {
        // Validate uploaded file
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10 MB max
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the following errors: ' . $validator->errors()->first());
        }

        try {
            // Read file
            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');

            if (!$handle) {
                return redirect()->back()->with('error', 'Unable to read the uploaded file.');
            }

            /** ------------------------------------------------------
             * USER-FRIENDLY HEADERS -> INTERNAL HEADERS MAPPING
             * ------------------------------------------------------ */
            $headerMapping = [
                'Customer Name' => 'customer_name',
                'Unit Number' => 'unit_number',
                'Issue Date' => 'issue_date',
                'Due Date' => 'due_date',
                'Category Name' => 'category_name',
                'Ref Number' => 'ref_number',
                'Item' => 'item',
                'Quantity' => 'quantity',
                'Price' => 'price',
                'Tax' => 'tax',
                'Discount' => 'discount',
                'Description' => 'description',
            ];

            // Read first line of CSV as header
            $header = fgetcsv($handle);

            // Trim each header to avoid spaces
            $header = array_map('trim', $header);

            /** ------------------------------------------------------
             * VALIDATE HEADERS
             * ------------------------------------------------------ */
            $normalizedHeaders = [];
            foreach ($header as $head) {
                if (!isset($headerMapping[$head])) {
                    return redirect()->back()->with('error', "Unexpected header found: {$head}");
                }
                $normalizedHeaders[] = $headerMapping[$head];
            }

            /** ------------------------------------------------------
             * TRANSACTION START FOR BOTH DATABASES
             * ------------------------------------------------------ */
            DB::beginTransaction();
            DB::connection(env('SECOND_DB_CONNECTION'))->beginTransaction();

            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // Combine CSV row with normalized headers
                $data = array_combine($normalizedHeaders, $row);

                /** ------------------------------------------------------
                 * 1. VALIDATE CUSTOMER
                 * ------------------------------------------------------ */
                $customer = Customer::where('name', trim($data['customer_name']))->first();
                if (!$customer) {
                    DB::rollBack();
                    DB::connection(env('SECOND_DB_CONNECTION'))->rollBack();
                    return redirect()->back()->with('error', "Invalid customer name '{$data['customer_name']}' at row {$rowNumber}");
                }

                /** ------------------------------------------------------
                 * 2. VALIDATE FLAT
                 * ------------------------------------------------------ */
                $flat = DB::connection(env('SECOND_DB_CONNECTION'))
                    ->table('flats')
                    ->where('property_number', trim($data['unit_number']))
                    ->first();
                $flatId = $flat->id ?? null;
                if (!$flat) {
                    DB::rollBack();
                    DB::connection(env('SECOND_DB_CONNECTION'))->rollBack();
                    return redirect()->back()->with('error', "Invalid flat name '{$data['flat_name']}' at row {$rowNumber}");
                }

                /** ------------------------------------------------------
                 * 3. VALIDATE CATEGORY
                 * ------------------------------------------------------ */
                $category = ProductServiceCategory::where('created_by', Auth::user()->creatorId())
                    ->where('type', 'income')
                    ->where('name', trim($data['category_name']))
                    ->first();

                if (!$category) {
                    DB::rollBack();
                    DB::connection(env('SECOND_DB_CONNECTION'))->rollBack();
                    return redirect()->back()->with('error', "Invalid category name '{$data['category_name']}' at row {$rowNumber}");
                }

                // 3. VALIDATE PRODUCT BY NAME AND GET ITS ID
                $product = ProductService::where('created_by', Auth::user()->creatorId())
                    ->where('name', trim($data['item']))
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    DB::connection(env('SECOND_DB_CONNECTION'))->rollBack();
                    return redirect()->back()->with('error', "Invalid product name '{$data['item']}' at row {$rowNumber}");
                }

                // Items array me product_id assign karein
                $items = [[
                    'item'        => $product->id, // Yahan name ke base par ID save ho rahi hai
                    'quantity'    => (int) $data['quantity'],
                    'price'       => (float) $data['price'],
                    'tax'         => (float) $data['tax'],
                    'discount'    => $data['discount'] ?? '',
                    'description' => $data['description'] ?? '',
                ]];
                // dd($items);

                /** ------------------------------------------------------
                 * 5. CREATE INVOICE
                 * ------------------------------------------------------ */
                $invoice = $this->CreateInvoice(
                    $customer->id,
                    $data['issue_date'],
                    $data['due_date'],
                    $category->id,
                    $items,
                    $data['ref_number'],
                    false,
                    null,
                    Auth::user()->currentBuilding(),
                    $flatId,
                );

                /** ------------------------------------------------------
                 * 6. MARK INVOICE AS SENT
                 * ------------------------------------------------------ */
                $sentResponse = $this->sent($invoice->id, Auth::user());
                if ($sentResponse instanceof RedirectResponse && $sentResponse->getSession()->get('error')) {
                    DB::rollBack();
                    DB::connection(env('SECOND_DB_CONNECTION'))->rollBack();
                    return redirect()->back()->with('error', "Invoice send failed at row {$rowNumber}. Transaction rolled back.");
                }

                $invoice->refresh();

                $typeName = ProductServiceCategory::where('id', $request->category_id)->value('name');

                $transactionLine = $invoice->getCustomerTransactionLine();
                $previousBalance = $transactionLine ? $transactionLine->opening_balance : 0;
                $invoiceAmount   = $transactionLine ? $transactionLine->debit : 0;

                $invoiceStatus = Invoice::$statues[$invoice->status] ?? 'Unknown';

                $ownerAssociationId = DB::connection(env('SECOND_DB_CONNECTION'))
                    ->table('buildings')
                    ->where('id', $invoice->building_id)   // invoice ka building_id
                    ->value('owner_association_id');

                $oaInvoiceData = [
                    'building_id'           => $invoice->building_id,
                    'flat_id'               => optional($invoice->customer)->flat_id ?? 0,
                    'invoice_number'        => $invoice->id,
                    'invoice_id'            => $invoice->id,
                    'invoice_date'          => $invoice->issue_date,
                    'invoice_status'        => $invoiceStatus,
                    'due_amount'            => optional($invoice->customer)->balance ?? 0,
                    'previous_balance'      => $previousBalance,
                    'invoice_due_date'      => $invoice->due_date,
                    'invoice_pdf_link'      => $invoice->invoice_pdf_link,
                    'type'                  => $typeName,
                    'invoice_amount'        => $invoiceAmount,
                    'owner_association_id'  => $ownerAssociationId,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];

                DB::connection(env('SECOND_DB_CONNECTION'))->table('oam_invoices')->insert($oaInvoiceData);
            }

            fclose($handle);

            /** ------------------------------------------------------
             * 8. COMMIT BOTH DATABASES
             * ------------------------------------------------------ */
            DB::commit();
            DB::connection(env('SECOND_DB_CONNECTION'))->commit();

            return redirect()->route('invoice.index')->with('success', 'Invoices imported successfully.');
        } catch (\Exception $e) {
            /** ------------------------------------------------------
             * ERROR HANDLING & ROLLBACK
             * ------------------------------------------------------ */
            DB::rollBack();
            DB::connection(env('SECOND_DB_CONNECTION'))->rollBack();
            Log::error('Invoice Import Failed: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function invoiceSample(Request $request)
    {
        try {
            $creatorId = Auth::user()->currentBuilding();

            // Fetch all customers of the current building
            $customers = Customer::where('building_id', $creatorId)
                ->get(['id', 'name', 'property_number']); // property_number = Unit Number

            // Fetch all service categories created by current creator
            $categories = ProductServiceCategory::where('created_by', Auth::user()->creatorId())
                ->where('type', 'income')
                ->pluck('id', 'name'); // name => id

            // Fetch all products with their category
            $products = ProductService::where('created_by', Auth::user()->creatorId())
                ->pluck('name', 'category_id');

            // Fetch all invoices of the current building
            $invoices = Invoice::with(['customer', 'items.product', 'category'])
                ->whereHas('customer', function ($query) use ($creatorId) {
                    $query->where('building_id', $creatorId);
                })
                ->get();

            $filename = 'Invoices-' . date('d-m-Y') . '.csv';

            $headers = [
                "Content-Type" => "text/csv",
                "Content-Disposition" => "attachment; filename={$filename}",
            ];

            // CSV headers
            $columns = [
                'Customer Name',
                'Unit Number', // Added Unit Number column below Customer Name
                'Issue Date',
                'Due Date',
                'Category Name',
                'Ref Number',
                'Item',
                'Quantity',
                'Price',
                'Tax',
                'Discount',
                'Description',
            ];

            $callback = function () use ($invoices, $columns, $customers, $categories, $products) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns);

                if ($invoices->count() > 0) {
                    /**
                     * If invoices exist, export full invoice data
                     */
                    foreach ($invoices as $invoice) {
                        foreach ($invoice->items as $item) {
                            fputcsv($file, [
                                $invoice->customer->name ?? '',                      // Customer Name
                                $invoice->customer->property_number ?? '',           // Unit Number
                                $invoice->issue_date ?? '',
                                $invoice->due_date ?? '',
                                $invoice->category->name ?? '',
                                $invoice->ref_number ?? '',
                                $item->product->name ?? '',
                                $item->quantity ?? 0,
                                $item->price ?? 0,
                                $item->tax ?? 0,
                                $item->discount ?? '',
                                $item->description ?? '',
                            ]);
                        }
                    }
                } else {
                    /**
                     * If no invoices exist, export default structure
                     * Customers × Categories × Products
                     */
                    foreach ($customers as $customer) {
                        foreach ($categories as $categoryName => $categoryId) {
                            foreach ($products as $productCategoryId => $productName) {
                                // Only include products belonging to current category
                                if ($productCategoryId == $categoryId) {
                                    fputcsv($file, [
                                        $customer->name,              // Customer Name
                                        $customer->property_number,   // Unit Number
                                        '',                           // Issue Date
                                        '',                           // Due Date
                                        $categoryName,                // Category Name
                                        '',                           // Ref Number
                                        $productName,                 // Product Name (Item)
                                        '',                           // Quantity
                                        '',                           // Price
                                        '',                           // Tax
                                        '',                           // Discount
                                        '',                           // Description
                                    ]);
                                }
                            }
                        }
                    }
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Invoice Export Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }






    public function edit($ids)
    {
        if (Auth::user()->can('edit invoice')) {
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::find($id);

            $invoice_number = Auth::user()->invoiceNumberFormat($invoice->invoice_id);
            $customers = Customer::where('created_by', Auth::user()->creatorId())
                ->get()
                ->mapWithKeys(function ($customer) {
                    return [
                        $customer->id => $customer->property_number . ' - ' . $customer->name
                    ];
                });
            $category = ProductServiceCategory::where('created_by', Auth::user()->creatorId())->where('type', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');

            $invoice->customField = CustomField::getData($invoice, 'invoice');
            $customFields = CustomField::where('created_by', '=', Auth::user()->creatorId())->where('module', '=', 'invoice')->get();

            return view('invoice.edit', compact('customers', 'product_services', 'invoice', 'invoice_number', 'category', 'customFields'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (!Auth::user()->can('edit bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        // if ($invoice->created_by != Auth::user()->creatorId()) {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }

        // Validation
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required',
            'issue_date'  => 'required',
            'due_date'    => 'required',
            'category_id' => 'required',
            'items'       => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->route('invoice.index')->with('error', $validator->getMessageBag()->first());
        }

        // Start Transactions for both DBs
        DB::beginTransaction();
        DB::connection(env('SECOND_DB_CONNECTION'))->beginTransaction();

        // ---------- ACCOUNTS INVOICE UPDATE ----------
        $invoice->customer_id = $request->customer_id;
        $invoice->issue_date = $request->issue_date;
        $invoice->due_date = $request->due_date;
        $invoice->ref_number = $request->ref_number;
        $invoice->discount_apply = isset($request->discount_apply) ? 1 : 0;
        $invoice->category_id = $request->category_id;
        $invoice->save();

        CustomField::saveData($invoice, $request->customField);
        $products = $request->items;

        for ($i = 0; $i < count($products); $i++) {
            $invoiceProduct = InvoiceProduct::find($products[$i]['id']);

            if ($invoiceProduct == null) {
                $invoiceProduct = new InvoiceProduct;
                $invoiceProduct->invoice_id = $invoice->id;

                Utility::total_quantity('minus', $products[$i]['quantity'], $products[$i]['item']);

                $updatePrice = ($products[$i]['price'] * $products[$i]['quantity']) + $products[$i]['itemTaxPrice'] - $products[$i]['discount'];
            } else {
                Utility::total_quantity('minus', $invoiceProduct->quantity, $invoiceProduct->product_id);
            }

            if (isset($products[$i]['item'])) {
                $invoiceProduct->product_id = $products[$i]['item'];
            }

            $invoiceProduct->quantity = $products[$i]['quantity'];
            $invoiceProduct->tax = $products[$i]['tax'];
            $invoiceProduct->discount = $products[$i]['discount'];
            $invoiceProduct->price = $products[$i]['price'];
            $invoiceProduct->description = $products[$i]['description'];
            $invoiceProduct->save();

            // inventory management (Quantity)
            if ($products[$i]['id'] > 0) {
                Utility::total_quantity('plus', $products[$i]['quantity'], $invoiceProduct->product_id);
            } else {
                Utility::total_quantity('plus', $products[$i]['quantity'], $products[$i]['item']);
            }

            // Product Stock Report
            $type = 'invoice';
            $type_id = $invoice->id;
            StockReport::where('type', 'invoice')->where('type_id', $invoice->id)->delete();

            $description = $products[$i]['quantity'] . ' ' . __(' quantity sold in invoice') . ' ' . Auth::user()->invoiceNumberFormat($invoice->invoice_id);

            if (empty($products[$i]['id'])) {
                Utility::addProductStock($products[$i]['item'], $products[$i]['quantity'], $type, $description, $type_id);
            }
        }

        TransactionLines::deleteAndRecalculateTransactionBalance($invoice, 'Invoice');

        $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
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

            $itemAmount = ($invoice_product->price * $invoice_product->quantity) - $invoice_product->discount + $totalTaxPrice;

            $data = [
                'account_id'        => $product->sale_chartaccount_id,
                'transaction_type'  => 'Credit',
                'transaction_amount' => $itemAmount,
                'reference'         => 'Invoice',
                'reference_id'      => $invoice->id,
                'reference_sub_id'  => $product->id,
                'date'              => $invoice->issue_date,
            ];
            Utility::addTransactionLines($data);
        }

        $invoice->updateCustomerBalance();

        // ---------- OAM INVOICE UPDATE ----------
        $oamInvoice = DB::connection(env('SECOND_DB_CONNECTION'))
            ->table('oam_invoices')
            ->where('invoice_id', $invoice->id)
            ->first();

        if ($oamInvoice) {
            $updated = DB::connection(env('SECOND_DB_CONNECTION'))
                ->table('oam_invoices')
                ->where('invoice_id', $invoice->id)
                ->update([
                    'building_id'          => $invoice->building_id,
                    'flat_id'              => $invoice->flat_id ?? 0,
                    'invoice_date'         => $request->issue_date,
                    'invoice_due_date'     => $request->due_date,
                    'general_fund_amount'  => $request->general_fund_amount ?? 0,
                    'reserve_fund_amount'  => $request->reserve_fund_amount ?? 0,
                    'additional_charges'   => $request->additional_charges ?? 0,
                    'previous_balance'     => $request->previous_balance ?? 0,
                    'adjust_amount'        => $request->adjust_amount ?? 0,
                    'budget_period'        => $request->budget_period ?? null,
                    'service_charge_group_id' => $request->service_charge_group_id ?? null,
                    'invoice_amount'       => $invoice->getTotal(),
                    'due_amount'           => $invoice->getDue(),
                    'updated_at'           => now(),
                ]);

            // Agar update fail ho jaye to rollback dono DB ka
            if (!$updated) {
                DB::rollBack();
                DB::connection(env('SECOND_DB_CONNECTION'))->rollBack();
                return redirect()->back()->with('error', 'OAM Invoice update failed. Transaction rolled back.');
            }
        }

        // ---------- COMMIT BOTH DATABASES ----------
        DB::commit();
        DB::connection(env('SECOND_DB_CONNECTION'))->commit();

        return redirect()->route('invoice.index')->with('success', __('Invoice successfully updated.'));
    }


    public function retainerNumber()
    {
        $latest = Utility::getValByName('retainer_starting_number');
        // $latest = Retainer::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (! $latest) {
            return 1;
        }

        // return $latest->retainer_id + 1;
        return $latest;
    }

    public function show($ids)
    {
        if (Auth::user()->can('show invoice')) {
            $id = Crypt::decrypt($ids);
            $invoice = Invoice::find($id);
            $users = User::where('id', $invoice->created_by)->first();

            if ($invoice->created_by == Auth::user()->creatorId()) {
                $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();
                $customer = $invoice->customer;
                $iteams = $invoice->items;

                $invoice->customField = CustomField::getData($invoice, 'invoice');
                $customFields = CustomField::where('created_by', '=', Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
                $revenueIds = DB::table('invoice_revenue')->where('invoice_id', $invoice->id)->pluck('revenue_id');
                $revenues = Revenue::whereIn('id', $revenueIds)->get();

                // Log::info('Revenue',[$revenues]);
                return view('invoice.view', compact('invoice', 'customer', 'iteams', 'invoicePayment', 'customFields', 'users', 'revenues'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy(Invoice $invoice, Request $request)
    {
        if (Auth::user()->can('delete invoice')) {
            if ($invoice->created_by == Auth::user()->creatorId()) {
                foreach ($invoice->payments as $invoices) {
                    Utility::bankAccountBalance($invoices->account_id, $invoices->amount, 'debit');

                    $invoicepayment = InvoicePayment::find($invoices->id);
                    $invoicepayment->deleteCustomerTransactionLine();
                    $invoices->delete();
                    $invoicepayment->delete();
                }

                if ($invoice->customer_id != 0 && $invoice->status != 0) {
                    $invoice->deleteCustomerTransactionLine();
                    // Utility::updateUserBalance('customer', $invoice->customer_id, $invoice->getDue(), 'debit');
                }

                // TransactionLines::where('reference_id', $invoice->id)->where('reference', 'Invoice')->delete();
                TransactionLines::deleteAndRecalculateTransactionBalance($invoice, 'Invoice');
                TransactionLines::deleteAndRecalculateTransactionBalance($invoice, 'Invoice Payment');
                // TransactionLines::where('reference_id', $invoice->id)->Where('reference', 'Invoice Payment')->delete();

                CreditNote::where('invoice', '=', $invoice->id)->delete();

                InvoiceProduct::where('invoice_id', '=', $invoice->id)->delete();
                $invoice->delete();

                return redirect()->route('invoice.index')->with('success', __('Invoice successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function productDestroy(Request $request)
    {
        if (Auth::user()->can('delete invoice product')) {
            $invoiceProduct = InvoiceProduct::find($request->id);
            $invoice = Invoice::find($invoiceProduct->invoice_id);
            $productService = ProductService::find($invoiceProduct->product_id);
            // Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

            TransactionLines::where('reference_sub_id', $productService->id)->where('reference', 'Invoice')->delete();

            InvoiceProduct::where('id', '=', $request->id)->delete();
            $invoice->updateCustomerBalance();

            $accountIds = TransactionLines::where('reference_id', $invoice->id)->where('reference', 'Invoice')->pluck('account_id');
            foreach ($accountIds as $accountId) {
                TransactionLines::recalculateTransactionBalance($accountId, $invoice->created_at);
            }

            return redirect()->back()->with('success', __('Invoice product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customerInvoice(Request $request)
    {
        if (Auth::user()->can('manage customer invoice')) {

            $status = Invoice::$statues;

            $query = Invoice::where('customer_id', '=', Auth::user()->id)->where('status', '!=', '0')->where('created_by', Auth::user()->creatorId());

            if (str_contains($request->issue_date, ' to ')) {
                $date_range = explode(' to ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            } elseif (! empty($request->issue_date)) {

                $query->where('issue_date', $request->issue_date);
            }

            // if(!empty($request->issue_date))
            // {
            //     $date_range = explode(' to ', $request->issue_date);
            //     $query->whereBetween('issue_date', $date_range);
            // }

            if (! empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $invoices = $query->get();

            return view('invoice.index', compact('invoices', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function customerInvoiceShow($id)
    {

        if (Auth::user()->can('show invoice')) {
            $invoice_id = Crypt::decrypt($id);
            $invoice = Invoice::where('id', $invoice_id)->first();
            if ($invoice->created_by == Auth::user()->creatorId()) {
                $customer = $invoice->customer;
                $iteams = $invoice->items;

                $company_payment_setting = Utility::getCompanyPaymentSetting($id);

                return view('invoice.view', compact('invoice', 'customer', 'iteams', 'company_payment_setting'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public static function sent($id, $user = null)
    {
        if (!Auth::user()?->can('send invoice') && !$user) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $invoice = Invoice::where('id', $id)->first();
        $invoice->send_date = date('Y-m-d');
        $invoice->status = 1;
        $invoice->save();

        $customer = Customer::where('id', $invoice->customer_id)->first();
        $invoice->name = ! empty($customer) ? $customer->name : '';
        $invoice->invoice = $user ? $user?->invoiceNumberFormat($invoice->invoice_id) : Auth::user()?->invoiceNumberFormat($invoice->invoice_id);

        $invoiceId = Crypt::encrypt($invoice->id);
        $invoice->url = route('invoice.pdf', $invoiceId);

        // Utility::updateUserBalance('customer', $customer->id, $invoice->getTotal(), 'credit');

        $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->get();
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
                'reference_id' => $invoice->id,
                'reference_sub_id' => $product->id,
                'date' => $invoice->issue_date,
            ];
            Utility::addTransactionLines($data, $user?->id, $invoice?->building_id);
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
        Utility::addTransactionLines($data, $user?->id, $invoice?->building_id);

        $uArr = [
            'invoice_name' => $invoice->name,
            'invoice_number' => $invoice->invoice,
            'invoice_url' => $invoice->url,
        ];
        $invoice->updateCustomerBalance();

        try {
            $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $uArr);
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        return redirect()->back()->with('success', __('Invoice successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function resent($id)
    {

        if (Auth::user()->can('send invoice')) {
            $invoice = Invoice::where('id', $id)->first();

            $customer = Customer::where('id', $invoice->customer_id)->first();
            $invoice->name = ! empty($customer) ? $customer->name : '';
            $invoice->invoice = Auth::user()->invoiceNumberFormat($invoice->invoice_id);

            $invoiceId = Crypt::encrypt($invoice->id);
            $invoice->url = route('invoice.pdf', $invoiceId);

            $uArr = [
                'invoice_name' => $invoice->name,
                'invoice_number' => $invoice->invoice,
                'invoice_url' => $invoice->url,
            ];

            try {
                $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->back()->with('success', __('Invoice successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function payment($invoice_id)
    {

        if (Auth::user()->can('create payment invoice')) {
            $invoice = Invoice::where('id', $invoice_id)->first();

            $customers = Customer::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $categories = ProductServiceCategory::where('created_by', '=', Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('invoice.payment', compact('customers', 'categories', 'accounts', 'invoice'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function createPayment(Request $request, $invoice_id)
    {
        if (Auth::user()->can('create payment invoice')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $user = Auth::user();

            $invoicePayment = $this->addPayment($request->date, $request->amount, $request->account_id, $invoice_id, $user, $request->reference, $request->description);

            if (! empty($request->add_receipt)) {
                // $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                // // $request->add_receipt->storeAs('uploads/payment', $fileName);
                // $invoicePayment->add_receipt = $fileName;

                // $dir        = 'uploads/payment';
                // $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
                // // $request->add_receipt  = $path;
                // if ($path['flag'] == 1) {
                //     $url = $path['url'];
                // } else {
                //     return redirect()->back()->with('error', __($path['msg']));
                // }
                $fileName = time() . '_' . $request->add_receipt->getClientOriginalName();

                $fileContent = file_get_contents($request->add_receipt);

                Storage::disk('s3')->put($fileName, $fileContent);

                $invoicePayment->add_receipt = $fileName;

                $invoicePayment->save();
            }

            return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        }
    }

    public function paymentDestroy(Request $request, $invoice_id, $payment_id)
    {

        if (Auth::user()->can('delete payment invoice')) {
            $payment = InvoicePayment::find($payment_id);
            $payment->deleteCustomerTransactionLine();

            InvoicePayment::where('id', '=', $payment_id)->delete();

            TransactionLines::where('reference_sub_id', $payment_id)->where('reference', 'Invoice Payment')->delete();

            $invoice = Invoice::where('id', $invoice_id)->first();
            $due = $invoice->getDue();
            $total = $invoice->getTotal();

            if ($due > 0 && $total != $due) {
                $invoice->status = 3;
            } else {
                $invoice->status = 2;
            }

            $invoice->save();
            $type = 'Partial';
            $user = 'Customer';
            Transaction::destroyTransaction($payment_id, $type, $user);

            $accountIds = TransactionLines::where('reference_id', $invoice_id)->where('reference', 'Invoice Payment')->pluck('account_id');
            foreach ($accountIds as $accountId) {
                TransactionLines::recalculateTransactionBalance($accountId, $invoice->created_at);
            }
            // Utility::updateUserBalance('customer', $invoice->customer_id, $payment->amount, 'credit');

            Utility::bankAccountBalance($payment->account_id, $payment->amount, 'debit');

            return redirect()->back()->with('success', __('Payment successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function paymentReminder($invoice_id)
    {

        $invoice = Invoice::find($invoice_id);
        $customer = Customer::where('id', $invoice->customer_id)->first();
        $invoice->dueAmount = Auth::user()->priceFormat($invoice->getDue());
        $invoice->name = $customer['name'];
        $invoice->date = Auth::user()->dateFormat($invoice->send_date);
        $invoice->invoice = Auth::user()->invoiceNumberFormat($invoice->invoice_id);

        $uArr = [
            'payment_name' => $invoice->name,
            'invoice_number' => $invoice->invoice,
            'payment_dueAmount' => $invoice->dueAmount,
            'payment_date' => $invoice->date,

        ];
        try {
            $resp = Utility::sendEmailTemplate('payment_reminder', [$customer->id => $customer->email], $uArr);
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        //Twilio Notification
        $setting = Utility::settings(Auth::user()->creatorId());
        $customer = Customer::find($invoice->customer_id);
        if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
            $uArr = [
                'payment_name' => $invoice->name,
                'invoice_number' => $invoice->invoice,
                'payment_dueAmount' => $invoice->dueAmount,
                'payment_date' => $invoice->date,

            ];
            Utility::send_twilio_msg($customer->contact, 'invoice_reminder', $uArr);
        }

        return redirect()->back()->with('success', __('Payment reminder successfully send.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function customerInvoiceSend($invoice_id)
    {
        return view('customer.invoice_send', compact('invoice_id'));
    }

    public function customerInvoiceSendMail(Request $request, $invoice_id)
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
        $invoice = Invoice::where('id', $invoice_id)->first();

        $customer = Customer::where('id', $invoice->customer_id)->first();
        $invoice->name = ! empty($customer) ? $customer->name : '';
        $invoice->invoice = Auth::user()->invoiceNumberFormat($invoice->invoice_id);

        $invoiceId = Crypt::encrypt($invoice->id);
        $invoice->url = route('invoice.pdf', $invoiceId);

        $uArr = [
            'invoice_name' => $invoice->name,
            'invoice_number' => $invoice->invoice,
            'invoice_url' => $invoice->url,
        ];

        try {
            $resp = Utility::sendEmailTemplate('customer_invoice_sent', [$customer->id => $customer->email], $uArr);
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        return redirect()->back()->with('success', __('Invoice successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function shippingDisplay(Request $request, $id)
    {
        $invoice = Invoice::find($id);

        if ($request->is_display == 'true') {
            $invoice->shipping_display = 1;
        } else {
            $invoice->shipping_display = 0;
        }
        $invoice->save();

        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($invoice_id)
    {
        if (Auth::user()->can('duplicate invoice')) {
            $invoice = Invoice::where('id', $invoice_id)->first();
            $duplicateInvoice = new Invoice;
            $duplicateInvoice->invoice_id = $this->invoiceNumber();
            $duplicateInvoice->customer_id = $invoice['customer_id'];
            $duplicateInvoice->issue_date = date('Y-m-d');
            $duplicateInvoice->due_date = $invoice['due_date'];
            $duplicateInvoice->send_date = null;
            $duplicateInvoice->category_id = $invoice['category_id'];
            $duplicateInvoice->ref_number = $invoice['ref_number'];
            $duplicateInvoice->status = 0;
            $duplicateInvoice->shipping_display = $invoice['shipping_display'];
            $duplicateInvoice->created_by = $invoice['created_by'];
            $duplicateInvoice->save();

            if ($duplicateInvoice) {
                $invoiceProduct = InvoiceProduct::where('invoice_id', $invoice_id)->get();
                foreach ($invoiceProduct as $product) {
                    $duplicateProduct = new InvoiceProduct;
                    $duplicateProduct->invoice_id = $duplicateInvoice->id;
                    $duplicateProduct->product_id = $product->product_id;
                    $duplicateProduct->quantity = $product->quantity;
                    $duplicateProduct->tax = $product->tax;
                    $duplicateProduct->discount = $product->discount;
                    $duplicateProduct->price = $product->price;
                    $duplicateProduct->save();
                }
            }

            return redirect()->back()->with('success', __('Invoice duplicate successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function previewInvoice($template, $color)
    {
        $objUser = Auth::user();
        $settings = Utility::settings();
        $invoice = new Invoice;

        $customer = new \stdClass;
        $customer->email = '<Email>';
        $customer->shipping_name = '<Customer Name>';
        $customer->shipping_country = '<Country>';
        $customer->shipping_state = '<State>';
        $customer->shipping_city = '<City>';
        $customer->shipping_phone = '<Customer Phone Number>';
        $customer->shipping_zip = '<Zip>';
        $customer->shipping_address = '<Address>';
        $customer->billing_name = '<Customer Name>';
        $customer->billing_country = '<Country>';
        $customer->billing_state = '<State>';
        $customer->billing_city = '<City>';
        $customer->billing_phone = '<Customer Phone Number>';
        $customer->billing_zip = '<Zip>';
        $customer->billing_address = '<Address>';
        $invoice->sku = 'Test123';

        $totalTaxPrice = 0;
        $taxesData = [];

        $items = [];
        for ($i = 1; $i <= 3; $i++) {
            $item = new \stdClass;
            $item->name = 'Item ' . $i;
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
                // $taxPrice         = 10;
                // $totalTaxPrice    += $taxPrice;
                // $itemTax['name']  = 'Tax ' . $k;
                // $itemTax['rate']  = '10 %';
                // $itemTax['price'] = '$10';
                // $itemTaxes[]      = $itemTax;

                $taxPrice = 10;
                $totalTaxPrice += $taxPrice;
                $itemTax['name'] = 'Tax ' . $k;
                $itemTax['rate'] = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[] = $itemTax;

                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[] = $item;
        }

        $invoice->invoice_id = 1;
        $invoice->issue_date = date('Y-m-d H:i:s');
        $invoice->due_date = date('Y-m-d H:i:s');
        $invoice->itemData = $items;

        $invoice->totalTaxPrice = 60;
        $invoice->totalQuantity = 3;
        $invoice->totalRate = 300;
        $invoice->totalDiscount = 10;
        $invoice->taxesData = $taxesData;
        $invoice->customField = [];
        $customFields = [];

        $preview = 1;
        $color = '#' . $color;
        $font_color = Utility::getFontColor($color);

        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $invoice_logo = Utility::getValByName('invoice_logo');
        if (isset($invoice_logo) && ! empty($invoice_logo)) {
            // $img = Utility::get_file($invoice_logo);

            $img = Utility::get_file('invoice_logo/') . $invoice_logo;
            // $img = asset(\Storage::url('invoice_logo/') . $invoice_logo);
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && ! empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        return view('invoice.templates.' . $template, compact('invoice', 'preview', 'color', 'img', 'settings', 'customer', 'font_color', 'customFields'));
    }

    public function invoice($invoice_id)
    {

        $settings = Utility::settings();

        $invoiceId = Crypt::decrypt($invoice_id);
        $invoice = Invoice::where('id', $invoiceId)->first();

        if (! empty($invoice)) {
            $data = DB::table('settings');
            $data = $data->where('created_by', '=', $invoice->created_by);
            $data1 = $data->get();
        } else {
            return redirect()->back()->with('error', __('Invoice is not found'));
        }

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $customer = $invoice->customer;
        $items = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];
        foreach ($invoice->items as $product) {
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
                    $itemTax['rate'] = $tax->rate . '%';
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

        $invoice->itemData = $items;
        $invoice->totalTaxPrice = $totalTaxPrice;
        $invoice->totalQuantity = $totalQuantity;
        $invoice->totalRate = $totalRate;
        $invoice->totalDiscount = $totalDiscount;
        $invoice->taxesData = $taxesData;
        $invoice->customField = CustomField::getData($invoice, 'invoice');
        $customFields = [];
        if (! empty(Auth::user())) {
            $customFields = CustomField::where('created_by', '=', Auth::user()->creatorId())->where('module', '=', 'invoice')->get();
        }

        //Set your logo
        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
        $invoice_logo = $settings_data['invoice_logo'];
        if (isset($invoice_logo) && ! empty($invoice_logo)) {
            // $img = asset(\Storage::url('invoice_logo/') . $invoice_logo);
            $img = Utility::get_file('invoice_logo/') . $invoice_logo;
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && ! empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($invoice) {
            $color = '#' . $settings['invoice_color'];
            $font_color = Utility::getFontColor($color);

            return view('invoice.templates.' . $settings['invoice_template'], compact('invoice', 'color', 'settings', 'customer', 'img', 'font_color', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function saveTemplateSettings(Request $request)
    {
        $user = Auth::user();
        $post = $request->all();
        unset($post['_token']);

        if ($request->invoice_logo) {
            $request->validate(
                [
                    'invoice_logo' => 'image',
                ]
            );

            $dir = 'invoice_logo/';
            $invoice_logo = $user->id . '_invoice_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];

            $path = Utility::upload_file($request, 'invoice_logo', $invoice_logo, $dir, $validation);
            if ($path['flag'] == 1) {
                $proposal_logo = $path['url'];
            } else {
                return redirect()->back()->with('error', __($path['msg']));
            }

            // $path                 = $request->file('invoice_logo')->storeAs('/invoice_logo', $invoice_logo);
            $post['invoice_logo'] = $invoice_logo;
        }

        if (isset($post['invoice_template']) && (! isset($post['invoice_color']) || empty($post['invoice_color']))) {
            $post['invoice_color'] = 'ffffff';
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

        return redirect()->back()->with('success', __('Invoice Setting updated successfully'));
    }

    public function items(Request $request)
    {

        $items = InvoiceProduct::where('invoice_id', $request->invoice_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }

    public function payinvoice($invoice_id)
    {
        if (! empty($invoice_id)) {
            $id = \Illuminate\Support\Facades\Crypt::decrypt($invoice_id);

            $invoice = Invoice::where('id', $id)->first();

            if (! is_null($invoice)) {

                $settings = Utility::settings();

                $items = [];
                $totalTaxPrice = 0;
                $totalQuantity = 0;
                $totalRate = 0;
                $totalDiscount = 0;
                $taxesData = [];

                foreach ($invoice->items as $item) {
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
                            $itemTax['tax'] = $tax->tax . '%';
                            $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                            $itemTaxes[] = $itemTax;

                            if (array_key_exists($tax->name, $taxesData) && isset($tax)) {
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

                            if (array_key_exists('No Tax', $taxesData) && isset($tax)) {
                                $taxesData[$tax->tax_name] = $taxesData['No Tax'] + $taxPrice;
                            } else {
                                $taxesData['No Tax'] = $taxPrice;
                            }
                        }
                    }
                    $item->itemTax = $itemTaxes;
                    $items[] = $item;
                }
                $invoice->items = $items;
                $invoice->totalTaxPrice = $totalTaxPrice;
                $invoice->totalQuantity = $totalQuantity;
                $invoice->totalRate = $totalRate;
                $invoice->totalDiscount = $totalDiscount;
                $invoice->taxesData = $taxesData;

                $ownerId = $invoice->created_by;

                $company_setting = Utility::settingById($ownerId);

                $payment_setting = Utility::invoice_payment_settings($ownerId);
                // dd($payment_setting);

                $users = User::where('id', $invoice->created_by)->first();
                if (! is_null($users)) {
                    \App::setLocale($users->lang);
                } else {
                    $users = User::where('type', 'owner')->first();
                    \App::setLocale($users->lang);
                }

                $invoice = Invoice::where('id', $id)->first();
                $customer = $invoice->customer;
                $iteams = $invoice->items;
                $company_payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);

                return view('invoice.invoicepay', compact('invoice', 'iteams', 'company_setting', 'users', 'company_payment_setting'));
            } else {
                return abort('404', 'The Link You Followed Has Expired');
            }
        } else {
            return abort('404', 'The Link You Followed Has Expired');
        }
    }

    public function pdffrominvoice($id)
    {

        $settings = Utility::settings();

        $invoiceId = Crypt::decrypt($id);
        $invoice = Invoice::where('id', $invoiceId)->first();

        $data = \DB::table('settings');
        $data = $data->where('created_by', '=', $invoice->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $user = new User;
        $user->name = $invoice->name;
        $user->email = $invoice->contacts;
        $user->mobile = $invoice->contacts;

        $user->bill_address = $invoice->billing_address;
        $user->bill_zip = $invoice->billing_postalcode;
        $user->bill_city = $invoice->billing_city;
        $user->bill_country = $invoice->billing_country;
        $user->bill_state = $invoice->billing_state;

        $user->address = $invoice->shipping_address;
        $user->zip = $invoice->shipping_postalcode;
        $user->city = $invoice->shipping_city;
        $user->country = $invoice->shipping_country;
        $user->state = $invoice->shipping_state;

        $items = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate = 0;
        $totalDiscount = 0;
        $taxesData = [];

        foreach ($invoice->items as $product) {
            $item = new \stdClass;
            $item->name = $product->item;
            $item->quantity = $product->quantity;
            $item->tax = ! empty($product->taxs) ? $product->taxs->rate : '';
            $item->discount = $product->discount;
            $item->price = $product->price;

            $totalQuantity += $item->quantity;
            $totalRate += $item->price;
            $totalDiscount += $item->discount;

            $taxes = Utility::tax($product->tax);
            $itemTaxes = [];
            foreach ($taxes as $tax) {
                $taxPrice = Utility::taxRate($tax->rate, $item->price, $item->quantity);
                $totalTaxPrice += $taxPrice;

                $itemTax['name'] = $tax->tax_name;
                $itemTax['rate'] = $tax->rate . '%';
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

        $invoice->items = $items;
        $invoice->totalTaxPrice = $totalTaxPrice;
        $invoice->totalQuantity = $totalQuantity;
        $invoice->totalRate = $totalRate;
        $invoice->totalDiscount = $totalDiscount;
        $invoice->taxesData = $taxesData;

        //Set your logo
        $logo = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($invoice->created_by);
        $invoice_logo = $settings_data['invoice_logo'];
        if (isset($invoice_logo) && ! empty($invoice_logo)) {
            $img = asset(\Storage::url('invoice_logo/') . $invoice_logo);
        } else {
            $img = asset($logo . '/' . (isset($company_logo) && ! empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($invoice) {
            $color = '#' . $settings['invoice_color'];
            $font_color = Utility::getFontColor($color);

            return view('invoice.templates.' . $settings['invoice_template'], compact('invoice', 'user', 'color', 'settings', 'img', 'font_color'));
        } else {
            return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoiceId))->with('error', __('Permission denied.'));
        }
    }

    public function export()
    {
        $name = 'customer_' . date('Y-m-d i:h:s');
        $data = Excel::download(new InvoiceExport, $name . '.xlsx');

        return $data;
    }

    public static function createInvoice($customerId, $issueDate, $dueDate, $categoryId, $items, $refNumber = null, $discountApply = false, $customField = [], $buildingId = null, $flatId = null, $creatorId = null, $invoicePeriod = null, $fromMollak = false, $invoiceDetailUrl = null, $invoicePDF = null, $paymentUrl = null)
    {
        $refNumber = (int) $refNumber ? (int) $refNumber : crc32(Invoice::latest()->first()?->id + 1);

        // Create a new Invoice instance
        $invoice = new Invoice;
        $nextNumber = Auth::user()->invoiceNumberFormat(
            Invoice::invoiceNumberData($creatorId)
        );
        $invoice->invoice_number = $nextNumber;
        $invoice->customer_id = $customerId;
        $invoice->status = 0;
        $invoice->issue_date = $issueDate;
        $invoice->due_date = $dueDate;
        $invoice->category_id = $categoryId;
        $invoice->ref_number = $refNumber;
        $invoice->discount_apply = $discountApply ? 1 : 0;
        $invoice->created_by = $creatorId ?? Auth::user()->creatorId();
        $invoice->building_id = $buildingId;
        $invoice->flat_id = $flatId;
        $invoice->invoice_period = $invoicePeriod;
        $invoice->invoice_detail_link = $invoiceDetailUrl;
        $invoice->invoice_pdf_link = $invoicePDF;
        $invoice->payment_url = $paymentUrl;

        // Save the invoice 
        // dd($invoice);
        $invoice->save();

        // Generate PDF link and update invoice
        $pdfLink = route('invoice.pdf', Crypt::encrypt($invoice->id));
        $invoice->invoice_pdf_link = $pdfLink;
        $invoice->save();
        // dd($invoice);
        // Update the starting number for invoices
        Utility::starting_number($invoice->id + 1, 'invoice', $creatorId ?? Auth::user()->creatorId());

        // Save custom fields data
        CustomField::saveData($invoice, $customField);

        // Process each item in the invoice
        foreach ($items as $product) {
            $invoiceProduct = new InvoiceProduct;
            $invoiceProduct->invoice_id = $invoice->id;
            $invoiceProduct->product_id = $product['item'];
            $invoiceProduct->quantity = $product['quantity'];
            $invoiceProduct->tax = $product['tax'];
            $invoiceProduct->discount = $product['discount'];
            $invoiceProduct->price = $product['price'];
            $invoiceProduct->description = $product['description'];
            $invoiceProduct->due_amount = array_key_exists('due_amount', $product) ? $product['due_amount'] : 0;

            // Save the invoice product
            $invoiceProduct->save();

            // Update the total quantity of the product
            Utility::total_quantity('minus', $invoiceProduct->quantity, $invoiceProduct->product_id);

            // Create a product stock report
            $user = $creatorId == null ? Auth::user() : User::find($creatorId);
            $type = 'invoice';
            $typeId = $invoice->id;
            // $description = $invoiceProduct->quantity . ' ' . __('quantity sold in invoice') . ' ' . $user->invoiceNumberFormat($invoice->invoice_number);
            $description = $invoiceProduct->quantity . ' ' . __('quantity sold in invoice') . ' ' . $invoice->invoice_number;
            Utility::addProductStock($invoiceProduct->product_id, $invoiceProduct->quantity, $type, $description, $typeId, $creatorId);
        }
        if ($fromMollak) {
            self::sent($invoice->id, $user);
        }
        self::sendInvoiceNotifications($invoice, $customerId, $creatorId);

        return $invoice;
    }

    private static function sendInvoiceNotifications($invoice, $customerId, $creatorId = null)
    {
        $setting = Utility::settings($creatorId ?? Auth::user()->creatorId());
        $customer = Customer::find($customerId);
        $invoiceId = Crypt::encrypt($invoice->id);
        $invoice->url = route('invoice.pdf', $invoiceId);

        $user = $creatorId == null ? User::find($creatorId) : Auth::user();
        if (isset($setting['invoice_notification']) && $setting['invoice_notification'] == 1) {
            $uArr = [
                'invoice_name' => $customer->name,
                'invoice_number' => $user()->invoiceNumberFormat($invoice->invoice_id),
                'invoice_url' => $invoice->url,
            ];

            Utility::send_twilio_msg($customer->contact, 'new_invoice', $uArr);
        }

        // Webhook
        // Need to fix this
        $module = 'New Invoice';
        $webhook = Utility::webhookSetting($module);
        if ($webhook) {
            $parameter = json_encode($invoice);
            Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
        }
    }

    public static function invoiceNumberData($created_by = null)
    {
        $latest = Utility::getValByName('invoice_starting_number');
        $latest = Invoice::where('created_by', '=', $created_by ?? Auth::user()->creatorId())->orderByDesc('id')->first();
        if (! $latest) {
            return 1;
        } else {
            return $latest->id + 1;
        }
    }

    public function downloadPdf($invoiceId)
    {
        $invoice = Invoice::findOrFail($invoiceId);

        $response = Http::withoutVerifying()->withHeaders([
            'Content-Type' => 'application/json',
            'Consumer-Id' => env('MOLLAK_CONSUMER_ID'),
        ])->get($invoice->invoice_pdf_link);

        // Generate a filename based on the invoice number
        $filename = 'invoice_' . $invoice->invoice_id . '.pdf';

        return response($response->body())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
