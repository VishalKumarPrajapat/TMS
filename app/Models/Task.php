<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'deadline',
        'status',
        'user_id',
        'assigned_to'
    ];

    protected $casts = [
        'deadline' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

 
    /** Priority constants */
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

    /** Status constants */
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
}
