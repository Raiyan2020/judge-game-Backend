<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MessageStoreRequest;
use App\Http\Resources\Api\V1\ChatResource;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Group;
use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(protected MessageService $messageService) {}

    public function getGroupMessages(Group $group)
    {
        $messages = $this->messageService->index('group',$group);
        return \responder::success(MessageResource::collection($messages));
    }

    public function getPrivateMessages()
    {
        $messages = $this->messageService->index('private');
        return \responder::success(MessageResource::collection($messages));
    }

    public function getChats(Request $request)
    {
        $chats = $this->messageService->getChatMessages($request->all());

        return \responder::success(ChatResource::collection($chats));
    }



    public function store(MessageStoreRequest $request)
    {
        $message = $this->messageService->sendMessage($request->validated());
        return \responder::success(new MessageResource($message->load('user', 'chat')));
    }

    public function votePoll(Request $request, $messageId)
    {
        $request->validate([
            'option_id' => 'required|exists:chat_poll_options,id',
        ]);

        $vote = $this->messageService->votePoll($messageId, $request->option_id);
        return \responder::success(['message' => 'Vote recorded']);
    }
}
