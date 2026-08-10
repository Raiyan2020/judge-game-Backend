<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class MessageStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // `requiredIf:group_id,null` compares against the STRING "null", so
            // neither field was ever actually required: a body with neither one
            // reached the service and died on an undefined key (HTTP 500).
            // `required_without` is the rule that expresses "one of the two".
            'receiver_id' => 'required_without:group_id|exists:users,id',
            'group_id' => 'required_without:receiver_id|exists:groups,id',
            'message' => 'nullable|string',
            'type' => 'nullable|string|in:text,voice,image,file',
            // Uploads land on the PUBLIC disk under this app's own origin, and
            // the stored extension follows the sniffed MIME type — so an
            // unvalidated upload of a text/html body is served back as
            // text/html from thejudgegame.com: stored XSS against the web
            // frontend. The allow-list is the set the app can actually render
            // (image / voice / document), and `max` caps an otherwise
            // unbounded upload.
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp3,m4a,wav,aac,ogg,mp4,pdf|max:10240',
        ];
    }
}
