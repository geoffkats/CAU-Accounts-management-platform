@php
    $companySettings = \App\Models\CompanySetting::first();
    $companyName = $companySettings?->company_name ?? 'Accounting System';
@endphp

<div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-blue-600 dark:bg-blue-500 shadow-sm">
    <x-app-logo-icon class="size-5 fill-current text-white" />
</div>
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="mb-0.5 truncate leading-tight font-semibold text-zinc-900 dark:text-zinc-100">{{ $companyName }}</span>
    <span class="text-xs text-zinc-500 dark:text-zinc-400">Financial Management</span>
</div>
