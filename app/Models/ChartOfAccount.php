<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'sub_type',
        'parent',
        'is_enabled',
        'description',
        'created_by',
        'building_id',
        'is_sync',
    ];

    public function types()
    {
        return $this->hasOne('App\Models\ChartOfAccountType', 'id', 'type');
    }

    public function accounts()
    {
        return $this->hasOne('App\Models\JournalItem', 'account', 'id');
    }

    public function balance()
    {
        $journalItem = JournalItem::select(\DB::raw('sum(credit) as totalCredit'), \DB::raw('sum(debit) as totalDebit'), \DB::raw('sum(credit) - sum(debit) as netAmount'))->where('account', $this->id);
        $journalItem = $journalItem->first();
        $data['totalCredit'] = $journalItem->totalCredit;
        $data['totalDebit'] = $journalItem->totalDebit;
        $data['netAmount'] = $journalItem->netAmount;

        return $data;
    }

    public function subType()
    {
        return $this->hasOne('App\Models\ChartOfAccountSubType', 'id', 'sub_type');
    }

    public function parentAccount()
    {
        return $this->hasOne('App\Models\ChartOfAccountParent', 'id', 'parent');
    }

    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'chart_account_id', 'id');
    }

    public static function getServiceChargeAccountName()
    {
        return 'Service Charges ' . date("Y") . ' (Tax) - Gen & Res Fund';
    }

    public function parent()
    {
        return $this->belongsTo(ChartOfAccountParent::class, 'parent');
    }

    public function parents()
    {
        return $this->hasMany(ChartOfAccountParent::class, 'account', 'id');
    }
    public static function tree($createdBy = null)
    {
        // Get all chart of accounts for the specified user (or all if null)
        $query = ChartOfAccount::query();

        if ($createdBy) {
            $query->where('created_by', $createdBy);
        }

        $allAccounts = $query->get();

        // Get root accounts (parent = 0)
        $rootAccounts = $allAccounts->where('parent', 0);

        // Build the tree structure
        self::buildTree($rootAccounts, $allAccounts, $createdBy);

        return $rootAccounts->values(); // Reset array keys
    }

    private static function buildTree($accounts, $allAccounts, $createdBy)
    {
        foreach ($accounts as $account) {
            // Find children of current account
            $children = ChartOfAccountParent::with('children')
                ->where('created_by', $createdBy)
                ->where('account', $account->id)
                ->first();

            if (isset($children) && !empty($children)) {
                // Add children to the account
                $account->children = $children->children->values();

                // Recursively build tree for children
                self::buildTree($account->children, $allAccounts, $createdBy);
            } else {
                // Ensure children property exists even if empty
                $account->children = null;
            }
        }
    }
}
