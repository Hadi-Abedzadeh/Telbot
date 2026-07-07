<?php

namespace controllers;

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
        $botId = '@credit_bot';
        $botName = 'credit';

        $data = file_get_contents("php://input");
        $update = json_decode($data, true);

        if (isset($update['bot_name'])) {
            unset($update['bot_name']);
            $update = $update['result'][0];

        }

        if (!isset($update['message'])) return;

        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        $userState = Telegraph::loadUserStateFromDB($chatId, $botName);

        if ($text === "/start") {
            $userState = ['state' => 'waiting_for_name', 'name' => null];
            Telegraph::saveUserStateToDB($chatId, $userState, $botName);
            Telegraph::sendMessage($chatId, "نام و نام خانوادگی خود را وارد کنید✍️");
            return;
        }

        $currentState = $userState['state'] ?? 'start';

        switch ($currentState) {

            case 'waiting_for_name':

                if (preg_match('/^[\x{0600}-\x{06FF}\x{FB8A}\x{067E}\x{0686}\x{0698}\x{06AF}\x{200C}\s]+$/u', $text)) {

                    $userState['name'] = $text;
                    $userState['state'] = 'waiting_for_phone';
                    Telegraph::saveUserStateToDB($chatId, $userState, $botName);

                    $keyboard = [
                        'keyboard' => [
                            [
                                ['text' => '📲 ارسال خودکار شماره من', 'request_contact' => true]
                            ]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true
                    ];

                    Telegraph::sendMessage(
                        $chatId,
                        "📱 برای اطلاع از شرایط دریافت اعتبار، شماره تلفن خود را وارد کنید یا از دکمه زیر استفاده کنید:",
                        $keyboard
                    );

                } else {
                    Telegraph::sendMessage($chatId, "✍️ نام می‌بایست با حروف فارسی وارد شود.");
                }

                break;


            case 'waiting_for_phone':

                if (isset($update['message']['contact'])) {
                    $contact = $update['message']['contact'];

                    if ($contact['user_id'] != $chatId) {
                        Telegraph::sendMessage($chatId, "⚠️ فقط می‌توانید شماره متعلق به خودتان را ارسال کنید.");
                        return;
                    }

                    $phone = $contact['phone_number'];

                } else {
                    $phone = $text;
                }

                $phone = Helper::persianToEnglish($phone);
                $phone = preg_replace('/[^0-9+]/', '', $phone);

                if (strpos($phone, '+98') === 0) $phone = '0' . substr($phone, 3);
                if (strpos($phone, '98') === 0 && strlen($phone) == 12) $phone = '0' . substr($phone, 2);
                if (strlen($phone) == 10 && strpos($phone, '9') === 0) $phone = '0' . $phone;

                if (!preg_match('/^09\d{9}$/', $phone)) {
                    Telegraph::sendMessage($chatId, "📱 لطفاً شماره معتبر وارد کنید.\nمثال: 09123456789");
                    return;
                }

                $checkNum = SqlSrv::getInstance()->first(
                    "SELECT * FROM {$botName} WHERE number = ? AND created_at > DATEADD(MONTH, -1, GETDATE())",
                    [$phone]
                );

                if ($checkNum) {
                    Telegraph::sendMessage($chatId, "📌 شماره شما در یک ماه گذشته ثبت شده است.");
                    Telegraph::deleteUserStateFromDB($chatId, $botName);
                    return;
                }

                $userState['phone'] = $phone;
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

                Telegraph::sendMessage($chatId, "💼 ارزش حدودی پرتفوی فعلی شما چقدر است؟", $replyMarkup);

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
                    Telegraph::sendMessage($chatId, "لطفاً یکی از گزینه‌های موجود را انتخاب کنید.");
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

                    SqlSrv::getInstance()->raw(
                        "INSERT INTO credit (chat_id, fullname, number, portfolioValue, last_transaction, created_at, origin)
                         VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [
                            $chatId,
                            $name,
                            $phone,
                            $portfolioValue,
                            $lastTransaction,
                            date('Y-m-d H:i:s'),
                            'Telegram'
                        ]
                    );

                    $inlineKeyboard = [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => 'لینک ثبت‌نام 📄',
                                    'url' => 'https://reg.irfarabi.com/reg/?ref=credit&utm_source=Telegram&utm_medium=Tbot&utm_campaign=Credit'
                                ]
                            ]
                        ]
                    ];

                    Telegraph::sendMessage(
                        $chatId,
                        "نام: $name\nشماره: $phone\nارزش پرتفوی: $portfolioValue\nآخرین معامله: $lastTransaction\n\nدرخواست شما ثبت شد! به‌زودی با شما تماس می‌گیریم.\nبرای تسریع در فرایند، از طریق لینک زیر ثبت‌نام کنید:\n$botId",
                        $inlineKeyboard
                    );

                    Telegraph::deleteUserStateFromDB($chatId, $botName);

                } else {
                    Telegraph::sendMessage($chatId, "لطفاً یکی از گزینه‌های موجود را انتخاب کنید.");
                }

                break;

            case 'come_back_message':
                Telegraph::sendMessage(
                    $chatId,
                    "💡 یک مرحله تا دریافت اعتبار رایگان!\n\n" .
                    "✔️ جهت دریافت اعتبار رایگان فارابی، مراحل ثبت درخواست خود را تکمیل کنید.\n\n" .
                    "برای شروع مجدد روی /start بزنید."
                );

                break;

            default:
                Telegraph::sendMessage($chatId, "🚀 لطفاً دستور /start را ارسال کنید.");
                break;
        }
    }


    public function crm()
    {
        $data = SqlSrv::getInstance()->raw("SELECT * FROM credit WHERE sent_at is null");

        foreach ($data as $datum) {

            $name = $datum['fullname'];
            $number = Helper::persianToEnglish($datum['number']);
            $portfoy = $datum['portfolioValue'];
            $last_transaction = $datum['last_transaction'];
            $origin = $datum['origin'];

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
