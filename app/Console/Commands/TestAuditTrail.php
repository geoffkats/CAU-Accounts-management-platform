<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityLog;
use App\Models\Sale;

class TestAuditTrail extends Command
{
    protected $signature = 'audit:test';
    protected $description = 'Test audit trail functionality';

    public function handle()
    {
        $this->info('Testing Audit Trail...');
        
        // Get latest activity logs
        $logs = ActivityLog::latest()->limit(10)->get();
        
        $this->info("Total logs in database: " . ActivityLog::count());
        $this->info("Latest 10 logs:");
        
        foreach ($logs as $log) {
            $this->line(sprintf(
                "ID: %d | Action: %s | Module: %s | Model: %s | User: %s | Date: %s",
                $log->id,
                $log->action,
                $log->module ?? 'N/A',
                class_basename($log->model_type),
                $log->user_id ?? 'System',
                $log->created_at->format('Y-m-d H:i:s')
            ));
            
            if ($log->changes) {
                $this->line("  Changes: " . json_encode($log->changes));
            }
        }
        
        // Test creating a sale
        $this->info("\nTesting: Creating a test sale...");
        try {
            $sale = Sale::create([
                'program_id' => 1,
                'customer_id' => 1,
                'account_id' => 8,
                'invoice_number' => 'TEST-' . time(),
                'sale_date' => now(),
                'amount' => 100000,
                'currency' => 'UGX',
                'status' => 'unpaid',
                'amount_paid' => 0,
            ]);
            
            $this->info("Sale created: " . $sale->invoice_number);
            
            // Check if log was created
            $saleLog = ActivityLog::where('model_type', get_class($sale))
                ->where('model_id', $sale->id)
                ->where('action', 'created')
                ->first();
            
            if ($saleLog) {
                $this->info("✓ Audit log created successfully!");
                $this->line("  Log ID: " . $saleLog->id);
                $this->line("  Module: " . $saleLog->module);
                $this->line("  Changes: " . json_encode($saleLog->changes));
            } else {
                $this->error("✗ No audit log found for the created sale!");
            }
            
            // Clean up
            $sale->delete();
            $this->info("Test sale deleted.");
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
        
        return 0;
    }
}
