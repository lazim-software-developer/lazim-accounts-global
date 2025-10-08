<?php

namespace App\Exports;

use App\Models\Revenue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RevenueExport implements FromCollection, WithHeadings
{
    protected $date;

    function __construct($date)
    {
        $this->date = $date;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = [];
        if (in_array(\Auth::user()->type, ['company', 'building'])) {
            $data = Revenue::select('id', 'date', 'amount', 'account_id', 'customer_id', 'category_id', 'reference', 'description')->where('created_by', \Auth::user()->creatorId())->get();
        } else {
            $data = Revenue::select('id', 'date', 'amount', 'account_id', 'customer_id', 'category_id', 'reference', 'description')->get();
        }
        // $data = Revenue::where('created_by' , \Auth::user()->id);

        if ($this->date != null && $this->date != 0) {
            if (str_contains($this->date, ' to ')) {
                $date_range = explode(' to ', $this->date);
                $data->whereBetween('date', $date_range);
            } elseif (!empty($this->date)) {
                $data->where('date', $this->date);
            }
        }

        // $data = $data->get();

        if (!empty($data)) {
            foreach ($data as $k => $Revenue) {
                $customer_id = json_decode($Revenue->customer_id);

                $account = Revenue::accounts($Revenue->account_id);
                $customer = Revenue::customers($customer_id[0]);
                $category = Revenue::categories($Revenue->category_id);
                // dd($category);

                unset($Revenue->created_by, $Revenue->updated_at, $Revenue->created_at, $Revenue->payment_method, $Revenue->add_receipt);
                $data[$k]['account_id'] = $account;
                $data[$k]['customer_id'] = $customer;
                $data[$k]['category_id'] = $category;
            }
        }
        return $data;
    }

    public function headings(): array
    {
        return [
            'Revenue Id',
            'Date',
            'Amount',
            'Account',
            'Customer',
            'Category',
            'Reference',
            'Description',
        ];
    }
}
