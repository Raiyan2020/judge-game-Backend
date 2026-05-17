<?php

namespace App\Services;

use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;


class AgoraService
{

    public function generateToken($room)
    {
        $appId = config('agora.agora.app_id');
        $appCertificate = config('agora.agora.app_certificate');
        $channelName = $room->name;

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
        ];
    }
}
