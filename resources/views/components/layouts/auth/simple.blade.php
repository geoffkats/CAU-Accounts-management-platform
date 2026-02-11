<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <style>
            body {
                background-image: url('https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?q=80&w=2070&auto=format&fit=crop');
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
                position: relative;
                overflow-x: hidden;
            }
            body::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, rgba(102, 126, 234, 0.9) 0%, rgba(118, 75, 162, 0.9) 100%);
                pointer-events: none;
            }
            @keyframes moveBackground {
                0% { transform: translate(0, 0); }
                100% { transform: translate(50px, 50px); }
            }
            .auth-card {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            }
            .logo-container {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
        </style>
    </head>
    <body class="min-h-screen antialiased">
        @php
            $companySettings = \App\Models\CompanySetting::first();
            $companyName = $companySettings?->company_name ?? 'Accounting System';
            $logoPath = $companySettings?->logo_path;
        @endphp
        
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10 relative z-10">
            <div class="flex w-full max-w-md flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 font-medium mb-4" wire:navigate>
                    @if($logoPath && Storage::disk('public')->exists($logoPath))
                        <span class="flex h-20 w-20 items-center justify-center rounded-2xl shadow-xl hover:shadow-2xl transition-shadow duration-300 bg-white overflow-hidden">
                            <img src="{{ Storage::url($logoPath) }}" alt="{{ $companyName }}" class="w-full h-full object-contain p-2">
                        </span>
                    @else
                        <span class="logo-container flex h-16 w-16 items-center justify-center rounded-2xl shadow-xl hover:shadow-2xl transition-shadow duration-300">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </span>
                    @endif
                    <span class="text-2xl font-bold text-white drop-shadow-lg">{{ $companyName }}</span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="auth-card flex flex-col gap-6 p-8 rounded-2xl">
                    {{ $slot }}
                </div>
                <p class="text-center text-sm text-white/90 mt-4 drop-shadow">
                    Software designed by <span class="font-semibold">Synthilogic Enterprises</span>
                </p>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
