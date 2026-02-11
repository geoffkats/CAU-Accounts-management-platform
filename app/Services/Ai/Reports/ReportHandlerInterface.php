<?php

namespace App\Services\Ai\Reports;

interface ReportHandlerInterface
{
    /**
     * Generate the structured data for the report.
     *
     * @param array $params (e.g., period, filters)
     * @return array
     */
    public function generate(array $params = []): array;
}
