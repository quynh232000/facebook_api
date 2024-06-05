<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Response;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Str;

class GroupController extends Controller
{
    public function createGroup(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'is_private' => 'required'
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }
        $group = [
            'uuid' => Str::uuid(),
            'user_id' => $user->id,
            'name' => $request->name,
            'is_private' => $request->is_private
        ];
        $group = Group::create($group);
        return Response::json(true, "Tạo nhóm thành công!", $group);

    }
    public function groupInfo($group_uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($group_uuid == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        $group = Group::where('uuid', $group_uuid)->with('user', 'members.user')->first();
        if (!($group)) {
            return Response::json(false, "Nhóm không tồn tại!");
        }
        $group->is_joined = $group->is_join();
        return Response::json(true, "Lấy thông tin nhóm thành công!", $group);

    }
    public function updateThumbnail(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'thumbnail' => 'required',
            'group_uuid' => 'required',
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }

        $group = Group::where('uuid', $request->group_uuid)->first();
        if ($group == null) {
            return Response::json(false, "Nhóm không tồn tại!");
        }
        if ($group->user_id != $user->id) {
            return Response::json(false, "Bạn không có quyền thay đổi thông tin nhóm này!");
        }
        $url = Cloudinary::upload($request->thumbnail->getRealPath())->getSecurePath();
        $group->thumbnail = $url;
        $group->save();
        return Response::json(true, "Thay đổi ảnh đại diện nhóm thành công!", $group);
    }
    public function myGroups()
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $groups = Group::where('user_id', $user->id)->orderBy("created_at", 'desc')->limit(5)->get();

        return Response::json(true, "Lấy danh sách nhóm của bạn thành công!", $groups);

    }
    public function suggestionGroups()
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $yourGroup_ids = Group::where('user_id', $user->id)->pluck("id")->toArray();
        $yourGroupJoin_ids = GroupMember::where('user_id', $user->id)->pluck("group_id")->toArray();

        $group_ids = array_unique(array_merge($yourGroup_ids, $yourGroupJoin_ids));

        $groups = Group::whereNotIn('id', $group_ids)->orderBy("created_at", 'desc')->limit(10)->get();
        return Response::json(true, "Lấy danh sách nhóm gợi ý thành công!", $groups);
    }
    public function joinGroup($group_id)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($group_id == "") {
            return Response::json(false, "Vui lòng nhập uuid");
        }
        $group = Group::where('id', $group_id)->first();
        if ($group == null) {
            return Response::json(false, "Nhóm không tồn tại");
        }
        GroupMember::create([
            'user_id' => $user->id,
            'group_id' => $group->id
        ]);
        return Response::json(true, "Bạn đã tham gia nhóm thành công");
    }
    public function leaveGroup($group_id)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($group_id == "") {
            return Response::json(false, "Vui lòng nhập group_id");
        }
        $group = Group::where('id', $group_id)->first();
        if ($group == null) {
            return Response::json(false, "Nhóm không tồn tại");
        }
        $groupMemner = GroupMember::where(['user_id' => $user->id, 'group_id' => $group_id])->first();
        if ($groupMemner == null) {
            return Response::json(false, "Bạn chưa tham gia nhóm này");
        }
        $groupMemner->delete();
        return Response::json(true, "Bạn đã rời nhóm thành công");
    }
    public function groupJoined()
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $group_ids = GroupMember::where('user_id', $user->id)->pluck("group_id");
        $groups = Group::whereIn('id', $group_ids)->orderBy("created_at")->limit(10)->get();
        return Response::json(true, "Lấy danh sách nhóm bạn tham gia thành công!", $groups);

    }
    public function getPostGroup($group_uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($group_uuid == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        $group = Group::where('uuid', $group_uuid)->first();
        if (!($group)) {
            return Response::json(false, "Nhóm không tồn tại!");
        }

        $posts = Post::where(['is_public' => 1, 'group_id' => $group->id])
            ->with(['user', 'post_media','group'])->withCount(["likes", 'comments'])->orderBy('created_at', 'desc')
            ->get()->map(function ($post) {
                $post->isLikePost = $post->isLikePost();
                $post->user->friends_count = $post->user->friends_count();
                $post->user->mutual_friends = $post->user->mutual_friends();
                return $post;
            });



        return Response::json(true, "Lấy danh sách bài viết thành công!", $posts);

    }
    public function getMediaImageGroup($group_uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($group_uuid == "") {
            return Response::json(false, "Vui lòng nhập group_uuid!");
        }
        $group = Group::where('uuid', $group_uuid)->first();
        if (!($group)) {
            return Response::json(false, "Nhóm không tồn tại!");
        }
        $post_ids = Post::where('group_id',$group->id)->pluck("id");
        $media = PostMedia::whereIn('post_id', $post_ids)->where('file_type', 'image')->get();
        return Response::json(true, "Lấy ảnh trong nhóm thành công!", $media);
    }
    public function getPostFeed() {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
       
        $my_groups_ids = Group::where("user_id", $user->id)->pluck('id');
        $group_join_ids = GroupMember::where("user_id", $user->id)->pluck('group_id');
        $group_ids = array_unique(array_merge($my_groups_ids->toArray(), $group_join_ids->toArray()));
        
        $posts = Post::whereIn( 'group_id' , $group_ids)
            ->with(['user', 'post_media','group'])->withCount(["likes", 'comments'])->orderBy('created_at', 'desc')->limit(20)
            ->get()->map(function ($post) {
                $post->isLikePost = $post->isLikePost();
                $post->user->friends_count = $post->user->friends_count();
                $post->user->mutual_friends = $post->user->mutual_friends();
                return $post;
            });
        return Response::json(true, "Lấy danh sách bài viết bảng feed thành công!", $posts);
    }
    public function deleteGroup($group_uuid)  {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($group_uuid == "") {
            return Response::json(false, "Vui lòng nhập group_uuid!");
        }
        $group = Group::where('uuid', $group_uuid)->first();
        if(!$group){
            return Response::json(false, "Nhóm không tồn tại!");
        }
        if($group->user_id!= $user->id){
            return Response::json(false, "Bạn không có quyền xóa nhóm này!");
        }
        $group->delete();
        Post::where('group_id',$group->id)->delete();
        GroupMember::where('group_id',$group->id)->delete();
        
        return Response::json(true, "Xóa nhóm thành công!", $group);
    }
}
