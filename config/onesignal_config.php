<?php
// OneSignal Bildirim Gönderme Fonksiyonu
function sendOneSignalNotification($title, $body, $url, $userIds = null) {
    // 1. OneSignal Anahtarlarınızı Buraya Girin
    $appId = '31414a9d-ab31-4d60-9e40-3bf74edfbdd7';
    $restApiKey = 'os_v2_app_gfauvhnlgfgwbhsahp3u5x5525ckyrf3ja5uoj53766v755ujns2jigf4jit2y7p64nhobuyc6j4umomdk3hj34ucg5rfcyhyiz5pei';

    $fields = [
        'app_id' => $appId,
        'headings' => ['en' => $title],
        'contents' => ['en' => $body],
        'url' => $url,
    ];

    // Eğer belirli kullanıcılara gönderilecekse
    if ($userIds && is_array($userIds) && count($userIds) > 0) {
        $filters = [];
        foreach ($userIds as $i => $userId) {
            $filters[] = ['field' => 'tag', 'key' => 'user_id', 'relation' => '=', 'value' => (string)$userId];
            if ($i < count($userIds) - 1) {
                $filters[] = ['operator' => 'OR'];
            }
        }
        $fields['filters'] = $filters;
    } else {
        // Herkese gönder
        $fields['included_segments'] = ['Subscribed Users'];
    }

    $fields = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $restApiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
?>
