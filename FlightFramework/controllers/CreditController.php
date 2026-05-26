<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;
use Classes\SqlSrv;
use Classes\Telegraph;



define('QUESTION_CHOICES', [
    [
        "از 500 میلیون تا یک میلیارد تومان",
        "تا 500 میلیون تومان",
        "بیش از یک میلیارد تومان"
    ],
    [
        "کمتر از یک ماه",
        "یک تا سه ماه",
        "بیشتر از سه ماه"
    ],
]);

class CreditController
{
    public function index()
    {
        $botId = '@farabi_creditbot';
        $botName = 'credit';

        $data = file_get_contents("php://input");
        $update = json_decode($data, true);

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';

            $db = Db::getInstance();
            $userState = Telegraph::loadUserStateFromDB($chatId, $botName);

            if ($text === "/start") {
                $userState = ['state' => 'waiting_for_name', 'name' => null];
                Telegraph::saveUserStateToDB($chatId, $userState, $botName);
                Telegraph::sendMessage($chatId, "نام و نام خانوادگی خود را وارد کنید✍️");
            } else {
                $currentState = $userState['state'] ?? 'start';

                switch ($currentState) {
                    case 'waiting_for_name':
                        if (preg_match('/^[\x{0600}-\x{06FF}\x{FB8A}\x{067E}\x{0686}\x{0698}\x{06AF}\x{200C}\s]+$/u', $text)) {
                            $userState['name'] = $text;
                            $userState['state'] = 'waiting_for_phone';
                            Telegraph::saveUserStateToDB($chatId, $userState, $botName);
                            Telegraph::sendMessage($chatId, "📱 لطفاً شماره تلفن خود را وارد کنید: 🔢✨");
                        } else {
                            Telegraph::sendMessage($chatId, "✍️نام می‌بایست با حروف فارسی وارد شود.");
                        }
                        break;
                    case 'waiting_for_phone':
                        $text = Helper::persianToEnglish($text);
                        if (preg_match('/^\d{9,12}$/', $text)) {
                            $db = Db::getInstance();
                            $checkNum = $db->first("SELECT * FROM {$botName} WHERE number = '{$text}' AND created_at > DATE_SUB(NOW(), INTERVAL 1 MONTH)");

                            if ($checkNum) {
                                Telegraph::sendMessage($chatId, "📌 شماره شما در یک ماه گذشته در سیستم وجود دارد. ⏳✨");
                                Telegraph::deleteUserStateFromDB($chatId, $botName);
                                exit;
                            }

                            $userState['phone'] = $text;

                            $userState['state'] = 'waiting_for_portfolio_value';
                            Telegraph::saveUserStateToDB($chatId, $userState, $botName);

                            $replyMarkup = [
                                'keyboard' => [[
                                    ['text' => QUESTION_CHOICES[0][0]],
                                    ['text' => QUESTION_CHOICES[0][1]],
                                    ['text' => QUESTION_CHOICES[0][2]]
                                ]],
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true
                            ];
                            Telegraph::sendMessage($chatId, "💼 ارزش حدودی پرتفوی فعلی شما چقدر است؟ 💰📊", $replyMarkup);

                        } else {
                            Telegraph::sendMessage($chatId, "📱 لطفاً شماره تلفن معتبر وارد کنید (فقط اعداد).\n📌 مثال: 09123456789 ✅");
                        }
                        break;

                    case 'waiting_for_portfolio_value':
                        if (in_array($text, QUESTION_CHOICES[0])) {
                            $userState['portfolio_value'] = $text;
                            $userState['state'] = 'waiting_for_last_transaction';
                            Telegraph::saveUserStateToDB($chatId, $userState, $botName);

                            $replyMarkup = [
                                'keyboard' => [[
                                    ['text' => QUESTION_CHOICES[1][0]],
                                    ['text' => QUESTION_CHOICES[1][1]],
                                    ['text' => QUESTION_CHOICES[1][2]]
                                ]],
                                'resize_keyboard' => true,
                                'one_time_keyboard' => true
                            ];
                            Telegraph::sendMessage($chatId, "⌛ از آخرین معامله شما چه مدت گذشته است؟", $replyMarkup);
                        } else {
                            Telegraph::sendMessage($chatId, " لطفاً یکی از گزینه‌های موجود را انتخاب کنید: 🔽✨");
                        }
                        break;

                    case 'waiting_for_last_transaction':
                        if (in_array($text, QUESTION_CHOICES[1])) {
                            $userState['last_transaction'] = $text;
                            $userState['state'] = 'completed';
                            Telegraph::saveUserStateToDB($chatId, $userState, $botName);

                            $name = $userState['name'];
                            $phone = $userState['phone'];
                            $portfolioValue = $userState['portfolio_value'];
                            $lastTransaction = $userState['last_transaction'];

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

                            $inlineKeyboard = [
                                'inline_keyboard' => [
                                    [
                                        ['text' => 'لینک ثبت‌نام 📄', 'url' => 'https://reg.irfarabi.com/reg/?ref=credit&utm_source=Telegram&utm_medium=Tbot&utm_campaign=Credit']
                                    ]
                                ]
                            ];
                            Telegraph::sendMessage($chatId, "نام: " . $name . "\nشماره: " . $phone . "\nارزش پرتفوی: " . $portfolioValue . "\nآخرین معامله: " . $lastTransaction . "\n\n" . "درخواست دریافت اعتبار با موفقیت ثبت شد! ✅ به‌زودی با شما تماس می‌گیریم. 📞\nجهت تسریع در فرایند دریافت اعتبار، از طریق لینک زیر در فارابی ثبت‌نام کنید. 🔗✨\n" . $botId, $inlineKeyboard);
                            Telegraph::deleteUserStateFromDB($chatId, $botName);
                        } else {
                            Telegraph::sendMessage($chatId, " لطفاً یکی از گزینه‌های موجود را انتخاب کنید: 🔽✨");
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
        $data = SqlSrv::getInstance()->raw("SELECT * FROM credit WHERE sent_at is null");

        foreach($data as $datum) {
            $name             = $datum['fullname'];
            $number           = Helper::persianToEnglish($datum['number']);
            $portfoy          = $datum['portfolioValue'];
            $last_transaction = $datum['last_transaction'];
            $origin           = $datum['origin'];

            $portvalue = ($portfoy === "تا 500 میلیون تومان") ? 500000000 :
            (($portfoy === "از 500 میلیون تا یک میلیارد تومان") ? 700000000 :
            (($portfoy === "بیش از یک میلیارد تومان") ? 1000000000 : null));

            Helper::reqCRM('fo', 'credit', [
                'MobileNumber'   => $number,
                'Id'             => '432',
                'NationalCode'   => '',
                'Title'          => 'مارکتینگ - کمپین اعتبار',
                'FullName'       => $name,
                'PortfolioValue' => $portfoy,
                'Description'    => $portvalue . ' | ' . $last_transaction,
                'Source'         => $origin
            ]);

        }
    }
}