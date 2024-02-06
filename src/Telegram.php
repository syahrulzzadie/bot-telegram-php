<?php

namespace syahrulzzadie\BotTelegram;

class Telegram
{
    protected static $apiUrl;

    public static function init($token)
    {
        self::$apiUrl = "https://api.telegram.org/bot".$token."/";
        return new self;
    }

    public static function getUpdates()
    {
        $data = [];
        $method = 'getUpdates';
        $url = self::$apiUrl . $method;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch) == 0) {
            $data = json_decode($response, true);
            if ($data['ok']) {
                $updates = $data['result'];
                foreach ($updates as $update) {
                    $message = $update['message'];
                    $chatId = $message['chat']['id'];
                    $messageText = $message['text'];
                    $data[] = [
                        'chat_id' => $chatId,
                        'message' => $messageText
                    ];
                }
            }
        }
        curl_close($ch);
        return $data;
    }

    public static function sendMessage($chatId, $textMessage)
    {
        $method = 'sendMessage';
        $url = self::$apiUrl . $method;
        $messageData = array(
            'chat_id' => $chatId,
            'text' => $textMessage
        );
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch) == 0) {
            $data = json_decode($response, true);
            if ($data['ok']) {
                return [
                    'status' => true,
                    'message' => 'Message sent successfully!'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => $data['description']
                ];
            }
        } else {
            return [
                'status' => false,
                'message' => curl_error($ch)
            ];
        }
        curl_close($ch);
    }
}
