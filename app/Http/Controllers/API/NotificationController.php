<?php

namespace App\Http\Controllers\API;

use App\Events\Message;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Response;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class NotificationController extends Controller
{
    public function getListNotis()
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");

        $notis = Notification::where('user_id', $user->id)->with('user')->limit(10)->orderBy('created_at', 'desc')->get();
        return Response::json(true, "Lấy danh sách thông báo thành công!", $notis);
    }
    public function readNoti($id)
    {
        $user = auth()->user();
        if ($user == null)
            return Response::json(false, "Unauthorized");
        if (!$id) {
            return Response::json(false, "Vui lòng nhập id thông báo!");
        }
        $noti = Notification::where(['id' => $id, 'user_id' => $user->id])->first();
        if ($noti == null) {
            return Response::json(false, "Không tìm thấy thông báo!");
        }
        $noti->read_at = Carbon::now();
        $noti->save();
        return Response::json(true, "Đã đọc thông báo thành công!");

    }
    public function countNoti()
    {
        $user = auth()->user();

       
        if ($user == null)
            return Response::json(false, "Unauthorized");
        $count = Notification::where(['user_id' => $user->id, 'read_at' => null])->where('created_at', ">=", now()->subDay())->count();
        return Response::json(true, "Đã đọc thông báo thành công!", $count ?? 0);
    }
}
