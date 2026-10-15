@extends('layouts.app')

@section('title', 'History - NewsGuard')

@section('content')
    <div class="max-w-7xl mx-auto h-[calc(100vh-10rem)]">
        
        <div class="flex h-full gap-6">
            
            <!-- Sidebar / List -->
            <div class="{{ $selectedPrediction ? 'w-1/3 hidden md:block' : 'w-full max-w-4xl mx-auto' }} bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col transition-colors">
                <div class="p-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                    <h2 class="font-semibold text-slate-900 dark:text-white">Prediction History</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $history->count() }} records found</p>
                </div>
                <div class="overflow-y-auto flex-1 p-2 space-y-2">
                    @forelse($history as $item)
                        <a href="{{ route('history', $item->id) }}" class="block p-4 rounded-xl border transition-all {{ isset($selectedPrediction) && $selectedPrediction->id === $item->id ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 ring-1 ring-blue-500 dark:ring-blue-400' : 'border-slate-100 dark:border-slate-700 hover:border-blue-200 dark:hover:border-blue-800 hover:bg-slate-50 dark:hover:bg-slate-700/50' }}">
                            <div class="flex justify-between items-start mb-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $item->prediction === 'Real' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' }}">
                                    {{ $item->prediction }}
                                </span>
                                <span class="text-xs text-slate-400 dark:text-slate-500">{{ $item->created_at->format('M d, H:i') }}</span>
                            </div>
                            <p class="text-sm text-slate-700 dark:text-slate-300 line-clamp-2">{{ $item->news_text }}</p>
                        </a>
                    @empty
                        <div class="p-8 text-center text-slate-500 dark:text-slate-400">
                            No history found. <a href="{{ route('home') }}" class="text-blue-600 dark:text-blue-400 hover:underline">Make a prediction</a>.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Detail View -->
            @if($selectedPrediction)
                <div class="w-full md:w-2/3 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden flex flex-col animate-fade-in transition-colors">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-900/50">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Prediction Details</h2>
                            <p class="text-sm text-slate-500 dark:text-slate-400">ID: #{{ $selectedPrediction->id }}</p>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs text-slate-500 dark:text-slate-400 uppercase tracking-wider">Result</span>
                            <span class="text-xl font-bold {{ $selectedPrediction->prediction === 'Real' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $selectedPrediction->prediction }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6 overflow-y-auto flex-1">
                        <!-- Confidence Bar -->
                        <div class="mb-8">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Confidence Score</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($selectedPrediction->confidence_score * 100, 1) }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
                                <div class="h-3 rounded-full {{ $selectedPrediction->prediction === 'Real' ? 'bg-green-500' : 'bg-red-500' }}" style="width: {{ $selectedPrediction->confidence_score * 100 }}%"></div>
                            </div>
                        </div>

                        <!-- Text Content -->
                        <div>
                            <h3 class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">Analyzed Text</h3>
                            <div class="prose prose-slate dark:prose-invert max-w-none bg-slate-50 dark:bg-slate-900/50 p-6 rounded-xl border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-200 leading-relaxed">
                                {{ $selectedPrediction->news_text }}
                            </div>
                        </div>

                        <!-- Explanation -->
                        @if($selectedPrediction->explanation && count($selectedPrediction->explanation) > 0)
                            <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-700">
                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-3">Explanation (Top Contributing Words)</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($selectedPrediction->explanation as $item)
                                        @php
                                            $word = $item[0];
                                            $weight = $item[1];
                                            $colorClass = $weight > 0 
                                                ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800' 
                                                : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium border {{ $colorClass }}" title="Influence: {{ number_format($weight, 4) }}">
                                            {{ $word }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700 text-right">
                        <span class="text-xs text-slate-400 dark:text-slate-500">Analyzed on {{ $selectedPrediction->created_at->toDayDateTimeString() }}</span>
                    </div>
                </div>
            @endif

        </div>
    </div>
@endsection
