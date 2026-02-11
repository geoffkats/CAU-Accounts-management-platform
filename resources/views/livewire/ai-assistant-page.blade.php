<?php

use function Livewire\Volt\{title};

title(__('AI Accounting Assistant'));

?>

<div class="max-w-4xl mx-auto py-8">
    <div class="mb-6 flex justify-between items-end">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">AI Accounting Assistant</h1>
            <p class="text-zinc-500 dark:text-zinc-400">Your intelligent partner for financial analysis and insights.</p>
        </div>
        <flux:button :href="route('dashboard')" variant="ghost" icon="chevron-left" wire:navigate>
            Back to Dashboard
        </flux:button>
    </div>

    <livewire:ai-assistant />
</div>
