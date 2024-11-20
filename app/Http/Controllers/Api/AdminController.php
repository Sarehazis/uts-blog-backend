<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{

    
    public function updateRole(Request $request, $id)
    {
        // Validasi data input
        $validatedData = $request->validate([
            'role' => 'required|in:reader,writer', // role dengan huruf kecil
        ]);

        // Temukan user berdasarkan ID
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // Cegah admin mengubah role admin
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Tidak dapat mengubah role admin'], 403);
        }

        // Perbarui role pengguna
        $user->role = $validatedData['role'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' =>  'Role dengan nama ' . $user->name . ' berhasil diperbarui',
            'data' => $user,
        ]);
    }
}
