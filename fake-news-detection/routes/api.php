<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExtensionController;

/*
| Called by the browser extension, which has no session and cannot send a
| CSRF token — this lives in routes/api.php, not routes/web.php, so the
| stateless "api" middleware group applies instead of "web".
*/
Route::post('/extension/check', [ExtensionController::class, 'check']);