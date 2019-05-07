<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Discussion;
use Illuminate\Http\Request;
use TimeHunter\LaravelGoogleReCaptchaV3\Validations\GoogleReCaptchaV3ValidationRule;

class DiscussionPostController extends Controller
{
    public function store(Discussion $discussion, $slug)
    {
        if ($discussion->locked || auth()->user()->cannot('create discussions')) {
            return abort(403);
        }

        request()->validate([
            'reply' => 'required|min:10',
            'g-recaptcha-response' => [new GoogleReCaptchaV3ValidationRule('reply_to_discussion_action')]
        ]);

        $post = $discussion->posts()->create([
            'body' => request()->input('reply'),
            'user_id' => auth()->user()->id,
        ]);

        return redirect(route('discussions.show', [
            $discussion->id,
            $discussion->slug
        ]));
    }

    public function edit(Discussion $discussion, $slug, Post $post){
        if (($post->user->id != auth()->user()->id && auth()->user()->cannot('bypass discussions guard')) || $discussion->private) {
            return abort(403);
        }

        $more_options = ($discussion->posts()->first() == $post);
        $categories = Category::ordered()->filtered()->pluck('name', 'id');

        return view('discussion.post.edit', compact('categories', 'discussion', 'post', 'more_options'));
    }

    public function update(Discussion $discussion, $slug, Post $post)
    {
        if (($post->user->id != auth()->user()->id && auth()->user()->cannot('bypass discussions guard')) || $discussion->private) {
            return abort(403);
        }

        request()->validate([
            'body' => 'required|min:10',
        ]);

        $post->body = request()->input('body');
        $post->save();

        return redirect(route('discussions.show', [
            $discussion->id,
            $discussion->slug
        ]));
    }

    public function delete(Discussion $discussion, $slug, Post $post){
        if (($post->user->id != auth()->user()->id && auth()->user()->cannot('bypass discussions guard')) || $discussion->private) {
            return abort(403);
        }

        return view('discussion.post.delete', compact('discussion', 'post'));
    }

    public function destroy(Discussion $discussion, $slug, Post $post)
    {
        if (($post->user->id != auth()->user()->id && auth()->user()->cannot('bypass discussions guard')) || $discussion->private) {
            return abort(403);
        }

        $post->deleted = true;
        $post->save();

        return redirect(route('discussions.show', [
            $discussion->id,
            $discussion->slug
        ]));
    }

}
