<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['receiver_id', 'sender_id', 'type', 'content', 'hasBeenSeen', 'is_global', 'reference_id', 'priority', 'metadata'];

    public function receiver() {
        return $this->belongsTo(User::class);
    }

    public function sender() {
        return $this->belongsTo(User::class);
    }
}

