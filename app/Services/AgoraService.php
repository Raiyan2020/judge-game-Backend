<?php

namespace App\Services;

use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;


class AgoraService
{

    public function generateToken($room)
    {
        $appId = config('agora.agora.app_id');
        $appCertificate = config('agora.agora.app_certificate');
        // Use a stable ASCII channel id, NOT the user-entered room name (which
        // can be Arabic/long and violate Agora's channel-name constraints).
        $channelName = 'room_' . $room->id;

        $uid = auth()->id();
        $role = RtcTokenBuilder::RolePublisher;
        $expireTimeInSeconds = 3600;
        $currentTimestamp = now()->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

        $token = RtcTokenBuilder::buildTokenWithUid(
            $appId,
            $appCertificate,
            $channelName,
            $uid,
            $role,
            $privilegeExpiredTs
        );
        return [
            'room_id' => $room->id,
            'channel_name' => $channelName,
            'token' => $token,
            'uid' => $uid,
            // Return the exact App ID the token was signed with so the client
            // reads it from the server instead of a compile-time constant. Reuse
            // the local $appId to guarantee it matches the signing App ID — a
            // mismatch is a silent join failure on the client, not an error.
            'app_id' => $appId,
        ];
    }
}
