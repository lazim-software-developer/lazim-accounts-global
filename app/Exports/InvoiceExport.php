<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoiceExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $data = [];
        // $data = Invoice::where('created_by' , \Auth::user()->id)->get();

        if (!\Auth::guard('customer')->check()) {
            $data = Invoice::select('invoice_number', 'customer_id', 'issue_date', 'due_date', 'send_date', 'category_id', 'ref_number', 'status')->where('created_by', \Auth::user()->id)->get();
        } else {
            $data = Invoice::select('invoice_number', 'customer_id', 'issue_date', 'due_date', 'send_date', 'category_id', 'ref_number', 'status')->where('customer_id', '=', \Auth::guard('customer')->check())->where('status', '!=', '0')->get();
        }

        if (!empty($data)) {
            foreach ($data as $k => $Invoice) {
                $customer  = Invoice::customers($Invoice->customer_id);
                $category  = Invoice::Invoicecategory($Invoice->category_id);
                if($Invoice->status == 0)
                {
                    $status = 'Draft';
                }
                elseif($Invoice->status == 1)
                {
                    $status = 'Sent';
                }
                elseif($Invoice->status == 2)
                {
                    $status = 'Unpaid';
                }
                elseif($Invoice->status == 3)
                {
                    $status = 'Partialy Paid';
                }
                elseif($Invoice->status == 4)
                {
                    $status = 'Paid';
                }
                unset($Invoice->discount_apply,$Invoice->shipping_display,$Invoice->id,$Invoice->created_by, $Invoice->updated_at, $Invoice->created_at);
                
                $data[$k]["customer_id"]        = $customer;
                $data[$k]["category_id"]   = $category;
                $data[$k]["status"]   = $status;

            }
        }
        // dd($data);
        return $data;
    }

    public function headings(): array
    {
        return [
            "Invoice Number",
            "Customer Name",
            "Issue Date",
            "Due Date",
            "Send Date",
            "Category_name",
            "Ref number",
            "status",
        ];
    }
}
