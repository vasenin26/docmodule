<?php

namespace App\Http\Controllers;

use App\Common\DTO\SendTerminalCommandDTO;
use App\Common\Enums\AgentTaskType;
use App\Http\Requests\SendTerminalCommandRequest;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Models\LLMChat;
use App\Models\Project;
use App\Models\Terminal;
use Vasenin26\Conversation\Factory\ConversationFactory;
use Vasenin26\Conversation\Messages\UserMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TerminalController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('terminals/Index');
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $terminals = Terminal::forUser($user->id)
            ->with('llmChat')
            ->orderBy('created_at', 'desc')
            ->get()
            ->values();
        
        return response()->json($terminals);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Получаем проект пользователя (можно расширить для выбора проекта)
        $project = Project::where('owner_id', $user->id)->first();
        
        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'У пользователя нет проектов'
            ], 400);
        }
        
        $terminal = DB::transaction(function () use ($user, $project) {
            // Создаем чат
            $chat = LLMChat::create([
                'project_id' => $project->id,
                'messages' => []
            ]);
            
            // Создаем терминал
            return Terminal::create([
                'project_id' => $project->id,
                'chat_id' => $chat->id,
                'created_by' => $user->id,
            ]);
        });
        
        $terminal->load('llmChat');
        
        return response()->json($terminal);
    }

    public function sendCommand(
        SendTerminalCommandRequest $request,
        Terminal $terminal,
        ConversationFactory $conversationFactory,
        AgentResultHandlerFactoryInterface $handlerFactory,
        AgentTaskManagerInterface $agentTaskManager
    ): JsonResponse {
        $dto = SendTerminalCommandDTO::fromRequest($request, $terminal);
        
        $success = DB::transaction(function () use ($dto, $terminal, $conversationFactory, $handlerFactory, $agentTaskManager) {
            // Получаем или создаем чат
            $chat = $terminal->llmChat;
            if (!$chat) {
                $chat = LLMChat::create([
                    'project_id' => $terminal->project_id,
                    'messages' => []
                ]);
                $terminal->update(['chat_id' => $chat->id]);
            }
            
            // Создаем conversation из существующих сообщений
            $conversation = $conversationFactory->fromMessages($chat->messages ?? []);
            
            // Добавляем сообщение пользователя
            $userMessage = new UserMessage($dto->message);
            $conversation->addMessage($userMessage);
            
            // Сохраняем обновленный чат
            $chatUpdated = $chat->update(['messages' => $conversation->serialize()]);
            
            if ($chatUpdated) {
                // Создаем handler и задачу агента
                $handler = $handlerFactory->createTerminalResultHandler($terminal);
                
                $agentTaskManager->createTask(
                    $handler,
                    $dto->userId,
                    $terminal->project_id,
                    $chat->id,
                    false,
                    AgentTaskType::TERMINAL
                );
                
                return true;
            }
            
            return false;
        });
        
        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Команда отправлена'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Ошибка при отправке команды'
        ], 500);
    }

    public function getChatState(Terminal $terminal): JsonResponse
    {
        $chat = $terminal->llmChat;
        
        if (!$chat) {
            return response()->json([
                'messages' => [],
                'totalTokens' => 0,
                'contextFill' => 0,
                'status' => 'completed'
            ]);
        }
        
        return response()->json([
            'messages' => $chat->messages ?? [],
            'totalTokens' => $chat->agentTasks()->sum('total_tokens') ?? 0,
            'contextFill' => $chat->context_fill ?? 0,
            'status' => $chat->getStatus()
        ]);
    }
}
