<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillAccount;
use App\Models\BillProduct;
use App\Models\ChartOfAccount;
use App\Models\DebitNote;
use App\Models\ProductService;
use App\Models\Tax;
use App\Models\Utility;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebitNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (\Auth::user()->can('manage debit note')) {
            $bills = Bill::where('created_by', \Auth::user()->creatorId())->get();

            return view('debitNote.index', compact('bills'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create($bill_id)
    {
        if (\Auth::user()->can('create debit note')) {
            $billDue = Bill::where('id', $bill_id)->first();
            $vendors = Vender::where('created_by', \Auth::user()->creatorId())->get();
            return view('debitNote.create', compact('billDue', 'bill_id', 'vendors'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function store(Request $request, $bill_id)
    {
        if (\Auth::user()->can('create debit note')) {
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
            $billDue = Bill::where('id', $bill_id)->first();

            if ($request->amount > $billDue->getDue()) {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($billDue->getDue()) . ' credit limit of this bill.');
            }
            $bill = Bill::where('id', $bill_id)->first();
            $debit = new DebitNote;
            $debit->bill = $bill_id;
            $debit->vendor = $bill->vender_id;
            $debit->date = $request->date;
            $debit->amount = $request->amount;
            $debit->description = $request->description;
            $debit->reference = crc32(DebitNote::latest()->first()?->id + 1);
            $debit->save();

            // Utility::userBalance('vendor', $bill->vender_id, $request->amount, 'debit');

            $debit->updateVendorBalance();
            // Utility::updateUserBalance('vendor', $bill->vender_id, $request->amount, 'credit');

            return redirect()->back()->with('success', __('Debit Note successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit($bill_id, $debitNote_id)
    {
        if (\Auth::user()->can('edit debit note')) {
            $debitNote = DebitNote::find($debitNote_id);
            $debitNote['vat_amount'] = $debitNote->amount * ($debitNote->vat_percentage / 100);
            $vendors = Vender::where('created_by', \Auth::user()->creatorId())->get();
            $bill = Bill::where('id', $debitNote->bill)->get();
            $bills = Bill::find($debitNote->bill);
            $billDue = $bills->getDue();
            return view('debitNote.edit', compact('debitNote', 'vendors', 'bill', 'billDue'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function update(Request $request, $bill_id, $debitNote_id)
    {
        if (\Auth::user()->can('edit debit note')) {
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
            $billDue = Bill::where('id', $bill_id)->first();
            if ($request->amount > $billDue->getDue()) {
                return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($billDue->getDue()) . ' credit limit of this bill.');
            }

            $debit = DebitNote::find($debitNote_id);
            // Utility::userBalance('vendor', $billDue->vender_id, $debit->amount, 'credit');

            // Utility::updateUserBalance('vendor', $billDue->vender_id, $debit->amount, 'debit');

            $debit->date = $request->date;
            $debit->amount = $request->amount;
            $debit->vat_percentage = (int) Tax::where('name', 'VAT')->where('building_id', \Auth::user()->currentBuilding())->first()?->rate ?? 5;
            $debit->description = $request->description;
            $debit->total_amount = $request->amount + $request->vat_amount;
            $debit->save();
            // Utility::userBalance('vendor', $billDue->vender_id, $request->amount, 'debit');

            $debit->updateVendorBalance();
            // Utility::updateUserBalance('vendor', $billDue->vender_id, $request->amount, 'credit');

            return redirect()->back()->with('success', __('Debit Note successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function destroy($bill_id, $debitNote_id)
    {
        if (\Auth::user()->can('delete debit note')) {
            $debitNote = DebitNote::find($debitNote_id);
            $debitNote->delete();

            // Utility::userBalance('vendor', $debitNote->vendor, $debitNote->amount, 'credit');

            // Utility::updateUserBalance('vendor', $debitNote->vendor, $debitNote->amount, 'debit');
            $debitNote->deleteVendorTransactionLine();

            return redirect()->back()->with('success', __('Debit Note successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customCreate()
    {
        if (\Auth::user()->can('create debit note')) {
            $bills = Bill::where('created_by', \Auth::user()->creatorId())->get()->pluck('bill_id', 'id');
            $vendors = Vender::where('created_by', \Auth::user()->creatorId())->get();
            return view('debitNote.custom_create', compact('bills', 'vendors'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customStore(Request $request)
    {
        if (!\Auth::user()->can('create debit note')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $validator = \Validator::make($request->all(), [
            'bill' => 'required|numeric|exists:bills,id',
            'amount' => 'required|numeric',
            'date' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $creatorId = \Auth::user()->creatorId();
        $buildingId = \Auth::user()->currentBuilding();

        // Validate VAT charge setting
        $VATCharge = DB::table('settings')->where('name', 'vat_charge')->where('created_by', $creatorId)->first();
        if (!$VATCharge) {
            return redirect()->back()->with('error', __('No VAT charge ledger found for this building. Please add VAT charge ledger first in system settings.'));
        }

        // Validate bill and amount
        $bill = Bill::findOrFail($request->bill);
        if ($request->amount > $bill->getDue()) {
            return redirect()->back()->with('error', 'Maximum ' . \Auth::user()->priceFormat($bill->getDue()) . ' credit limit of this bill.');
        }

        DB::beginTransaction();
        try {
            $vat = Tax::where('name', 'VAT')->where('building_id', $buildingId)->first();
            $vatRate = $vat?->rate ?? 5;
            $vatAmount = ($vatRate / 100) * $request->amount;

            // Create debit note
            $debit = DebitNote::create([
                'bill' => $request->bill,
                'vendor' => $bill->vender_id,
                'date' => $request->date,
                'amount' => $request->amount,
                'total_amount' => $request->amount + $vatAmount,
                'vat_percentage' => (int) $vatRate,
                'description' => $request->description,
                'reference' => crc32(DebitNote::latest()->first()?->id + 1),
                'building_id' => $buildingId,
                'created_by' => $creatorId,
            ]);

            // Update vendor balance
            $debit->updateVendorBalance();

            // Add VAT transaction line
            $vatAccount = ChartOfAccount::where('id', $VATCharge->value)
                ->where('created_by', $creatorId)
                ->first();

            if ($vatAccount) {
                Utility::addTransactionLines([
                    'account_id' => $vatAccount->id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $vatAmount,
                    'reference' => DebitNote::TYPE_DEBIT_NOTE,
                    'reference_id' => $debit->id,
                    'reference_sub_id' => $vat?->id,
                    'date' => $debit->date,
                ], $creatorId, $buildingId);
            }

            // Add product transaction line
            $invoiceProduct = BillAccount::where('bill_id', $bill->id)->first();
            if ($invoiceProduct && $product = ChartOfAccount::find($invoiceProduct->chart_account_id)) {
                Utility::addTransactionLines([
                    'account_id' => $product->id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $request->amount,
                    'reference' => DebitNote::TYPE_DEBIT_NOTE,
                    'reference_id' => $debit->id,
                    'reference_sub_id' => $product->id,
                    'date' => $debit->date,
                ], $creatorId, $buildingId);
            }

            DB::commit();
            return redirect()->back()->with('success', __('Debit Note successfully created.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('Failed to create Debit Note: ') . $e->getMessage());
        }
    }

    public function getbill(Request $request)
    {
        $bill = Bill::where('id', $request->bill_id)->first();
        echo json_encode($bill->getDue());
    }

    public function show($bill_id, $debitNote_id)
    {
        $debitNote = DebitNote::find($debitNote_id);
        $bill = Bill::find($bill_id);

        return view('debitNote.show', compact('debitNote', 'bill'));
    }
}
