<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;
    protected $table = "groups";
    protected $fillable = [
        "uuid",
        "user_id",
        "avatar",
        "thumbnail",
        "description",
        "name",
        "location",
        "type",
        "is_private",
        "members",
    ];
    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'group_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function is_join()
    {
        $authUserId = auth()->id();
        if ($this->user_id == $authUserId) {
            return true;
        } else {
            return GroupMember::where(['user_id' => $authUserId, 'group_id' => $this->id])->exists();
        }
    }
    public function activate_recent() {
        $recentPost = Post::where('group_id', $this->id)
                          ->orderBy('created_at', 'DESC')
                          ->first();
    
        return $recentPost ? $recentPost->created_at : $this->created_at;
    }
}
