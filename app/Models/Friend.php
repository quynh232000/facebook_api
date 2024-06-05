<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friend extends Model
{
    use HasFactory;
    protected $table = "friends";
    protected $fillable = [
        "user_id",
        "friend_id",
        "friend_id",
        "accepted_at",
        'status'
    ];
    // public function user() : BelongsTo {
    //     return $this->belongsTo(User::class,'user_id');
    // }
}
