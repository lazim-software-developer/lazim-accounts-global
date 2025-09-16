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

class ImportInvoiceController extends Controller
{
    use HelperTrait;

    public function __construct()
    {
        $this->middleware('auth', ['except' => ['invoice', 'payinvoice', 'export']]);
    }

    public function invoicePopup()
    {
        return view('invoice.import');
    }
    // public function invoiceSample()
    // {
    //     $creatorId = Auth::user()->currentBuilding();
    //     $buildings = DB::connection('mysql_lazim')->table('buildings')->where('id', $creatorId)->first();
    //     $headers = array(
    //         "Content-Type" => "text/csv",
    //         "Content-Disposition" => "attachment; filename=" . $buildings->name . "-Invoice-" . date('d-m-Y') . "-.csv"
    //     );

    //     $contents = "Customer Name,Customer Email,Building,Flat,Issue Date,Due Date,Send Date,Product Category,Reference Number,Amount,Invoice Period,Description\r\n";
    //     $customer = Customer::where('building_id', '=', $creatorId)->whereNotNull('flat_id')->get();
    //     foreach ($customer as $key => $value) {
    //         $buildings = DB::connection('mysql_lazim')->table('buildings')->where('id', $value->building_id)->first();
    //         $flat = DB::connection('mysql_lazim')->table('flats')->where('id', $value->flat_id)->first();
    //         $contents .= $value->name . "," . $value->email . "," . $buildings->name . "," . $flat->property_number . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "," . "" . "\r\n";
    //     }

    //     return response()->make($contents, 200, $headers);
    // }


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

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Invoice Export Failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
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
}
