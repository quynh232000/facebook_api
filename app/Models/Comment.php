<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $table = "comments";
    protected $fillable = [
        "post_id",
        "user_id",
        "comment",
        "status"
    ];
    use HasFactory;
    public function user() : BelongsTo {
        return $this->belongsTo(User::class,'user_id');
    }
    public function post() : BelongsTo {
        return $this->belongsTo(Post::class,'post_id');
    }
}
