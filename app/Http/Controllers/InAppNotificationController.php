<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InAppNotificationController extends Controller
{
    public function markRead(Request $request, int $id): RedirectResponse
    {
        $notifikasi = DB::table('notifikasi_aplikasi')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first(['id', 'dibaca_at']);
        abort_unless($notifikasi, 404);

        if (! $notifikasi->dibaca_at) {
            DB::table('notifikasi_aplikasi')
                ->where('id', $id)
                ->whereNull('dibaca_at')
                ->update(['dibaca_at' => now(), 'updated_at' => now()]);
        }

        return back()->with('success', 'Pemberitahuan ditandai sudah dibaca.');
    }
}
