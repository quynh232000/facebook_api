<?php

namespace App\Http\Controllers\API;

use App\Events\NewNotification;
use App\Http\Controllers\Controller;
use App\Models\Friend;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Response;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Str;
use Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use DB;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return Response::json(false, 'Vui lòng nhập đầy đủ thông tin!', $validator->errors());
        }
        $user = User::where('email', $request->email)->get();
        if (count($user) == 0) {
            return Response::json(false, 'Email không tồn tại trên hệ thống!', $validator->errors());
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return Response::json(false, 'Mật khẩu không đúng!', $validator->errors());
        }
        $user = auth()->user();
        if (($user->email_verified_at == "") || ($user->email_verified_at == null)) {
            return Response::json(false, 'Vui lòng xác thực email của bạn!', "", "", ['redirect' => "/verify_email_notification"]);
        }

        return Response::json(true, "Losgin successfully authenticated", ['user' => auth()->user()], $this->createNewToken($token));
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'birthday' => 'required',
            'gender' => 'required|string'
        ]);
        // 'name' => 'required|string|between:2,100',
        if ($validator->fails()) {
            return Response::json(false, "Empty fileds or invalid data!", $validator->errors());
        }

        $user = User::create(
            array_merge(
                $validator->validated(),
                ['uuid' => Str::uuid(), 'password' => Hash::make($request->password)]
            )
        );
        // send mail verify
        $random = Str::random(40);
        // $domain = env("FRONTEND_URL");
        $domain = "http://localhost:5173/";
        $url = $domain . "verify_email/" . $random;

        $data['url'] = $url;
        $data['email'] = $request->email;
        $data['title'] = "Email verification";
        $data['body'] = "Please click here to verify your email.";
        Mail::send("SendVerifyEmail", ['data' => $data], function ($message) use ($data) {
            $message->to($data['email'])->subject($data['title']);
        });

        $user->remember_token = $random;
        $user->save();


        return Response::json(true, 'User successfully registered', $user, ['redirect' => '/verify_email_notification']);
    }
    public function resendEmail(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
        ]);
        if ($validator->fails()) {
            return Response::json(false, 'Vui lòng nhập đầy đủu thông tin!', $validator->errors());
        }
        $user = User::where('email', $request->email)->get();
        if (count($user) == 0) {
            return Response::json(false, 'Email không tồn tại trên hệ thống!', $validator->errors());
        }
        $random = Str::random(40);
        // $domain = env("FRONTEND_URL");
        $domain = "http://localhost:5173/";
        $url = $domain . "verify_email/" . $random;
        $data['url'] = $url;
        $data['email'] = $request->email;
        $data['title'] = "Email verification";
        $data['body'] = "Please click here to verify your email.";
        Mail::send("SendVerifyEmail", ['data' => $data], function ($message) use ($data) {
            $message->to($data['email'])->subject($data['title']);
        });
        $user = User::where('email', $request->email)->first();
        $user->remember_token = $random;
        $user->save();
        return Response::json(true, 'User successfully registered', $user, "", ['redirect' => '/verify_email_notification']);
    }

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string'
        ]);
        if ($validator->fails()) {
            return Response::json(false, 'Vui lòng nhập đầy đủu thông tin!', $validator->errors());
        }
        $user = User::where('remember_token', $request->token)->get();
        // return Response::json(false,$user);
        if (count($user) == 0) {
            return Response::json(false, 'Token đã hết hạn hoặc không tồn tại trên hệ thống!');
        }
        $user = User::where('remember_token', $request->token)->first();
        $user->email_verified_at = Carbon::now();
        $user->remember_token = null;
        $user->save();
        return Response::json(true, 'Email đã xác thực thành công!', $user, ['redirect' => '/login']);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['status' => true, 'message' => 'User successfully signed out']);
    }


    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }


    public function me()
    {
        if (auth()->user()) {
            return Response::json(true, "Get user information successfully", ['user' => auth()->user()]);
        }
        return Response::json(false, "Get user information failed, unauthenticated!");
    }

    protected function createNewToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ];

    }

    public function changePassWord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $userId = auth()->user()->id;

        $user = User::where('id', $userId)->update(
            ['password' => bcrypt($request->new_password)]
        );

        return response()->json([
            'message' => 'User successfully changed password',
            'user' => $user,
        ], 201);
    }
    // get user infomation
    public function getUserInfo($uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($uuid == "") {
            return Response::json(false, 'Vui lòng thêm uuid!', "", "", "/error");
        }
        $user = User::where('uuid', $uuid)->first();
        if (!$user) {
            return Response::json(false, 'User không tồn tại trên hệ thống!', "", "", "/error");
        }
        $user->is_friend = $user->is_friend();
        $user->mutual_friends = $user->mutual_friends();
        $user->friends_count = $user->friends_count();
        return Response::json(true, 'Get user infomation successfully', $user);
    }
    public function getUserSuggestion()
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");

        $userId = $user->id;
        $friendIds1 = Friend::where('user_id', $userId)->pluck('friend_id');
        $friendIds2 = Friend::where('friend_id', $userId)->pluck('user_id');
        $friendIds = $friendIds1->merge($friendIds2)->unique();
        $friendIds[] = $userId;
        $users = User::whereNotIn('id', $friendIds)->latest()->get()->map(function ($user) {
            $user->friends_count = $user->friends_count();
            $user->mutual_friends = $user->mutual_friends();
            return $user;
        });

        return Response::json(true, 'Get user infomation successfully', $users);
    }
    public function getRequestFriend()
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");

        $userId = $user->id;
        $request_ids = Friend::where('friend_id', $userId)->whereNull('accepted_at')->pluck("user_id");
        $requestFriends = User::whereIn('id', $request_ids)->get()->map(function ($user) {
            $user->friends_count = $user->friends_count();
            $user->mutual_friends = $user->mutual_friends();
            return $user;
        });

        return Response::json(true, 'Get request friend  successfully', $requestFriends);
    }

    public function addFriend(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        Friend::create([
            'user_id' => $user->id,
            'friend_id' => $request->friend_id,
            'status' => 'pedding'
        ]);
        Notification::create([
            'type' => "friend_request",
            'from_user_id' => $user->id,
            'user_id' => $request->friend_id,
            'message' =>  "đã gửi cho bạn một lời mời kết bạn!",
            'url' => "friends?type=requests",
        ]);
        event(new NewNotification($request->friend_id, "$user->first_name $user->last_name đã gửi cho bạn 1 lời mời kết bạn"));
        return Response::json(true, 'Gửi lời mời kết bạn thành công!');

    }
    public function cancelSendRequest(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $userId = $user->id;

        $friend = Friend::where(['user_id' => $userId, 'friend_id' => $request->friend_id])->first();
        $friend->delete();
        return Response::json(true, 'Hủy gửi lời mời kết bạn thành công!');
    }
    public function cancelRequest(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $userId = $user->id;

        $friend = Friend::where(['user_id' => $request->friend_id, 'friend_id' => $userId])->first();
        $friend->delete();
        return Response::json(true, 'Hủy yêu cầu kết bạn thành công!');
    }
    public function acceptFriend(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'friend_id' => 'required'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        $userId = $user->id;
        $friend = Friend::where(['friend_id' => $userId, 'user_id' => $request->friend_id])->first();
        $friend->accepted_at = Carbon::now();
        $friend->status = 'accepted';
        $friend->save();
        Notification::create([
            'type' => "friend_request_accepted",
            'from_user_id' => $userId,
            'message' => "đã chấp nhận lời mời kết bạn của bạn.",
            'user_id' => $request->friend_id,
            'url' => "user/".$user->uuid
        ]);
        event(new NewNotification( $request->friend_id, "$user->first_name $user->last_name đã chấp nhận lời mời kết bạn của bạn"));
        return Response::json(true, "Xác nhận trở thành bạn bè của nhau thành công!");

    }
    public function getMediaImageUser($user_uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($user_uuid == "") {
            return Response::json(false, "Vui lòng nhập uuid!");
        }
        $profile = User::where('uuid', $user_uuid)->first();
        $post_ids = Post::where(['is_page_post' => 0, 'is_group_post' => 0, 'user_id' => $profile->id])->pluck("id");
        $media = PostMedia::whereIn('post_id', $post_ids)->where('file_type', 'image')->orderBy('created_at', 'desc')->get();
        return Response::json(true, "Get media image successfully", $media);
    }
    public function getMediaUser($user_uuid, $type = "image")
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($user_uuid == "") {
            return Response::json(false, "Vui lòng nhập uuid!");
        }
        $profile = User::where('uuid', $user_uuid)->first();
        $post_ids = Post::where(['is_page_post' => 0, 'is_group_post' => 0, 'user_id' => $profile->id])->pluck("id");
        $media = PostMedia::whereIn('post_id', $post_ids)->where('file_type', $type)->get();
        return Response::json(true, "Get media image successfully", $media);
    }

    public function getListFriend($user_uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($user_uuid == "") {
            return Response::json(false, "Vui lòng nhập uuid!");
        }
        $profile = User::where('uuid', $user_uuid)->first();

        $friend1_ids = Friend::where(['user_id' => $profile->id, 'status' => 'accepted'])->pluck('friend_id');
        $friend2_ids = Friend::where(['friend_id' => $profile->id, 'status' => 'accepted'])->pluck('user_id');
        $friend_ids = $friend1_ids->merge($friend2_ids)->unique();
        $friends = User::whereIn('id', $friend_ids)->limit(20)->get();

        return Response::json(true, "Get my friend successfully", $friends);
    }
    public function updateThumbnailUser(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'thumbnail' => 'required'
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }

        $user = User::find($user->id);


        $url = Cloudinary::upload($request->thumbnail->getRealPath())->getSecurePath();
        $user->thumbnail = $url;
        $user->save();
        return Response::json(true, "Thay đổi ảnh bìa thành công!", $user);
    }
    public function changeAvatarUser(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'avatar' => 'required'
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }
        $user = User::find($user->id);
        $url = Cloudinary::upload($request->avatar->getRealPath())->getSecurePath();
        $user->avatar = $url;
        $user->save();
        $content = $request->content ?? "Đã cập nhật ảnh đại diện mới";
        $newPost = [
            'uuid' => Str::uuid(),
            'user_id' => $user->id,
            'status' => 'published',
            'content' => $content,
            'type' => 'change_avatar'
        ];
        $post = Post::create($newPost);
        PostMedia::create([
            'uuid' => Str::uuid(),
            'post_id' => $post->id,
            'file_type' => 'image',
            'file' => json_encode([$url]),
            'position' => 'general'
        ]);
        return Response::json(true, "Thay đổi ảnh đại diện thành công!", $user);
    }

    public function updateDescriptionUser(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'description' => 'required'
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }
        $user = User::find($user->id);
        $user->description = $request->description;
        $user->save();
        return Response::json(true, "Thay đổi mô tả thành công!", $user);
    }
    public function setAvatarUser($media_uuid, $index)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($media_uuid == "" || $index == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        $media = PostMedia::where('uuid', $media_uuid)->first();
        if ($media == null) {
            return Response::json(false, "Post media không tồn tại");
        }
        $list = json_decode($media->file);
        if ($list[$index]) {
            $user = User::find($user->id);
            $user->avatar = $list[$index];
            $user->save();


            $content = "Đã cập nhật ảnh đại diện mới";
            $newPost = [
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'status' => 'published',
                'content' => $content,
                'type' => 'change_avatar'
            ];
            $post = Post::create($newPost);
            PostMedia::create([
                'uuid' => Str::uuid(),
                'post_id' => $post->id,
                'file_type' => 'image',
                'file' => json_encode([$list[$index]]),
                'position' => 'general'
            ]);

            return Response::json(true, "Thay đổi ảnh đại diện thành công!", $user);
        } else {
            return Response::json(false, "Hình ảnh không tồn tại!");
        }

    }
    public function setThumbnailUser($media_uuid, $index)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($media_uuid == "" || $index == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        $media = PostMedia::where('uuid', $media_uuid)->first();
        if ($media == null) {
            return Response::json(false, "Post media không tồn tại");
        }
        $list = json_decode($media->file);
        if ($list[$index]) {
            $user = User::find($user->id);
            $user->thumbnail = $list[$index];
            $user->save();
            return Response::json(true, "Thay đổi ảnh bìa thành công!", $user);
        } else {
            return Response::json(false, "Hình ảnh không tồn tại!");
        }

    }
    public function deleteMedia($media_uuid, $index)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($media_uuid == "" || $index == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        $media = PostMedia::where('uuid', $media_uuid)->first();
        if ($media == null) {
            return Response::json(false, "Post media không tồn tại");
        }
        $list = json_decode($media->file);
        if (count($list) == 1) {
            $media->delete();
            return Response::json(true, "Xóa media thành công!");
        } else {
            array_splice($list,$index,1);
            // return $list;
            $media->file = json_encode($list);
            $media->save();
            return Response::json(true, "Xóa media thành công!", $media);
        }

    }

}
