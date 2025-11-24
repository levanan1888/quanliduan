<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class TaskController extends Controller
{
    /**
     * List activity logs for a task
     *
     * @OA\Get(
     *     path="/api/tasks/{task}/activities",
     *     tags={"Tasks"},
     *     summary="Get activity logs for a task",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="task", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", description="Filter by type", @OA\Schema(type="string")),
     *     @OA\Parameter(name="since", in="query", description="ISO datetime to fetch newer logs", @OA\Schema(type="string", format="date-time")),
     *     @OA\Response(response=200, description="List of task activities")
     * )
     */
    public function activities(Request $request, Task $task)
    {
        // Access: PM, assignee, creator, project members
        $user = $request->user();
        $allowed = $user->role === 'PM'
            || $user->id === $task->assigned_to
            || $user->id === $task->created_by
            || $task->project->members->contains($user->id);

        if (! $allowed) {
            return response()->json([
                'message' => 'You do not have access to this task.'
            ], 403);
        }

        $query = $task->activities()->with('user')->getQuery();

        if ($request->filled('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->filled('since')) {
            $query->where('created_at', '>', $request->query('since'));
        }

        $activities = $query->latest('created_at')->paginate(15);

        return response()->json($activities);
    }
    /**
     * Get all tasks (filter by project, sprint, assigned_to, status)
     *
     * @OA\Get(
     *     path="/api/tasks",
     *     tags={"Tasks"},
     *     summary="Get all tasks",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="project_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sprint_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="assigned_to", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"TO_DO", "IN_PROGRESS", "COMPLETED"})),
     *     @OA\Response(response=200, description="List of tasks"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Task::query();

        // MEMBER only sees tasks assigned to them or in their projects
        if ($user->role === 'MEMBER') {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhereHas('project.members', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            });
        }

        // Filters
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->has('sprint_id')) {
            if ($request->sprint_id === 'null' || $request->sprint_id === null) {
                $query->whereNull('sprint_id'); // Backlog tasks
            } else {
                $query->where('sprint_id', $request->sprint_id);
            }
        }

        if ($request->has('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $tasks = $query->with(['project', 'sprint', 'assignedUser', 'creator', 'subTasks', 'assets'])
            ->latest()
            ->paginate(15);

        return response()->json($tasks);
    }

    /**
     * Create a new task
     *
     * @OA\Post(
     *     path="/api/tasks",
     *     tags={"Tasks"},
     *     summary="Create a new task",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"project_id", "title"},
     *         @OA\Property(property="project_id", type="integer"),
     *         @OA\Property(property="sprint_id", type="integer", nullable=true),
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="date", type="string", format="date", nullable=true),
     *         @OA\Property(property="priority", type="string", enum={"HIGH", "MEDIUM", "LOW"}),
     *         @OA\Property(property="status", type="string", enum={"TO_DO", "IN_PROGRESS", "COMPLETED"}),
     *         @OA\Property(property="assigned_to", type="integer", nullable=true)
     *     )),
     *     @OA\Response(response=201, description="Task created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'sprint_id' => ['nullable', 'exists:sprints,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:HIGH,MEDIUM,LOW'],
            'status' => ['nullable', 'in:TO_DO,IN_PROGRESS,COMPLETED'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        // Check project access
        $project = Project::findOrFail($data['project_id']);
        $user = $request->user();
        
        if ($user->role !== 'PM' && ! $project->members->contains($user->id)) {
            return response()->json([
                'message' => 'You do not have access to this project.',
            ], 403);
        }

        // Check sprint belongs to project
        if (! empty($data['sprint_id'])) {
            $sprint = Sprint::findOrFail($data['sprint_id']);
            if ($sprint->project_id !== $project->id) {
                return response()->json([
                    'message' => 'Sprint does not belong to this project.',
                ], 422);
            }
        }

        $task = Task::create([
            'project_id' => $data['project_id'],
            'sprint_id' => $data['sprint_id'] ?? null,
            'title' => $data['title'],
            'date' => $data['date'] ?? null,
            'priority' => $data['priority'] ?? 'MEDIUM',
            'status' => $data['status'] ?? 'TO_DO',
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $user->id,
        ]);

        // Log activity
        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'type' => 'subtask_created',
            'content' => "Task created: {$task->title}",
        ]);

        // Send notification if assigned
        if ($task->assigned_to) {
            Notification::create([
                'user_id' => $task->assigned_to,
                'title' => 'New Task Assigned',
                'message' => "You have been assigned to task: {$task->title}",
                'type' => 'task_assigned',
                'related_task_id' => $task->id,
                'related_project_id' => $task->project_id,
            ]);
        }

        $task->load(['project', 'sprint', 'assignedUser', 'creator', 'subTasks', 'assets']);

        return response()->json($task, 201);
    }

    /**
     * Get single task
     *
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Get task by ID",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Task details"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Access denied"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function show(Request $request, Task $task)
    {
        // Check access
        $user = $request->user();
        if ($user->role !== 'PM' && $task->assigned_to !== $user->id && ! $task->project->members->contains($user->id)) {
            return response()->json([
                'message' => 'You do not have access to this task.',
            ], 403);
        }

        $task->load(['project', 'sprint', 'assignedUser', 'creator', 'subTasks', 'assets', 'activities.user']);

        return response()->json($task);
    }

    /**
     * Update task
     *
     * @OA\Put(
     *     path="/api/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Update task",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="date", type="string", format="date"),
     *         @OA\Property(property="priority", type="string", enum={"HIGH", "MEDIUM", "LOW"}),
     *         @OA\Property(property="status", type="string", enum={"TO_DO", "IN_PROGRESS", "COMPLETED"}),
     *         @OA\Property(property="sprint_id", type="integer"),
     *         @OA\Property(property="assigned_to", type="integer")
     *     )),
     *     @OA\Response(response=200, description="Task updated successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Permission denied"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, Task $task)
    {
        // Check access
        $user = $request->user();
        $canEdit = $user->role === 'PM' || 
                   $user->id === $task->project->manager_id || 
                   $user->id === $task->assigned_to ||
                   $user->id === $task->created_by;

        if (! $canEdit) {
            return response()->json([
                'message' => 'You do not have permission to update this task.',
            ], 403);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'priority' => ['sometimes', 'in:HIGH,MEDIUM,LOW'],
            'status' => ['sometimes', 'in:TO_DO,IN_PROGRESS,COMPLETED'],
            'sprint_id' => ['nullable', 'exists:sprints,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $oldStatus = $task->status;
        $oldAssignedTo = $task->assigned_to;

        $task->update($data);

        // Log activities
        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            TaskActivity::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'type' => 'status_changed',
                'content' => "Status changed from {$oldStatus} to {$data['status']}",
                'metadata' => [
                    'old_status' => $oldStatus,
                    'new_status' => $data['status'],
                ],
            ]);

            // Notification for assignee
            if ($task->assigned_to) {
                Notification::create([
                    'user_id' => $task->assigned_to,
                    'title' => 'Task Status Updated',
                    'message' => "Task '{$task->title}' status changed to {$data['status']}",
                    'type' => 'status_changed',
                    'related_task_id' => $task->id,
                ]);
            }

            // Notify Project Manager when task is completed
            if ($data['status'] === 'COMPLETED') {
                $pmId = $task->project->manager_id;
                if ($pmId) {
                    Notification::create([
                        'user_id' => $pmId,
                        'title' => 'Task Completed',
                        'message' => "Task '{$task->title}' has been marked as COMPLETED.",
                        'type' => 'status_changed',
                        'related_task_id' => $task->id,
                        'related_project_id' => $task->project_id,
                    ]);
                }
            }
        }

        if (isset($data['assigned_to']) && $data['assigned_to'] !== $oldAssignedTo) {
            TaskActivity::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'type' => 'assigned',
                'content' => $data['assigned_to'] 
                    ? "Task assigned to user ID: {$data['assigned_to']}"
                    : "Task unassigned",
            ]);

            // Notification for new assignee
            if ($data['assigned_to']) {
                Notification::create([
                    'user_id' => $data['assigned_to'],
                    'title' => 'Task Assigned',
                    'message' => "You have been assigned to task: {$task->title}",
                    'type' => 'task_assigned',
                    'related_task_id' => $task->id,
                ]);
            }
        }

        $task->load(['project', 'sprint', 'assignedUser', 'creator', 'subTasks', 'assets']);

        return response()->json($task);
    }

    /**
     * Upload image assets to task (supports multiple images)
     *
     * @OA\Post(
     *     path="/api/tasks/{task}/assets",
     *     tags={"Tasks"},
     *     summary="Upload images to task",
     *     description="Upload one or multiple images to a task. Accepts single image or array of images.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="task",
     *         in="path",
     *         required=true,
     *         description="Task ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="images[]",
     *                     type="array",
     *                     description="Array of image files (supports multiple uploads)",
     *                     @OA\Items(type="string", format="binary")
     *                 ),
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Single image file (alternative to images[] for backward compatibility)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Images uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Array of uploaded asset objects",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="task_id", type="integer", example=1),
     *                     @OA\Property(property="image_url", type="string", example="/storage/task-assets/abc123.jpg"),
     *                     @OA\Property(property="uploaded_by", type="integer", example=1),
     *                     @OA\Property(property="uploaded_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="count", type="integer", example=3, description="Number of images uploaded")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Permission denied"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function uploadAsset(Request $request, Task $task)
    {
        // Check access
        $user = $request->user();
        $canEdit = $user->role === 'PM' || 
                   $user->id === $task->project->manager_id || 
                   $user->id === $task->assigned_to;

        if (! $canEdit) {
            return response()->json([
                'message' => 'You do not have permission to upload assets to this task.',
            ], 403);
        }

        // Validate: accept both single image and array of images
        $request->validate([
            'images' => ['sometimes', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:5120'], // 5MB max per image
            'image' => ['sometimes', 'image', 'max:5120'], // Backward compatibility: single image
        ]);

        // Ensure at least one image is provided
        if (! $request->hasFile('images') && ! $request->hasFile('image')) {
            return response()->json([
                'message' => 'No images provided. Please upload at least one image using "images[]" or "image" field.',
            ], 422);
        }

        $uploadedAssets = [];
        $uploadedUrls = [];

        // Handle array of images (preferred method)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('task-assets', 'public');
                $url = Storage::url($path);

                $asset = $task->assets()->create([
                    'image_url' => $url,
                    'uploaded_by' => $user->id,
                ]);

                $uploadedAssets[] = $asset;
                $uploadedUrls[] = $url;
            }
        }
        // Handle single image (backward compatibility)
        elseif ($request->hasFile('image')) {
            $path = $request->file('image')->store('task-assets', 'public');
            $url = Storage::url($path);

            $asset = $task->assets()->create([
                'image_url' => $url,
                'uploaded_by' => $user->id,
            ]);

            $uploadedAssets[] = $asset;
            $uploadedUrls[] = $url;
        }

        // Log activity for all uploaded images
        $imageCount = count($uploadedUrls);
        $activityContent = $imageCount === 1 
            ? "Image uploaded: {$uploadedUrls[0]}"
            : "{$imageCount} images uploaded";

        TaskActivity::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'type' => 'asset_added',
            'content' => $activityContent,
            'metadata' => [
                'asset_urls' => $uploadedUrls,
                'count' => $imageCount,
            ],
        ]);

        return response()->json([
            'data' => $uploadedAssets,
            'count' => count($uploadedAssets),
        ], 201);
    }

    /**
     * Delete task (PM or creator only)
     *
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     tags={"Tasks"},
     *     summary="Delete task",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Task deleted successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Permission denied"),
     *     @OA\Response(response=404, description="Task not found")
     * )
     */
    public function destroy(Request $request, Task $task)
    {
        $user = $request->user();
        if ($user->role !== 'PM' && $user->id !== $task->created_by) {
            return response()->json([
                'message' => 'You do not have permission to delete this task.',
            ], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully.',
        ]);
    }
}

