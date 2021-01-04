<?php

namespace App\Http\Controllers;

use App\Helpers\SucresHelper;
use App\Helpers\SucresParser;
use App\Models\Board;
use App\Models\Notification as NotificationModel;
use App\Models\Post;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DiscussionController extends Controller
{
    public function create()
    {
        if (user()->restricted) {
            return redirect()->route('home')->with('error', 'Tout doux bijou ! Tu dois vérifier ton adresse email avant créer un topic !');
        }

        if (user()->cannot('create threads')) {
            return abort(403);
        }

        $boards = Board::postables()->pluck('name', 'id');

        return view('thread.create', compact('boards'));
    }

    public function preview()
    {
        if (user()->cannot('create threads')) {
            return abort(403);
        }

        request()->validate([
            'body' => 'required|min:3|max:10000',
        ]);

        SucresHelper::throttleOrFail(__METHOD__, 15, 1);

        $post = new Post();
        $post->user = user();
        $post->body = request()->body;

        return response([
            'render' => (new SucresParser($post))->render(),
        ]);
    }

    public function store()
    {
        if (user()->restricted) {
            return redirect()->route('home')->with('error', 'Tout doux bijou ! Tu dois vérifier ton adresse email avant créer un topic !');
        }

        if (user()->cannot('create threads')) {
            return abort(403);
        }

        $boards = Board::postables()->pluck('id');

        request()->validate([
            'title' => ['required', 'min:3', 'max:255'],
            'body' => ['required', 'min:3', 'max:10000'],
            'board' => ['required', 'exists:boards,id', Rule::in($boards)],
        ]);

        SucresHelper::throttleOrFail(__METHOD__, 5, 10);

        $thread = Thread::create([
            'title' => request()->title,
            'user_id' => user()->id,
            'board_id' => request()->board,
        ]);

        $post = $thread->posts()->create([
            'body' => request()->body,
            'user_id' => user()->id,
        ]);

        if (user()->getSetting('notifications.subscribe_on_create', true)) {
            $thread->subscribed()->syncWithoutDetaching(user()->id);
        }

        return redirect($post->link);
    }

    public function index(Board $board = null, $slug = null)
    {
        $boards = Board::viewables();

        if ($board && ! in_array($board->id, $boards->pluck('id')->toArray())) {
            return abort(403);
        }

        $threads = Thread::query()
            ->whereIn('board_id', $boards->pluck('id'))
            ->with('board')
            ->with('latest_reply')
            ->with('latest_reply.user')
            ->with('user');

        if ($board) {
            $threads = $threads
                ->where('board_id', $board->id);
        } else {
            $threads = $threads
                ->where('board_id', '!=', Board::CATEGORY_SHITPOST);
        }

        if (request()->input('page', 1) == 1) {
            $sticky_threads = clone $threads;
            $sticky_threads = $sticky_threads->sticky()->get();
        } else {
            $sticky_threads = collect([]);
        }

        $threads = $threads->ordered()->paginate(20);

        if (user()) {
            $user_has_read = DB::table('has_read_threads_users')
                ->select('thread_id')
                ->where('user_id', user()->id)
                ->whereIn('thread_id', array_merge($sticky_threads->pluck('id')->toArray(), $threads->pluck('id')->toArray()))
                ->pluck('thread_id')
                ->toArray();
        } else {
            $user_has_read = [];
        }

        return view('welcome', compact('boards', 'sticky_threads', 'threads', 'user_has_read'));
    }

    public function subscriptions()
    {
        $boards = Board::viewables();

        $threads = Thread::query()
            ->whereIn('board_id', $boards->pluck('id'))
            ->with('board')
            ->with('latestPost')
            ->with('latestPost.user')
            ->with('user')
            ->whereHas('subscribed', function ($q) {
                return $q->where('user_id', user()->id);
            });

        if (request()->input('page', 1) == 1) {
            $sticky_threads = clone $threads;
            $sticky_threads = $sticky_threads->sticky()->get();
        } else {
            $sticky_threads = collect([]);
        }

        $threads = $threads->ordered()->paginate(20);

        if (user()) {
            $user_has_read = DB::table('has_read_threads_users')
                ->select('thread_id')
                ->where('user_id', user()->id)
                ->whereIn('thread_id', array_merge($sticky_threads->pluck('id')->toArray(), $threads->pluck('id')->toArray()))
                ->pluck('thread_id')
                ->toArray();
        } else {
            $user_has_read = [];
        }

        return view('welcome', compact('boards', 'sticky_threads', 'threads', 'user_has_read'));
    }

    public function show($id, $slug) // Ne pas utiliser thread $thread (pour laisser possible le 410)
    {
        $thread = Thread::query()
            ->findOrFail($id);

        if (null !== $thread->board && ! in_array($thread->board->id, Board::viewables()->pluck('id')->toArray())) {
            return abort(403);
        }

        if ($thread->deleted_at && ! (user() && user()->can('read deleted threads'))) {
            return abort(410);
        }

        if ($thread->private && (auth()->guest() || $thread->members()->where('user_id', user()->id)->count() == 0)) {
            return abort(403);
        }

        // Invalidation des notifications qui font référence à cette thread pour l'utilisateur connecté
        if (auth()->check()) {
            $classes = [
                \App\Notifications\NewPrivateThread::class,
                \App\Notifications\RepliesInThread::class,
                \App\Notifications\ReplyInThread::class,
            ];

            NotificationModel::query()
                ->where('read_at', null)
                ->where('notifiable_id', user()->id)
                ->whereIn('type', $classes)
                ->where('data->thread_id', $thread->id)
                ->each(function ($notification) {
                    $notification->read_at = now();
                    $notification->save();
                });
        }

        if (request()->page == 'last') {
            $post = $thread
                ->hasMany(Post::class)
                ->orderBy('created_at', 'desc')
                ->first();

            return redirect(Thread::link_to_post($post));
        }

        $posts = $thread
            ->posts()
            ->with('user')
            ->with('thread')
            ->paginate(10);

        // Invalidation des notifications qui font référence à ces posts pour l'utilisateur connecté
        if (auth()->check()) {
            $classes = [
                \App\Notifications\MentionnedInPost::class,
                \App\Notifications\QuotedInPost::class,
            ];

            NotificationModel::query()
                ->where('read_at', null)
                ->where('notifiable_id', user()->id)
                ->whereIn('type', $classes)
                ->whereIn('data->post_id', $posts->pluck('id'))
                ->each(function ($notification) {
                    $notification->read_at = now();
                    $notification->save();
                });
        }

        $thread->has_read()->attach(user());

        return view('thread.show', compact('thread', 'posts'));
    }

    public function update(thread $thread, $slug)
    {
        if (($thread->user->id != user()->id && user()->cannot('bypass threads guard')) || $thread->private) {
            return abort(403);
        }

        $boards = Board::postables();

        if (! in_array($thread->board->id, $boards->pluck('id')->toArray())) {
            return abort(403);
        }

        request()->validate([
            'title' => 'required|min:4|max:255',
            'board' => ['required', 'exists:boards,id', Rule::in($boards->pluck('id'))],
        ]);

        SucresHelper::throttleOrFail(__METHOD__, 3, 5);

        $thread->title = request()->title;

        // Do not update board if the post is in #shitpost
        if ($thread->board_id !== \App\Models\Board::CATEGORY_SHITPOST || user()->can('bypass threads guard')) {
            $thread->board_id = request()->board;
        }

        if (user()->can('bypass threads guard')) {
            $thread->sticky = request()->sticky ?? false;
            $thread->locked = request()->locked ?? false;

            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'level' => 'warning',
                    'method' => __METHOD__,
                    'elevated' => true,
                ])
                ->log('threadUpdated');
        }

        $thread->save();

        return redirect(route('threads.show', [
            $thread->id,
            $thread->slug,
        ]));
    }

    public function subscribe(thread $thread, $slug)
    {
        if ($thread->private) {
            return abort(403);
        }

        if (! in_array($thread->board->id, Board::viewables()->pluck('id')->toArray())) {
            return abort(403);
        }

        $thread->subscribed()->syncWithoutDetaching(user()->id);

        return redirect(route('threads.show', [
            $thread->id,
            $thread->slug,
        ]));
    }

    public function unsubscribe(thread $thread, $slug)
    {
        if ($thread->private) {
            return abort(403);
        }

        if (! in_array($thread->board->id, Board::viewables()->pluck('id')->toArray())) {
            return abort(403);
        }

        $thread->subscribed()->detach(user()->id);

        return redirect(route('threads.show', [
            $thread->id,
            $thread->slug,
        ]));
    }
}
