<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalChatMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfessionalChatController extends Controller
{
    private const ALLOWED_TAGS = [
        'interconsulta', 'seguimiento', 'riesgo', 'medicacion', 'agenda', 'derivacion', 'caso-clinico', 'general'
    ];

    public function index(): View
    {
        $messages = ProfessionalChatMessage::with('user.professionalProfile')
            ->latest()
            ->take(80)
            ->get()
            ->reverse()
            ->values();

        $availableTags = self::ALLOWED_TAGS;

        return view('psicologo.chat-profesional', compact('messages', 'availableTags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['string', 'in:'.implode(',', self::ALLOWED_TAGS)],
        ]);

        ProfessionalChatMessage::create([
            'user_id' => Auth::id(),
            'message' => $data['message'],
            'tags' => array_values($data['tags'] ?? ['general']),
        ]);

        return back()->with('success', 'Mensaje enviado al chat profesional.');
    }
}
