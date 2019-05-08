<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        self::created(function ($post) {
            $discussion = $post->discussion;
            ++$discussion->replies;
            $discussion->last_reply_at = now();
            $discussion->save();

            $post->discussion->has_read()->sync([]);
            $post->discussion->notify_subscibers($post);

            return true;
        });
    }

    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPresentedBodyAttribute()
    {
        $body = $this->presented_light_body;

        // Rendu des citations :
        $preg_result = [];
        preg_match_all('/(?:#p:)(?:\d+)(?:<br\/>|<br>|[\s]|$|\z)/', $body, $preg_result);

        foreach($preg_result[0] as $tag) {
            $tag = trim($tag);
            $clear_tag = trim(str_replace(['#p:'], '', $tag));

            $post = Post::find($clear_tag);
            if (!$post || $post->discussion->private) continue;

            $body = str_replace(
                $tag,
                view('discussion.post._show_as_quote', compact('post'))->render(),
                $body
            );
        }

        return $body;
    }

    public function getPresentedLightBodyAttribute()
    {
        $body = $this->body;

        // Rendu de [mock][/mock]
        $preg_result = [];
        preg_match_all('/(?:\[mock\])(.*)(?:\[\/mock\])/', $body, $preg_result);

        foreach($preg_result[0] as $k => $tag){
            $str = str_split(strtolower($preg_result[1][$k]));
            foreach ($str as &$char) {
                if (rand(0, 1)) $char = strtoupper($char);
            }

            $body = str_replace(
                $tag,
                implode('', $str),
                $body
            );
        }

        // Rendu du BBCode :
        $bbcode = new \ChrisKonnertz\BBCode\BBCode();

        $bbcode->addTag('glitch', function ($tag, &$html, $openingTag) {
            if ($tag->opening) {
                return '<span class="baffle">';
            } else {
                return '</span>';
            }
        });

        $body = $bbcode->render($body);

        // Rendu des mentions :
        $preg_result = [];
        preg_match_all('/(?:@|#u:)(?:\w+)(?:<br\/>|<br>|[\s]|$|\z)/', $body, $preg_result);

        foreach($preg_result[0] as $tag) {
            $tag = trim($tag);
            $clear_tag = trim(str_replace(['@', '#u:'], '', $tag));

            $user = User::where('name', $clear_tag)->first();
            if (!$user) continue;

            $body = str_replace(
                $tag,
                '<a href="' . $user->link . '" class="badge badge-primary">@' . $clear_tag . '</a>' . ' ',
                $body
            );
        }

        return $body;
    }
}
