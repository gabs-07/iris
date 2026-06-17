<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityReport;
use App\Notifications\CommunityInteraction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        $query = CommunityPost::with(['user', 'comments.user', 'likes'])
            ->withCount(['comments', 'likes'])
            ->where('status', 'published')
            ->latest();

        if ($request->filled('category')) {
            $query->where('category', (string) $request->string('category')); 
        }

        $posts = $query->paginate(10)->withQueryString();
        return view('comunidad.comunidad', compact('posts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'anonymous' => ['nullable', 'boolean'],
        ]);

        CommunityPost::create([
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'content' => $data['content'],
            'category' => $data['category'] ?? 'general',
            'anonymous' => $request->boolean('anonymous'),
            'status' => 'published',
        ]);

        return back()->with('success', 'Publicación creada.');
    }

    public function comment(Request $request, CommunityPost $post): RedirectResponse
    {
        abort_unless($post->status === 'published', 404);
        $data = $request->validate([
            'content' => ['required', 'string', 'max:1500'],
            'anonymous' => ['nullable', 'boolean'],
        ]);

        $comment = CommunityComment::create([
            'community_post_id' => $post->id,
            'user_id' => Auth::id(),
            'content' => $data['content'],
            'anonymous' => $request->boolean('anonymous'),
        ]);

        if ($post->user_id !== Auth::id()) {
            \App\Support\SafeNotifier::notify($post->user, new CommunityInteraction($post, 'Alguien comentó tu publicación: '.$post->title));
        }

        return back()->with('success', 'Comentario publicado.');
    }

    public function update(Request $request, CommunityPost $post): RedirectResponse
    {
        abort_unless($post->user_id === Auth::id() || Auth::user()->isAdmin(), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'anonymous' => ['nullable', 'boolean'],
        ]);
        $post->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'category' => $data['category'] ?? 'general',
            'anonymous' => $request->boolean('anonymous'),
        ]);
        return back()->with('success', 'Publicación actualizada.');
    }

    public function destroy(CommunityPost $post): RedirectResponse
    {
        abort_unless($post->user_id === Auth::id() || Auth::user()->isAdmin(), 403);
        $post->delete();
        return back()->with('success', 'Publicación eliminada.');
    }

    public function destroyComment(CommunityComment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === Auth::id() || Auth::user()->isAdmin(), 403);
        $comment->delete();
        return back()->with('success', 'Comentario eliminado.');
    }

    public function like(CommunityPost $post): RedirectResponse
    {
        abort_unless($post->status === 'published', 404);
        $existing = $post->likes()->where('user_id', Auth::id())->first();
        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Reacción retirada.');
        }
        $post->likes()->create(['user_id' => Auth::id()]);
        if ($post->user_id !== Auth::id()) {
            \App\Support\SafeNotifier::notify($post->user, new CommunityInteraction($post, 'Alguien reaccionó a tu publicación: '.$post->title));
        }
        return back()->with('success', 'Reacción registrada.');
    }

    public function report(Request $request, CommunityPost $post): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);
        CommunityReport::create([
            'community_post_id' => $post->id,
            'user_id' => Auth::id(),
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
            'status' => 'pending',
        ]);
        return back()->with('success', 'Reporte enviado a administración.');
    }

    public function myPosts(): View
    {
        $posts = CommunityPost::with(['comments.user', 'likes'])
            ->withCount(['comments', 'likes'])
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);
        return view('comunidad.mis-publicaciones', compact('posts'));
    }
}
