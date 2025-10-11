<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use App\Models\Invoice;
use App\Models\Utility;
use App\Models\Customer;
use App\Models\CreditNote;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\InvoiceProduct;
use App\Models\ProductService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CreditNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (\Auth::user()->can('manage credit note')) {
            $invoices = Invoice::where('created_by', \Auth::user()->creatorId())->get();

            return view('creditNote.index', compact('invoices'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create($invoice_id)
    {
        if (\Auth::user()->can('create credit note')) {
            $invoiceDue = Invoice::where('id', $invoice_id)->first();
            dd($invoiceDue);
            return view('creditNote.create', compact('invoiceDue', 'invoice_id'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request, $invoice_id)
    {

        if (\Auth::user()->can('create credit note')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $invoiceDue = Invoice::where('id', $invoice_id)->first();
            // if($request->amount > $invoiceDue->getDue())
            // {
            //     return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            // }
            $invoice = Invoice::where('id', $invoice_id)->first();

            $credit              = new CreditNote();
            $credit->invoice     = $invoice_id;
            $credit->customer    = $invoice->customer_id;
            $credit->date        = $request->date;
            $credit->amount      = $request->amount;
            $credit->vat_percentage  = (int) Tax::where('name', 'VAT')->where('building_id', \Auth::user()->currentBuilding())->first()?->rate ?? 5;
            $credit->description = $request->description;
            $credit->reference   = crc32(CreditNote::latest()->first()?->id + 1);
            $credit->save();

            // Utility::userBalance('customer', $invoice->customer_id, $request->amount, 'debit');

            // Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');
            $credit->updateCustomerBalance();

            return redirect()->back()->with('success', __('Credit Note successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function edit($invoice_id, $creditNote_id)
    {
        if (\Auth::user()->can('edit credit note')) {

            $creditNote = CreditNote::find($creditNote_id);
            $creditNote['vat_amount'] = $creditNote->amount * ($creditNote->vat_percentage / 100);
            $customers = Customer::where('building_id', \Auth::user()->currentBuilding())->get()->pluck('name', 'id');
            $invoices = Invoice::where('id', $creditNote->invoice)
            ->select('id', 'invoice_id')
            ->get()
            ->mapWithKeys(function ($invoice) {
                $total = $invoice->getDue(); // Calculate the total using the method
                return [
                    $invoice->id => Auth::user()->invoiceNumberFormat($invoice->invoice_id) . ' - ' . $total,
                ];
            });
            $invoice = Invoice::with('customer')
                ->where('id', $invoice_id)
                ->where('building_id', Auth::user()->currentBuilding())
                ->firstOrFail();
            $dueAmount = $invoice->getDue();
            return view('creditNote.edit', compact('creditNote', 'customers', 'invoices', 'dueAmount'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, $invoice_id, $creditNote_id)
    {

        if (\Auth::user()->can('edit credit note')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $invoiceDue = Invoice::where('id', $invoice_id)->first();

            if ($request->amount > $invoiceDue->getDue()) {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            }

            $credit = CreditNote::find($creditNote_id);
            // Utility::updateUserBalance('customer', $invoiceDue->customer_id, $credit->amount, 'debit');
            $credit->date        = $request->date;
            $credit->amount      = $request->amount;
            $credit->vat_percentage  = (int) Tax::where('name', 'VAT')->where('building_id', \Auth::user()->currentBuilding())->first()?->rate ?? 5;
            $credit->description = $request->description;
            $credit->total_amount   = $request->amount + $request->vat_amount;
            $credit->save();
            $credit->updateCustomerBalance();

            // Utility::updateUserBalance('customer', $invoiceDue->customer_id, $request->amount, 'credit');

            return redirect()->back()->with('success', __('Credit Note successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy($invoice_id, $creditNote_id)
    {
        if (\Auth::user()->can('delete credit note')) {

            $creditNote = CreditNote::find($creditNote_id);
            $creditNote->delete();

            // Utility::updateUserBalance('customer', $creditNote->customer, $creditNote->amount, 'credit');

            return redirect()->back()->with('success', __('Credit Note successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customCreate()
    {
        if (\Auth::user()->can('create credit note')) {

            $invoices = Invoice::where('created_by', \Auth::user()->creatorId())->get()->pluck('invoice_id', 'id');
            $customers = Customer::where('building_id', \Auth::user()->currentBuilding())->get()->pluck('name', 'id');
            return view('creditNote.custom_create', compact('invoices', 'customers'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customStore(Request $request)
    {
        if (\Auth::user()->can('create credit note')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'invoice' => 'required|numeric|exists:invoices,id',
                    'amount' => 'required|numeric',
                    'date' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $creatorId = \Auth::user()->creatorId();
            $VATCharge = DB::table('settings')->where('name', 'vat_charge')->where('created_by', $creatorId)->first();
            if ($VATCharge === null) {
                return redirect()->back()->with('error', __('No VAT charge ledger found for this building.Please add VAT charge ledger first in system settings.'));
            }
            $invoice_id = $request->invoice;
            $invoiceDue = Invoice::where('id', $invoice_id)->first();

            // if($request->amount > $invoiceDue->getDue())
            // {
            //     return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($invoiceDue->getDue()) . ' credit limit of this invoice.');
            // }

            $vat = Tax::where('name', 'VAT')->where('building_id', \Auth::user()->currentBuilding())->first();
            $invoice             = Invoice::where('id', $invoice_id)->first();
            $credit              = new CreditNote();
            $credit->invoice     = $invoice_id;
            $credit->customer    = $invoice->customer_id;
            $credit->date        = $request->date;
            $credit->amount      = $request->amount;
            $credit->total_amount  = $request->amount + ($request->vat_percentage / 100) * $request->amount;
            $credit->vat_percentage  = (int) $vat?->rate ?? 5;
            $credit->description = $request->description;
            $credit->reference   = crc32(CreditNote::latest()->first()?->id + 1);
            $credit->building_id   = \Auth::user()->currentBuilding();
            $credit->created_by   = \Auth::user()->creatorId();
            $credit->save();

            // Utility::userBalance('customer', $invoice->customer_id, $request->amount, 'debit');

            $credit->updateCustomerBalance();

            $vatAccount = ChartOfAccount::where('id', $VATCharge->value)->where('created_by', '=', \Auth::user()->creatorId())->first();
            $creditNoteTotalTax = ($credit->vat_percentage / 100) * $request->amount;
            if ($vatAccount) {
                $data = [
                    'account_id' => $vatAccount->id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $creditNoteTotalTax,
                    'reference' => CreditNote::TYPE_CREDIT_NOTE,
                    'reference_id' => $credit->id,
                    'reference_sub_id' => $vat->id,
                    'date' => $credit->date,
                ];
                Utility::addTransactionLines($data, \Auth::user()->creatorId(), $credit?->building_id);
            }

            $invoice_products = InvoiceProduct::where('invoice_id', $invoice->id)->first();
            $product = ProductService::find($invoice_products->product_id);
            $data = [
                'account_id' => $product->sale_chartaccount_id,
                'transaction_type' => 'Debit',
                'transaction_amount' => $request->amount,
                'reference' => CreditNote::TYPE_CREDIT_NOTE,
                'reference_id' => $credit->id,
                'reference_sub_id' => $product->id,
                'date' => $credit->date,
            ];

            Utility::addTransactionLines($data, \Auth::user()->creatorId(), $credit?->building_id);

            return redirect()->back()->with('success', __('Credit Note successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getinvoice(Request $request)
    {
        $invoice = Invoice::where('id', $request->id)->first();

        echo json_encode($invoice->getDue());
    }

    public function show($invoice_id, $creditNote_id)
    {
        $creditNote = CreditNote::find($creditNote_id);
        $invoice = Invoice::find($invoice_id);

        return view('creditNote.show', compact('creditNote', 'invoice'));
    }
}
