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
            'consult'  => ['telegram' => null,                        'bale' => $_ENV['TOKEN_CONSULT']],
            'solar'    => ['telegram' => null,                        'bale' => $_ENV['TOKEN_BALE_SOLAR']],
        ];

        $botKey = null;
        foreach (array_keys($bots) as $key) {
            if (strpos($uri, $key) !== false) {
                $botKey = $key;
                break;
            }
        }

        if (!$botKey) {
            throw new Exception("No matching bot found.");
        }

        $token = $bots[$botKey][$type] ?? null;
        if (!$token) {
            throw new Exception("Token not configured for bot [$botKey] on [$type].");
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

        return self::tg($botKey, 'sendMessage', $params);
    }

    public static function tg($botKey, $method, $data = [])
    {
        $url = "https://foreign-server.com/{$botKey}/api.php?method=" . urlencode($method);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

}