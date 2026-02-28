<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pocket extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_pockets';

    protected $fillable = [
        'user_id',
        'name',
        'balance',
    ];
}
