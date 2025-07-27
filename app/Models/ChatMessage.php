<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;
    protected $fillable = [
        'thread_id',
        'sender_type',
        'sender_id',
        'message',
        'image'
    ];

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }
}
