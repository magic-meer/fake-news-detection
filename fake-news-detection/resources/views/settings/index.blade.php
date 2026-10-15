@extends('layouts.app')

@section('title', 'Settings - NewsGuard')

@section('content')
    <div class="max-w-3xl mx-auto space-y-8" 
         x-data="{
             trainingStatus: null,
             pollInterval: null,
             async checkStatus() {
                 try {
                     const response = await fetch('{{ config('services.ml_model.url') }}/status');
                     const data = await response.json();
                     this.trainingStatus = data.training_status;
                     
                     if (!this.trainingStatus.is_training && this.pollInterval) {
                         clearInterval(this.pollInterval);
                         this.pollInterval = null;
                     }
                 } catch (error) {
                     console.error('Failed to fetch training status:', error);
                 }
             },
             startPolling() {
                 if (!this.pollInterval) {
                     this.checkStatus();
                     this.pollInterval = setInterval(() => this.checkStatus(), 2000);
                 }
             }
         }"
         x-init="checkStatus()"
    >
        
        <div class="flex items-center justify-between">
            <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Model Settings</h2>
            
            <!-- Status Badge -->
            @if(isset($status['model_loaded']) && $status['model_loaded'])
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    Model Active
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800">
                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                    Model Offline
                </span>
            @endif
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-r-md shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-r-md shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error</h3>
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

        <!-- Training Progress Bar -->
        <div x-show="trainingStatus && trainingStatus.is_training" x-cloak class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-r-md shadow-sm">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Model Training in Progress</h3>
                    <div class="mt-2">
                        <p class="text-sm text-blue-700 dark:text-blue-300" x-text="trainingStatus.current_step"></p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1" x-text="trainingStatus.message"></p>
                    </div>
                    <div class="mt-3">
                        <div class="w-full bg-blue-200 dark:bg-blue-900 rounded-full h-2">
                            <div class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full transition-all duration-500" :style="`width: ${trainingStatus.progress}%`"></div>
                        </div>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1 text-right" x-text="`${trainingStatus.progress}%`"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Data Section -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Update Dataset</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Upload new CSV files to update the training data.</p>
            </div>
            <div class="p-6">
                <form action="{{ route('settings.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Fake News CSV</label>
                            <input type="file" name="fake_csv" accept=".csv" class="block w-full text-sm text-slate-500 dark:text-slate-400
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-blue-50 file:text-blue-700
                                hover:file:bg-blue-100
                                dark:file:bg-blue-900/30 dark:file:text-blue-400
                            "/>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">True News CSV</label>
                            <input type="file" name="true_csv" accept=".csv" class="block w-full text-sm text-slate-500 dark:text-slate-400
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-green-50 file:text-green-700
                                hover:file:bg-green-100
                                dark:file:bg-green-900/30 dark:file:text-green-400
                            "/>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Upload Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Retrain Model Section -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50">
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Retrain Model</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Configure parameters and start a new training session.</p>
            </div>
            <div class="p-6">
                <form action="{{ route('settings.retrain') }}" method="POST" class="space-y-6" @submit="startPolling()">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="max_features" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Max Features (TF-IDF)</label>
                            <input type="number" name="max_features" id="max_features" value="10000" min="1000" max="50000" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Number of top words to consider.</p>
                        </div>
                        <div>
                            <label for="max_iter" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Max Iterations</label>
                            <input type="number" name="max_iter" id="max_iter" value="1000" min="100" max="5000" class="mt-1 block w-full rounded-md border-slate-300 dark:border-slate-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Training epochs for Logistic Regression.</p>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Warning</h3>
                                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                                    <p>Retraining can take several minutes. The API might be temporarily slower during this process.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            Start Retraining
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection
