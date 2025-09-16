<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionLines extends Model
{
    protected $fillable = [
        'id',
        'account_id',
        'reference',
        'reference_id',
        'reference_sub_id',
        'date',
        'credit',
        'debit',
        'created_by',
        'opening_balance',
        'closing_balance'
    ];

    public function getPreviousTransaction()
    {
        return self::where('account_id', $this->account_id)
                ->when(!empty($this->id), function ($q){
                    $q->where('id', '<', $this->id);
                })
                ->latest('id')
                ->first();
    }

    public function account(){
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public static function recalculateTransactionBalance($account_id, $fromDate){
        $transactions = TransactionLines::where('account_id', $account_id)
                                   ->where('created_at', '>=', $fromDate)
                                   ->orderBy('created_at')
                                   ->get();

        foreach ($transactions as $transaction) {
            $previousTransaction = $transaction->getPreviousTransaction();

            $openingBalance = $previousTransaction ? $previousTransaction->closing_balance : $transaction->account->initial_balance;

            // Recalculate closing balance
            if (!empty((float) $transaction->credit)) {
                $closingBalance = Utility::getClosingBalance($transaction->account->types->name, 'credit', $transaction->credit, $openingBalance);
            } else {
                $closingBalance = Utility::getClosingBalance($transaction->account->types->name, 'debit', $transaction->debit, $openingBalance);
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
        $accountIds = self::where('reference_id', $obj->id)->where('reference', $reference)->pluck('account_id');
        self::where('reference_id', $obj->id)->where('reference', $reference)->delete();
        foreach($accountIds as $accountId){
            self::recalculateTransactionBalance($accountId, $obj->created_at);
        }
    }
}
