<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;
use Classes\Telegraph;

class CommonController
{
    public function leaved($bot_name)
    {
        $message = "برای اطلاع از زمان شروع پذیره‌نویسی، شماره خود را ثبت کنید.";

        $test_chat_ids = [];
        $followUpMessage = 0;

        $enableTest = true;
        if ($enableTest) {
            $test_chat_ids = ['392223271']; // Hadi ChatID
        }

        if (isset($bot_name)) {
            $db = Db::getInstance();

            $test = "";

            if (!empty($test_chat_ids)) {
                $chat_id_list = implode("','", $test_chat_ids);
                $test = "AND chat_id IN ('{$chat_id_list}')";
            }

            $result = $db->query("SELECT * FROM bot_user_states WHERE bot_name = :bot_name {$test} AND followUpMessage = :followUpMessage ORDER BY updated_at DESC;", ['bot_name' => $bot_name, 'followUpMessage' => $followUpMessage]);

            foreach ($result as $row) {
                if (!empty($row['name']) && isset($row['name'])) {
                    $dearName = $row['name'] . " عزیز.\n ";
                } else {
                    $dearName = "جام طلا را از دست ندهید!\n ";
                }


                $chat_id = $row['chat_id'];

                Telegraph::sendMessage($chat_id, $dearName . $message);
                $db->modify("UPDATE bot_user_states SET followUpMessage = followUpMessage + 1 WHERE bot_name = :bot_name AND chat_id = :chat_id", [
                    'bot_name' => $bot_name,
                    'chat_id' => $chat_id
                ]);
            }
        } else {
            print_r('bot name is not set');
        }

    }

    // ezafe kardane userai ke shomare vared kardan dg jelo naraftan be list
    public function addToDb($botName)
    {

        $db = Db::getInstance();
        $results = $db->query("SELECT * FROM `bot_user_states` WHERE phone IS NOT NULL AND phone != '' AND name AND bot_name = :bot_name ORDER BY `created_at` DESC", ['bot_name' => $botName]);

        foreach ($results as $result) {
            $chatId = $result['chat_id'];
            $name = $result['name'];
            $phone = $result['phone'];
            $portfolioValue = $result['portfolio_value'];
            $lastTransaction = $result['last_transaction'];


            $db->insert("INSERT INTO {$botName} (chat_id, name, number, portfoy, last_transaction, created_at, origin) VALUES (:chat_id, :name, :number, :portfoy, :last_transaction, :created_at, :origin)",
                [
                    'chat_id'          => $chatId,
                    'name'             => $name,
                    'number'           => $phone,
                    'portfoy'          => $portfolioValue,
                    'last_transaction' => $lastTransaction,
                    'created_at'       => date('Y-m-d H:i:s'),
                    'origin'           => 'Telegram'
                ]);

        }

    }


}