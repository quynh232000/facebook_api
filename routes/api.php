<?php

use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\GroupController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\StoryConttroller;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::group(['middleware'=>'api'],function () {
    Route::post('/register',[UserController::class,'register']);
    Route::post('/login',[UserController::class,'login']);
    Route::post('/verifyEmail',[UserController::class,'verifyEmail']);
    Route::post('/resend_email',[UserController::class,'resendEmail']);
    Route::get('/me',[UserController::class,'me']);
    Route::get('/get_user_info/{uuid}',[UserController::class,'getUserInfo']);
    Route::prefix("/post")->group(function(){
        Route::get("/list",[PostController::class,'getList']);
        Route::get("/list_post_user/{user_uuid}",[PostController::class,'getPostUser']);
        Route::post("/create",[PostController::class,'create']);
        Route::get("/like_post/{post_id}",[PostController::class,'likePost']);
        Route::get("/dislike_post",[PostController::class,'dislikePost']);
        Route::post("/comment",[PostController::class,'commentPost']);
        Route::get("/get_list_comment/{post_id}",[PostController::class,'getListComment']);
        Route::get("/get_post_media_detail/{post_media_id}",[PostController::class,'getPostMediaDetail']);
        Route::get("/get_post_detail_by_post_media/{post_media_id}",[PostController::class,'getPostDetailByPostMedia']);
        Route::get("/get_single_post/{post_uuid}",[PostController::class,'getSinglePost']);
        Route::get("/save_post/{post_uuid}",[PostController::class,'savePost']);
        Route::get("/delete_post/{post_uuid}",[PostController::class,'deletePost']);
        Route::get("/get_saved_posts",[PostController::class,'getSavedPosts']);
        Route::get("/watch_list",[PostController::class,'watchList']);

    });
    Route::prefix("/user")->group(function(){
        Route::get("/list",[UserController::class,'getUserSuggestion']);
        Route::get("/request_friend",[UserController::class,'getRequestFriend']);
        Route::get("/cancel_send_request_friend",[UserController::class,'cancelSendRequest']);
        Route::get("/cancel_request_friend",[UserController::class,'cancelRequest']);
        Route::get("/add_friend",[UserController::class,'addFriend']);
        Route::get("/accept_friend",[UserController::class,'acceptFriend']);
        Route::get("/get_media_user/{user_uuid}/{type}",[UserController::class,'getMediaUser']);
        Route::get("/get_list_friend/{user_uuid}",[UserController::class,'getListFriend']);
        Route::post("/update_thumbnail",[UserController::class,'updateThumbnailUser']);
        Route::post("/change_avatar",[UserController::class,'changeAvatarUser']);
        Route::prefix('/update')->group(function () {
            Route::post("/description",[UserController::class,'updateDescriptionUser']);
        });
        Route::get("/delete_media/{media_uuid}/{index}",[UserController::class,'deleteMedia']);
        Route::get("/set_thumbnail/{media_uuid}/{index}",[UserController::class,'setThumbnailUser']);
        Route::get("/set_avatar/{media_uuid}/{index}",[UserController::class,'setAvatarUser']);

    });
    Route::prefix("/story")->group(function(){
        Route::post("/create",[StoryConttroller::class,'create']);
        Route::get("/list",[StoryConttroller::class,'list']);
        Route::get("/get_story_user/{uuid}",[StoryConttroller::class,'get_story_user']);
    });
    Route::prefix("/group")->group(function () {
       Route::post('/create_group',[GroupController::class,'createGroup']); 
       Route::get('/group_info/{group_uuid}',[GroupController::class,'groupInfo']); 
       Route::post('/update_thumnail',[GroupController::class,'updateThumbnail']); 
       Route::get('/my_groups',[GroupController::class,'myGroups']);
       Route::get('/suggestion_groups',[GroupController::class,'suggestionGroups']); 
       Route::get('/join_group/{group_id}',[GroupController::class,'joinGroup']); 
       Route::get('/leave_group/{group_id}',[GroupController::class,'leaveGroup']); 
       Route::get('/group_joined',[GroupController::class,'groupJoined']); 
       Route::get('/get_post_by_group/{group_uuid}',[GroupController::class,'getPostGroup']); 
       Route::get('/get_images_group/{group_uuid}',[GroupController::class,'getMediaImageGroup']); 
       Route::get('/get_post_feed',[GroupController::class,'getPostFeed']); 
       Route::get('/delete_group/{group_uuid}',[GroupController::class,'deleteGroup']); 

    });
    Route::prefix("/notification")->group(function () {
        Route::get('/list_notifications',[NotificationController::class,'getListNotis']);
        Route::get('/read_noti/{id}',[NotificationController::class,'readNoti']);
        Route::get('/count_noti',[NotificationController::class,'countNoti']);

    });
    Route::prefix(('/chat'))->group(function () {
        Route::get('/message/{user_uuid}',[ChatController::class,'getMessage']);
        Route::post('/send_message',[ChatController::class,'sendMessage']);
        Route::get('/delete_message/{message_id}',[ChatController::class,'deleteMessage']);
        Route::get('/get_conversations',[ChatController::class,'getConversations']);

    });
});

