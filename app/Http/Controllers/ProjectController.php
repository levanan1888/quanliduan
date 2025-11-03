<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ProjectController extends Controller
{
    /**
     * Get all projects (PM sees all, MEMBER sees only assigned projects)
     *
     * @OA\Get(
     *     path="/api/projects",
     *     tags={"Projects"},
     *     summary="Get all projects",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of projects",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Project")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'PM') {
            $projects = Project::with(['manager', 'members'])
                ->latest()
                ->paginate(15);
        } else {
            $projects = Project::whereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
                ->with(['manager', 'members'])
                ->latest()
                ->paginate(15);
        }

        return response()->json($projects);
    }

    /**
     * Create a new project (PM only)
     *
     * @OA\Post(
     *     path="/api/projects",
     *     tags={"Projects"},
     *     summary="Create a new project",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateProjectRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Project created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only PM can create projects"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,archived,completed'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['exists:users,id'],
        ]);

        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
            'manager_id' => $request->user()->id,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        // Attach members if provided
        if (! empty($data['member_ids'])) {
            $project->members()->attach($data['member_ids']);

            // Create notifications for invited members
            foreach ($data['member_ids'] as $memberId) {
                Notification::create([
                    'user_id' => $memberId,
                    'title' => 'Project Invitation',
                    'message' => "You have been added to project: {$project->name}",
                    'type' => 'mention',
                    'related_project_id' => $project->id,
                ]);
            }

            // Refresh relationship to ensure members are loaded
            $project->load('members');
        }

        // Load all relationships
        $project->load(['manager', 'members', 'sprints', 'tasks']);

        return response()->json($project, 201);
    }

    /**
     * Get single project
     *
     * @OA\Get(
     *     path="/api/projects/{id}",
     *     tags={"Projects"},
     *     summary="Get project by ID",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project details",
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Project not found")
     * )
     */
    public function show(Request $request, Project $project)
    {
        // Load relationships first - ensure members are loaded
        $project->load(['manager', 'members', 'sprints', 'tasks']);
        
        // Debug: Check members directly from database
        $membersFromDb = DB::table('project_members')
            ->where('project_id', $project->id)
            ->join('users', 'project_members.user_id', '=', 'users.id')
            ->select('users.*', 'project_members.joined_at')
            ->get();
        
        \Log::info('Project members debug', [
            'project_id' => $project->id,
            'members_via_relationship' => $project->members->count(),
            'members_from_db' => $membersFromDb->count(),
            'members_from_db_data' => $membersFromDb->toArray(),
        ]);
        
        // Check access: PM can see all, MEMBER can only see if assigned
        $user = $request->user();
        if ($user->role !== 'PM' && ! $project->members->contains($user->id)) {
            return response()->json([
                'message' => 'You do not have access to this project.',
            ], 403);
        }

        // Đảm bảo members được load và serialize đúng cách
        $response = $project->toArray();
        
        // Nếu members rỗng nhưng có dữ liệu trong DB, load lại
        if (empty($response['members']) && $membersFromDb->count() > 0) {
            $project->load('members');
            $response = $project->toArray();
        }

        return response()->json($response);
    }

    /**
     * Update project (PM only, must be manager or PM)
     *
     * @OA\Put(
     *     path="/api/projects/{id}",
     *     tags={"Projects"},
     *     summary="Update project",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateProjectRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Project")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only project manager can update"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Project $project)
    {
        // Only PM who is the manager can update
        if ($request->user()->id !== $project->manager_id) {
            return response()->json([
                'message' => 'Only the project manager can update this project.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:active,archived,completed'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['exists:users,id'],
        ]);

        // Keep current members to detect newly added users
        $previousMemberIds = $project->members()->pluck('users.id')->toArray();

        $project->update($data);

        // Sync members if provided and notify newly added ones
        if (isset($data['member_ids'])) {
            $project->members()->sync($data['member_ids']);

            $newMemberIds = array_diff($data['member_ids'], $previousMemberIds);
            foreach ($newMemberIds as $memberId) {
                Notification::create([
                    'user_id' => $memberId,
                    'title' => 'Project Invitation',
                    'message' => "You have been added to project: {$project->name}",
                    'type' => 'mention',
                    'related_project_id' => $project->id,
                ]);
            }
        }

        $project->load(['manager', 'members']);

        return response()->json($project);
    }

    /**
     * Delete project (PM only, must be manager)
     *
     * @OA\Delete(
     *     path="/api/projects/{id}",
     *     tags={"Projects"},
     *     summary="Delete project",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Project deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Project deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only project manager can delete"),
     *     @OA\Response(response=404, description="Project not found")
     * )
     */
    public function destroy(Request $request, Project $project)
    {
        if ($request->user()->id !== $project->manager_id) {
            return response()->json([
                'message' => 'Only the project manager can delete this project.',
            ], 403);
        }

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }
}

