<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;
    protected $table = "pages";
    protected $fillable = [
        "uuid",
        "user_id",
        "avatar",
        "thumbnail",
        "name",
        "description",
        "location",
        "type",
        "follwers",
        "is_private",
    ];
}
