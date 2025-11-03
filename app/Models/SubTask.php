<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubTask extends Model
{
    use HasFactory;

    protected $table = 'sub_tasks';

    protected $fillable = [
        'task_id',
        'title',
        'date',
        'tag',
        'is_completed',
    ];

    protected $casts = [
        'date' => 'date',
        'is_completed' => 'boolean',
    ];

    public $timestamps = false; // Only created_at, no updated_at

    /**
     * Get the task this subtask belongs to
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

