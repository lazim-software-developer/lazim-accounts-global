<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\TransferType;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class BillPaymentController extends Controller
{
    public function index(Request $request)
    {
        if (\Auth::user()->can('manage bill')) {
            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $status = Bill::$statues;

            $bill = Bill::where('created_by', '=', \Auth::user()->creatorId());

            if (!empty($request->vender)) {
                $bill->where('vender_id', '=', $request->vender);
            }

            if (!empty($request->status)) {
                $bill->where('status', '=', $request->status);
            }

            $query = BillPayment::whereIn('bill_id', $bill->pluck('id'));

            if (str_contains($request->bill_date, ' to ')) {
                $date_range = explode(' to ', $request->bill_date);
                $query->whereBetween('date', $date_range);
            } elseif (!empty($request->bill_date)) {
                $query->where('date', $request->bill_date);
            }

            $billpayments = $query->get();

            return view('billPayment.index', compact('billpayments', 'vender', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if(!\Auth::user()->can('create bill')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $bills = Bill::where('created_by', \Auth::user()->creatorId())
                    ->where('status', '!=', 0)  // Not fully paid
                    ->get()
                    ->filter(function($bill) {
                        return $bill->getDue() > 0;
                    })
                    ->mapWithKeys(function($bill) {
                        $key = $bill->id;
                        $value = \Auth::user()->billNumberFormat($bill->bill_id) . ' (Due: ' . \Auth::user()->priceFormat($bill->getDue()) . ')';
                        return [$key => $value];
                    });

        $accounts = BankAccount::where('created_by', \Auth::user()->creatorId())
                             ->pluck('holder_name', 'id');

        return view('billPayment.create', compact('bills', 'accounts'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'required|exists:bank_accounts,id',
            'description' => 'nullable|string',
            'payment_receipt' => 'nullable|file|mimes:jpeg,png,pdf',
            'bills' => 'required|json',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        $bills = json_decode($request->input('bills'), true);
        $totalAdjustedAmount = array_sum(array_column($bills, 'adjusted_amount'));

        if ($totalAdjustedAmount != $request->amount) {
            return response()->json(['success' => false, 'errors' => ['amount' => ['The payment amount must be equal to the sum of the adjusted amounts.']]]);
        }

        foreach ($bills as $bill) {
            if ($bill['adjusted_amount'] <= 0) {
                return response()->json(['success' => false, 'errors' => ['adjusted_amount' => ['Adjusted amount must be greater than 0.']]]);
            }

            $billPayment = new BillPayment();
            $billPayment->date = $request->date;
            $billPayment->amount = $bill['adjusted_amount'];
            $billPayment->account_id = $request->account_id;
            $billPayment->description = $request->description;
            $billPayment->bill_id = $bill['bill_id'];
            $billPayment->building_id    = \Auth::user()->currentBuilding();

            if ($request->hasFile('payment_receipt')) {
                $fileName = time() . '_' . $request->file('payment_receipt')->getClientOriginalName();
                $fileContent = file_get_contents($request->file('payment_receipt'));
                Storage::disk('s3')->put($fileName, $fileContent);
                $billPayment->add_receipt = $fileName;
            }

            $billPayment->save();

            $billModel = Bill::find($bill['bill_id']);
            $billModel->status = $billModel->getDue() <= $bill['adjusted_amount'] ? 0 : $billModel->status;
            $billModel->save();
        }

        return response()->json(['success' => true, 'message' => __('Bill Payment created successfully.'), 'redirect' => route('BillPayment.index')]);
        
    }

    public function getBillsByVendor($vendor_id) 
    {
        $bills = Bill::where('vender_id', $vendor_id)
            ->where('created_by', \Auth::user()->creatorId())
            ->where('status', '!=', 0)  // Not fully paid
            // Get bills that still have remaining amount to be paid
            ->get()
            ->filter(function($bill) {
                return $bill->getDue() > 0;
            })
            ->map(function($bill) {
                return [
                    'id' => $bill->id,
                    'text' => \Auth::user()->billNumberFormat($bill->bill_id) . ' (' . \Auth::user()->priceFormat($bill->getDue()) . ')'
                ];
            })->values();
            
        return response()->json($bills);
    }

    public function getBillDueAmount($bill_id)
    {
        $bill = Bill::find($bill_id);
        return response()->json([
            'due_amount' => $bill->getDue()
        ]);
    }

    public function edit($id)
    {
        $billPayment = BillPayment::find($id);
        $bills = Bill::where('created_by', \Auth::user()->creatorId())
                    ->where('id', $billPayment->bill_id)  // Not fully paid
                    ->get()
                    ->filter(function($bill) {
                        return $bill->total_due >= 0;
                    })
                    ->mapWithKeys(function($bill) {
                        $key = $bill->id;
                        $value = \Auth::user()->billNumberFormat($bill->bill_id) . ' (Due: ' . $bill->total_due . ')';
                        return [$key => $value];
                    });

        $accounts = BankAccount::where('created_by', \Auth::user()->creatorId())
                             ->pluck('holder_name', 'id');
        $totalDue = Bill::where('created_by', \Auth::user()->creatorId())
        ->where('id', $billPayment->bill_id)  // Not fully paid
        ->first()->total_due;
        return view('billPayment.edit', compact('billPayment', 'bills', 'accounts','totalDue'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'account_id' => 'required|exists:bank_accounts,id',
            'description' => 'nullable|string',
            'payment_receipt' => 'nullable|file|mimes:jpeg,png,pdf',
            'bill_id' => 'required|exists:bills,id',
            'adjusted_amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        $billPayment = BillPayment::find($id);
        if ($billPayment) {
            $billPayment->date = $request->date;
            $billPayment->amount = $request->adjusted_amount;
            $billPayment->account_id = $request->account_id;
            $billPayment->description = $request->description;
            $billPayment->bill_id = $request->bill_id; // Ensure bill_id is updated

            if ($request->hasFile('payment_receipt')) {
                $fileName = time() . '_' . $request->file('payment_receipt')->getClientOriginalName();
                $fileContent = file_get_contents($request->file('payment_receipt'));
                Storage::disk('s3')->put($fileName, $fileContent);
                $billPayment->add_receipt = $fileName;
            }

            $billPayment->save();

            $billModel = Bill::find($request->bill_id);
            $billModel->status = $billModel->getDue() <= $request->adjusted_amount ? 0 : $billModel->status;
            $billModel->save();
        }

        return response()->json(['success' => true, 'message' => __('Bill Payment updated successfully.'), 'redirect' => route('BillPayment.index')]);
    }

    public function destroy($id)
    {
        if (\Auth::user()->can('delete bill')) {
            $billPayment = BillPayment::find($id);
            if ($billPayment) {
                $billPayment->delete();
                return redirect()->route('BillPayment.index')->with('success', __('Bill Payment deleted successfully.'));
            } else {
                return redirect()->route('BillPayment.index')->with('error', __('Bill Payment not found.'));
            }
        } else {
            return redirect()->route('BillPayment.index')->with('error', __('Permission denied.'));
        }
    }

    public function transfer($id)
    {
        $billPayment = BillPayment::find($id);
        $transferMethods = ['online' => 'Online', 'cheque' => 'Cheque', 'cash' => 'Cash'];
        return view('billPayment.payment', compact('billPayment', 'transferMethods'));
    }

    public function updateTransfer(Request $request, $id)
    {
        $billPayment = BillPayment::find($id);

        // Create a new transfer type entry
        $transferType = new TransferType();
        $transferType->transfer_type = $request->transfer_method;
        $transferType->reference_number = $request->reference_number;
        $transferType->date = $request->transfer_date;
        $transferType->transferable_id = $billPayment->id;
        $transferType->transferable_type = BillPayment::class;

        $transferType->save();

        return redirect()->route('BillPayment.index')->with('success', __('Transfer method updated successfully.'));
    }

    public function show($id)
    {
        $billPayment = BillPayment::findOrFail($id);
        $transferTypes = TransferType::where('transferable_id', $billPayment->id)
                                     ->where('transferable_type', BillPayment::class)
                                     ->get();
        return view('billPayment.show', compact('billPayment', 'transferTypes'));
    }
}
