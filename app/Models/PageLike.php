<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageLike extends Model
{
    use HasFactory;
    protected $table = "page_likes";
    protected $fillable = [
        "user_id",
        "page_id"
    ];
}
