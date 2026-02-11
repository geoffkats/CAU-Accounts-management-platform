<?php

namespace App\Services\Ai\Reports;

use App\Services\Ai\IntentClassifierService;
use Exception;

class ReportFactory
{
    public static function make(string $intent): ReportHandlerInterface
    {
        switch ($intent) {
            case IntentClassifierService::INTENT_PROFIT_LOSS:
                return app(ProfitLossHandler::class);
            
            case IntentClassifierService::INTENT_EXPENSES:
                return app(ExpenseHandler::class);

            case IntentClassifierService::INTENT_BALANCE_SHEET:
                return app(BalanceSheetHandler::class);

            case IntentClassifierService::INTENT_TRIAL_BALANCE:
                return app(TrialBalanceHandler::class);

            default:
                throw new Exception("No handler found for intent: {$intent}");
        }
    }
}
