<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;
use Classes\SqlSrv;
use Classes\Telegraph;

class GoldController
{
    private $botName = 'gold';
    private $botId = '@farabi_gold_bot';

    public function index()
    {


        $data = file_get_contents("php://input");
        $update = json_decode($data, true);

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';

            $userState = Telegraph::loadUserStateFromDB($chatId, $this->botName);

            if ($text === "/start") {
                $userState = ['state' => 'waiting_for_name', 'name' => null];
                Telegraph::saveUserStateToDB($chatId, $userState, $this->botName);
                Telegraph::sendMessage($chatId, "نام و نام خانوادگی خود را وارد کنید✍️");
            } else {
                $currentState = $userState['state'] ?? 'start';

                switch ($currentState) {
                    case 'waiting_for_name':
                        if (preg_match('/^[\x{0600}-\x{06FF}\x{FB8A}\x{067E}\x{0686}\x{0698}\x{06AF}\x{200C}\s]+$/u', $text)) {
                            $userState['name'] = $text;
                            $userState['state'] = 'waiting_for_phone';
                            Telegraph::saveUserStateToDB($chatId, $userState, $this->botName);
                            Telegraph::sendMessage($chatId, "📱 لطفاً شماره تلفن خود را وارد کنید: 🔢✨");
                        } else {
                            Telegraph::sendMessage($chatId, "✍️نام می‌بایست با حروف فارسی وارد شود.");
                        }
                        break;
                    case 'waiting_for_phone':
                        $text = Helper::persianToEnglish($text);
                        if (preg_match('/^\d{9,12}$/', $text)) {
                            $db = Db::getInstance();
                            $checkNum = $db->first("SELECT * FROM {$this->botName} WHERE number = '{$text}' AND created_at > DATE_SUB(NOW(), INTERVAL 1 MONTH)");

                            if ($checkNum) {
                                Telegraph::sendMessage($chatId, "📌 شماره شما در یک ماه گذشته در سیستم وجود دارد. ⏳✨");
                                Telegraph::deleteUserStateFromDB($chatId, $this->botName);
                                exit;
                            }

                            $userState['phone'] = $text;

                            $userState['state'] = 'completed';
                            Telegraph::saveUserStateToDB($chatId, $userState, $this->botName);

                            $name = $userState['name'];
                            $phone = $userState['phone'];

                            $db->insert("INSERT INTO {$this->botName} (chat_id, name, number, created_at, origin) VALUES (:chat_id, :name, :number, :created_at, :origin)",
                                [
                                    'chat_id'          => $chatId,
                                    'name'             => $name,
                                    'number'           => $phone,
                                    'created_at'       => date('Y-m-d H:i:s'),
                                    'origin'           => 'Telegram'
                                ]);

                            Telegraph::sendMessage($chatId, "درخواست مشاوره شما با موفقیت ثبت شد! ✅
                             \nبه‌زودی با شما تماس می‌گیریم. 📞"."\n\n".$this->botId);
                            Telegraph::deleteUserStateFromDB($chatId, $this->botName);

                        } else {
                            Telegraph::sendMessage($chatId, "📱 لطفاً شماره تلفن معتبر وارد کنید (فقط اعداد).\n📌 مثال: 09123456789 ✅");
                        }
                        break;

                    default:
                        Telegraph::sendMessage($chatId, "🚀 لطفاً دستور /start را ارسال کنید. 📩✨");
                        break;
                }
            }
        }
    }

    public function crm()
    {
        $data = SqlSrv::getInstance()->raw("SELECT * FROM [$this->botName] WHERE sent_at IS NULL AND ipo = 0");

        if ($data) {
            foreach ($data as $datum) {
                $number = Helper::persianToEnglish($datum['number']);

                Helper::reqCRM('op', $this->botName, [
                    'MobileNumber'     => $number,
                    'FullName'         => $datum['name'],
                    'CustomerNeedID'   => 5936,
                    'QuestionCategory' => 5936,
                    'Source'           => $datum['origin'],
                    'Topic'            => 'معرفی صندوق طلای جام فارابی',
                ]);
            }
        }
    }
}
