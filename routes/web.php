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
    if ($request->getSchemeAndHttpHost() === 'https://test.larihq.com' || $request->getSchemeAndHttpHost() === 'test.larihq.com' || $request->getSchemeAndHttpHost() === 'http://test.larihq.com') {
        return response()->json([
            'status' => 200,
            'success' => true,
            'message' => 'This is for api only.',
            'data' => null,
        ]);
    } else {
        return '<center>So what you looking for?</center>';
    }
});

require __DIR__ . '/auth.php';
