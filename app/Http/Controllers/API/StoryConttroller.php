<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Response;
use App\Models\Story;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;

class StoryConttroller extends Controller
{
    public function create(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = Validator::make($request->all(), [
            'story' => 'required',
            'type' => 'required'
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }
        if ($request->type == 'image') {
            $file = Cloudinary::upload($request->story->getRealPath())->getSecurePath();
        } else {
            $file = Cloudinary::uploadVideo($request->story->getRealPath())->getSecurePath();
        }
        $story = [
            'user_id' => $user->id,
            'story' => $file,
            'type' => $request->type,
            "status" => 'published',
            "likes" => 0,
            "comments" => 0
        ];
        if ($request->content)
            $story['content'] = $request->content;
        $Story = Story::create($story);
        return Response::json(true, "Tạo tin viết thành công!", $Story);
    }
    public function list()
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        // ->where('created_at', ">=", now()->subDay())

        $stories = Story::with('user')->select('stories.*')
            ->join(DB::raw('(SELECT user_id, MAX(created_at) as latest_story_time FROM stories GROUP BY user_id) as grouped_stories'), function ($join) {
                $join->on('stories.user_id', '=', 'grouped_stories.user_id')
                    ->on('stories.created_at', '=', 'grouped_stories.latest_story_time');
            })
            ->get();
        return Response::json(true, "Lấy tin viết thành công!", $stories);
    }
    public function get_story_user($uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        
        if($uuid ==""){
            return Response::json(false,"Missing user uuid");
        }
        $user = User::where("uuid", $uuid)->first();
        $stories = Story::where('user_id',$user->id)->get();
        return Response::json(true, "Lấy tin viết thành công!", ['stories'=>$stories,'user'=>$user]);
    }

}
