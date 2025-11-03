<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    use HasFactory;

    protected $table = 'task_activities';

    protected $fillable = [
        'task_id',
        'user_id',
        'type',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public $timestamps = false; // Only created_at, no updated_at

    protected $dates = ['created_at'];

    /**
     * Get the task this activity belongs to
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who performed this activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

