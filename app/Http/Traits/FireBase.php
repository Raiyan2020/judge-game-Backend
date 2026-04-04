<?php

namespace App\Http\Traits;

class FireBase
{
    const fcm_server_key = '';

    public static function notification($notifiable, $title, $body, $data)
    {
        if (!blank($notifiable->fcm_token_android)) {
//            dd($notifiable->fcm_token_android);
            info(self::notifyByFirebase($title, $body, [$notifiable->fcm_token_android], $data, false));
        }
        if (!blank($notifiable->fcm_token_ios)) {
            self::notifyByFirebase($title, $body, [$notifiable->fcm_token_ios], $data + ['title' => $title, 'body' => $body], true);
        }
    }
    public static function sendFcm($title, $body, array $tokens, $data)
    {
        $tokens=array_values($tokens);
        $fcmFields = [
            'registration_ids' => $tokens,
            'priority' => 'high',
            'data' => $data,
            'notification' => [
                'body' => $body,
                'title' => $title,
                'sound' => "default",
                'color' => "#203E78",
                'image'=>\Arr::get($data,'image',asset('logo.png'))
            ]
        ];
        $headers = [
            'Authorization' => 'key=' . self::fcm_server_key,
            'Content-Type' => 'application/json'
        ];
        $request = \Http::async()->withHeaders($headers)->post('https://fcm.googleapis.com/fcm/send',$fcmFields);
    }
    public static function notifyByFirebase($title, $body, $tokens, $data = [], $is_notification = true)
    {
        // https://gist.github.com/rolinger/d6500d65128db95f004041c2b636753a
        $registrationIDs = $tokens;

        // prep the bundle
        // to see all the options for FCM to/notification payload:
        // https://firebase.google.com/docs/cloud-messaging/http-server-ref#notification-payload-support

        // 'vibrate' available in GCM, but not in FCM
        $fcmMsg = array(
            'body' => $body,
            'title' => $title,
            'sound' => "default",
            'color' => "#203E78"
        );
        // I haven't figured 'color' out yet.
        // On one phone 'color' was the background color behind the actual app icon.  (ie Samsung Galaxy S5)
        // On another phone, it was the color of the app icon. (ie: LG K20 Plush)

        // 'to' => $singleID ;      // expecting a single ID
        // 'registration_ids' => $registrationIDs ;     // expects an array of ids
        // 'priority' => 'high' ; // options are normal and high, if not set, defaults to high.
        $fcmFields = array(
            'registration_ids' => $registrationIDs,
            'priority' => 'high',
            'data' => $data
        );
//        if ($is_notification) {
            $fcmFields['notification'] = $fcmMsg;
//        }

        $headers = array(
            'Authorization: key=' . self::fcm_server_key,
            'Content-Type: application/json'
        );

        /*        info("API_ACCESS_KEY_client: ".env('API_ACCESS_KEY_client'));
                info("PUSHER_APP_ID: ".env('PUSHER_APP_ID'));*/

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmFields));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public static function sendFCMTopic($target, $title, $body, $data, $is_notification = true)
    {
        //FCM API end-point
        $url = 'https://fcm.googleapis.com/fcm/send';
        //api_key available in Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key
        $server_key = self::fcm_server_key;

        $fields = array();
        $fields['data'] = $data;
        $fields['to'] = $target;
        $fcmMsg = array(
            'body' => $body,
            'title' => $title,
            'sound' => "default",
            'color' => "#203E78"
        );
        if ($is_notification) {
            $fields['notification'] = $fcmMsg;
        }

        //header with content_type api key
        $headers = array(
            'Content-Type:application/json',
            'Authorization:key=' . $server_key
        );
        //CURL request to route notification to FCM connection server (provided by Google)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Oops! FCM Send Error: ' . curl_error($ch));
        }
//        dd($ch);
        curl_close($ch);
        return $result;
    }

}
