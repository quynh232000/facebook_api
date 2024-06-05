<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\BelongsToManyRelationship;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;
    protected $table = "posts";
    protected $fillable = [
        "uuid",
        "user_id",
        "content",
        "is_public",
        'type',
        "status",
        "is_page_post",
        "page_id",
        "is_group_post",
        "group_id"
    ];
    public function comments() : HasMany {
        return $this->hasMany(Comment::class)->orderBy('updated_at','DESC');
    }
    public function user() : BelongsTo {
        return $this->belongsTo(User::class,'user_id');
    }
    public function group() : BelongsTo {
        return $this->belongsTo(Group::class,'group_id');
    }
    public function page() : BelongsTo {
        return $this->belongsTo(Page::class,'page_id');
    }
    public function post_media() : HasMany {
        return $this->hasMany(PostMedia::class);
    }
    public function likes()
    {
        return $this->hasMany(Like::class);
    }
    public function isLikePost()
    {
        return Like::where(['user_id'=>auth()->id(),'post_id'=>$this->id])->exists();
    }
    public function is_saved(){
        $user = auth()->user();
        return SavePost::where(['post_id'=>$this->id,'user_id'=>$user->id])->exists();
    }
}
