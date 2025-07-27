<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatThread extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'admin_id','subject', 'status', 'unread_messages_count'];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'thread_id');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'thread_id')
            ->latestOfMany();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increment the unread messages count
     * 
     * @param int $count Number to increment by (default: 1)
     * @return bool
     */
    public function incrementUnreadCount($count = 1)
    {
        return $this->increment('unread_messages_count', $count);
    }

    /**
     * Reset the unread messages count to zero
     * 
     * @return bool
     */
    public function resetUnreadCount()
    {
        return $this->update(['unread_messages_count' => 0]);
    }
}
