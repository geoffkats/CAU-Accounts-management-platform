<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'assigned_to_staff_id',
        'assigned_to_student',
        'assigned_date',
        'return_date',
        'status',
        'assignment_notes',
        'return_notes',
        'condition_on_return',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'return_date' => 'date',
    ];

    // Relationships
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedToStaff()
    {
        return $this->belongsTo(Staff::class, 'assigned_to_staff_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    // Accessors
    public function getAssignedToNameAttribute()
    {
        if ($this->assigned_to_staff_id && $this->assignedToStaff) {
            return $this->assignedToStaff->full_name;
        }
        
        if ($this->assigned_to_student) {
            return $this->assigned_to_student;
        }
        
        return 'Unknown';
    }

    public function getDaysAssignedAttribute()
    {
        $endDate = $this->return_date ?? now();
        return $this->assigned_date->diffInDays($endDate);
    }

    /**
     * Mark assignment as returned
     */
    public function returnAsset($condition, $notes = null)
    {
        $this->update([
            'status' => 'returned',
            'return_date' => now(),
            'condition_on_return' => $condition,
            'return_notes' => $notes,
        ]);
        
        // Update asset assignment
        $this->asset->update([
            'assigned_to_staff_id' => null,
            'assigned_to_student' => null,
            'assigned_date' => null,
        ]);
        
        return $this;
    }
}
