<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SprintController extends Controller
{
    /**
     * Get all sprints for a project
     *
     * @OA\Get(
     *     path="/api/projects/{project}/sprints",
     *     tags={"Sprints"},
     *     summary="Get all sprints for a project",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of sprints"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function index(Request $request, Project $project)
    {
        // Check access
        $user = $request->user();
        if ($user->role !== 'PM' && ! $project->members->contains($user->id)) {
            return response()->json([
                'message' => 'You do not have access to this project.',
            ], 403);
        }

        $sprints = Sprint::where('project_id', $project->id)
            ->with(['tasks'])
            ->latest()
            ->paginate(15);

        return response()->json($sprints);
    }

    /**
     * Create a new sprint (PM only)
     *
     * @OA\Post(
     *     path="/api/projects/{project}/sprints",
     *     tags={"Sprints"},
     *     summary="Create a new sprint",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "start_date", "end_date"},
     *             @OA\Property(property="name", type="string", example="Sprint 1"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-11-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-11-14"),
     *             @OA\Property(property="status", type="string", enum={"planned", "active", "completed", "cancelled"}, example="planned")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Sprint created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only PM can create sprints"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request, Project $project)
    {
        // Only PM who is the manager can create sprints
        if ($request->user()->id !== $project->manager_id) {
            return response()->json([
                'message' => 'Only the project manager can create sprints.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['nullable', 'in:planned,active,completed,cancelled'],
        ]);

        $sprint = Sprint::create([
            'project_id' => $project->id,
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'] ?? 'planned',
        ]);

        $sprint->load(['tasks']);

        return response()->json($sprint, 201);
    }

    /**
     * Get single sprint
     *
     * @OA\Get(
     *     path="/api/projects/{project}/sprints/{sprint}",
     *     tags={"Sprints"},
     *     summary="Get sprint by ID",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sprint", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sprint details"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Sprint not found")
     * )
     */
    public function show(Request $request, Project $project, Sprint $sprint)
    {
        // Check access and sprint belongs to project
        if ($sprint->project_id !== $project->id) {
            return response()->json([
                'message' => 'Sprint does not belong to this project.',
            ], 404);
        }

        $user = $request->user();
        if ($user->role !== 'PM' && ! $project->members->contains($user->id)) {
            return response()->json([
                'message' => 'You do not have access to this sprint.',
            ], 403);
        }

        $sprint->load(['tasks.subTasks', 'tasks.assignedUser', 'tasks.assets']);

        return response()->json($sprint);
    }

    /**
     * Update sprint (PM only)
     *
     * @OA\Put(
     *     path="/api/projects/{project}/sprints/{sprint}",
     *     tags={"Sprints"},
     *     summary="Update sprint",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sprint", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="start_date", type="string", format="date"),
     *         @OA\Property(property="end_date", type="string", format="date"),
     *         @OA\Property(property="status", type="string", enum={"planned", "active", "completed", "cancelled"})
     *     )),
     *     @OA\Response(response=200, description="Sprint updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only PM can update"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Project $project, Sprint $sprint)
    {
        if ($sprint->project_id !== $project->id) {
            return response()->json([
                'message' => 'Sprint does not belong to this project.',
            ], 404);
        }

        if ($request->user()->id !== $project->manager_id) {
            return response()->json([
                'message' => 'Only the project manager can update sprints.',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'status' => ['sometimes', 'in:planned,active,completed,cancelled'],
        ]);

        $sprint->update($data);
        $sprint->load(['tasks']);

        return response()->json($sprint);
    }

    /**
     * Delete sprint (PM only)
     *
     * @OA\Delete(
     *     path="/api/projects/{project}/sprints/{sprint}",
     *     tags={"Sprints"},
     *     summary="Delete sprint",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="project", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sprint", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Sprint deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Only PM can delete"),
     *     @OA\Response(response=404, description="Sprint not found")
     * )
     */
    public function destroy(Request $request, Project $project, Sprint $sprint)
    {
        if ($sprint->project_id !== $project->id) {
            return response()->json([
                'message' => 'Sprint does not belong to this project.',
            ], 404);
        }

        if ($request->user()->id !== $project->manager_id) {
            return response()->json([
                'message' => 'Only the project manager can delete sprints.',
            ], 403);
        }

        $sprint->delete();

        return response()->json([
            'message' => 'Sprint deleted successfully.',
        ]);
    }
}

