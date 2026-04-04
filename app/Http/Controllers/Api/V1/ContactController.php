<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ContactService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ContactRequest;

class ContactController extends Controller
{
    public function __construct(protected ContactService $contactService)
    {
    }

    public function store(ContactRequest $request)
    {
        $contact = $this->contactService->create($request->validated());

        return \responder::success(__('Message sent successfully'));
    }
}