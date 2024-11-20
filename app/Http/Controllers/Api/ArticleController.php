<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    // public function index()
    // {
    //     // Menampilkan semua artikel
    //     $articles = Article::with('user')->get()->map(function ($article) {
    //         $article->image_url = $article->image ? asset('storage/' . $article->image) : null;
    //         return $article;
    //     });
    
    //     return response()->json($articles, 200);
    // }
     // Menampilkan semua artikel beserta nama pengguna
     public function index()
     {
         $articles = Article::with('user:id,name')->get(); 
    
         // Menambahkan URL gambar ke setiap artikel
         foreach ($articles as $article) {
             $article->image_url = $article->image ? asset('storage/' . $article->image) : null;
         }
         return response()->json($articles); // Mengirimkan artikel bersama dengan data user
     }
    

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi gambar
        ]);
    
        // Proses upload gambar
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('articles', 'public'); // Simpan ke folder "storage/app/public/articles"
        }
    
        // Simpan artikel
        $article = Article::create([
            'title' => $request->title,
            'content' => $request->content,
            'image' => $imagePath, // Path gambar
            'user_id' => Auth::id(),
        ]);
    
        return response()->json(['message' => 'Article created successfully', 'article' => $article], 201);
    }
    
    // public function show(Article $article)
    // {
    
    //     // Menampilkan artikel berdasarkan ID
    //     $article = Article::with('user')->find($article->id);
    //     $article->image_url = $article->image ? asset('storage/' . $article->image) : null;
    
    //     return response()->json($article, 200);
    // }
    
    public function show(Article $article)
    {
        // Mengambil artikel dengan relasi user
        $article->load('user:id,name');
    
        // Menambahkan URL gambar ke artikel
        $article->image_url = $article->image ? asset('storage/' . $article->image) : null;
    
        // Menambahkan data tambahan seperti jumlah likes dan komentar
        return response()->json([
            'article' => $article,
            'likes_count' => $article->likes()->count(),
            'comments' => $article->comments()->with('user:id,name')->get(), // Komentar dengan informasi user
        ]);
    }
    
    public function update(Request $request, Article $article)
    {
        // Validasi input
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validasi gambar
        ]);
    
        // Proses upload gambar
        $imagePath = $article->image; // Gunakan gambar lama jika tidak ada gambar baru
        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($article->image && Storage::disk('public')->exists($article->image)) {
                Storage::disk('public')->delete($article->image);
            }
            // Simpan gambar baru
            $imagePath = $request->file('image')->store('articles', 'public');
        }
    
        // Update artikel
        $article->update([
            'title' => $request->title,
            'content' => $request->content,
            'image' => $imagePath, // Path gambar
        ]);
    
        return response()->json([
            'message' => 'Article updated successfully',
            'article' => $article
        ], 200);
    }
    

    public function destroy(Article $article)
    {
        $article = Article::find($article->id);
        $article->delete();
    
        return response()->json(['message' => 'Article deleted successfully'], 200);
    }


    public function like(Article $article)
    {
        $article->likes()->create([
            'user_id' => Auth::id(),
        ]);
    
        return response()->json(['message' => 'Article liked successfully'], 200);
    }

    public function unlike(Request $request, $articleId) {
        $user = $request->user();
        $article = Article::findOrFail($articleId);
    
        // Hapus "like" dari database
        $article->likes()->where('user_id', $user->id)->delete();
    
        // Kurangi jumlah likes
        $article->decrement('likes_count');
    
        return response()->json([
            'status' => true,
            'message' => 'Unlike successful',
            'likes' => $article->likes_count,
        ]);
    }
    
    public function comment(Article $article)
    {
        $article->comments()->create([
            'content' => request('content'),
            'user_id' => Auth::id(),
        ]);
    
        return response()->json(['message' => 'Article commented successfully'], 200);
    }

    public function getComment(Article $article)
    {
        $comments = $article->comments()->paginate(10);
    
        return response()->json(['comments' => $comments], 200);
    }

   // Laravel Controller
public function trackRead(Request $request, $articleId)
{
    $user = $request->user();

    // Pastikan hanya pembaca dengan role 'reader'
    if ($user->role !== 'reader') {
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
    }

    $article = Article::findOrFail($articleId);

    // Cegah pembacaan duplikat (jika diperlukan)
    $alreadyRead = DB::table('article_reads')
        ->where('article_id', $articleId)
        ->where('user_id', $user->id)
        ->exists();

    if ($alreadyRead) {
        return response()->json(['status' => false, 'message' => 'Article already read.'], 400);
    }

    DB::table('article_reads')->insert([
        'article_id' => $articleId,
        'user_id' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Tambahkan balance ke penulis artikel
    $writer = $article->writer; // Asumsi relasi sudah ada
    $writer->increment('balance', 10); // Tambah 10 kredit atau sesuai aturan

    return response()->json(['status' => true, 'message' => 'Read tracked successfully.']);
}

}
