<?php

namespace App\Core\Services;

use App\Core\Traits\AuthenticatedUserTrait;
use App\Core\Traits\PagingTrait;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;

// class ValidationService
// {
//     public static function validationMessages()
//     {
//         return [
//             'from_date.required' => 'The from date is required.',
//             'from_date.date' => 'The from date must be a valid date format (YYYY-MM-DD).',
//             'to_date.required' => 'The to date is required.',
//             'to_date.date' => 'The to date must be a valid date format (YYYY-MM-DD).',
//             'to_date.after_or_equal' => 'The to date must be the same or after the from date.',
//             'page.required' => 'The page number is required.',
//             'page.integer' => 'The page number must be a valid integer.',
//             'customer.required' => 'The customer ID is required.',
//             'customer.integer' => 'The customer ID must be a valid integer.',
//         ];
//     }
// }
class ValidationService
{
    /**
     * Get validation messages dynamically.
     *
     * @param array $rules
     * @return array
     */
    public static function validationMessages(array $rules)
    {
        $messages = [];

        foreach ($rules as $field => $rule) {
            // Split rules (e.g., 'required|date' -> ['required', 'date'])
            $ruleList = explode('|', $rule);

            foreach ($ruleList as $singleRule) {
                $messages["{$field}.{$singleRule}"] = self::getMessage($field, $singleRule);
            }
        }

        return $messages;
    }

    /**
     * Generate the validation message for a specific rule.
     *
     * @param string $field
     * @param string $rule
     * @return string
     */
    private static function getMessage(string $field, string $rule)
    {
        // Define base messages
        $messages = [
            'required' => ':attribute is required.',
            'date' => ':attribute must be a valid date.',
            'after_or_equal' => ':attribute must be the same or after :date.',
            'integer' => ':attribute must be an integer.',
            'numeric' => ':attribute must be a number.',
            'email' => ':attribute must be a valid email address.',
        ];

        // If custom messages exist for a field/rule, return them
        $customMessages = self::getCustomMessages();

        if (isset($customMessages["{$field}.{$rule}"])) {
            return $customMessages["{$field}.{$rule}"];
        }

        // Otherwise, use the default message
        if (isset($messages[$rule])) {
            return str_replace(':attribute', $field, $messages[$rule]);
        }

        return ':attribute is invalid.';
    }

    /**
     * Example custom messages for specific fields/rules
     *
     * @return array
     */
    private static function getCustomMessages()
    {
        return [
            'from_date.required' => 'The "From Date" is required for filtering.',
            'from_date.date' => 'The "From Date" should be a valid date in the format YYYY-MM-DD.',
            'to_date.required' => 'The "To Date" is required for filtering.',
            'to_date.after_or_equal' => 'The "To Date" must be the same as or later than the "From Date".',
            'page.required' => 'The page number is necessary for pagination.',
            'page.integer' => 'The page number must be a valid integer.',
            'customer.required' => 'The customer ID is required.',
            'customer.integer' => 'The customer ID must be a valid integer.',
            'building.required' => 'The Building ID is required.',
            'building.integer' => 'The Building ID must be a valid integer.',
            'year.required' => 'Year is required.',
            'year.integer' => 'Year must be a valid integer.',
            'vender.required' => 'The vender ID is required.',
            'vender.integer' => 'The vender ID must be a valid integer.',
        ];
    }
}
