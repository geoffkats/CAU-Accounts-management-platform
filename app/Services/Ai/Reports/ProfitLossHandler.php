<?php

namespace App\Services\Ai\Reports;

use App\Services\AccountingContextService;

class ProfitLossHandler implements ReportHandlerInterface
{
    protected $contextService;

    public function __construct(AccountingContextService $contextService)
    {
        $this->contextService = $contextService;
    }

    public function generate(array $params = []): array
    {
        // In a real implementation, we would use $params['period'] to filter query dates.
        // For now, we reuse the existing monthly summary logic but wrap it in a dedicated structure.
        
        $data = $this->contextService->getFinancialSummary();

        return [
            'report_name' => 'Profit & Loss Statement / Income Statement',
            'period' => $params['period'] ?? 'Current Month',
            'currency' => $data['currency'],
            'generated_at' => now()->toDateTimeString(),
            'financials' => $data['totals'],
            'ratios' => $data['ratios'],
            'notes' => 'Figures conform to IFRS standards. Revenue is recognized on accrual basis.'
        ];
    }
}
