<?php

namespace App\Jobs;

use App\Http\Controllers\InvoiceController;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\Tax;
use App\Models\User;
use App\Traits\HelperTrait;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAndSaveInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use HelperTrait;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $building)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $propertyGroupId = $this->building?->property_group_id;
        $buildingId = $this->building?->id;

        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');
        $currentQuarter = ceil($currentDate->format('n') / 3);

        $quarter = "Q" . $currentQuarter . "-JAN" . $currentYear . "-DEC" . $currentYear;

        try {
            // Build the URL
            $url = env("MOLLAK_API_URL") . "/sync/invoices/" . $propertyGroupId . "/all/".$quarter;
            // $url = env("MOLLAK_API_URL") . "/sync/invoices/" . $propertyGroupId . "/all/Q1-JAN2023-DEC2023";

            // Make the API request
            $response = Http::withoutVerifying()->withHeaders([
                'content-type' => 'application/json',
                'consumer-id'  => env("MOLLAK_CONSUMER_ID"),
            ])->get($url);

            // Log the response
            Log::info('RESPONSE', [$response->json()]);

            // Check if the response has the expected structure
            if (!$response->successful() || !isset($response->json()['response']['serviceChargeGroups'])) {
                throw new \Exception("Unexpected response structure or API call failed.");
            }

            $invoices = $response->json()['response']['serviceChargeGroups'];

            // Process the invoices data
            foreach ($invoices as $invoice) {
                foreach ($invoice['properties'] as $property) {
                    $connection = DB::connection('mysql_lazim');

                    // Fetch the flat
                    $flat = $connection->table('flats')->where('mollak_property_id', $property['mollakPropertyId'])->first();
                    if (!$flat) {
                        Log::warning("Flat not found for mollakPropertyId: " . $property['mollakPropertyId']);
                        continue;
                    }

                    // Fetch the created_by user
                    $created_by = User::where('owner_association_id', $flat->owner_association_id)->where('type', 'company')->first()?->id;
                    if (!$created_by) {
                        Log::warning("Created by user not found for owner association id: " . $flat->owner_association_id);
                        continue;
                    }

                    // Fetch the latest invoice number
                    $invoiceNo = Invoice::where('created_by', $created_by)->orderByDesc('invoice_id')->first();
                    $invoiceId = $invoiceNo ? $invoiceNo->invoice_id + 1 : 1;

                    // Fetch the customer ID
                    $customerId = Customer::where('flat_id', $flat->id)->where('building_id', $buildingId)->first()?->id;
                    if (!$customerId) {
                        Log::warning("Customer not found for flat id: " . $flat->id . " and building id: " . $buildingId);
                        continue;
                    }

                    // Fetch the category ID
                    $category_id = ProductServiceCategory::where('name', 'Service Charges')->first()?->id;
                    if (!$category_id) {
                        Log::warning("Service Charges category not found.");
                        continue;
                    }

                    // Generate a reference number
                    $ref_number = random_int(11111111, 99999999);

                    // Fetch the product
                    $product = ProductService::where('building_id', $buildingId)
                        ->where('name', 'Service Charges ' . date("Y") . ' (Tax) - Gen & Res Fund')->first();
                    if (!$product) {
                        Log::warning("Product not found for building id: " . $buildingId . " and name: Service Charges " . date("Y") . " (Tax) - Gen & Res Fund");
                        continue;
                    }

                    $discount = 0;
                    $customField = [];

                    // Define the products array
                    $creator = User::where(['type' => 'building', 'building_id' => $buildingId])->first();
                    $tax = Tax::where('created_by', $creator->id)->first();
                    $products = [
                        [
                            'item' => $product->id,
                            'quantity' => 1,
                            'tax' => $tax->id,
                            'discount' => $discount,
                            'price' => $property['invoiceAmount'],
                            'due_amount' => $property['dueAmount'],
                            'description' => 'Service charge invoice'
                        ]
                    ];
                    //mollak data
                    $from_mollak = true;
                    // Create the invoice
                    // Check if for the invoice already present for the given flat and for the given duration
                    if (!Invoice::where(['flat_id' => $flat->id, 'invoice_period' => $invoice['invoicePeriod']])->exists()) {
                        InvoiceController::createInvoice(
                            $customerId,
                            $property['invoiceDate'],
                            $property['invoiceDueDate'],
                            $category_id,
                            $products,
                            $ref_number,
                            $discount,
                            $customField,
                            $buildingId,
                            $flat->id,
                            $creator->id,
                            $invoice['invoicePeriod'],
                            $from_mollak,
                            $property['invoiceDetailUrl'] ?? null,
                            $property['invoicePDF'] ?? null,
                            $property['paymentUrl'] ?? null
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch or save invoices'. $e->getMessage());
        }
    }
}
