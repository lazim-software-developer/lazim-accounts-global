<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'user_type',
        'account',
        'type',
        'amount',
        'description',
        'date',
        'created_by',
        'customer_id',
        'payment_id',
        'category',
        'deleted_at',
    ];


    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account')->first();
    }

    public static function addTransaction($request)
    {
        $transaction              = new Transaction();
        $transaction->account     = $request->account;
        $transaction->user_id     = $request->user_id;
        $transaction->user_type   = $request->user_type;
        $transaction->type        = $request->type;
        $transaction->amount      = $request->amount;
        $transaction->description = $request->description;
        $transaction->date        = $request->date;
        $transaction->created_by  = $request->created_by;
        $transaction->payment_id  = $request->id;
        $transaction->category    = $request->category;
        $transaction->building_id    = $request->building_id ?? Auth::user()->currentBuilding();
        $transaction->save();
    }

    public static function editTransaction($request)
    {
        if (isset($request->payment_id)) {
            $transaction              = Transaction::where('payment_id', $request->payment_id)->where('type', $request->type)->first();
            $transaction->account     = $request->account;
            $transaction->amount      = $request->amount;
            $transaction->description = $request->description;
            $transaction->date        = $request->date;
            $transaction->category    = $request->category;
            $transaction->save();
        }
    }

    public static function destroyTransaction($id, $type, $user)
    {
        Transaction::where('payment_id', $id)->where('type', $type)->where('user_type', $user)->delete();
    }

    public function payment()
    {
        return $this->hasOne('App\Models\InvoicePayment', 'id', 'payment_id');
    }

    public function billPayment()
    {
        return $this->hasOne('App\Models\BillPayment', 'id', 'payment_id');
    }

    public static function accounts($account)
    {
        $categoryArr  = explode(',', $account);
        $unitRate = 0;
        foreach ($categoryArr as $account) {
            if ($account == 0) {
                $unitRate = '';
            } else {
                $account        = BankAccount::find($account);
                $unitRate       = ($account->bank_name . '  ' . $account->holder_name);
            }
        }

        return $unitRate;
    }
}
