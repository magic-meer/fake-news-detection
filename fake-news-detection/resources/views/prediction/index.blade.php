@extends('layouts.app')

@section('title', 'NewsGuard')

@section('content')
    <div class="max-w-3xl mx-auto space-y-8">
        
        <!-- Intro -->
        <div class="text-center space-y-4">
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight transition-colors">
                Verify News Credibility
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400 max-w-2xl mx-auto transition-colors">
                Analyze authenticity by pasting the article text or providing a direct URL.
            </p>
        </div>

        <!-- Error Message -->
        @if($errors->any())
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-md shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">There were errors with your submission</h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Prediction Result -->
        @if(isset($result))
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl overflow-hidden border border-slate-200 dark:border-slate-700 animate-fade-in-up transition-colors">
                <div class="p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div class="text-center sm:text-left">
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Prediction Result</p>
                            @if($result['prediction'] === 'Real')
                                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 font-bold text-2xl border border-green-200 dark:border-green-800">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    REAL NEWS
                                </div>
                            @else
                                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 font-bold text-2xl border border-red-200 dark:border-red-800">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    FAKE NEWS
                                </div>
                            @endif
                        </div>

                        <div class="w-full sm:w-1/2">
                            <div class="flex justify-between mb-2">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Confidence Score</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ number_format($result['confidence_score'] * 100, 1) }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all duration-1000 ease-out {{ $result['prediction'] === 'Real' ? 'bg-green-500' : 'bg-red-500' }}" style="width: {{ $result['confidence_score'] * 100 }}%"></div>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 text-right">Based on CNN Model Analysis</p>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 dark:bg-slate-900/50 px-6 py-4 border-t border-slate-100 dark:border-slate-700 transition-colors">
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                        <span class="font-semibold text-slate-700 dark:text-slate-300">Analyzed Text Snippet:</span> 
                        "{{ \Illuminate\Support\Str::limit($news_text, 100) }}"
                    </p>

                    @if(isset($explanation) && count($explanation) > 0)
                        <div class="border-t border-slate-200 dark:border-slate-700 pt-4">
                            <h4 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">Why this prediction?</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($explanation as $item)
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
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">Words highlighted in <span class="text-green-600 dark:text-green-400 font-medium">Green</span> suggest Real news, while <span class="text-red-600 dark:text-red-400 font-medium">Red</span> suggest Fake news.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Input Form -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 sm:p-8 transition-colors relative" 
             x-data="{ mode: '{{ old('news_url', $news_url ?? '') ? 'url' : 'text' }}', isLoading: false }">
            
            <!-- Loading Overlay -->
            <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm z-10 rounded-2xl flex items-center justify-center">
                <div class="text-center">
                    <svg class="animate-spin h-12 w-12 text-blue-600 dark:text-blue-400 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-lg font-semibold text-slate-700 dark:text-slate-300">Analyzing...</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1" x-text="mode === 'url' ? 'Extracting content from URL and running prediction' : 'Running AI analysis'"></p>
                </div>
            </div>
            
            <!-- Toggle -->
            <div class="flex justify-center mb-6">
                <div class="bg-slate-100 dark:bg-slate-700 p-1 rounded-lg inline-flex transition-colors">
                    <button 
                        @click="mode = 'text'" 
                        :class="mode === 'text' ? 'bg-white dark:bg-slate-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-all"
                    >
                        Paste Text
                    </button>
                    <button 
                        @click="mode = 'url'" 
                        :class="mode === 'url' ? 'bg-white dark:bg-slate-600 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-all"
                    >
                        Enter URL
                    </button>
                </div>
            </div>

            <form action="{{ route('predict') }}" method="POST" @submit="isLoading = true">
                @csrf
                
                <!-- Text Input -->
                <div x-show="mode === 'text'" class="space-y-4">
                    <label for="news_text" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        News Article Text
                    </label>
                    <div class="relative">
                        <textarea 
                            id="news_text" 
                            name="news_text" 
                            rows="8" 
                            class="block w-full rounded-xl border-slate-300 dark:border-slate-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-4 bg-slate-50 dark:bg-slate-900 placeholder-slate-400 dark:placeholder-slate-500 text-slate-900 dark:text-white transition-all"
                            placeholder="Paste the full text of the news article here..."
                        >{{ old('news_text', $news_text ?? '') }}</textarea>
                        <div class="absolute bottom-3 right-3 text-xs text-slate-400">
                            Min 10 chars
                        </div>
                    </div>
                </div>

                <!-- URL Input -->
                <div x-show="mode === 'url'" class="space-y-4" style="display: none;">
                    <label for="news_url" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Article URL
                    </label>
                    <div class="flex gap-2">
                        <input 
                            type="url" 
                            id="news_url" 
                            name="news_url" 
                            value="{{ old('news_url', $news_url ?? '') }}"
                            class="block w-full rounded-xl border-slate-300 dark:border-slate-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-4 bg-slate-50 dark:bg-slate-900 placeholder-slate-400 dark:placeholder-slate-500 text-slate-900 dark:text-white transition-all"
                            placeholder="https://example.com/news-article"
                        >
                        <button 
                            type="button"
                            @click="navigator.clipboard.readText().then(text => { document.getElementById('news_url').value = text; })"
                            class="flex-shrink-0 px-4 py-2 border border-slate-300 dark:border-slate-600 shadow-sm text-sm font-medium rounded-xl text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                            title="Paste from Clipboard"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">We will extract the main content from this URL to analyze.</p>
                </div>

                <div class="mt-6">
                    <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:scale-[1.01] active:scale-[0.99]">
                        Analyze Authenticity
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Predictions -->
        @if(isset($recentPredictions) && $recentPredictions->count() > 0)
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Recent Predictions</h3>
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
                    <ul class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach($recentPredictions as $history)
                            <li class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                <a href="{{ route('history', $history->id) }}" class="block p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0 pr-4">
                                            <p class="text-sm text-slate-900 dark:text-slate-200 truncate">
                                                {{ \Illuminate\Support\Str::limit($history->news_text, 80) }}
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                {{ $history->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $history->prediction === 'Real' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' }}">
                                                {{ $history->prediction }}
                                            </span>
                                            <span class="text-xs font-semibold text-slate-600 dark:text-slate-400">
                                                {{ number_format($history->confidence_score * 100, 0) }}%
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

    </div>
@endsection
