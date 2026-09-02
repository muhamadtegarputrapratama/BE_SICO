<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotifikasiResource;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $notifikasi = $request->user()
            ->notifikasi()
            ->latest('created_at')
            ->paginate($request->integer('per_page', 15));

        return NotifikasiResource::collection($notifikasi);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()
            ->notifikasi()
            ->where('dibaca', false)
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    public function markRead(Request $request, int $id)
    {
        $notifikasi = $request->user()
            ->notifikasi()
            ->findOrFail($id);

        $notifikasi->update(['dibaca' => true]);

        return new NotifikasiResource($notifikasi);
    }

    public function markAllRead(Request $request)
    {
        $request->user()
            ->notifikasi()
            ->where('dibaca', false)
            ->update(['dibaca' => true]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }
}