<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;

class AgoraController extends Controller
{

    public function generateToken()
    {
        $appId = env('AGORA_APP_ID');
        $appCertificate = env('AGORA_APP_CERTIFICATE');
      $channelName = 'room_1';

$uid = auth()->id();
      $role = RtcTokenBuilder::RolePublisher;
        $expireTimeInSeconds = 3600;
        $currentTimestamp = (new \DateTime())->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;

$token = RtcTokenBuilder::buildTokenWithUid(
    $appId,
    $appCertificate,
    $channelName,
    $uid,
    $role,
    $privilegeExpiredTs
);
       return \responder::success([
    'token' => $token,
    'channel' => $channelName,
    'uid' => $uid,
]);
    }
}
