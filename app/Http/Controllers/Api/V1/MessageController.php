<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MessageStoreRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Group;
use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(protected MessageService $messageService) {}

    public function index(Group $group, Request $request)
    {
        $messages = $this->messageService->index($group, $request->type);
        return \responder::success(MessageResource::collection($messages));
    }

  

    public function store(Group $group, MessageStoreRequest $request)
    {
        $message = $this->messageService->store($group, $request->validated());
        return \responder::success(new MessageResource($message->load('user')));
    }

}
