<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssetMaintenance extends Model
{
    use HasFactory;

    protected $table = 'asset_maintenance';

    protected $fillable = [
        'asset_id',
        'type',
        'scheduled_date',
        'completed_date',
        'status',
        'description',
        'work_performed',
        'performed_by',
        'cost',
        'invoice_number',
        'downtime_hours',
        'notes',
        'parts_replaced',
        'next_maintenance_date',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'next_maintenance_date' => 'date',
        'cost' => 'decimal:2',
        'downtime_hours' => 'integer',
    ];

    // Relationships
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_date', '<', now());
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('status', 'scheduled')
            ->whereBetween('scheduled_date', [now(), now()->addDays($days)]);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->status === 'scheduled' && $this->scheduled_date->isPast();
    }

    public function getIsUpcomingAttribute()
    {
        return $this->status === 'scheduled' 
            && $this->scheduled_date->isFuture() 
            && $this->scheduled_date->diffInDays(now()) <= 7;
    }

    /**
     * Mark maintenance as completed
     */
    public function complete($workPerformed, $cost = 0, $partsReplaced = null, $nextMaintenanceDate = null)
    {
        $this->update([
            'status' => 'completed',
            'completed_date' => now(),
            'work_performed' => $workPerformed,
            'cost' => $cost,
            'parts_replaced' => $partsReplaced,
            'next_maintenance_date' => $nextMaintenanceDate,
        ]);
        
        return $this;
    }
}
