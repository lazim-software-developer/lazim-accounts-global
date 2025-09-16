<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StakeholderTransactionLine extends Model
{
    use HasFactory;

    protected $table = 'stakeholder_transaction_lines';

    protected $fillable = [
        'vender_id',
        'customer_id',
        'reference',
        'reference_id',
        'reference_sub_id',
        'date',
        'opening_balance',
        'credit',
        'debit',
        'closing_balance',
        'created_by',
        'updated_by',
        'building_id'
    ];

    // Relationship to Vendor
    public function vendor()
    {
        return $this->belongsTo(Vender::class, 'vender_id');
    }

    // Relationship to Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function getStakeholder()
    {
        return $this->vendor ?: $this->customer;
    }

    public function getPreviousTransaction()
    {
        return StakeholderTransactionLine::when($this->vender_id, function ($q) {
            $q->where('vender_id', $this->vender_id);
        })->when($this->customer_id, function ($q) {
            $q->where('customer_id', $this->customer_id);
        })
            ->where('created_at', '<', $this->created_at)
            ->latest('created_at')
            ->first();
    }

    public static function recalculateStakeholderBalances($userColumn, $userId, $fromDate)
    {
        $transactions = StakeholderTransactionLine::where($userColumn, $userId)
            ->where('created_at', '>=', $fromDate)
            ->orderBy('created_at')
            ->get();

        foreach ($transactions as $transaction) {
            $previousTransaction = $transaction->getPreviousTransaction();
            $openingBalance = $previousTransaction ? $previousTransaction->closing_balance : $transaction->getStakeholder()->initial_balance;

            // Recalculate closing balance
            if (!empty((float) $transaction->credit)) {
                $closingBalance = Utility::getClosingBalance($transaction->getStakeholder()::ACCOUNT_TYPE, 'credit', $transaction->credit, $openingBalance);
            } else {
                $closingBalance = Utility::getClosingBalance($transaction->getStakeholder()::ACCOUNT_TYPE, 'debit', $transaction->debit, $openingBalance);
            }

            // Update transaction balances
            $transaction->update([
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance
            ]);
        }
    }

    public static function deleteAndRecalculateTransactionBalance($obj, $reference)
    {
        $tansactions = self::where('reference_id', $obj->id)->where('reference', $reference)->get();
        self::where('reference_id', $obj->id)->where('reference', $reference)->delete();
        foreach ($tansactions as $transaction) {
            if (!empty($transaction->customer_id)) {
                self::recalculateStakeholderBalances("customer_id", $transaction->customer_id, $transaction->created_at);
            } else {
                self::recalculateStakeholderBalances("vender_id", $transaction->vender_id, $transaction->created_at);
            }
        }
    }

    public static function getTransactionHistory($type, $id, $start_date = null, $end_date = null)
    {
        $q = self::query();

        if (!empty($start_date) && !empty($end_date)) {
            $start = $start_date;
            $end = $end_date;
        } else {
            $start = date('Y-m-01');
            $end = date('Y-m-t');
        }

        if ($type == "customer") {
            $q->whereNotNull('stakeholder_transaction_lines.customer_id')
                ->whereNull('stakeholder_transaction_lines.vender_id');

            if ($id) {
                $q->where('stakeholder_transaction_lines.customer_id', $id);
            }
        } else {
            $q->whereNotNull('stakeholder_transaction_lines.vender_id')
                ->whereNull('stakeholder_transaction_lines.customer_id');
            if ($id) {
                $q->where('stakeholder_transaction_lines.vender_id', $id);
            }
        }

        $transactionData = $q->where('stakeholder_transaction_lines.created_by', \Auth::user()->creatorId())
            ->whereBetween('stakeholder_transaction_lines.created_at', [$start, $end])
            ->leftJoin('invoices', function ($join) {
                $join->on('stakeholder_transaction_lines.reference_id', '=', 'invoices.id')
                    ->whereIn('stakeholder_transaction_lines.reference', ['Invoice Payment', 'Invoice']);
            })
            ->leftJoin('bills', function ($join) {
                $join->on('stakeholder_transaction_lines.reference_id', '=', 'bills.id')
                    ->whereIn('stakeholder_transaction_lines.reference', ['Bill', 'Bill Payment', 'Bill Account', 'Expense', 'Expense Account', 'Expense Payment']);
            })
            ->leftJoin('revenues', function ($join) {
                $join->on('stakeholder_transaction_lines.reference_id', '=', 'revenues.id')
                    ->whereIn('stakeholder_transaction_lines.reference', ['Revenue']);
            })
            ->leftJoin('payments', function ($join) {
                $join->on('stakeholder_transaction_lines.reference_id', '=', 'payments.id')
                    ->whereIn('stakeholder_transaction_lines.reference', ['Payment']);
            })
            ->leftJoin('bank_accounts', function ($join) {
                $join->on('stakeholder_transaction_lines.reference_id', '=', 'bank_accounts.id')
                    ->whereIn('stakeholder_transaction_lines.reference', ['Bank Account']);
            })
            ->leftJoin('customers as revenues_customers', 'revenues.customer_id', '=', 'revenues_customers.id')
            ->leftJoin('venders as payments_venders', 'payments.vender_id', '=', 'payments_venders.id')
            ->leftJoin('customers', 'stakeholder_transaction_lines.customer_id', '=', 'customers.id')
            ->leftJoin('venders', 'stakeholder_transaction_lines.vender_id', '=', 'venders.id')
            ->leftJoin('chart_of_accounts', 'stakeholder_transaction_lines.reference_id', '=', 'chart_of_accounts.id')
            ->select(
                'stakeholder_transaction_lines.*',
                'invoices.customer_id as customer_id',
                'bills.vender_id as vendor_id',
                'chart_of_accounts.name as account_name',
                \DB::raw("COALESCE(customers.name, venders.name , revenues_customers.name , payments_venders.name, bank_accounts.holder_name) as user_name"),
                \DB::raw("COALESCE(invoices.invoice_id, bills.bill_id) as ids"),
            )
            ->get();

        return $transactionData;
    }

    public static function getCustomerStatement($customerId, $fromDate = null, $toDate = null)
    {
        $query = self::where('stakeholder_transaction_lines.customer_id', $customerId)
            ->where('stakeholder_transaction_lines.created_by', \Auth::user()->creatorId())
            ->where('stakeholder_transaction_lines.building_id', \Auth::user()->building_id);

        if (!empty($fromDate) && !empty($toDate)) {
            $query->whereBetween('stakeholder_transaction_lines.date', [$fromDate, $toDate]);
        }

        return $query->leftJoin('invoices', function ($join) {
            $join->on('stakeholder_transaction_lines.reference_id', '=', 'invoices.id')
                ->where('stakeholder_transaction_lines.reference', 'Invoice');
        })
            ->leftJoin('revenues', function ($join) {
                $join->on('stakeholder_transaction_lines.reference_id', '=', 'revenues.id')
                    ->where('stakeholder_transaction_lines.reference', 'Revenue');
            })
            ->select(
                'stakeholder_transaction_lines.*',
                'invoices.ref_number as invoice_number',
                'revenues.reference as revenue_number'
            )
            ->orderBy('stakeholder_transaction_lines.date')
            ->get();
    }
}
