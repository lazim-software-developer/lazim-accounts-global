<?php

namespace App\Core\Services;

use App\Core\Models\PersonalAccessToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SanctumService
{
    //use ExceptionHandlerTrait;

    /**
     * Generate Access & Refresh Tokens
     */
    public function generateToken(User $user)
    {
        // Soft delete old refresh tokens
        PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('name', 'refresh_token')
            ->delete();

        // Generate new access token
        $accessToken = $user->createToken('access_token', ['*'])->plainTextToken;
        $hashedAccessToken = hash('sha256', $accessToken); // Hash the access token

        // Store hashed access token
        $user->tokens()->create([
            'name' => 'access_token',
            'token' => $hashedAccessToken, // Store the hashed token
            'expires_at' => Carbon::now()->addDays(1), // Set an expiry date
        ]);

        // Generate and store refresh token
        $refreshToken = Str::random(64);
        $user->tokens()->create([
            'name' => 'refresh_token',
            'token' => hash('sha256', $refreshToken),
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        return [
            'token' => $accessToken, // Return plain text access token
            'refresh_token' => $refreshToken,
        ];
    }

    /**
     * Refresh Access Token using Refresh Token
     */
    public function refreshAccessToken(string $refreshToken)
    {
        $hashedToken = hash('sha256', $refreshToken);

        // Retrieve the refresh token, including soft-deleted ones
        $tokenRecord = PersonalAccessToken::withTrashedRecords()
            ->where('token', $hashedToken)
            ->where('name', 'refresh_token')
            ->where('expires_at', '>', Carbon::now())
            ->firstOrFail();

        if ($tokenRecord->trashed()) {
            throw new \Exception('Invalid or expired refresh token');
        }

        $user = $tokenRecord->tokenable;

        // Soft delete the old access and refresh tokens
        $user->tokens()->where('name', 'access_token')->delete();
        $tokenRecord->delete(); // Soft delete the refresh token after use

        // Generate new tokens
        return $this->generateToken($user);
    }

    /**
     * Revoke all tokens for the user (Soft Delete)
     */
    public function revokeTokens(User $user)
    {
        $user->tokens()->delete(); // Soft delete all tokens
    }

    /**
     * Restore a soft-deleted token
     */
    public function restoreToken($tokenId)
    {
        $token = PersonalAccessToken::onlyDeleted()->findOrFail($tokenId);
        return $token->restoreRecord($token);
    }

    /**
     * Permanently delete a soft-deleted token
     */
    public function permanentlyDeleteToken($tokenId)
    {
        $token = PersonalAccessToken::onlyDeleted()->findOrFail($tokenId);
        return $token->forceDeleteRecord($token);
    }

    /**
     * Retrieve the user from the access token.
     */
    public function getUserFromAccessToken(string $accessToken): ?User
    {
        // Hash the access token before searching for it in the database
        $hashedToken = hash('sha256', $accessToken);

        // Retrieve the personal access token record associated with the hashed token
        $tokenRecord = PersonalAccessToken::where('token', $hashedToken)
            ->where('name', 'access_token')
            ->first();

        if (!$tokenRecord) {
            return null; // Token not found
        }

        // Return the user associated with the token

        return $tokenRecord->tokenable;
    }
}
