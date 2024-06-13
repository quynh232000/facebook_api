<?php

namespace App\Http\Controllers\API;

// use App\Events\Message;
use App\Events\NewNotification;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Response;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    function message(Request $request)
    {
        // $user = User::find("14");
        // event(new Noti($user->id, "hello"));
        // event(new NewNotification($user->id, "okokok"));
        // event(new Message($user));
        // broadcast(new Message($request->username,$request->message));
        // $user = auth()
        // broadcast(new Noti(auth()->id()));

        return response()->json(['status' => 'Message broadcasted successfully'], 200);
    }
    public function getMessage($user_uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($user_uuid == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        $friend = User::where('uuid', $user_uuid)->first();
        if ($friend == null) {
            return Response::json(false, "Uuid không chính xác!");
        }

        // Check if the conversation exists
        $conversation = Conversation::where(function ($query) use ($user, $friend) {
            $query->where('user1_id', $user->id)
                ->where('user2_id', $friend->id);
        })->orWhere(function ($query) use ($user, $friend) {
            $query->where('user1_id', $friend->id)
                ->where('user2_id', $user->id);
        })->first();

        $listMessages = [];

        // Get list of messages if the conversation exists
        if ($conversation) {
            $listMessages = Message::where(['conversation_id' => $conversation->id, 'is_deleted' => 0])
                ->with('user')
                ->limit(10)
                ->orderBy('created_at', 'desc')
                ->get();
            Message::where(function ($query) use ($friend, $conversation) {
                $query->where('conversation_id', $conversation->id)
                    ->where('user_id', $friend->id);
            })->update(['is_read' => 1]);
        } else {
            // Create a new conversation if none exists
            $conversation = Conversation::create([
                'user1_id' => $friend->id,
                'user2_id' => $user->id
            ]);
        }

        return Response::json(true, "Lấy tin nhắn thành công!", ['messages' => ($listMessages), 'conversation' => $conversation]);
    }
    public function sendMessage(Request $request)
    {

        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = validator::make($request->all(), [
            'conversation_id' => 'required',
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }
        // if (!$request->content && !$request->medias) {
        //     return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        // }
        $conversation = Conversation::find($request->conversation_id);
        if ($conversation == null) {
            return Response::json(false, "Đoạn chat không tồn tại!");
        }
        $message = [
            'conversation_id' => $request->conversation_id,
            'user_id' => $user->id
        ];
        if ($request->content) {
            $message['content'] = $request->content;
        }
        if ($request->medias) {
            $files = [];
            // return($request->file('medias'));
            try {
                foreach ($request->medias as $media) {
                    $mimeType = $media->getClientMimeType();
                    if (str_contains($mimeType, 'image')) {
                        $files[] = Cloudinary::upload($media->getRealPath())->getSecurePath();
                    } else {
                        $files[] = Cloudinary::uploadVideo($media->getRealPath())->getSecurePath();
                    }
                }
                $message['media'] = json_encode($files);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        $message = Message::create($message);
        $message->user = $user;
        event(new \App\Events\Message($conversation, $message));
        return Response::json(true, "Gửi tin nhắn thành công!", $message);
    }
    public function deleteMessage($message_id)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($message_id == "") {
            return Response::json(false, "Vui lòng nhập message_id!");
        }
        $message = Message::find($message_id);
        if ($message == null) {
            return Response::json(false, "Tin nhắn không tồn tại!");
        }
        if ($message->user_id != $user->id) {
            return Response::json(false, "Bạn không có quyền xóa tin nhắn này!");
        }
        $message->is_deleted = 1;
        $message->save();
        return Response::json(true, "Xóa tin nhắn thành công!");
    }
    public function getConversations()
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $conversations = Conversation::where(function ($query) use ($user) {
            $query->where('user1_id', $user->id)
                ->orWhere('user2_id', $user->id);
        })->limit(10)->get()->map(function ($conversation) {
            $conversation->user = $conversation->user();
            $conversation->recent_message = $conversation->recent_message();
            return $conversation;
        });
        return Response::json(true, "Lấy danh sách đoạn chat thành công!", $conversations);
    }
}
