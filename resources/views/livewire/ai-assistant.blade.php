<div class="flex flex-col h-[600px] bg-white dark:bg-zinc-800 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden" 
     x-data="{ 
        init() {
            this.scrollToBottom();
            Livewire.on('messageSent', () => {
                setTimeout(() => this.scrollToBottom(), 100);
            });
        },
        scrollToBottom() {
            const container = this.$refs.chatContainer;
            container.scrollTop = container.scrollHeight;
        }
     }"
     x-on:trigger-analysis.window="$wire.generateResponse()">
    <!-- Header -->
    <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white shadow-inner">
                <flux:icon icon="cpu-chip" class="size-6" />
            </div>
            <div>
                <h3 class="font-bold text-zinc-900 dark:text-white">Accounting Assistant</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-green-500 mr-2 animate-pulse"></span>
                    AI-Powered Insights
                </p>
            </div>
        </div>
        <flux:button variant="ghost" icon="trash" size="sm" wire:click="clearChat" wire:confirm="Are you sure you want to clear this chat history?">
            Clear
        </flux:button>
    </div>

    <!-- Chat Messages -->
    <div x-ref="chatContainer" class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-zinc-50/50 dark:bg-zinc-900/50">
        @foreach($messages as $msg)
            <div class="flex {{ $msg->role === 'user' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[85%] {{ $msg->role === 'user' ? 'bg-zinc-800 text-white rounded-l-xl rounded-tr-xl' : 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 rounded-r-xl rounded-tl-xl shadow-sm border border-zinc-200 dark:border-zinc-700' }} p-5">
                    
                    @if($msg->role === 'user')
                        <div class="text-sm font-light tracking-wide">
                            {!! nl2br(e($msg->content)) !!}
                        </div>
                    @else
                        @php
                            $data = json_decode($msg->content, true);
                        @endphp
                        
                        @if($data && isset($data['summary']))
                            <!-- ... Professional Report Layout (keeping existing structure) ... -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-700 pb-2 mb-2">
                                    <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400">Financial Analysis Report</span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold {{ 
                                        ($data['confidence_level'] ?? 'Low') === 'High' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 
                                        (($data['confidence_level'] ?? 'Low') === 'Medium' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400')
                                    }}">
                                        {{ $data['confidence_level'] ?? 'Low' }} Confidence
                                    </span>
                                </div>

                                <div class="prose prose-sm dark:prose-invert max-w-none">
                                    <h4 class="text-xs font-bold uppercase text-zinc-500 mb-1">Executive Summary</h4>
                                    <p class="text-zinc-700 dark:text-zinc-300 leading-relaxed">{{ $data['summary'] }}</p>
                                </div>

                                @if(isset($data['key_metrics']) && count($data['key_metrics']) > 0)
                                    <div class="grid grid-cols-2 gap-3 py-3 border-y border-zinc-100 dark:border-zinc-700">
                                        @foreach($data['key_metrics'] as $label => $value)
                                            <div class="bg-zinc-50 dark:bg-zinc-900/50 p-2 rounded border border-zinc-100 dark:border-zinc-800">
                                                <div class="text-[10px] text-zinc-500 uppercase">{{ str_replace('_', ' ', $label) }}</div>
                                                <div class="text-sm font-bold text-zinc-900 dark:text-white">
                                                    @if(is_numeric($value))
                                                        {{ strpos($label, 'percent') !== false ? number_format($value, 1) . '%' : number_format($value, 0) }}
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if(isset($data['insights']) && count($data['insights']) > 0)
                                        <div>
                                            <h4 class="text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400 mb-2">Key Insights</h4>
                                            <ul class="text-xs space-y-1 text-zinc-600 dark:text-zinc-400 list-disc pl-4">
                                                @foreach($data['insights'] as $insight)
                                                    <li>{{ $insight }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if(isset($data['risks']) && count($data['risks']) > 0)
                                        <div>
                                            <h4 class="text-[10px] font-bold uppercase text-red-600 dark:text-red-400 mb-2">Risks & Flags</h4>
                                            <ul class="text-xs space-y-1 text-zinc-600 dark:text-zinc-400 list-disc pl-4">
                                                @foreach($data['risks'] as $risk)
                                                    <li>{{ $risk }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>

                                @if(isset($data['recommendations']) && count($data['recommendations']) > 0)
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-100 dark:border-blue-800/50 mt-2">
                                        <h4 class="text-[10px] font-bold uppercase text-blue-800 dark:text-blue-300 mb-2">Recommendations</h4>
                                        <ul class="text-xs space-y-1 text-blue-900/80 dark:text-blue-200/80 list-disc pl-4">
                                            @foreach($data['recommendations'] as $rec)
                                                <li>{{ $rec }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if(isset($data['metadata']['chart_data']) && $data['metadata']['chart_data'])
                                    <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700" x-data="{
                                        chart: null,
                                        init() {
                                            this.renderChart();
                                        },
                                        renderChart() {
                                            const chartData = @js($data['metadata']['chart_data']);
                                            const ctx = this.$refs.canvas.getContext('2d');
                                            if (this.chart) this.chart.destroy();
                                            this.chart = new Chart(ctx, {
                                                type: chartData.type || 'bar',
                                                data: {
                                                    labels: chartData.labels,
                                                    datasets: chartData.datasets
                                                },
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
                                                    plugins: {
                                                        legend: { display: true, position: 'bottom' }
                                                    }
                                                }
                                            });
                                        }
                                    }">
                                        <div class="h-64">
                                            <canvas x-ref="canvas"></canvas>
                                        </div>
                                    </div>
                                @endif
                                
                                @if(($data['confidence_level'] ?? 'Low') === 'Low')
                                    <div class="mt-2 p-2 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 rounded text-[9px] text-red-600 dark:text-red-400 italic">
                                        Notice: This analysis has low confidence. Review by a human accountant is mandatory.
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-sm prose dark:prose-invert">
                                {!! Str::markdown($msg->content) !!}
                            </div>
                        @endif
                    @endif
                    
                    <div class="mt-4 text-[10px] {{ $msg->role === 'user' ? 'text-zinc-400' : 'text-zinc-400' }} text-right opacity-80">
                        {{ $msg->created_at->format('H:i') }}
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Loading Indicator: shows only when 'sendMessage' action is in flight -->
        <div wire:loading wire:target="generateResponse" class="flex justify-start">
            <div class="bg-white dark:bg-zinc-800 p-4 rounded-r-xl rounded-tl-xl border border-zinc-200 dark:border-zinc-700 shadow-sm flex items-center space-x-3">
                <div class="flex space-x-1">
                    <div class="w-1.5 h-1.5 bg-zinc-400 rounded-full animate-bounce"></div>
                    <div class="w-1.5 h-1.5 bg-zinc-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    <div class="w-1.5 h-1.5 bg-zinc-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                </div>
                <span class="text-[10px] text-zinc-500 uppercase font-bold tracking-tighter shimmer">Analyzing Financial Data...</span>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
        <form wire:submit.prevent="sendMessage" class="flex items-center space-x-3">
            <div class="relative flex-1">
                <input 
                    type="text" 
                    wire:model="message" 
                    placeholder="Enter analytical query (e.g., 'Perform a quarterly profitability review')..." 
                    class="w-full bg-zinc-50 dark:bg-zinc-900 border-none rounded-full px-5 py-3 text-sm focus:ring-1 focus:ring-zinc-300 dark:text-white pr-10 shadow-inner"
                    wire:loading.attr="disabled"
                    wire:target="sendMessage"
                >
            </div>
            <button 
                type="submit" 
                class="bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 rounded-full p-3 hover:scale-105 active:scale-95 transition-transform shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
            >
                <flux:icon icon="paper-airplane" class="size-5" />
            </button>
        </form>
        <p class="text-[9px] text-zinc-400 mt-3 text-center uppercase tracking-[0.2em] font-medium">
            Enterprise Financial Analysis Engine • Audit Status: Unverified
        </p>
    </div>

    <!-- Scripts for Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</div>

