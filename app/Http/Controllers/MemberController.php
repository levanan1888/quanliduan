<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MemberController extends Controller
{
    /**
     * Get all members (users)
     * - PM: Can see all members
     * - MEMBER: Can only see members in their projects
     *
     * @OA\Get(
     *     path="/api/members",
     *     tags={"Members"},
     *     summary="Get all members",
     *     description="Get list of members with role-based access control. PM can see all members in the system. MEMBER can only see members (including project managers) from projects they are assigned to. If a MEMBER has no projects, an empty list is returned.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         required=false,
     *         description="Filter by role (PM or MEMBER). If not provided, returns all roles within the user's access scope.",
     *         @OA\Schema(type="string", enum={"PM", "MEMBER"}, example="MEMBER")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         required=false,
     *         description="Filter by active status (true/false). Only active users are returned if set to true.",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by full name or email address (case-insensitive partial match)",
     *         @OA\Schema(type="string", example="john")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", default=1, example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=15, example=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of members returned successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Array of member objects",
     *                 @OA\Items(ref="#/components/schemas/User")
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1, description="Current page number"),
     *             @OA\Property(property="per_page", type="integer", example=15, description="Items per page"),
     *             @OA\Property(property="total", type="integer", example=100, description="Total number of items"),
     *             @OA\Property(property="last_page", type="integer", example=7, description="Last page number"),
     *             @OA\Property(property="from", type="integer", example=1, description="Starting item number"),
     *             @OA\Property(property="to", type="integer", example=15, description="Ending item number"),
     *             @OA\Property(property="path", type="string", example="/api/members", description="Base path for the resource"),
     *             @OA\Property(property="prev_page_url", type="string", nullable=true, example=null, description="Previous page URL"),
     *             @OA\Property(property="next_page_url", type="string", nullable=true, example="/api/members?page=2", description="Next page URL")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - User must be logged in",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // PM can see all members, MEMBER can only see members in their projects
        if ($user->role === 'PM') {
            // PM: Query all users
            $query = User::query();
        } else {
            // MEMBER: Only see members in their projects (including project managers)
            $userProjectIds = $user->projects()->pluck('projects.id')->toArray();
            
            // If member has no projects, return empty result
            if (empty($userProjectIds)) {
                $query = User::where('id', 0); // Empty query
            } else {
                $query = User::where(function ($q) use ($userProjectIds) {
                    // Get users who are members of the same projects
                    $q->whereHas('projects', function ($projectQuery) use ($userProjectIds) {
                        $projectQuery->whereIn('projects.id', $userProjectIds);
                    })
                    // Or users who manage projects the member is in
                    ->orWhereHas('managedProjects', function ($projectQuery) use ($userProjectIds) {
                        $projectQuery->whereIn('projects.id', $userProjectIds);
                    });
                })->distinct();
            }
        }

        // Filter by role (optional - if not provided, returns all roles)
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $members = $query->orderBy('full_name')->paginate($perPage);

        return response()->json($members);
    }
}

