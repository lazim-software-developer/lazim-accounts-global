<?php

namespace App\Http\Controllers\Api\Common;

use App\Core\Services\ResponseService;
use App\Core\Services\SanctumService;
use App\Core\Traits\AuthenticatedUserTrait;
use App\Core\Traits\LoggerTrait;
use App\Core\Traits\PagingTrait;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
    use AuthenticatedUserTrait;
    protected $sanctumService;

    public function __construct(SanctumService $sanctumService)
    {
        $this->sanctumService = $sanctumService;
    }

    /**
     * Login and generate tokens.
     */
    public function login(Request $request)
    {
        // throw ValidationException::withMessages([
        //     'field_name' => ['The field is required.']
        // ]);
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            //'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ResponseService::error($validator->errors(), 'Validation Errors', 422);
        }

        $user = User::where('email', $request->email)->first();

        // if (!$user || !password_verify($request->password, $user->password)) {
        //     return ResponseService::error([], 'Invalid credentials', 401);
        // }
        if (!$user) {
            return ResponseService::error([], 'Invalid credentials', 401);
        }
        $tokens = $this->sanctumService->generateToken($user);
        $result = [
            "user" => $user,
            "token" => $tokens["token"],
            "refresh_token" => $tokens["refresh_token"]
        ];
        return ResponseService::success($result, 'User authenticated successfully');
    }

    /**
     * Refresh access token using refresh token.
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ResponseService::error($validator->errors(), 'Validation Errors', 422);
        }

        $tokens = $this->sanctumService->refreshAccessToken($request->refresh_token);
        return ResponseService::success($tokens, 'Token is refreshed');
    }

    /**
     * Logout and revoke all tokens.
     */
    public function logout(Request $request)
    {
        $user = $request->authenticated_user;
        $this->sanctumService->revokeTokens($user);

        return ResponseService::success('Logged out successfully');
    }

    /**
     * Restore a soft-deleted token.
     */
    public function restoreToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return ResponseService::error($validator->errors(), 'Validation Errors', 422);
        }

        $response = [
            "token" => $this->sanctumService->restoreToken($request->token_id)
        ];
        return ResponseService::success($response, 'Token restored');
    }

    /**
     * Permanently delete a soft-deleted token.
     */
    public function deleteToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return ResponseService::error($validator->errors(), 'Validation Errors', 422);
        }

        $this->sanctumService->permanentlyDeleteToken($request->token_id);
        return ResponseService::success('Token permanently deleted');
    }

    public function authenticatedUser(Request $request)
    {
        // $user = $request->authenticated_user;
        $user = $this->getAuthenticatedUser();
        if ($user != null)
            return ResponseService::success($user, 'User Found.');
        return ResponseService::error('User not found');
    }
}
