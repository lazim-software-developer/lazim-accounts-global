<?php

namespace App\Traits;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoiceProduct;
use App\Models\ProductServiceCategory;
use App\Models\Revenue;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Stripe as Stripe;

trait HelperTrait
{

    public function createInvoice($customerId, $issueDate, $dueDate, $categoryId, $items, $refNumber = null, $discountApply = false, $customField = [], $buildingId = null, $flatId = null, $invoicePeriod = null, $created_by = null, $invoiceId = null)
    {
        try {
            if ($customerId) {
                $invoice = Invoice::create([
                    'invoice_id' => $invoiceId,
                    'customer_id' => $customerId,
                    'status' => 0,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'category_id' => $categoryId,
                    'ref_number' => $refNumber,
                    'discount_apply' => $discountApply ? 1 : 0,
                    'created_by' => $created_by,
                    'building_id' => $buildingId,
                    'flat_id' => $flatId,
                    'invoice_period' => $invoicePeriod,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to save invoice', ['error' => $e->getMessage()]);
        }
    }


    public function addPayment($date, $amount, $account_id, $invoice_id, $creator, $reference = null, $description = null, $buildingId = null)
    {
        $invoicePayment                 = new InvoicePayment();
        $invoicePayment->invoice_id     = $invoice_id;
        $invoicePayment->date           = $date;
        $invoicePayment->amount         = $amount;
        $invoicePayment->account_id     = $account_id;
        $invoicePayment->payment_method = 0;
        $invoicePayment->reference      = $reference;
        $invoicePayment->description    = $description;

        $invoicePayment->save();

        $invoicePayment->updateCustomerBalance();
        $invoice = Invoice::where('id', $invoice_id)->first();
        $due     = $invoice->getDue();
        $total   = $invoice->getTotal();
        if ($invoice->status == 0) {
            $invoice->send_date = date('Y-m-d');
            $invoice->save();
        }

        if ($due <= 0) {
            $invoice->status = 4;
            $invoice->save();
        } else {
            $invoice->status = 3;
            $invoice->save();
        }
        $invoicePayment->user_id    = $invoice->customer_id;
        $invoicePayment->user_type  = 'Customer';
        $invoicePayment->type       = 'Partial';
        $invoicePayment->created_by = $creator->id;
        $invoicePayment->payment_id = $invoicePayment->id;
        $invoicePayment->category   = 'Invoice';
        $invoicePayment->account    = $account_id;
        $invoicePayment->building_id    = $buildingId ?? \Auth::user()->currentBuilding();

        Transaction::addTransaction($invoicePayment);

        $customer = Customer::where('id', $invoice->customer_id)->first();

        $payment            = new InvoicePayment();
        $payment->name      = $customer['name'];
        $payment->date      = $creator->dateFormat($date);
        $payment->amount    = $creator->priceFormat($amount);
        $payment->invoice   = 'invoice ' . $creator->invoiceNumberFormat($invoice->invoice_id);
        $payment->dueAmount = $creator->priceFormat($invoice->getDue());

        // Utility::updateUserBalance('customer', $invoice->customer_id, $amount, 'debit');

        Utility::bankAccountBalance($account_id, $amount, 'credit');

        $invoicePayments = InvoicePayment::where('invoice_id', $invoice->id)->get();
        foreach ($invoicePayments as $invoicePayment) {
            $accountId = BankAccount::find($invoicePayment->account_id);
            if ($accountId) {
                $data = [
                    'account_id' => $accountId->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $invoicePayment->amount,
                    'reference' => 'Invoice Payment',
                    'reference_id' => $invoice->id,
                    'reference_sub_id' => $invoicePayment->id,
                    'date' => $invoicePayment->date,
                    'building_id' => $buildingId ?? \Auth::user()->currentBuilding()
                ];
                Utility::addTransactionLines($data);
            }
        }

        $uArr = [
            'payment_name' => $payment->name,
            'payment_amount' => $payment->amount,
            'invoice_number' => $payment->invoice,
            'payment_date' => $payment->date,
            'payment_dueAmount' => $payment->dueAmount
        ];
        try {
            $resp = Utility::sendEmailTemplate('new_invoice_payment', [$customer->id => $customer->email], $uArr);
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        return $invoicePayment;
    }

    public function createRevenue($date, $amount, $accountId, $categoryId, $customerId = null, $reference = null, $description = null, $addReceipt = null, $created_by = null, $buildingId = null, $flatId = null, $receiptPeriod = null)
    {
        $validator = \Validator::make(
            compact('date', 'amount', 'accountId', 'categoryId'),
            [
                'date' => 'required',
                'amount' => 'required',
                'accountId' => 'required',
                'categoryId' => 'required',
            ]
        );

        if ($validator->fails()) {
            return [
                'status' => 'error',
                'message' => $validator->getMessageBag()->first()
            ];
        }
        $reference = $reference ?? $reference = crc32(Revenue::latest()->first()?->id+1);

        $revenue = new Revenue();
        $revenue->date = $date;
        $revenue->amount = $amount;
        $revenue->account_id = $accountId;
        $revenue->customer_id = $customerId;
        $revenue->category_id = $categoryId;
        $revenue->payment_method = 0;
        $revenue->reference = $reference;
        $revenue->description = $description;

        if ($addReceipt) {
            $image_size = $addReceipt->getSize();
            $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

            if ($result == 1) {
                $fileName = time() . "_" . $addReceipt->getClientOriginalName();
                $revenue->add_receipt = $fileName;
                $dir = 'uploads/revenue';
                $path = Utility::upload_file(compact('addReceipt'), 'add_receipt', $fileName, $dir, []);

                if ($path['flag'] == 0) {
                    return [
                        'status' => 'error',
                        'message' => __($path['msg'])
                    ];
                }
            } else {
                return [
                    'status' => 'error',
                    'message' => $result
                ];
            }
        }

        $revenue->created_by = $created_by;
        $revenue->flat_id = $flatId;
        $revenue->building_id = $buildingId;
        $revenue->receipt_period = $receiptPeriod;
        $revenue->save();
        $revenue->updateCustomerBalance();

        $category = ProductServiceCategory::find($categoryId);
        $revenue->payment_id = $revenue?->id;
        $revenue->type = 'Revenue';
        $revenue->category = $category->name;
        $revenue->user_id = $customerId ?? 0;
        $revenue->user_type = 'Customer';
        $revenue->account = $accountId;
        Transaction::addTransaction($revenue);

        if ($customerId) {
            $customer = Customer::find($customerId);
            Utility::userBalance('customer', $customer?->id, $revenue->amount, 'credit');
        }

        Utility::bankAccountBalance($accountId, $amount, 'credit');

        $account = BankAccount::find($revenue->account_id);
        $data = [
            'account_id' => $account->chart_account_id,
            'transaction_type' => 'Debit',
            'transaction_amount' => $revenue->amount,
            'reference' => 'Revenue',
            'reference_id' => $revenue?->id,
            'reference_sub_id' => 0,
            'date' => $revenue->date,
        ];
        Utility::addTransactionLines($data,$created_by, $buildingId);
        $user = User::find($created_by);
        if (isset($customer) && !empty($customer->email)) {
            $uArr = [
                'payment_name' => $customer->name,
                'payment_amount' => $user? $user->priceFormat($amount) : \Auth::user()->priceFormat($amount),
                'invoice_number' => $revenue->type,
                'payment_date' => $user? $user->dateFormat($date) : \Auth::user()->dateFormat($date),
                'payment_dueAmount' => '-',
            ];

            try {
                Utility::sendEmailTemplate('new_invoice_payment', [$customer?->id => $customer->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }
        }

        $setting = Utility::settings($created_by);
        if (isset($setting['revenue_notification']) && $setting['revenue_notification'] == 1 && isset($customer)) {
            $uArr = [
                'payment_name' => $customer->name,
                'payment_amount' => $user? $user->priceFormat($amount) : \Auth::user()->priceFormat($amount),
                'payment_date' => $user? $user->dateFormat($date) : \Auth::user()->dateFormat($date),
                'user_name' => $user? $user->name :\Auth::user()->name,
            ];
            Utility::send_twilio_msg($customer->contact, 'new_revenue', $uArr);
        }

        $module = 'New Revenue';
        $webhook = Utility::webhookSetting($module,$created_by);
        if ($webhook) {
            $parameter = json_encode($revenue);
            $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
            if (!$status) {
                return [
                    'status' => 'error',
                    'message' => __('Webhook call failed.')
                ];
            }
        }

        return [
            'status' => 'success',
            'message' => __('Revenue successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''),
            'revenue' => $revenue,
        ];
    }
}
