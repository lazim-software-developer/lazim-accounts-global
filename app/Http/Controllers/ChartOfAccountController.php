<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ChartOfAccountParent;
use Illuminate\Support\Facades\Auth;
use App\Models\ChartOfAccountSubType;
use Illuminate\Support\Facades\Validator;

class ChartOfAccountController extends Controller
{

    public function index(Request $request)
    {
        if (Auth::user()->can('manage chart of account')) {
            if (!empty($request->start_date) && !empty($request->end_date)) {
                $start = $request->start_date;
                $end = $request->end_date;
            } else {
                $start = date('Y-01-01');
                $end = date('Y-m-d', strtotime('+1 day'));
            }
            $filter['startDateRange'] = $start;
            $filter['endDateRange'] = $end;

            $types = ChartOfAccountType::where('created_by', '=', Auth::user()->creatorId())->get();

            // Get tree structure for all accounts
            $treeAccounts = ChartOfAccount::tree(Auth::user()->creatorId());

            // Filter tree accounts by types
            $filteredTreeAccounts = $treeAccounts->filter(function ($account) use ($types) {
                return $types->pluck('id')->contains($account->type);
            });

            // Group the filtered tree accounts by type
            $accounts = $filteredTreeAccounts->groupBy('type');

            // $accounts = ChartOfAccount::whereIn('type', $types->pluck('id'))
            //     ->where('created_by', '=', Auth::user()->creatorId())
            //     ->with(['subType', 'parentAccount'])
            //     ->get()
            //     ->groupBy('type');

            $chartAccounts = [];
            foreach ($types as $type) {
                $typeName = $type->name;
                $chartAccounts[$typeName] = $accounts[$type->id] ?? [];
            }

            return view('chartOfAccount.index', compact('chartAccounts', 'types', 'filter'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        $types = ChartOfAccountType::where('building_id', Auth::user()->currentBuilding())->get();

        $account_type = [];

        foreach ($types as $type) {
            $accountTypes = ChartOfAccountSubType::where('type', $type->id)->where('building_id', Auth::user()->currentBuilding())->get();
            $temp = [];
            foreach ($accountTypes as $accountType) {
                $temp[$accountType->id] = $accountType->name;
            }
            $account_type[$type->name] = $temp;
        }
        $selectAcc =     [
            null => "Select",
        ];

        $account_type =  array_merge($selectAcc, $account_type);

        return view('chartOfAccount.create', compact('account_type'));
    }


    public function store(Request $request)
    {
        if (!Auth::user()->can('create chart of account')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'sub_type' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            DB::beginTransaction();

            $type = ChartOfAccountSubType::where('id', $request->sub_type)->where('created_by', '=', Auth::user()->creatorId())->first();

            if (isset($request->parent) && !empty($request->parent)) {
                $account = ChartOfAccount::where('id', $request->parent)->where('created_by', '=', Auth::user()->creatorId())->first();
                $existingparentAccount = ChartOfAccountParent::where('name', $account->name)->where('created_by', Auth::user()->creatorId())->first();
                if (!isset($existingparentAccount) && empty($existingparentAccount)) {
                    if (isset($account) && !empty($account)) {
                        $parentAccount = new ChartOfAccountParent();
                        $parentAccount->name        = $account->name;
                        $parentAccount->sub_type    = $request->sub_type;
                        $parentAccount->type        = $type->type;
                        $parentAccount->account     = $request->parent;
                        $parentAccount->created_by  = Auth::user()->creatorId();
                        $parentAccount->save();
                    } else {
                        Log::error('####### ChartOfAccountController -> store() #######  ' . 'Parent account not found');
                    }
                }
            }

            $account              = new ChartOfAccount();
            $account->name        = $request->name ?? null;
            $account->code        = $request->code ?? null;
            $account->type        = $type->type ?? null;
            $account->sub_type    = $request->sub_type ?? null;
            $account->parent      = $parentAccount->id ?? 0; // need to define self table relation to save parent account
            $account->description = $request->description ?? null;
            $account->is_enabled  = isset($request->is_enabled) ? 1 : 0;
            $account->created_by  = Auth::user()->creatorId();
            $account->building_id  = Auth::user()->currentBuilding();
            $account->initial_balance  = $request->initial_balance ?? null;
            $account->save();

            DB::commit();
            return redirect()->route('chart-of-account.index')->with('success', __('Account successfully created.'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('####### ChartOfAccountController -> store() #######  ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    public function show(ChartOfAccount $chartOfAccount)
    {
        //
    }


    public function edit(ChartOfAccount $chartOfAccount)
    {
        $types = ChartOfAccountType::get()->pluck('name', 'id');
        $types->prepend('--', 0);

        return view('chartOfAccount.edit', compact('chartOfAccount', 'types'));
    }


    public function update(Request $request, ChartOfAccount $chartOfAccount)
    {

        if (Auth::user()->can('edit chart of account')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }


            $chartOfAccount->name        = $request->name;
            $chartOfAccount->code        = $request->code;
            $chartOfAccount->description = $request->description;
            $chartOfAccount->is_enabled  = isset($request->is_enabled) ? 1 : 0;
            $chartOfAccount->building_id  = Auth::user()->currentBuilding();
            $chartOfAccount->initial_balance  = $request->initial_balance ?? null;
            $chartOfAccount->save();

            return redirect()->route('chart-of-account.index')->with('success', __('Account successfully updated.'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function destroy(ChartOfAccount $chartOfAccount)
    {
        if (Auth::user()->can('delete chart of account')) {
            $chartOfAccount->delete();

            return redirect()->route('chart-of-account.index')->with('success', __('Account successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getSubType(Request $request)
    {
        $types = ChartOfAccount::where('sub_type', $request->type)->get()->pluck('name', 'id');
        // $types->prepend('Select an account', 0);

        return response()->json($types);
    }
}
