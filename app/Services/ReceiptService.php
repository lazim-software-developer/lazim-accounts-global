<?php

namespace App\Services;

use App\Models\Tax;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\ChartOfAccount;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;

class ReceiptService
{
    public function createReceipt(array $data)
    {
        return DB::transaction(function () use ($data) {
            // Validate totals
            $totalAmount = collect($data['invoices'])->sum('amount');
            $bankTotal = collect($data['banks'])->sum('amount');
            if ($totalAmount !== $bankTotal) {
                throw new \Exception('Invoice payments and bank allocations must balance');
            }

            // Create Receipt
            $receipt = Receipt::create([
                'receipt_number' => $data['receipt_number'],
                'date' => $data['date'],
                'total_amount' => $totalAmount,
                'narration' => $data['narration'],
                'created_by' => auth()->id() ?: $data['created_by'],
                'building_id' => $data['building_id'] ?? null,
            ]);

            // Process Invoices
            foreach ($data['invoices'] as $invoiceData) {
                $invoice = Invoice::findOrFail($invoiceData['invoice_id']);

                // Validate partial payment
                if ($invoiceData['amount'] > $invoice->outstandingAmount()) {
                    throw new \Exception("Payment for invoice {$invoice->invoice_id} exceeds outstanding amount");
                }

                // Create Invoice Payment
                $payment = InvoicePayment::create([
                    'receipt_id' => $receipt->id,
                    'invoice_id' => $invoiceData['invoice_id'],
                    'date' => $data['date'],
                    'amount' => $invoiceData['amount'],
                    'account_id' => $invoiceData['account_id'] ?? $data['banks'][0]['account_id'],
                    'payment_method' => $invoiceData['payment_method'] ?? 1, // Default method
                    'reference' => $invoiceData['reference'] ?? null,
                    'description' => $invoiceData['description'] ?? null,
                ]);

                // Update Invoice Status
                $outstanding = $invoice->outstandingAmount();
                $invoice->status = $outstanding <= 0 ? 2 : 1; // 2 = Paid, 1 = Partially Paid
                $invoice->save();

                // Create Transaction for Customer (Credit)
                Transaction::create([
                    'user_id' => $invoice->customer_id,
                    'user_type' => 'customer',
                    'account' => $this->getCustomerAccountId($invoice->customer_id),
                    'type' => 'payment',
                    'amount' => $invoiceData['amount'],
                    'description' => "Payment for invoice {$invoice->invoice_id}",
                    'date' => $data['date'],
                    'created_by' => auth()->id() ?: $data['created_by'],
                    'payment_id' => $payment->id,
                ]);

                // Handle VAT 5%
                $tax = Tax::where('name', 'VAT 5%')->first();
                if ($tax && $invoiceData['apply_vat']) {
                    $vatAmount = $invoiceData['amount'] * 0.05;
                    Transaction::create([
                        'user_id' => $invoice->customer_id,
                        'user_type' => 'customer',
                        'account' => $tax->account_id,
                        'type' => 'tax',
                        'amount' => $vatAmount,
                        'description' => "VAT 5% for invoice {$invoice->invoice_id}",
                        'date' => $data['date'],
                        'created_by' => auth()->id() ?: $data['created_by'],
                        'payment_id' => $payment->id,
                    ]);
                    // Adjust customer transaction
                    Transaction::where('payment_id', $payment->id)
                        ->where('type', 'payment')
                        ->update(['amount' => $invoiceData['amount'] - $vatAmount]);
                }
            }

            // Process Bank Allocations
            foreach ($data['banks'] as $bankData) {
                $bank = BankAccount::findOrFail($bankData['account_id']);
                Transaction::create([
                    'user_id' => auth()->id() ?: $data['created_by'],
                    'user_type' => 'admin',
                    'account' => $bank->chart_account_id,
                    'type' => 'deposit',
                    'amount' => $bankData['amount'],
                    'description' => "Deposit for receipt {$receipt->receipt_number}",
                    'date' => $data['date'],
                    'created_by' => auth()->id() ?: $data['created_by'],
                    'payment_id' => 0,
                ]);
            }

            return $receipt;
        });
    }

    private function getCustomerAccountId($customerId)
    {
        // Map customer to chart of accounts (assumes customer-specific ledger exists)
        $customer = Customer::find($customerId);
        return ChartOfAccount::where('name', $customer->name)->first()->id ?? 0; // Fallback to default
    }
}