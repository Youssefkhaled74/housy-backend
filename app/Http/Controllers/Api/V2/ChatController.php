<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Services\FirebaseService;
use App\Traits\ResponsesTrait;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use ResponsesTrait;
    public function sendMessage(Request $request, FirebaseService $firebase)
    {
        $request->validate([
            'receiver_id' => 'nullable|integer',
            'message'     => 'nullable|string',
            'image'       => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:4096',
            'thread_id'   => 'nullable|exists:chat_threads,id',
            'force_new'   => 'nullable|boolean',
            'subject'     => 'nullable|string|max:255',
        ]);

        // Get the authenticated user
        $user = auth('sanctum')->user();
        
        if (!$user) {
            return $this->unauthorizedRespond(__('messages.unauthorized', ['item' => __('messages.message')]));
        }
        // Determine if user is admin or regular user
        $isAdmin = $user->user_type === 'admin';
        $senderId = $user->id;
        $senderType = $isAdmin ? 'admin' : 'user';

        $thread = null;
        $isNewThread = false;

        if (!$isAdmin) {
            // Regular user flow
            if (!$request->filled('receiver_id') && !$request->filled('thread_id')) {
                return $this->badRequest('Either receiver_id or thread_id is required');
            }
            
            $user_id = $senderId;
            $admin_id = $request->receiver_id;

            if ($request->filled('thread_id')) {
                $thread = ChatThread::where('id', $request->thread_id)
                    ->where('user_id', $user_id)
                    ->first();
                if (!$thread) {
                    return $this->respondNotFound(__('messages.not_found', ['item' => __('messages.thread')]));
                }
                
                if ($thread->status === 'closed') {
                    return $this->forbiddenRequest(__('messages.thread_closed'));
                }
            } elseif (!$request->force_new) {
                $thread = ChatThread::where('user_id', $user_id)
                    ->where('admin_id', $admin_id)
                    ->where('status', 'open')
                    ->latest()
                    ->first();
            }

            if (!$thread) {
                $thread = ChatThread::create([
                    'user_id'  => $user_id,
                    'admin_id' => $admin_id,
                    'status'   => 'open',
                    'subject'  => $request->subject,
                ]);
                $isNewThread = true;
            }
            
            // Increment unread messages count when user sends a message
            $thread->incrementUnreadCount();
        } else {
            // Admin flow
            if (!$request->filled('thread_id')) {
                return $this->badRequest(__('messages.thread_required'));
            }

            $thread = ChatThread::findOrFail($request->thread_id);

            // Verify admin has access to this thread
            if ($thread->admin_id !== $senderId) {
                return $this->forbiddenRequest(__('messages.unauthorized', ['item' => __('messages.thread')]));
            }
            
            // Reset unread messages count when admin sends a message
            $thread->resetUnreadCount();
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $this->upload_files($request->file('image'), 'chat_uploads');
        }

        $message = ChatMessage::create([
            'thread_id'   => $thread->id,
            'sender_type' => $senderType,
            'sender_id'   => $senderId,
            'message'     => $request->message,
            'image'       => $imageUrl,
        ]);
 
        // Push message to Firebase
         $firebase->pushMessage($thread->id, [
            'id' => $message->id,
            'sender_type' => $senderType,
            'sender_id'   => $senderId,
            'message'     => $message->message,
            'image'       => $imageUrl,
            'created_at'  => $message->created_at,
        ]);

        // Update thread info in Firebase
        $thread->load('user', 'latestMessage');
        $firebase->updateThread($thread->id, [
            'id' => $thread->id,
            'user_id' => $thread->user_id,
            'admin_id' => $thread->admin_id,
            'status' => $thread->status,
            'subject' => $thread->subject,
            'updated_at' => $thread->updated_at,
            'user_name' => $thread->user ? $thread->user->name : null,
            'unread_messages_count' => $thread->unread_messages_count,
            'latest_message' => $thread->latestMessage ? [
                'message' => $thread->latestMessage->message,
                'image' => $thread->latestMessage->image,
                'created_at' => $thread->latestMessage->created_at,
            ] : null
        ]);

        return $this->respondCreated(
            $message->load(['thread']),
            __('messages.created_successfully', ['item' => __('messages.message')])
        );
       
    }

    public function getMessages(Request $request)
    {
        $request->validate([
            'thread_id'    => 'required|exists:chat_threads,id',
            'sender_type'  => 'nullable|in:user,admin',
            'sender_id'    => 'nullable|integer',
            'status'       => 'nullable|in:open,closed',
            'from_date'    => 'nullable|date',
            'to_date'      => 'nullable|date',
            'per_page'     => 'nullable|integer',
        ]);

        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->unauthorizedRespond(__('messages.unauthorized', ['item' => __('messages.message')]));
        }

        $thread = ChatThread::findOrFail($request->thread_id);

        // Check permissions based on user type
        $isAdmin = $user->user_type === 'admin';
        
        if (!$isAdmin && $thread->user_id !== $user->id) {
            return $this->forbiddenRequest(__('messages.unauthorized', ['item' => __('messages.thread')]));
        }

        if ($isAdmin && $thread->admin_id !== $user->id) {
            return $this->forbiddenRequest(__('messages.unauthorized', ['item' => __('messages.thread')]));
        }

        $query = $thread->messages()->orderBy('created_at', 'asc');

        if ($request->filled('sender_type')) {
            $query->where('sender_type', $request->sender_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sender_id')) {
            $query->where('sender_id', $request->sender_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $messages = $query->paginate($request->get('per_page', 10));

        return $this->respondOk([
            'thread'   => $thread,
            'messages' => $messages
        ]);
    }

    public function getThreads(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:open,closed',
        ]);

        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->unauthorizedRespond(__('messages.unauthorized', ['item' => __('messages.threads')]));
        }

        $query = ChatThread::with(['user', 'latestMessage']);
        $isAdmin = $user->user_type === 'admin';

        if ($isAdmin) {
            $query->where('admin_id', $user->id);
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $threads = $query->latest()->paginate(10);

        return $this->respondOk(
            $threads,
            __('messages.retrieved_successfully', ['item' => __('messages.threads')])
        );
    }


    public function closeThread(Request $request, $id, FirebaseService $firebase)
    {
        $thread = ChatThread::findOrFail($id);

        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->unauthorizedRespond(__('messages.unauthorized', ['item' => __('messages.thread')]));
        }

        $isAdmin = $user->user_type === 'admin';
        $senderId = $user->id;

        if (!$isAdmin && $thread->user_id !== $senderId) {
            return $this->forbiddenRequest(__('messages.unauthorized', ['item' => __('messages.thread')]));
        }

        if ($isAdmin && $thread->admin_id !== $senderId) {
            return $this->forbiddenRequest(__('messages.unauthorized', ['item' => __('messages.thread')]));
        }

        $thread->update(['status' => 'closed']);

        // Update thread info in Firebase
        $thread->load('user', 'latestMessage');
        $firebase->updateThread($thread->id, [
            'id' => $thread->id,
            'user_id' => $thread->user_id,
            'admin_id' => $thread->admin_id,
            'status' => $thread->status,
            'subject' => $thread->subject,
            'updated_at' => $thread->updated_at,
            'user_name' => $thread->user ? $thread->user->name : null,
            'latest_message' => $thread->latestMessage ? [
                'message' => $thread->latestMessage->message,
                'image' => $thread->latestMessage->image,
                'created_at' => $thread->latestMessage->created_at,
            ] : null
        ]);

        return $this->respondOk($thread, __('messages.updated_successfully', ['item' => __('messages.thread')]));
    }

    // New method for temporary chat closing
    public function temporaryCloseThread($id, FirebaseService $firebase)
    {
        $thread = ChatThread::findOrFail($id);

        $user = auth('sanctum')->user();
        if (!$user) {
            return $this->unauthorizedRespond(__('messages.unauthorized', ['item' => __('messages.thread')]));
        }

        $isAdmin = $user->user_type === 'admin';
        $senderId = $user->id;

        if (!$isAdmin && $thread->user_id !== $senderId) {
            return $this->forbiddenRequest(__('messages.unauthorized', ['item' => __('messages.thread')]));
        }

        if ($isAdmin && $thread->admin_id !== $senderId) {
            return $this->forbiddenRequest(__('messages.unauthorized', ['item' => __('messages.thread')]));
        }

        // Update Firebase to show thread as temporarily closed for this session
        $thread->load('user', 'latestMessage');
        $firebase->updateThread($thread->id, [
            'id' => $thread->id,
            'user_id' => $thread->user_id,
            'admin_id' => $thread->admin_id,
            'status' => $thread->status, // Keep original status
            'subject' => $thread->subject,
            'updated_at' => $thread->updated_at,
            'user_name' => $thread->user ? $thread->user->name : null,
            'temp_closed' => true, // Add temporary close flag
            'latest_message' => $thread->latestMessage ? [
                'message' => $thread->latestMessage->message,
                'image' => $thread->latestMessage->image,
                'created_at' => $thread->latestMessage->created_at,
            ] : null
        ]);

        return $this->respondOk($thread, __('messages.chat_temporarily_closed'));
    }

    public function deleteMessage($id)
    {
        $message = ChatMessage::findOrFail($id);

        if ($message->image) {
            $this->delete_file($message->image);
        }

        $message->delete();

        return $this->noContent(__('messages.deleted_successfully', ['item' => __('messages.message')]));
    }

    public function deleteThread($id)
    {
        $thread = ChatThread::findOrFail($id);

        foreach ($thread->messages as $message) {
            if ($message->image) {
                $this->delete_file($message->image);
            }
            $message->delete();
        }

        $thread->delete();

        return $this->noContent(__('messages.deleted_successfully', ['item' => __('messages.thread')]));
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        $url = $this->upload_files($request->file('image'), 'chat_uploads');

        return $this->respondOk($url, __('messages.retrieved_successfully', ['item' => __('messages.image')]));
    }
}
