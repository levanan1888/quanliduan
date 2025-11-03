<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\SubTask;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SubTaskController extends Controller
{
    /**
     * Get all subtasks for a task
     *
     * @OA\Get(
     *     path="/api/tasks/{task}/sub-tasks",
     *     tags={"SubTasks"},
     *     summary="Get all subtasks for a task",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of subtasks"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied")
     * )
     */
    public function index(Request $request, Task $task)
    {
        // Check task access
        $user = $request->user();
        if ($user->role !== 'PM' && $task->assigned_to !== $user->id && ! $task->project->members->contains($user->id)) {
            return response()->json([
                'message' => 'You do not have access to this task.',
            ], 403);
        }

        $subTasks = $task->subTasks()->latest('created_at')->get();

        return response()->json($subTasks);
    }

    /**
     * Create a new subtask
     *
     * @OA\Post(
     *     path="/api/tasks/{task}/sub-tasks",
     *     tags={"SubTasks"},
     *     summary="Create a new subtask",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"title"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="date", type="string", format="date", nullable=true),
     *         @OA\Property(property="tag", type="string", maxLength=100, nullable=true)
     *     )),
     *     @OA\Response(response=201, description="SubTask created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Permission denied"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request, Task $task)
    {
        // Check access
        $user = $request->user();
        $canEdit = $user->role === 'PM' || 
                   $user->id === $task->project->manager_id || 
                   $user->id === $task->assigned_to;

        if (! $canEdit) {
            return response()->json([
                'message' => 'You do not have permission to create subtasks for this task.',
            ], 403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'tag' => ['nullable', 'string', 'max:100'],
        ]);

        $subTask = $task->subTasks()->create($data);

        return response()->json($subTask, 201);
    }

    /**
     * Update subtask
     *
     * @OA\Put(
     *     path="/api/tasks/{task}/sub-tasks/{subTask}",
     *     tags={"SubTasks"},
     *     summary="Update subtask",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subTask", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="date", type="string", format="date", nullable=true),
     *         @OA\Property(property="tag", type="string", nullable=true),
     *         @OA\Property(property="is_completed", type="boolean")
     *     )),
     *     @OA\Response(response=200, description="SubTask updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Permission denied"),
     *     @OA\Response(response=404, description="SubTask not found")
     * )
     */
    public function update(Request $request, Task $task, SubTask $subTask)
    {
        if ($subTask->task_id !== $task->id) {
            return response()->json([
                'message' => 'SubTask does not belong to this task.',
            ], 404);
        }

        // Check access
        $user = $request->user();
        $canEdit = $user->role === 'PM' || 
                   $user->id === $task->project->manager_id || 
                   $user->id === $task->assigned_to;

        if (! $canEdit) {
            return response()->json([
                'message' => 'You do not have permission to update this subtask.',
            ], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'tag' => ['nullable', 'string', 'max:100'],
            'is_completed' => ['sometimes', 'boolean'],
        ]);

        $subTask->update($data);

        return response()->json($subTask);
    }

    /**
     * Delete subtask
     *
     * @OA\Delete(
     *     path="/api/tasks/{task}/sub-tasks/{subTask}",
     *     tags={"SubTasks"},
     *     summary="Delete subtask",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subTask", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="SubTask deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Permission denied"),
     *     @OA\Response(response=404, description="SubTask not found")
     * )
     */
    public function destroy(Request $request, Task $task, SubTask $subTask)
    {
        if ($subTask->task_id !== $task->id) {
            return response()->json([
                'message' => 'SubTask does not belong to this task.',
            ], 404);
        }

        // Check access
        $user = $request->user();
        $canEdit = $user->role === 'PM' || 
                   $user->id === $task->project->manager_id || 
                   $user->id === $task->assigned_to;

        if (! $canEdit) {
            return response()->json([
                'message' => 'You do not have permission to delete this subtask.',
            ], 403);
        }

        $subTask->delete();

        return response()->json([
            'message' => 'SubTask deleted successfully.',
        ]);
    }
}

