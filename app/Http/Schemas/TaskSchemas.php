<?php

namespace App\Http\Schemas;

/**
 * @OA\Schema(
 *   schema="TaskActivity",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="task_id", type="integer"),
 *   @OA\Property(property="user_id", type="integer"),
 *   @OA\Property(property="type", type="string"),
 *   @OA\Property(property="content", type="string", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 *   @OA\Property(property="created_at", type="string", format="date-time")
 * )
 */
class TaskSchemas {}


