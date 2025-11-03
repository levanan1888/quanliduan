<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAsset extends Model
{
    use HasFactory;

    protected $table = 'task_assets';

    protected $fillable = [
        'task_id',
        'image_url',
        'uploaded_by',
    ];

    public $timestamps = false; // Only uploaded_at, no updated_at

    protected $dates = ['uploaded_at'];

    /**
     * Get the task this asset belongs to
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who uploaded this asset
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

