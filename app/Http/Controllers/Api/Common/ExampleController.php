<?php

namespace App\Http\Controllers\Api\Common;

use App\Core\Services\ResponseService;
use App\Core\Traits\LoggerTrait;
use App\Core\Traits\PagingTrait;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExampleController extends Controller
{

    use PagingTrait;
    use LoggerTrait;

    public function getUsers(Request $request)
    {
        $this->logRequest($request); // Log the request
        $perPage = $request->get('per_page', 10); // Default to 10
        $page = $request->get('page', 1); // Default to page 1

        // $users = User::paginate($perPage, ['*'], 'page', $page);
        $users = $this->paginateQuery(User::query(), $request);
        return $this->paginateResponse($users);
    }

    public function getData(Request $request)
    {
        $this->logRequest($request); // Log the request
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
        ];
        return ResponseService::success($data, 'Data retrieved successfully');
    }

    public function store(Request $request)
    {
        $this->logRequest($request); // Log the request
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email',
            ]);

            // Assume we store the user here
            return ResponseService::success(null, 'User created successfully');
        } catch (ValidationException  $e) {
            // Return validation errors in key-value format
            return ResponseService::error($e->errors(), 'Validation Errors', 422);
        }
    }

    public function validationErrorExample()
    {

        $errors = [
            'name' => 'The name field is required.',
            'email' => 'The email must be a valid email address.',
        ];
        return ResponseService::error($errors, 'Validation failed', 422);
    }
}
