<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'expenses';

    protected $fillable = [
        'user_id',
        'pocket_id',
        'amount',
        'notes',
    ];

    public function pocket()
    {
        return $this->belongsTo(Pocket::class, 'pocket_id');
    }
}
