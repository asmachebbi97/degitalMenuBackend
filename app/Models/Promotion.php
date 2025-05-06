<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'restaurant_id', 'description', 'start_date', 'end_date', 'discount_percent'
    ];

    public function owner() {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function menuItem() {
        return $this->belongsTo(MenuItem::class);
    }
}

