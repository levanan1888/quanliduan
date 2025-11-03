<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'sprint_id',
        'title',
        'date',
        'priority',
        'status',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the project this task belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the sprint this task belongs to (nullable for backlog)
     */
    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /**
     * Get the user assigned to this task
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this task
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all subtasks for this task
     */
    public function subTasks(): HasMany
    {
        return $this->hasMany(SubTask::class);
    }

    /**
     * Get all assets (images) for this task
     */
    public function assets(): HasMany
    {
        return $this->hasMany(TaskAsset::class);
    }

    /**
     * Get all activity logs for this task
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }
}

