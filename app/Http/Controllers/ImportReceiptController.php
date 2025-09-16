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
use Illuminate\Support\Facades\Crypt;
use App\Models\ProductServiceCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;


class ImportReceiptController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['invoice', 'payinvoice', 'export']]);
    }

    public function receiptPopup()
    {
        return view('revenue.import');
    }
    public function receiptSample()
    {
        $creatorId = Auth::user()->currentBuilding();
        $buildings = DB::connection('mysql_lazim')->table('buildings')->where('id', $creatorId)->first();
        $headers = array(
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=" . rawurlencode($buildings->name) . "-Receipt-" . date('d-m-Y') . ".csv"
        );

        $contents = "Reference Type(New Ref|Advance|Against Ref),Customer Name,Customer Email,Unit,Building,Category,Date,Invoice Number,Invoice Amount,General Fund,Reserve Fund,Cash,General Fund + Reserve Fund,Reference Number,Description\r\n";

        $customer = Customer::where('building_id', '=', $creatorId)->whereNotNull('flat_id')->get();
        foreach ($customer as $key => $value) {
            $buildings = DB::connection('mysql_lazim')->table('buildings')->where('id', $value->building_id)->first();
            $flat = DB::connection('mysql_lazim')->table('flats')->where('id', $value->flat_id)->first();
            $contents .= "" . "," . $value->name . "," . $value->email . "," . $flat->property_number . "," . $buildings->name . "," . "Service Charges" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "\r\n";
        }

        return response()->make($contents, 200, $headers);
    }

    // public function receiptSample(Request $request)
    // {
    //     try {
    //         $creatorId = Auth::user()->currentBuilding();

    //         // ✅ Customers (for template)
    //         $customers = Customer::where('building_id', $creatorId)
    //             ->get(['id', 'name', 'property_number']);

    //         // ✅ Categories
    //         $categories = ProductServiceCategory::where('created_by', Auth::user()->creatorId())
    //             ->where('type', 'income')
    //             ->pluck('id', 'name'); // name => id

    //         // ✅ Bank Accounts
    //         $bankAccounts = BankAccount::where('created_by', Auth::user()->creatorId())
    //             ->pluck('holder_name', 'id'); // id => holder_name

    //         // ✅ Actual Receipts (Revenue + relations)
    //         $receipts = Revenue::with(['customer', 'category', 'bankAllocations', 'customerDetails'])
    //             ->whereHas('customer', function ($query) use ($creatorId) {
    //                 $query->where('building_id', $creatorId);
    //             })
    //             ->get();

    //         $filename = 'Receipts-' . date('d-m-Y') . '.csv';

    //         $headers = [
    //             "Content-Type"        => "text/csv",
    //             "Content-Disposition" => "attachment; filename={$filename}",
    //         ];

    //         // ✅ CSV Headers (store() ke mutabiq)
    //         $columns = [
    //             'Date',
    //             'Receipt Number',
    //             'Customer IDs',
    //             'Customer Name(s)',
    //             'Unit Number(s)',
    //             'Invoice Numbers',
    //             'Adjusted Amounts',
    //             'Reference Types',
    //             'Reference Details',
    //             'Bank Account IDs',
    //             'Bank Account Names',
    //             'Bank Amounts',
    //             'VAT Applicable (Yes/No)',
    //             'Category Name',
    //             'Reference',
    //             'Description',
    //             'Payment Method',
    //         ];

    //         $callback = function () use ($receipts, $columns, $customers, $categories, $bankAccounts) {
    //             $file = fopen('php://output', 'w');
    //             fputcsv($file, $columns);

    //             if ($receipts->count() > 0) {
    //                 /**
    //                  * ✅ Export actual receipt data (one row per receipt)
    //                  */
    //                 foreach ($receipts as $receipt) {
    //                     $customerIds     = $receipt->customerDetails->pluck('customer_id')->implode(',');
    //                     $customerNames   = $receipt->customerDetails->map(fn($c) => $c->customer->name ?? '')->implode(',');
    //                     $unitNumbers     = $receipt->customerDetails->map(fn($c) => $c->customer->property_number ?? '')->implode(',');
    //                     $invoiceNumbers  = $receipt->customerDetails->pluck('invoice_number')->implode(',');
    //                     $adjustedAmounts = $receipt->customerDetails->pluck('amount')->implode(',');
    //                     $refTypes        = $receipt->customerDetails->pluck('reference_type')->implode(',');
    //                     $refDetails      = $receipt->customerDetails->pluck('reference_details')->implode(',');

    //                     $bankIds     = $receipt->bankAllocations->pluck('bank_account_id')->implode(',');
    //                     $bankNames   = $receipt->bankAllocations->map(fn($b) => $bankAccounts[$b->bank_account_id] ?? '')->implode(',');
    //                     $bankAmounts = $receipt->bankAllocations->pluck('amount')->implode(',');
    //                     $vatFlags    = $receipt->bankAllocations->map(fn($b) => $b->vat_applicable ? 'Yes' : 'No')->implode(',');

    //                     fputcsv($file, [
    //                         $receipt->date ?? '',
    //                         $receipt->receipt_number ?? ('REC-' . $receipt->id),
    //                         $customerIds,
    //                         $customerNames,
    //                         $unitNumbers,
    //                         $invoiceNumbers,
    //                         $adjustedAmounts,
    //                         $refTypes,
    //                         $refDetails,
    //                         $bankIds,
    //                         $bankNames,
    //                         $bankAmounts,
    //                         $vatFlags,
    //                         $receipt->category->name ?? '',
    //                         $receipt->reference ?? '',
    //                         $receipt->description ?? '',
    //                         $receipt->payment_method ?? '',
    //                     ]);
    //                 }
    //             } else {
    //                 /**
    //                  * 📝 Export sample structure if no receipts exist
    //                  */
    //                 foreach ($customers as $customer) {
    //                     foreach ($categories as $categoryName => $categoryId) {
    //                         foreach ($bankAccounts as $bankId => $bankName) {
    //                             fputcsv($file, [
    //                                 now()->toDateString(),     // Date
    //                                 '',                        // Receipt Number
    //                                 $customer->id,             // Customer ID
    //                                 $customer->name,           // Customer Name
    //                                 $customer->property_number, // Unit Number
    //                                 '',                        // Invoice Numbers
    //                                 '',                        // Adjusted Amounts
    //                                 '',                        // Reference Types
    //                                 '',                        // Reference Details
    //                                 $bankId,                   // Bank Account ID
    //                                 $bankName,                 // Bank Account Name
    //                                 '',                        // Bank Amount
    //                                 'No',                      // VAT Applicable
    //                                 $categoryName,             // Category Name
    //                                 '',                        // Reference
    //                                 '',                        // Description
    //                                 '',                        // Payment Method
    //                             ]);
    //                         }
    //                     }
    //                 }
    //             }

    //             fclose($file);
    //         };

    //         return response()->stream($callback, 200, $headers);
    //     } catch (\Exception $e) {
    //         Log::error('Receipt Export Failed: ' . $e->getMessage());
    //         return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
    //     }
    // }


    public function receiptImport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the following errors: ' . $validator->errors()->first());
        }
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        try {
            if (in_array($extension, ['xlsx', 'xls'])) {
                // Handle Excel files using Laravel Excel package
                $data = Excel::toArray([], $file);

                if (empty($data) || empty($data[0])) {
                    return redirect()->back()->with('error', 'The uploaded file is empty or invalid.');
                }

                $rows = $data[0]; // Get first sheet
                $header = array_shift($rows);

                // Clean header row
                $header = array_map('trim', $header);
            } else {
                // Handle CSV files
                $csvContent = file_get_contents($file->getRealPath());

                if ($csvContent === false) {
                    return redirect()->back()->with('error', 'Failed to read the uploaded file.');
                }

                // Handle different line endings and convert to UTF-8 if needed
                $csvContent = str_replace(["\r\n", "\r"], "\n", $csvContent);

                // Check if file is empty
                if (empty(trim($csvContent))) {
                    return redirect()->back()->with('error', 'The uploaded CSV file is empty.');
                }

                $rows = array_map('str_getcsv', explode("\n", $csvContent));

                // Remove empty rows
                $rows = array_filter($rows, function ($row) {
                    return !empty(array_filter($row, function ($cell) {
                        return !empty(trim($cell));
                    }));
                });

                if (empty($rows)) {
                    return redirect()->back()->with('error', 'No valid data found in the CSV file.');
                }

                $header = array_shift($rows);
                $header = array_map('trim', $header);
            }

            // Validate header structure
            if (empty($header)) {
                return redirect()->back()->with('error', 'Invalid file format: Header row is missing.');
            }

            $processedCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row, function ($cell) {
                        return !empty(trim($cell));
                    }))) {
                        continue;
                    }

                    // Ensure row has same number of columns as header
                    $row = array_pad($row, count($header), '');

                    // Combine header with row data
                    $data = array_combine($header, $row);

                    if ($data === false) {
                        $errors[] = "Row " . ($index + 2) . ": Invalid data structure";
                        $errorCount++;
                        continue;
                    }

                    // Clean the data
                    $data = array_map('trim', $data);

                    // Process the data here
                    echo "<pre>";
                    print_r($data);
                    die;

                    $processedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    $errorCount++;

                    // Stop processing if too many errors
                    if ($errorCount > 10) {
                        $errors[] = "Too many errors encountered. Processing stopped.";
                        break;
                    }
                }
            }

            // Prepare response message
            $message = "File processed successfully. {$processedCount} records processed.";

            if ($errorCount > 0) {
                $message .= " {$errorCount} errors encountered.";

                // Log errors for debugging
                \Log::warning('File upload errors:', $errors);

                return redirect()->back()
                    ->with('warning', $message)
                    ->with('upload_errors', array_slice($errors, 0, 5)); // Show first 5 errors
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('File upload processing failed: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to process the uploaded file. Please try again.');
        }
    }
}
