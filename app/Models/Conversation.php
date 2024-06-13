<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;
    protected $table = "conversations";
    protected $fillable = [
        'user1_id',
        'user2_id'
    ];
    public function user(){
        $user_id = auth()->id();
        if($user_id == $this->user1_id){
            $friend = User::find($this->user2_id);
        }else{
            $friend = User::find($this->user1_id);
        }
        return $friend;
    }
    public function recent_message(){
        $user_id = auth()->id();
        $message = Message::where(['conversation_id'=>$this->id])->latest()->first();
        // if($user_id == $this->user1_id){
        // }else{
        //     $message = Message::where(['conversation_id'=>$this->id,'user_id'=>$this->user1_id])->first();
        // }
        return $message;
    }
}
