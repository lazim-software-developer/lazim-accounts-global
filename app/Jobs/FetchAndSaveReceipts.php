<?php

namespace App\Jobs;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoiceProduct;
use App\Models\ProductServiceCategory;
use App\Models\Revenue;
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

class FetchAndSaveReceipts implements ShouldQueue
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
        try {
            $propertyGroupId = $this->building?->property_group_id;
            $buildingId = $this->building?->id ;
            $connection = DB::connection('mysql_lazim');

            $dateRange = $this->getCurrentQuarterDateRange();

                // $url = env("MOLLAK_API_URL") . '/sync/receipts/' . $propertyGroupId . '/01-Jan-2023/31-Mar-2023';
                $url = env("MOLLAK_API_URL") . '/sync/receipts/' . $propertyGroupId . '/' . $dateRange;
            
            $response = Http::withoutVerifying()->withHeaders([
                'content-type' => 'application/json',
                'consumer-id'  => env("MOLLAK_CONSUMER_ID"),
            ])->get($url);
            // ])->get(env("MOLLAK_API_URL") . '/sync/receipts/' . $propertyGroupId . '/' . $dateRange);

            $properties = $response->json()['response']['properties'];
            
            $currentQuarterDates = $this->getCurrentQuarterDates();

            foreach ($properties as $property) {
                $flat = $connection->table('flats')->where('mollak_property_id', $property['mollakPropertyId'])->first();
                foreach ($property['receipts'] as $receipt) {
                    $date = $receipt['receiptDate'];
                    $amount = $receipt['receiptAmount'];
                    $accountId = BankAccount::where('building_id', $this->building?->id)->where('holder_name','General Fund')->first()?->id;
                    $categoryId = ProductServiceCategory::where('name', 'Service Charges')->where('building_id', $this->building?->id)->first()?->id;
                    $customerId = Customer::where('flat_id', $flat?->id)->where('building_id', $this->building?->id)->where('is_active', true)->first()?->id;
                    $reference = $receipt['transactionReference'];
                    $description = null;
                    $addReceipt = null;
                    $createdBy = User::where('building_id',$this->building?->id)->where('type', 'building')->first()?->id;
                    $buildingId = $this->building?->id;
                    $flatId = $flat?->id;
                    $receiptPeriod = $currentQuarterDates['receipt_period'];
                    $this->createRevenue($date, $amount, $accountId, $categoryId, $customerId, $reference, $description, $addReceipt, $createdBy, $buildingId, $flatId, $receiptPeriod);
                    // $accountId = BankAccount::where('building_id', $this->building->id)->where('holder_name','General fund')->first()?->id;
                    // $invoice = Invoice::where([
                    //     'building_id' => $buildingId,
                    //     'flat_id' => $flat?->id,
                    //     'invoice_period' => $currentQuarterDates['receipt_period']
                    // ])->first();
                    // $amount = $receipt['receiptAmount'];
                    // $paymentDate = $receipt['receiptDate'];
                    // $reference = null;
                    // $description = null;
                    // // Fetch the created_by user
                    // $createdBy = User::where('owner_association_id', $flat->owner_association_id)->where('type', 'company')->first()?->id;

                    // if($invoice){
                    //     $this->addPayment($paymentDate, $amount, $accountId, $invoice->id, $createdBy, $description, $reference, $buildingId);
                    // }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch or save receipts: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
        }
    }

    public static function getCurrentQuarterDateRange()
    {
        $currentDate = new DateTime();
        $currentMonth = (int)$currentDate->format('m');
        $currentYear = $currentDate->format('Y');

        // Determine the current quarter
        $currentQuarter = ceil($currentMonth / 3);

        // Define the start and end months for each quarter
        $quarterMonths = [
            1 => ['start' => '01-Jan', 'end' => '31-Mar'],
            2 => ['start' => '01-Apr', 'end' => '30-Jun'],
            3 => ['start' => '01-Jul', 'end' => '30-Sep'],
            4 => ['start' => '01-Oct', 'end' => '31-Dec'],
        ];

        // Get the start and end date for the current quarter
        $startDate = $quarterMonths[$currentQuarter]['start'] . '-' . $currentYear;
        $endDate = $quarterMonths[$currentQuarter]['end'] . '-' . $currentYear;

        return $startDate . '/' . $endDate;
    }

    public static function getCurrentQuarterDates()
    {
        $currentDate = new DateTime();
        $currentYear = $currentDate->format('Y');
        $currentQuarter = ceil($currentDate->format('n') / 3);

        // Define start and end months for each quarter
        $quarterMonths = [
            1 => ['start' => '01-Jan', 'end' => '31-Mar'],
            2 => ['start' => '01-Apr', 'end' => '30-Jun'],
            3 => ['start' => '01-Jul', 'end' => '30-Sep'],
            4 => ['start' => '01-Oct', 'end' => '31-Dec'],
        ];

        $startMonthDay = $quarterMonths[$currentQuarter]['start'];
        $endMonthDay = $quarterMonths[$currentQuarter]['end'];

        // Format dates
        $fromDate = DateTime::createFromFormat('d-M-Y', $startMonthDay . '-' . $currentYear)->format('Y-m-d');
        $toDate = DateTime::createFromFormat('d-M-Y', $endMonthDay . '-' . $currentYear)->format('Y-m-d');
        $receiptPeriod = str_replace('-', ' ', $startMonthDay) . ' To ' . str_replace('-', ' ', $endMonthDay) . '-' . $currentYear;

        return [
            'from_date' => '2024-01-01',
            'to_date' => '2024-03-31',
            'receipt_period' => '01-Jan-2023 To 31-Mar-2023'
        ];
    }
}
