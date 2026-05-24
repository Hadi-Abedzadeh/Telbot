<?php

namespace Classes;


class Telegraph
{
    public static function loadUserStateFromDB($chatId)
    {
        return SqlSrv::getInstance()->first("SELECT state, name, phone, portfolio_value, last_transaction FROM bot_user_states WHERE chat_id = ?", [$chatId]) ?? [];
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

    public static function sendMessage($chatId, $text, $replyMarkup = null, $bale = false)
    {
        $postFields = [
            'chat_id' => $chatId,
            'text' => $text
        ];

        $url = Helper::generateTelegramApiUrl($_SERVER['REQUEST_URI']) . "sendMessage";

            $postFields['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if($bale)
        {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

    }
}