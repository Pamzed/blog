<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function (Request $request) {
    if ($request->getSchemeAndHttpHost() === 'http://127.0.0.1:8000' || $request->getSchemeAndHttpHost() === '127.0.0.1:8000' || $request->getSchemeAndHttpHost() === 'http://127.0.0.1:8000') {
        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'This is for api only.',
            'data' => null,
        ]);
    } else {
        return '<center>This is the backend for the blog</center>';
    }
});

require __DIR__ . '/auth.php';
