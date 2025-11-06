<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <flux:sidebar sticky stashable class="border-e border-zinc-200/80 bg-white dark:border-zinc-700/50 dark:bg-zinc-800/95 overflow-x-hidden custom-scrollbar">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Overview')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('Core Modules')" expandable class="grid">
                    <flux:navlist.item icon="folder" :href="route('programs.index')" :current="request()->routeIs('programs.*')" wire:navigate>{{ __('Programs') }}</flux:navlist.item>
                    <flux:navlist.item icon="calculator" :href="route('accounts.index')" :current="request()->routeIs('accounts.*')" wire:navigate>{{ __('Chart of Accounts') }}</flux:navlist.item>
                    <flux:navlist.item icon="document-plus" :href="route('accounts.opening-balances')" :current="request()->routeIs('accounts.opening-balances')" wire:navigate>{{ __('Opening Balances') }}</flux:navlist.item>
                    <flux:navlist.item icon="banknotes" :href="route('sales.index')" :current="request()->routeIs('sales.*')" wire:navigate>{{ __('Sales & Income') }}</flux:navlist.item>
                    <flux:navlist.item icon="receipt-refund" :href="route('expenses.index')" :current="request()->routeIs('expenses.*')" wire:navigate>{{ __('Expenses') }}</flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('Contacts')" expandable class="grid">
                    <flux:navlist.item icon="users" :href="route('customers.index')" :current="request()->routeIs('customers.*')" wire:navigate>{{ __('Customers') }}</flux:navlist.item>
                    <flux:navlist.item icon="user-group" :href="route('vendors.index')" :current="request()->routeIs('vendors.*')" wire:navigate>{{ __('Vendors') }}</flux:navlist.item>
                    <flux:navlist.item icon="document-text" :href="route('vendor-invoices.index')" :current="request()->routeIs('vendor-invoices.*')" wire:navigate>{{ __('Vendor Invoices') }}</flux:navlist.item>
                </flux:navlist.group>

                {{--
                    TEMPORARILY HIDDEN: Student Fees navigation
                    -------------------------------------------------
                    This block is intentionally commented out per request to hide
                    all Student Fees links (Students, Invoices, Payments, Fee Structures,
                    Scholarships) from the sidebar until the module is ready.

                    To re-enable later, simply remove the surrounding Blade comment.

                    <flux:navlist.group :heading="__('Student Fees')" expandable class="grid">
                        <flux:navlist.item icon="academic-cap" :href="route('students.index')" :current="request()->routeIs('students.*')" wire:navigate>{{ __('Students') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('invoices.index')" :current="request()->routeIs('invoices.*')" wire:navigate>{{ __('Invoices') }}</flux:navlist.item>
                        <flux:navlist.item icon="credit-card" :href="route('payments.index')" :current="request()->routeIs('payments.*')" wire:navigate>{{ __('Payments') }}</flux:navlist.item>
                        <flux:navlist.item icon="currency-dollar" :href="route('fees.index')" :current="request()->routeIs('fees.*')" wire:navigate>{{ __('Fee Structures') }}</flux:navlist.item>
                        <flux:navlist.item icon="gift" :href="route('scholarships.index')" :current="request()->routeIs('scholarships.*')" wire:navigate>{{ __('Scholarships') }}</flux:navlist.item>
                    </flux:navlist.group>
                --}}

                @if(auth()->user() && auth()->user()->role === 'admin')
                <flux:navlist.group :heading="__('Staff & Payroll')" expandable class="grid">
                    <flux:navlist.item icon="user-circle" :href="route('staff.index')" :current="request()->routeIs('staff.*')" wire:navigate>{{ __('Staff Management') }}</flux:navlist.item>
                    <flux:navlist.item icon="currency-dollar" :href="route('payroll.index')" :current="request()->routeIs('payroll.*')" wire:navigate>{{ __('Payroll Runs') }}</flux:navlist.item>
                </flux:navlist.group>
                @endif

                <flux:navlist.group :heading="__('Assets')" expandable class="grid">
                    <flux:navlist.item icon="computer-desktop" :href="route('assets.index')" :current="request()->routeIs('assets.*')" wire:navigate>{{ __('Asset Register') }}</flux:navlist.item>
                    <flux:navlist.item icon="wrench-screwdriver" :href="route('maintenance.index')" :current="request()->routeIs('maintenance.*')" wire:navigate>{{ __('Maintenance Schedule') }}</flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.group :heading="__('Reports')" expandable class="grid">
                    <flux:navlist.item icon="book-open" :href="route('general-ledger')" :current="request()->routeIs('general-ledger') || request()->routeIs('account-statement')" wire:navigate>{{ __('General Ledger') }}</flux:navlist.item>
                    <flux:navlist.item icon="table-cells" :href="route('trial-balance')" :current="request()->routeIs('trial-balance')" wire:navigate>{{ __('Trial Balance') }}</flux:navlist.item>
                    <flux:navlist.item icon="chart-bar" :href="route('reports.balance-sheet')" :current="request()->routeIs('reports.balance-sheet')" wire:navigate>{{ __('Balance Sheet') }}</flux:navlist.item>
                    <flux:navlist.item icon="document-text" :href="route('journal-entries.index')" :current="request()->routeIs('journal-entries.*')" wire:navigate>{{ __('Journal Entries') }}</flux:navlist.item>
                    <flux:navlist.item icon="chart-bar" :href="route('reports.profit-loss')" :current="request()->routeIs('reports.profit-loss')" wire:navigate>{{ __('Profit & Loss') }}</flux:navlist.item>
                    <flux:navlist.item icon="chart-pie" :href="route('reports.expense-breakdown')" :current="request()->routeIs('reports.expense-breakdown')" wire:navigate>{{ __('Expense Breakdown') }}</flux:navlist.item>
                    <flux:navlist.item icon="presentation-chart-line" :href="route('reports.sales-by-program')" :current="request()->routeIs('reports.sales-by-program')" wire:navigate>{{ __('Sales by Program') }}</flux:navlist.item>
                    <flux:navlist.item icon="banknotes" :href="route('reports.currency-conversion')" :current="request()->routeIs('reports.currency-conversion')" wire:navigate>{{ __('Currency Conversion') }}</flux:navlist.item>
                </flux:navlist.group>

                @if(auth()->user() && in_array(auth()->user()->role, ['admin', 'accountant']))
                <flux:navlist.group :heading="__('Planning')" expandable class="grid">
                    <flux:navlist.item icon="calculator" :href="route('budgets.index')" :current="request()->routeIs('budgets.index') || request()->routeIs('budgets.show') || request()->routeIs('budgets.create') || request()->routeIs('budgets.edit') || request()->routeIs('budgets.alerts')" wire:navigate>{{ __('Budget vs Actual') }}</flux:navlist.item>
                    <flux:navlist.item icon="arrows-right-left" :href="route('budgets.reallocations.index')" :current="request()->routeIs('budgets.reallocations.*')" wire:navigate>{{ __('Budget Reallocations') }}</flux:navlist.item>
                </flux:navlist.group>
                @endif

                @if(auth()->user() && auth()->user()->role === 'admin')
                <flux:navlist.group :heading="__('Compliance')" expandable class="grid">
                    <flux:navlist.item icon="shield-check" :href="route('settings.audit')" :current="request()->routeIs('settings.audit*')" wire:navigate>{{ __('Audit Trail') }}</flux:navlist.item>
                    <flux:navlist.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>{{ __('User Management') }}</flux:navlist.item>
                    <flux:navlist.item icon="cog" :href="route('settings.company')" :current="request()->routeIs('settings.company')" wire:navigate>{{ __('Company Settings') }}</flux:navlist.item>
                    <flux:navlist.item icon="banknotes" :href="route('settings.currencies')" :current="request()->routeIs('settings.currencies')" wire:navigate>{{ __('Currencies') }}</flux:navlist.item>
                </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="user-circle" :href="route('profile.edit')" :current="request()->routeIs('profile.edit')" wire:navigate>
                {{ __('My Profile') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                    data-test="sidebar-menu-button"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>{{ __('My Profile') }}</flux:menu.item>
                        @if(auth()->user() && auth()->user()->role === 'admin')
                        <flux:menu.item :href="route('settings.audit')" icon="shield-check" wire:navigate>{{ __('Audit Trail') }}</flux:menu.item>
                        <flux:menu.item :href="route('users.index')" icon="users" wire:navigate>{{ __('User Management') }}</flux:menu.item>
                        @endif
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="user-circle" wire:navigate>{{ __('My Profile') }}</flux:menu.item>
                        @if(auth()->user() && auth()->user()->role === 'admin')
                        <flux:menu.item :href="route('settings.audit')" icon="shield-check" wire:navigate>{{ __('Audit Trail') }}</flux:menu.item>
                        <flux:menu.item :href="route('users.index')" icon="users" wire:navigate>{{ __('User Management') }}</flux:menu.item>
                        @endif
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
