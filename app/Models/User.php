<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $table = "users";
    protected $fillable = [
        "uuid",
        'first_name',
        'last_name',
        'birthday',
        'email',
        'email_verified_at',
        "avatar",
        "thumbnail",
        "gender",
        "phone_number",
        "relationship",
        "location",
        "address",
        "description",
        "is_private",
        "is_banned",
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function is_friend()
    {
        $auth_id = auth()->id();
        $status = "new";
        $sendRequest = Friend::where(['friend_id' => $this->id,'user_id'=>$auth_id])->first();
        $requestFriend = Friend::where(['user_id' => $this->id,'friend_id'=>$auth_id])->first();
        if ($sendRequest) {
            $status = $sendRequest->status == 'accepted' ? "accepted" : 'you_send';
        }
        if ($requestFriend) {
            $status = $requestFriend->status == 'accepted' ? "accepted" : 'friend_send';
        }
        return $status;
    }
    public function friends_count()
    {
        $friendsCount = Friend::where(function ($query) {
            $query->where('user_id', $this->id)
                ->orWhere('friend_id', $this->id);
        })->where('status', 'accepted')->count();

        return $friendsCount;
    }
    public function mutual_friends()
    {
        $authUserId = auth()->id();
        $authUserFriends = Friend::where(function ($query) use ($authUserId) {
            $query->where('user_id', $authUserId)
                ->orWhere('friend_id', $authUserId);
        })->where('status', 'accepted')
            ->get()
            ->map(function ($friend) use ($authUserId) {
                return $friend->user_id == $authUserId ? $friend->friend_id : $friend->user_id;
            })
            ->toArray();

        // Get the friend's friends
        $userFriends = Friend::where(function ($query) {
            $query->where('user_id', $this->id)
                ->orWhere('friend_id', $this->id);
        })->where('status', 'accepted')
            ->get()
            ->map(function ($friend) {
                return $friend->user_id == $this->id ? $friend->friend_id : $friend->user_id;
            })
            ->toArray();

        // Find mutual friends
        $mutualFriends = array_intersect($authUserFriends, $userFriends);

        return (count($mutualFriends));
    }
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
