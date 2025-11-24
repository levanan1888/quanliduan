<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Sprint;
use App\Models\User;
use App\Models\Notification;
use App\Models\TaskActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     * - PM: See all system statistics
     * - MEMBER: See only statistics for their assigned projects and tasks
     *
     * @OA\Get(
     *     path="/api/dashboard",
     *     tags={"Dashboard"},
     *     summary="Get dashboard statistics",
     *     description="Get comprehensive dashboard statistics including projects, tasks, activities, and notifications. PM sees all statistics, MEMBER sees only their assigned data.",
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard statistics",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="overview", type="object",
     *                 @OA\Property(property="total_projects", type="integer", example=10),
     *                 @OA\Property(property="total_tasks", type="integer", example=45),
     *                 @OA\Property(property="total_members", type="integer", example=25),
     *                 @OA\Property(property="total_sprints", type="integer", example=8),
     *                 @OA\Property(property="my_tasks", type="integer", example=12),
     *                 @OA\Property(property="overdue_tasks", type="integer", example=3),
     *                 @OA\Property(property="unread_notifications", type="integer", example=5)
     *             ),
     *             @OA\Property(property="tasks_by_status", type="object",
     *                 @OA\Property(property="TO_DO", type="integer", example=15),
     *                 @OA\Property(property="IN_PROGRESS", type="integer", example=20),
     *                 @OA\Property(property="COMPLETED", type="integer", example=10)
     *             ),
     *             @OA\Property(property="tasks_by_priority", type="object",
     *                 @OA\Property(property="HIGH", type="integer", example=8),
     *                 @OA\Property(property="MEDIUM", type="integer", example=25),
     *                 @OA\Property(property="LOW", type="integer", example=12)
     *             ),
     *             @OA\Property(property="projects_by_status", type="object",
     *                 @OA\Property(property="active", type="integer", example=7),
     *                 @OA\Property(property="archived", type="integer", example=2),
     *                 @OA\Property(property="completed", type="integer", example=1)
     *             ),
     *             @OA\Property(property="recent_activities", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="user", type="object"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="upcoming_deadlines", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string"),
     *                     @OA\Property(property="date", type="string", format="date"),
     *                     @OA\Property(property="project", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Base queries with role-based filtering
        $projectQuery = $this->getProjectQuery($user);
        $taskQuery = $this->getTaskQuery($user);

        // Overview statistics
        $overview = [
            'total_projects' => $projectQuery->count(),
            'total_tasks' => $taskQuery->count(),
            'total_members' => $this->getTotalMembers($user),
            'total_sprints' => $this->getTotalSprints($user),
            'my_tasks' => $this->getMyTasksCount($user),
            'overdue_tasks' => $this->getOverdueTasksCount($user),
            'unread_notifications' => $user->notifications()->where('is_read', false)->count(),
        ];

        // Tasks by status
        $tasksByStatus = $taskQuery->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $tasksByStatus = [
            'TO_DO' => $tasksByStatus['TO_DO'] ?? 0,
            'IN_PROGRESS' => $tasksByStatus['IN_PROGRESS'] ?? 0,
            'COMPLETED' => $tasksByStatus['COMPLETED'] ?? 0,
        ];

        // Tasks by priority
        $tasksByPriority = $taskQuery->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        $tasksByPriority = [
            'HIGH' => $tasksByPriority['HIGH'] ?? 0,
            'MEDIUM' => $tasksByPriority['MEDIUM'] ?? 0,
            'LOW' => $tasksByPriority['LOW'] ?? 0,
        ];

        // Projects by status
        $projectsByStatus = $projectQuery->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $projectsByStatus = [
            'active' => $projectsByStatus['active'] ?? 0,
            'archived' => $projectsByStatus['archived'] ?? 0,
            'completed' => $projectsByStatus['completed'] ?? 0,
        ];

        // Recent activities (last 10)
        $recentActivities = $this->getRecentActivities($user, 10);

        // Upcoming deadlines (next 7 days)
        $upcomingDeadlines = $this->getUpcomingDeadlines($user, 7);

        return response()->json([
            'overview' => $overview,
            'tasks_by_status' => $tasksByStatus,
            'tasks_by_priority' => $tasksByPriority,
            'projects_by_status' => $projectsByStatus,
            'recent_activities' => $recentActivities,
            'upcoming_deadlines' => $upcomingDeadlines,
        ]);
    }

    /**
     * Get project query based on user role
     */
    private function getProjectQuery($user)
    {
        if ($user->role === 'PM') {
            return Project::query();
        }

        // MEMBER: Only projects they are assigned to
        return Project::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });
    }

    /**
     * Get task query based on user role
     */
    private function getTaskQuery($user)
    {
        if ($user->role === 'PM') {
            return Task::query();
        }

        // MEMBER: Tasks assigned to them or in their projects
        return Task::where(function ($query) use ($user) {
            $query->where('assigned_to', $user->id)
                ->orWhereHas('project.members', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
        });
    }

    /**
     * Get total members count
     */
    private function getTotalMembers($user)
    {
        if ($user->role === 'PM') {
            return User::where('is_active', true)->count();
        }

        // MEMBER: Count members in their projects
        $projectIds = $user->projects()->pluck('projects.id')->toArray();
        
        if (empty($projectIds)) {
            return 0;
        }

        return User::where(function ($query) use ($projectIds) {
            $query->whereHas('projects', function ($q) use ($projectIds) {
                $q->whereIn('projects.id', $projectIds);
            })
            ->orWhereHas('managedProjects', function ($q) use ($projectIds) {
                $q->whereIn('projects.id', $projectIds);
            });
        })
        ->where('is_active', true)
        ->distinct()
        ->count();
    }

    /**
     * Get total sprints count
     */
    private function getTotalSprints($user)
    {
        $projectQuery = $this->getProjectQuery($user);
        $projectIds = $projectQuery->pluck('id')->toArray();

        if (empty($projectIds)) {
            return 0;
        }

        return Sprint::whereIn('project_id', $projectIds)->count();
    }

    /**
     * Get my tasks count
     */
    private function getMyTasksCount($user)
    {
        return Task::where('assigned_to', $user->id)->count();
    }

    /**
     * Get overdue tasks count
     */
    private function getOverdueTasksCount($user)
    {
        $taskQuery = $this->getTaskQuery($user);
        
        return $taskQuery->whereNotNull('date')
            ->where('date', '<', Carbon::today())
            ->where('status', '!=', 'COMPLETED')
            ->count();
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities($user, $limit = 10)
    {
        $taskQuery = $this->getTaskQuery($user);
        $taskIds = $taskQuery->pluck('id')->toArray();

        if (empty($taskIds)) {
            return [];
        }

        return TaskActivity::whereIn('task_id', $taskIds)
            ->with(['user:id,full_name,email', 'task:id,title'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'content' => $activity->content,
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'full_name' => $activity->user->full_name,
                        'email' => $activity->user->email,
                    ] : null,
                    'task' => $activity->task ? [
                        'id' => $activity->task->id,
                        'title' => $activity->task->title,
                    ] : null,
                    'created_at' => $activity->created_at,
                ];
            });
    }

    /**
     * Get upcoming deadlines
     */
    private function getUpcomingDeadlines($user, $days = 7)
    {
        $taskQuery = $this->getTaskQuery($user);
        
        $deadline = Carbon::today()->addDays($days);

        return $taskQuery->whereNotNull('date')
            ->where('date', '>=', Carbon::today())
            ->where('date', '<=', $deadline)
            ->where('status', '!=', 'COMPLETED')
            ->with(['project:id,name'])
            ->orderBy('date', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'date' => $task->date,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'project' => $task->project ? [
                        'id' => $task->project->id,
                        'name' => $task->project->name,
                    ] : null,
                ];
            });
    }
}

