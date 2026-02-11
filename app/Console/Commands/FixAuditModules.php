<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class FixAuditModules extends Command
{
    protected $signature = 'audit:fix-modules';
    protected $description = 'Fix module field for existing audit logs';

    public function handle()
    {
        $this->info('Fixing module field for existing audit logs...');
        
        $logs = ActivityLog::whereNull('module')->get();
        $count = 0;
        
        foreach ($logs as $log) {
            $module = class_basename($log->model_type);
            DB::table('activity_logs')
                ->where('id', $log->id)
                ->update(['module' => $module]);
            $count++;
        }
        
        $this->info("Fixed {$count} audit logs.");
        
        return 0;
    }
}
