<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function users()
    {
        // Logika untuk mengambil data pengguna
        $users = User::all(); // contoh mengambil semua data user
        return response()->json(['data' => $users]);
    }
    
}
