<?php

namespace App\Http\Controllers;

use App\Models\Patch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

class PatchController extends Controller
{
    public static function route(): void
    {
        Route::get('patches/{patch}', [PatchController::class, 'getPatch'])->name('patches.get');
    }

    public function getPatch(Patch $patch): JsonResponse
    {
        return response()->json($patch);
    }
}
