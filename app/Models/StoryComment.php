<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoryComment extends Model
{
    use HasFactory;
    protected $table = "story_comments";
    protected $fillable = [
        "user_id",
        "story_id",
        "comment"
    ];
}
