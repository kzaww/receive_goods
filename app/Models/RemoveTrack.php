<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemoveTrack extends Model
{
    use HasFactory;

    protected $fillable = ['received_goods_id','user_id','product_id','remove_qty'];
}
