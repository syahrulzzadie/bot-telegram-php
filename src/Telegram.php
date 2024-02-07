<?php

namespace syahrulzzadie\BotTelegram;

session_start();

class Telegram
{
    protected static $token;
    protected static $apiUrl;
    protected static $offset;
    protected static $sessionName = 'bot_telegram_update_id';
    protected static $dataMessages = [];
    protected static $dataCommands = [];

    public static function init($token)
    {
        self::$token = $token;
        self::$offset = isset($_SESSION[self::$sessionName]) ? $_SESSION[self::$sessionName] : 0;
        self::$apiUrl = "https://api.telegram.org/bot".$token."/";
        return new self;
    }

    private static function setLastId($data = [])
    {
        if (count($data) > 0) {
            $lastId = 0;
            foreach ($data as $item) {
                if (intval($item['id']) > $lastId) {
                    $lastId = intval($item['id']);
                }
            }
            self::$offset = $lastId;
            $_SESSION[self::$sessionName] = $lastId;
        }
    }

    public static function getUpdates()
    {
        $dataUpdates = [];
        $method = 'getUpdates?offset=';
        $offset = intval(self::$offset + 1);
        $url = self::$apiUrl . $method . $offset;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        if (curl_errno($ch) == 0) {
            $data = json_decode($response, true);
            if ($data['ok']) {
                $updates = $data['result'];
                foreach ($updates as $update) {
                    $message = isset($update['message']) ? $update['message'] : false;
                    if ($message) {
                        $updateId = $update['update_id'];
                        $chatId = $message['chat']['id'];
                        $messageText = $message['text'];
                        $dataUpdates[] = [
                            'id' => $updateId,
                            'chat_id' => $chatId,
                            'message' => $messageText
                        ];
                    }
                    $editedMessage = isset($update['edited_message']) ? $update['edited_message'] : false;
                    if ($editedMessage) {
                        $updateId = $update['update_id'];
                        $chatId = $editedMessage['chat']['id'];
                        $messageText = $editedMessage['text'];
                        $dataUpdates[] = [
                            'id' => $updateId,
                            'chat_id' => $chatId,
                            'message' => $messageText
                        ];
                    }
                }
                self::setLastId($dataUpdates);
            }
        }
        self::$dataMessages = $dataUpdates;
        return new self;
    }

    public static function addCommand($keyword, $callback)
    {
        self::$dataCommands[] = [
            'keyword' => $keyword." ",
            'callback' => $callback
        ];
        return new self;
    }

    public static function execute()
    {
        $init = self::init(self::$token);
        foreach (self::$dataMessages as $msg) {
            $chatId = $msg['chat_id'];
            $message = $msg['message'];
            foreach (self::$dataCommands as $cmd) {
                $command = $cmd['keyword'];
                $callback = $cmd['callback'];
                if (strpos($message,$command) !== false) {
                    $message = str_replace($command,"",$message);
                    $params = explode(" ",$message);
                    if (count($params) >= 1) {
                        $callback(true,$params,$chatId,$init);
                    } else {
                        $callback(false,null,$chatId,$init);
                    }
                } else {
                    $callback(false,null,$chatId,$init);
                }
            }
        }
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
        curl_close($ch);
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
    }
}
