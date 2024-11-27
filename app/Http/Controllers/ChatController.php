<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function show(Chat $chat)
    {
        return view('chats.show', compact('chat'));
    }

    public function edit(Chat $chat)
    {
        return view('chats.edit', compact('chat'));
    }
}
