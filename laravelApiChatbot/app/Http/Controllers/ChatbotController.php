<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\Uuid;

class ChatbotController extends Controller
{
    private $model = "mistral";

    public function chat(Request $request) {
        $request->validate([
            'message' => 'required|string',
            'session_id' => 'string',
        ]);

        try {
            $user = $request->user('sanctum');

            if ($request->session_id && $user) {
                $user_id = $user->id;
                $session_id = $request->session_id;
                $previousMessages = ChatHistory::where(['user_id' => $user_id, 'session_id' => $session_id])
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(fn($chat) => [
                        ['role' => 'user', 'content' => $chat->user_message],
                        ['role' => 'assistant', 'content' => $chat->bot_response],
                    ])
                    ->flatten(1)
                    ->toArray();
                
                if($previousMessages) {
                    $messages = array_merge($previousMessages, [
                        ['role' => 'user', 'content' => $request->message]
                    ]);
                } else {
                    $session_id = (string) Uuid::uuid4();
                    $messages = [['role' => 'user', 'content' => $request->message]];
                }

                $responseData = Http::post('http://localhost:11434/api/chat', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'stream' => false,
                ]);
                
                $response = $responseData->json()['message']['content'] ?? 'Sorry, I had trouble processing that.';

                ChatHistory::create([
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'user_message' => $request->message,
                    'bot_response' => $response,
                ]);

                return response()->json(['session_id' => $session_id, 'response' => $response]);
            } else if ($user) {
                $user_id = $user->id;
                $session_id = (string) Uuid::uuid4();
                $message = [['role' => 'user', 'content' => $request->message]];

                $responseData = Http::post('http://localhost:11434/api/chat', [
                    'model' => $this->model,
                    'messages' => $message,
                    'stream' => false
                ]);

                $response = $responseData->json()['message']['content'] ?? 'Sorry, I had trouble processing that.';

                ChatHistory::create([
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'user_message' => $request->message,
                    'bot_response' => $response,
                ]);

                return response()->json(['session_id' => $session_id, 'response' => $response]);
            } else {

                $responseData = Http::post('http://localhost:11434/api/generate', [
                    'model' => $this->model,
                    'prompt' => $request->message,
                    'stream' => false
                ]);

                $response = $responseData->json()['response'] ?? 'Sorry, I had trouble processing that.';

                return response()->json(['response' => $response]);
            }
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
