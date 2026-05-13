<?php

namespace controllers\Bale;

use Classes\Db;
use Classes\Helper;
use Classes\SqlSrv;
use Classes\Telegraph;

class ConsultController
{
    public $botId = '@farabi_consult_bot';
    public $botName = 'consult';
	
    public function index()
    {
        $data = file_get_contents("php://input");
        $update = json_decode($data, true);

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';
            $userState = Telegraph::loadUserStateFromDB($chatId);
			
            if ($text === "/start") {
                $userState = ['state' => 'waiting_for_name', 'name' => null];
                Telegraph::saveUserStateToDB($chatId, $userState, $this->botName);
                Telegraph::sendMessage($chatId, "نام و نام خانوادگی خود را وارد کنید✍️", null, true);
            } else {
                $currentState = $userState['state'] ?? 'start';
                switch ($currentState) {
                    case 'waiting_for_name':
                        if (preg_match('/^[\x{0600}-\x{06FF}\x{FB8A}\x{067E}\x{0686}\x{0698}\x{06AF}\x{200C}\s]+$/u', $text)) {
                            $userState['name'] = $text;
                            $userState['state'] = 'waiting_for_phone';
                            Telegraph::saveUserStateToDB($chatId, $userState, $this->botName);
                            Telegraph::sendMessage($chatId, "📱 لطفاً شماره تلفن خود را وارد کنید: 🔢✨", null, true);
                        } else {
                            Telegraph::sendMessage($chatId, "✍️نام می‌بایست با حروف فارسی وارد شود.", null, true);
                        }
                        break;
                    case 'waiting_for_phone':
					
	
                        $text = Helper::persianToEnglish($text);			
                        if (preg_match('/^\d{9,12}$/', $text)) {
							
	
                            $db = new Db();
							
                            $checkNum = $db->first("SELECT * FROM {$this->botName} WHERE number = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MONTH)", [$text]);
                            if ($checkNum) {
                                Telegraph::sendMessage($chatId, "📌 شماره شما در یک ماه گذشته در سیستم وجود دارد. ⏳✨", null, true);
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
                                    'origin'           => 'Bale'
                                ]);

                            Telegraph::sendMessage($chatId, "درخواست مشاوره شما با موفقیت ثبت شد! ✅
                             \nبه‌زودی با شما تماس می‌گیریم. 📞"."\n\n".$this->botId, null, true);
                            Telegraph::deleteUserStateFromDB($chatId, $this->botName);

                        } else {
                            Telegraph::sendMessage($chatId, "📱 لطفاً شماره تلفن معتبر وارد کنید (فقط اعداد).\n📌 مثال: 09123456789 ✅", null, true);
                        }
                        break;

                    default:
                        Telegraph::sendMessage($chatId, "🚀 لطفاً دستور /start را ارسال کنید. 📩✨", null, true);
                        break;
                }
            }
        }
    }

    public function crm()
    {
        $data = SqlSrv::getInstance()->raw("SELECT * FROM {$this->botName} WHERE sent_at IS NULL");

        if ($data) {
            foreach ($data as $datum) {
                $number = Helper::persianToEnglish($datum['number']);
                Helper::reqCRM('op', $this->botName, [
                    'MobileNumber' 	   => $number,
                    'FullName' 		   => $datum['name'],
                    'CustomerNeedID'   => 6038,
                    'QuestionCategory' => 21561,
                    'Source'           => $datum['origin'],
                ]);
            }
        }
    }
}
