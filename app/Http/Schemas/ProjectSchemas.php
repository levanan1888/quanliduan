<?php

namespace App\Http\Schemas;

/**
 * @OA\Schema(
 *     schema="Project",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Project Alpha"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"active", "archived", "completed"}, example="active"),
 *     @OA\Property(property="manager_id", type="integer", example=1),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="manager", ref="#/components/schemas/User"),
 *     @OA\Property(property="members", type="array", @OA\Items(ref="#/components/schemas/User")),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="CreateProjectRequest",
 *     required={"name"},
 *     @OA\Property(property="name", type="string", example="Project Alpha"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="status", type="string", enum={"active", "archived", "completed"}, example="active"),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true),
 *     @OA\Property(property="member_ids", type="array", @OA\Items(type="integer"), example={2, 3, 4})
 * )
 */
class ProjectSchemas
{
    // This class exists only to hold Swagger annotations
}


