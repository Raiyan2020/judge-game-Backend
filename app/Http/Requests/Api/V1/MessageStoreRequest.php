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
            // The allow-list mirrors ChatAttachmentPolicy in the app
            // (image / document / audio sets, including HEIC from iPhone
            // cameras) so nothing the client already permits is refused here —
            // a rule that breaks a working upload would be a worse bug than the
            // one it closes. What it excludes is what makes the upload
            // dangerous: `html`, `htm`, `svg`, `xml` and scripts. Uploads land
            // on the PUBLIC disk with a sniffed extension, so those would be
            // served back as active content from this app's own origin
            // (stored XSS). `max` is the PER-TYPE ceiling below — a flat
            // `max:10240` rejected a chat video the case-evidence path
            // (`videos.*|max:51200`) accepts.
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,heic,heif,pdf,doc,docx,txt,mp3,m4a,wav,aac,ogg,mp4,mov|max:'.$this->attachmentMaxKilobytes(),
        ];
    }

    /**
     * Per-type upload ceiling (in kilobytes) for the `attachment` file.
     *
     * Mirrors the app's ChatAttachmentPolicy and the case-evidence limits: a
     * video (mp4/mov) may be 50 MiB (`max:51200`), everything else 15 MiB
     * (`max:15360`). `public/.user.ini` raises PHP's `upload_max_filesize`/
     * `post_max_size` to 60M/64M, so a 50 MiB clip survives to validation.
     *
     * The kind is read from the uploaded file's sniffed MIME (belt) and its
     * extension (suspenders) — `$this->file()` is available inside `rules()`.
     */
    private function attachmentMaxKilobytes(): int
    {
        $file = $this->file('attachment');
        if ($file === null) {
            return 15360;
        }

        $isVideo = str_starts_with((string) $file->getMimeType(), 'video/')
            || in_array(
                strtolower((string) $file->getClientOriginalExtension()),
                ['mp4', 'mov'],
                true
            );

        return $isVideo ? 51200 : 15360;
    }
}
