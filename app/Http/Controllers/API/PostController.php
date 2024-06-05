<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\SavePost;
use App\Models\User;
use App\Models\Notification;
use App\Models\Response;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Str;

class PostController extends Controller
{
    public function create(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = validator::make($request->all(), [
            'content' => 'required|string',
            'is_public' => 'required',
            'type' => 'required'
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }
        DB::beginTransaction();
        try {
            $newPost = [
                'uuid' => Str::uuid(),
                'content' => $request->content,
                'is_public' => $request->is_public,
                'status' => 'published'
            ];
            if ($request->type == 'group') {
                $newPost['is_group_post'] = 1;
                $newPost['group_id'] = $request->id;
            }
            // if ($request->page_id) {
            //     $newPost['page_id'] = $request->page_id;
            //     $newPost['is_post_page'] = 1;
            // }
            // if ($request->group_id) {
            //     $newPost['group_id'] = $request->group_id;
            //     $newPost['is_group_post'] = 1;
            // }
            $newPost['user_id'] = auth()->id();

            $post = Post::create($newPost);
            if ($request->images) {
                $images = [];
                $videos = [];
                foreach ($request->images as $image) {
                    $mimeType = $image->getClientMimeType();
                    if (str_contains($mimeType, 'image')) {
                        $images[] = Cloudinary::upload($image->getRealPath())->getSecurePath();
                    } else {
                        $videos[] = Cloudinary::uploadVideo($image->getRealPath())->getSecurePath();
                    }
                }
                if (count($images) > 0) {
                    PostMedia::create([
                        'uuid' => Str::uuid(),
                        'post_id' => $post->id,
                        'file_type' => 'image',
                        'file' => json_encode($images),
                        'position' => 'general'
                    ]);
                }
                if (count($videos) > 0) {
                    PostMedia::create([
                        'uuid' => Str::uuid(),
                        'post_id' => $post->id,
                        'file_type' => 'video',
                        'file' => json_encode($videos),
                        'position' => 'general'
                    ]);
                }
            }
            DB::commit();
            $post->with(['user', 'post_media', 'group'])->withCount(["likes", 'comments']);
            $post->isLikePost = $post->isLikePost();
            $post->user->friends_count = $post->user->friends_count();
            $post->user->mutual_friends = $post->user->mutual_friends();
            $post->is_saved = $post->is_saved();


            $result = $this->getSinglePost($post->uuid);
            $result->message ="Tạo bài viết thành công!";
            return $result;
            // return Response::json(true, "Tạo bài viết thành công!", $post);
        } catch (\Throwable $th) {
            DB::rollBack();
            return Response::json(false, $th->getMessage());
        }
    }

    public function getList(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");


        $posts = Post::where('is_public', 1)
            ->with(['user', 'post_media', 'group'])->withCount(["likes", 'comments'])->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()->map(function ($post) {
                $post->isLikePost = $post->isLikePost();
                $post->user->friends_count = $post->user->friends_count();
                $post->user->mutual_friends = $post->user->mutual_friends();
                $post->is_saved = $post->is_saved();
                return $post;
            });
        return Response::json(true, "Lấy danh sách bài viết thành công!", $posts);
        // return Response::json(true, "Get list post success!", $posts->items(), [
        //     'current_page' => $posts->currentPage(),
        //     'per_page' => $posts->perPage(),
        //     'total' => $posts->total(),
        //     'last_page' => $posts->lastPage(),
        //     'from' => $posts->firstItem(),
        //     'to' => $posts->lastItem(),
        // ], [
        //     'self' => $posts->url($posts->currentPage()),
        //     'first' => $posts->url(1),
        //     'last' => $posts->url($posts->lastPage()),
        //     'prev' => $posts->previousPageUrl(),
        //     'next' => $posts->nextPageUrl(),
        // ]);

    }
    public function likePost(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = validator::make($request->all(), [
            'post_id' => 'required'
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }
        $post = Post::find($request->post_id);
        if ($post == null)
            return Response::json(false, "Bài viết không tồn tại!");
        $like = $post->likes()->where('user_id', $user->id)->first();
        if ($like == null) {
            $status = "Like";
            $like = $post->likes()->create([
                'user_id' => $user->id
            ]);
        } else {
            $status = "Unlike";

            $like->delete();
        }
        return Response::json(true, $status . " bài viết thành công!", $like);
    }
    public function commentPost(Request $request)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $validator = validator::make($request->all(), [
            'post_id' => 'required',
            'comment' => 'required',
        ]);
        if ($validator->fails()) {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!", $validator->errors());
        }
        $post = Post::find($request->post_id);
        if ($post == null)
            return Response::json(false, "Bài viết không tồn tại!");


        $comment = Comment::create([
            'user_id' => $user->id,
            'comment' => $request->comment,
            'post_id' => $request->post_id
        ]);
        Notification::create([
            'type' => "comment",
            'from_user_id' => $user->id,
            'user_id' => $post->user->id,
            'message' => $user->first_name . " " . $user->last_name . " đã bình luận một bài viết của bạn!",
            'url' => "post/" . $post->id,
        ]);
        return Response::json(true, "Bình luận bài viết thành công!", $comment);



    }
    public function getListComment($post_id)
    {
        $limit = 6;
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");

        if ($post_id == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        $post = Post::find($post_id);
        if ($post == null)
            return Response::json(false, "Bài viết không tồn tại!");
        $comments = Comment::where(['post_id' => $post_id])->orderBy('created_at', 'desc')->with('user')->orderBy("created_at", "desc")->limit(5)->get();
        return Response::json(true, "Lấy danh sách bình luận bài viết thành công!", $comments);
    }
    public function getPostMediaDetail($post_media_id)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($post_media_id == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        $postMedia = PostMedia::where('uuid', $post_media_id)->first();
        if ($postMedia == null)
            return Response::json(false, "Bài viết không tồn tại!");
        $post_id = $postMedia->post_id;
        $postMedias = PostMedia::where('post_id', $post_id)->get();
        return Response::json(true, "Lấy thông tin bài viết thành công!", $postMedias);
    }
    public function getPostDetailByPostMedia($post_media_id)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($post_media_id == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        $postMedia = PostMedia::where('uuid', $post_media_id)->first();
        if ($postMedia == null)
            return Response::json(false, "Bài viết không tồn tại!");
        $postInfo = Post::where("id", $postMedia->post_id)->with(['user', 'comments.user'])->withCount(['comments', 'likes'])->first();
        $postInfo->isLikePost = $postInfo->isLikePost();
        return Response::json(true, "Lấy thông tin bài viết thành công!", $postInfo);
    }
    public function getPostUser($user_uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if ($user_uuid == "") {
            return Response::json(false, "Vui lòng nhập đầy đủ thông tin!");
        }
        if ($user_uuid == "") {
            return Response::json(false, "Vui lòng nhập uuid!");
        }
        $profile = User::where('uuid', $user_uuid)->first();

        $posts = Post::where(['user_id' => $profile->id, 'is_group_post' => 0, 'is_page_post' => 0])
            ->with(['user', 'post_media'])->withCount(["likes", 'comments'])->orderBy('created_at', 'desc')
            ->get()->map(function ($post) {
                $post->isLikePost = $post->isLikePost();
                $post->is_saved = $post->is_saved();
                return $post;
            });
        return Response::json(true, "Lấy danh sách bài viết thành công!", $posts);
    }
    public function getSinglePost($post_uuid)
    {

        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");

        if ($post_uuid == "") {
            return Response::json(false, "Vui lòng nhập uuid!");
        }
        $post = Post::where('uuid', $post_uuid)
            ->with(['user', 'post_media', 'group'])->withCount(["likes", 'comments'])->orderBy('created_at', 'desc')
            ->first();
        if (!$post) {
            return Response::json(false, "Bài viết không tồn tại!");
        }
        $post->isLikePost = $post->isLikePost();
        $post->user->friends_count = $post->user->friends_count();
        $post->user->mutual_friends = $post->user->mutual_friends();
        $post->is_saved = $post->is_saved();
        return Response::json(true, "Lấy thông tin bài viết thành công!", $post);

    }
    public function savePost($post_uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");

        if ($post_uuid == "") {
            return Response::json(false, "Vui lòng nhập uuid!");
        }
        $post = Post::where('uuid', $post_uuid)->first();
        if (!$post) {
            return Response::json(false, "Bài viết không tồn tại!");
        }
        $savePost = SavePost::where(['user_id' => $user->id, 'post_id' => $post->id])->first();
        if ($savePost) {
            $savePost->delete();
            return Response::json(true, "Bỏ lưu bài viết thành công!");
        }
        $savePost = SavePost::create([
            'user_id' => $user->id,
            'post_id' => $post->id
        ]);
        return Response::json(true, "Lưu bài viết thành công!");

    }
    public function deletePost($post_uuid)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");

        if ($post_uuid == "") {
            return Response::json(false, "Vui lòng nhập uuid!");
        }
        $post = Post::where('uuid', $post_uuid)->first();
        if (!$post) {
            return Response::json(false, "Bài viết không tồn tại!");
        }
        if ($post->user_id != $user->id) {
            return Response::json(false, "Bạn không có quyền xóa bài viết này!");
        }
        $post->delete();
        PostMedia::where('post_id', $post->id)->delete();
        Comment::where('post_id', $post->id)->delete();
        SavePost::where('post_id', $post->id)->delete();
        return Response::json(true, "Xóa bài viết thành công!");

    }

}


