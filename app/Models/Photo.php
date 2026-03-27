<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    // sample
    protected $fillable = [
        'title',
        'image_path',
        'processed',
    ];
}
