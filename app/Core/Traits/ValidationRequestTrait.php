<?php

namespace App\Core\Traits;

use App\Core\Services\ValidationService;

trait ValidationRequestTrait
{

    /**
     * Validate the request with dynamic messages.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $rules
     * @return void
     */
    public function validateRequestWithDynamicMessages($request, array $rules)
    {
        // Get the validation messages dynamically
        $messages = ValidationService::validationMessages($rules);

        // Validate the request with the dynamically generated messages
        $request->validate($rules, $messages);
    }
}
