<?php

namespace App\Http\Controllers;

use App\Common\Enums\AgentTaskType;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Interfaces\AgentTaskManagerInterface;
use App\Interfaces\Factory\AgentResultHandlerFactoryInterface;
use App\Models\LLMChat;
use Illuminate\Support\Facades\Auth;
use Vasenin26\Conversation\Interface\ConversationFactoryInterface;
use Vasenin26\Conversation\Messages\UserMessage;

class ChatController extends Controller
{
    public function state(LLMChat $chat)
    {
        return response()->json([
            'messages' => $chat->messages,
            'totalTokens' => $chat->total_tokens,
            'contextFill' => $chat->context_fill,
            'status' => $chat->getStatus(),
        ]);
    }

    public function stop(LLMChat $chat, AgentTaskManagerInterface $agentTaskManager)
    {
        $chat->stopGeneration($agentTaskManager);

        return response()->json([
            'status' => 'ok'
        ]);
    }

    public function sendMessage(
        LLMChat $chat,
        AgentTaskManagerInterface $agentTaskManager,
        ConversationFactoryInterface $conversationFactory,
        AgentResultHandlerFactoryInterface $handlerFactory,
        SendMessageRequest $request
    )
    {

        // Используем фабрику для создания чата из существующих сообщений
        $conversation = $conversationFactory->fromMessages($chat->messages ?? []);

        // Добавляем новое пользовательское сообщение
        $userMessage = new UserMessage($request->getMessage());
        $conversation->addMessage($userMessage);

        // Запускаем задачу для генерации
        $handler = $handlerFactory->createChatHandler($chat);

        $agentTaskManager->createTask(
            $handler,
            $request->user->id,
            $chat->project_id,
            $chat->id,
            false,
            $chat->type,
        );

        $chat->update([
            'messages' => $conversation->serialize()
        ]);

        return response()->json([
            'status' => 'ok'
        ]);
    }
}
