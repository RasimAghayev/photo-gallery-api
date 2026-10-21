<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'type', 'file',
    ];

    /**
     * The type of an image is a number, not a string.
     */
    protected $casts = [
        'type' => 'integer',
    ];
}
