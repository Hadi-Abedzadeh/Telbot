<?php

namespace Classes;


class Telegraph
{
    public static function loadUserStateFromDB($chatId, $botName)
    {
        return SqlSrv::getInstance()->first("SELECT state, name, phone, portfolio_value, last_transaction FROM bot_user_states WHERE chat_id = ? AND bot_name = ?", [$chatId, $botName]) ?? [];
    }

    public static function saveUserStateToDB($chatId, $userState, $botName)
    {
        SqlSrv::getInstance()->raw("MERGE bot_user_states AS target
            USING (
                SELECT
                    ? AS chat_id, ? AS bot_name, ? AS state, ? AS name, ? AS phone, ? AS portfolio_value, ? AS last_transaction, ? AS created_at, ? AS updated_at
            ) AS source
            ON target.chat_id = source.chat_id          
            WHEN MATCHED THEN UPDATE SET bot_name = source.bot_name, state = source.state, name = source.name, phone = source.phone, portfolio_value = source.portfolio_value, last_transaction = source.last_transaction, updated_at = source.updated_at           
            WHEN NOT MATCHED THEN
                INSERT (
                    chat_id,
                    bot_name,
                    state,
                    name,
                    phone,
                    portfolio_value,
                    last_transaction,
                    created_at,
                    updated_at
                ) VALUES (
                    source.chat_id,
                    source.bot_name,
                    source.state,
                    source.name,
                    source.phone,
                    source.portfolio_value,
                    source.last_transaction,
                    source.created_at,
                    source.updated_at
                );
            ", [
            $chatId,
            $botName,
            $userState['state'],
            $userState['name'] ?? null,
            $userState['phone'] ?? null,
            $userState['portfolio_value'] ?? null,
            $userState['last_transaction'] ?? null,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ]);

    }

    public static function otherRequestsToDB($chatId, $message, $botName)
    {
        SqlSrv::getInstance()->raw("INSERT INTO bot_other_requests (chat_id, bot_name, message, created_at, updated_at) VALUES (?, ?, ?, ?, ?)", [
            $chatId,
            $botName,
            $message ?? null,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s')
        ]);
    }

    public static function deleteUserStateFromDB($chatId, $botName)
    {
        SqlSrv::getInstance()->raw("DELETE FROM bot_user_states WHERE chat_id = ? AND bot_name = ?",[
            $chatId,
            $botName
        ]);
    }

    public static function sendMessage($chatId, $text, $replyMarkup = null)
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        $type = (strpos($uri, 'bale/') !== false) ? 'bale' : 'telegram';

        $bots = [
            'credit'   => ['telegram' => $_ENV['TOKEN_CREDIT'],       'bale' => $_ENV['TOKEN_BALE_CREDIT']],
            'labkhand' => ['telegram' => $_ENV['TOKEN_LABKHAND'],     'bale' => null],
            'gold'     => ['telegram' => $_ENV['TOKEN_GOLD'],         'bale' => null],
            'consult'  => ['telegram' => $_ENV['TOKEN_CONSULT'],      'bale' => $_ENV['TOKEN_BALE_CONSULT']],
            'solar'    => ['telegram' => null,                        'bale' => $_ENV['TOKEN_BALE_SOLAR']],
            'exir'     => ['telegram' => null,                        'bale' => $_ENV['TOKEN_BALE_EXIR']],
        ];

        $token = null;
        foreach (array_keys($bots) as $key) {
            if (strpos($uri, $key) !== false) {
                $token = $key;
                break;
            }
        }

        if (!$token) {
            throw new Exception("No matching bot found.");
        }

        $token = $bots[$token][$type] ?? null;
        if (!$token) {
            throw new Exception("Token not configured for bot [$token] on [$type].");
        }

        $params = [
            'chat_id' => $chatId,
            'text'    => $text
        ];

        if ($replyMarkup) {
            $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }

        if ($type === 'bale') {
            $ch = curl_init($_ENV['API_URL_BALE'] . $token . '/sendMessage');
            curl_setopt_array($ch, [
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $params,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_RETURNTRANSFER => true,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        }

        return self::tg($params);
    }

    public static function tg($data = [])
    {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return true;
    }

    public static function pushTelegram($bot_name, $user_id)
    {
        $payload = [
            "message" => [
                "chat" => [
                    "id" => $user_id,
                    "type" => "private"
                ],
                "text" => "come_back_message"
            ]
        ];

        $url = $_ENV['PUSH_TLG_URL'] . "?bot_name=" . urlencode($bot_name);

        $ch = curl_init($url);

        if ($ch === false) {
            throw new \Exception('Failed to initialize cURL');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,

            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,

        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);

            curl_close($ch);

            throw new \Exception("cURL error ({$errno}): {$error}");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception("HTTP request failed with status code {$httpCode}. Response: {$response}");
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(
                'Invalid JSON response: ' . json_last_error_msg()
            );
        }

        return $decoded;
    }
}
