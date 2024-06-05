<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMedia extends Model
{
    use HasFactory;
    protected $table = "post_media";
    protected $fillable = [
        "uuid",
        "post_id",
        "file_type",
        "file",
        "position"
    ];
    
    public function post() : BelongsTo {
        return $this->belongsTo(Post::class,'post_id');
    }
}
