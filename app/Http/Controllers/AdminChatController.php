<?php

namespace App\Http\Controllers;

use App\Models\ChatThread;
use App\Models\ChatMessage;
use App\Services\FirebaseService;
use App\Services\OneSignalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminChatController extends Controller
{

    public function index(FirebaseService $firebase)
    {
        $admin = auth()->user() ;
        
        // Initialize admin threads in Firebase if needed
        $firebase->initializeAdminThreads($admin->id);
        
        // Get threads from database
        $dbThreads = ChatThread::where('admin_id', $admin->id)
            ->with('latestMessage', 'user')
            ->latest()
            ->get();
            
        // Get threads from Firebase to ensure we have the latest data
        $firebaseThreads = $firebase->getAdminThreads($admin->id);
        
        // If Firebase has threads data, use it to filter the threads
        if ($firebaseThreads) {
            // Convert to array if it's not already
            $firebaseThreadIds = array_keys((array)$firebaseThreads);
            
            // Filter database threads to only include those in Firebase
            $threads = $dbThreads->filter(function($thread) use ($firebaseThreadIds) {
                return in_array($thread->id, $firebaseThreadIds);
            })->values();
        } else {
            $threads = $dbThreads;
        }

        return view('backend.admin_chat', compact('threads'));
    }

    // Add new method to get admin threads from Firebase
    public function getAdminThreads(FirebaseService $firebase)
    {
        $admin = auth()->user() ;
        
        if (!$admin) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $threads = $firebase->getAdminThreads($admin->id);
        
        return response()->json(['threads' => $threads]);
    }

    // Add new method to temporarily close a thread
    public function temporaryCloseThread($threadId, FirebaseService $firebase)
    {
        $admin = auth()->user() ;
        
        if (!$admin) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $thread = ChatThread::findOrFail($threadId);
        
        if ($thread->admin_id !== $admin->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $firebase->closeThreadTemporarily($threadId, $admin->id);
        
        return response()->json(['success' => true, 'message' => 'Thread temporarily closed']);
    }
    
    // Add new method to reopen a temporarily closed thread
    public function temporaryReopenThread($threadId, FirebaseService $firebase)
    {
        $admin = auth()->user() ;
        
        if (!$admin) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $thread = ChatThread::findOrFail($threadId);
        
        if ($thread->admin_id !== $admin->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $firebase->reopenThreadTemporarily($threadId, $admin->id);
        
        return response()->json(['success' => true, 'message' => 'Thread reopened']);
    }


    public function getMessages(Request $request, $threadId)
    {
        $thread = ChatThread::findOrFail($threadId);

        if ($thread->admin_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Reset unread messages count when admin opens the thread
        $thread->resetUnreadCount();
        
        // Update Firebase with the reset count
        try {
            $firebase = app(FirebaseService::class);
            $thread->load('user', 'latestMessage');
            $firebase->updateThread($thread->id, [
                'id' => $thread->id,
                'user_id' => $thread->user_id,
                'admin_id' => $thread->admin_id,
                'status' => $thread->status,
                'subject' => $thread->subject,
                'updated_at' => $thread->updated_at,
                'user_name' => $thread->user ? $thread->user->first_name : null,
                'unread_messages_count' => 0,
                'latest_message' => $thread->latestMessage ? [
                    'message' => $thread->latestMessage->message,
                    'image' => $thread->latestMessage->image,
                    'created_at' => $thread->latestMessage->created_at,
                ] : null
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to update Firebase: ' . $e->getMessage());
        }

        $messages = $thread->messages()
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'thread' => $thread,
            'messages' => $messages
        ]);
    }


    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'thread_id' => 'required|exists:chat_threads,id',
                'message' => 'nullable|string',
                'image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:4096',
            ]);

            $thread = ChatThread::findOrFail($request->thread_id);

            if ($thread->admin_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $imageUrl = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();

                $path = $file->storeAs('chat_uploads', $fileName, 'public');

                $baseUrl = rtrim(config('app.url'), '/');
                $imageUrl = $baseUrl . '/storage/' . $path;
            }

            $message = ChatMessage::create([
                'thread_id' => $thread->id,
                'sender_type' => 'admin',
                'sender_id' => Auth::id(),
                'message' => $request->message,
                'image' => $imageUrl,
            ]);

            // Always initialize Firebase (don't rely on dependency injection which might be null)
            $firebase = app(FirebaseService::class);
            
            try {
                // Push the message
                $firebase->pushMessage($thread->id, [
                    'id' => $message->id,
                    'sender_type' => 'admin',
                    'sender_id' => Auth::id(),
                    'message' => $message->message,
                    'image' => $imageUrl,
                    'created_at' => $message->created_at,
                ]);
                
                // Update the thread info
                $thread->load('user', 'latestMessage');
                $firebase->updateThread($thread->id, [
                    'id' => $thread->id,
                    'user_id' => $thread->user_id,
                    'admin_id' => $thread->admin_id,
                    'status' => $thread->status,
                    'subject' => $thread->subject,
                    'updated_at' => $thread->updated_at,
                    'user_name' => $thread->user ? $thread->user->first_name : null,
                    'latest_message' => $thread->latestMessage ? [
                        'message' => $thread->latestMessage->message,
                        'image' => $thread->latestMessage->image,
                        'created_at' => $thread->latestMessage->created_at,
                    ] : null
                ]);
            } catch (\Exception $e) {
                Log::error('Firebase error: ' . $e->getMessage());
            }

            return response()->json($message);
        } catch (\Exception $e) {
            Log::error('Chat error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function updateThreadStatus(Request $request, $threadId)
    {
        try {
            $request->validate([
                'status' => 'required|in:open,closed',
            ]);

            $thread = ChatThread::findOrFail($threadId);

            if ($thread->admin_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $thread->status = $request->status;
            $thread->save();

            return response()->json(['success' => true, 'thread' => $thread]);
        } catch (\Exception $e) {
            Log::error('Thread status update error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function upload_files(\Illuminate\Http\UploadedFile $file, string $location): string
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->storeAs($location, $fileName, 'public');
        return url('storage/' . $location . '/' . $fileName);
    }
}
