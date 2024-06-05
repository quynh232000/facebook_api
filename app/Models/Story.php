<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Story extends Model
{
    use HasFactory;
    protected $table = "stories";
    protected $fillable = [
        "user_id",
        "story",
        "content",
        "type",
        "status",
        "likes",
        "comments"
    ];
    public function user():BelongsTo{
        return $this->belongsTo(User::class,'user_id');
    }
}
