<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use Illuminate\Http\Request;

class ChatSessionController extends Controller
{
    public function close(Request $request, $id)
    {
        $session = ChatSession::findOrFail($id);

        $session->status = 'closed';
        $session->save();

        return response()->json([
            'message' => 'Session berhasil ditutup',
            'status'  => 'closed'
        ]);
    }
}
