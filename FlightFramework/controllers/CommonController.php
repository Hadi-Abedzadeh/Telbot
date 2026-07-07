<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;
use Classes\SqlSrv;
use Classes\Telegraph;

class CommonController
{
    public function leaved($bot_name)
    {
        $test_chat_ids = [];
        $followUpMessage = 0;

        $enableTest = (int)$_ENV['ENABLE_TEST_LEAVED_USERS']; // 1 or 0

        if ($enableTest) {
            $test_chat_ids = ['123123'];
        }

        if (isset($bot_name)) {

            $test = "";

            if (!empty($test_chat_ids)) {
                $chat_id_list = implode("','", $test_chat_ids);
                $test = "AND chat_id IN ('{$chat_id_list}')";
            }

            $result = SqlSrv::getInstance()->raw(
                "SELECT TOP 50 * 
             FROM bot_user_states 
             WHERE bot_name = ? {$test} 
             AND followUpMessage = ? 
			 AND exception IS NULL

             ORDER BY updated_at DESC;",
                [$bot_name, $followUpMessage]
            );

            foreach ($result as $row) {


                echo $chat_id = $row['chat_id'];
                echo "<br>";

                SqlSrv::getInstance()->raw(
                    "UPDATE bot_user_states 
                 SET state = 'come_back_message' 
                 WHERE bot_name = ? AND chat_id = ?",
                    [$bot_name, $chat_id]
                );

                try {

                    $response = Telegraph::pushTelegram($bot_name, $chat_id);


                    if (isset($response['ok']) && $response['ok'] === true) {
                        SqlSrv::getInstance()->raw("UPDATE bot_user_states SET followUpMessage = followUpMessage + 1, exception = NULL WHERE bot_name = ? AND chat_id = ?",[$bot_name, $chat_id]);
                    }

                } catch (\Throwable $e) {
                    $errorMessage = $e->getMessage();

                    SqlSrv::getInstance()->raw(
                        "UPDATE bot_user_states 
                     SET exception = ? ,
					 followUpMessage = followUpMessage + 1
                     WHERE bot_name = ? AND chat_id = ?",
                        [$errorMessage, $bot_name, $chat_id]
                    );

                    continue; // برو سراغ کاربر بعدی
                }
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

    public function tokens()
    {
        $data = [
            'CREDIT'      => $_ENV['TOKEN_CREDIT'],
            'LABKHAND'    => $_ENV['TOKEN_LABKHAND'],
            'GOLD'        => $_ENV['TOKEN_GOLD'],
            'CONSULT'     => $_ENV['TOKEN_CONSULT'],
            'BALE_CREDIT' => $_ENV['TOKEN_BALE_CREDIT'],
            'BALE_SOLAR'  => $_ENV['TOKEN_BALE_SOLAR'],
            'UPDATE'      => 'UPDATE_TOKENS'
        ];

        $encrypted = Helper::encrypt_data($data, 'MY_SECRET_KEY');

        return \Flight::json([
            'data' => $encrypted
        ]);
    }
}
