<?php

namespace Classes;

class Telegraph
{
    public static function loadUserStateFromDB($chatId)
    {
        $db = new Db();
        return $db->first("SELECT state, name, phone, portfolio_value, last_transaction FROM bot_user_states WHERE chat_id = ?", [$chatId]) ?? [];
    }

	public static function saveUserStateToDB($chatId, $userState, $botName)
	{
		$db = new Db();
		$db->insert("INSERT INTO bot_user_states
		(chat_id, bot_name, state, name, phone, portfolio_value, last_transaction, created_at, updated_at)
		VALUES
		(:chat_id, :bot_name, :state, :name, :phone, :portfolio_value, :last_transaction, :created_at, :updated_at)
		ON DUPLICATE KEY UPDATE
		state = VALUES(state),
		bot_name = VALUES(bot_name),
		name = VALUES(name),
		phone = VALUES(phone),
		portfolio_value = VALUES(portfolio_value),
		last_transaction = VALUES(last_transaction),
		updated_at = VALUES(updated_at)",
		[
		'chat_id' => $chatId,
		'state' => $userState['state'],
		'bot_name' => $botName,
		'name' => $userState['name'] ?? null,
		'phone' => $userState['phone'] ?? null,
		'portfolio_value' => $userState['portfolio_value'] ?? null,
		'last_transaction' => $userState['last_transaction'] ?? null,
		'created_at' => date('Y-m-d H:i:s'),
		'updated_at' => date('Y-m-d H:i:s')
		]);
	}
    public static function otherRequestsToDB($chatId, $message, $botName)
    {
        $db = Db();
        $db->insert("INSERT INTO bot_other_requests (chat_id, bot_name, message, updated_at) VALUES (:chat_id, :bot_name, :message, :updated_at)",
            [
                'chat_id'          => $chatId,
                'bot_name'         => $botName,
                'message'          => $message ?? null,
                'created_at'       => date('Y-m-d H:i:s'),
                'updated_at'       => date('Y-m-d H:i:s')
            ]);
    }

    public static function deleteUserStateFromDB($chatId, $botName)
    {
        $db = Db();
        $db->insert("DELETE FROM bot_user_states WHERE chat_id = :chat_id AND bot_name = :bot_name", ['chat_id' => $chatId, 'bot_name' => $botName]);
    }

    public static function sendMessage($chatId, $text, $replyMarkup = null, $bale = false)
    {

        $postFields = [
            'chat_id' => $chatId,
            'text' => $text
        ];

        if(!$bale)
        {
            $url = Helper::generateTelegramApiUrl($_SERVER['REQUEST_URI']) . "sendMessage";

            if ($replyMarkup)
            {
                $postFields['reply_markup'] = json_encode($replyMarkup);
            }
        }
        else
        {
            $url = "https://tapi.bale.ai/bot1544882322:n5tYqOLO6D623P1ebRG1VDRUO4GhZ-IOk4c/sendMessage";
        }

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