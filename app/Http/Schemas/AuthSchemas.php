<?php

namespace App\Http\Schemas;

/**
 * @OA\Schema(
 *     schema="LoginRequest",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email", example="pm@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password")
 * )
 *
 * @OA\Schema(
 *     schema="RegisterRequest",
 *     required={"full_name", "email", "password"},
 *     @OA\Property(property="full_name", type="string", example="John Doe"),
 *     @OA\Property(property="title", type="string", nullable=true, example="Developer"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *     @OA\Property(property="password", type="string", format="password", example="password"),
 *     @OA\Property(property="role", type="string", enum={"PM", "MEMBER"}, example="MEMBER")
 * )
 *
 * @OA\Schema(
 *     schema="RefreshTokenRequest",
 *     required={"email", "refresh_token"},
 *     @OA\Property(property="email", type="string", format="email", example="pm@example.com"),
 *     @OA\Property(property="refresh_token", type="string", example="abc123...")
 * )
 *
 * @OA\Schema(
 *     schema="AuthResponse",
 *     @OA\Property(property="access_token", type="string", example="1|abc123..."),
 *     @OA\Property(property="refresh_token", type="string", example="xyz789..."),
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="user", ref="#/components/schemas/User")
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="full_name", type="string", example="John Doe"),
 *     @OA\Property(property="title", type="string", nullable=true, example="Developer"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *     @OA\Property(property="role", type="string", enum={"PM", "MEMBER"}, example="MEMBER"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class AuthSchemas
{
    // This class exists only to hold Swagger annotations
}

