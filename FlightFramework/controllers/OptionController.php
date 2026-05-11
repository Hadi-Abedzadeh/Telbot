<?php

namespace controllers;

class OptionController
{
    public function index() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);


        $update = json_decode(file_get_contents("php://input"), true);

        $message = $update['message'] ?? null;
        if (!$message) return;

        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        switch ($text) {
            case '/start':
                $keyboard = [
                    [['text' => "قیمت فردایی اهرم"]],
//                    [['text' => "تعداد موقعیت‌های باز باقی‌مانده‌ی اهرم"]],
//                    [['text' => "قابلیت صدور واحدهای صندوق‌ها"]]
                ];

                $this->sendMessage($chatId, "یک نماد را انتخاب کنید:", $keyboard);
                break;

            case "قیمت فردایی اهرم":
                $pricing = $this->zAhrom();

                $messageLines = [
                    $this->getLine('', "قیمت فردایی اهرم", $this->convertToPersianNumbers(number_format($pricing[0]))),
                    $this->getLine('', "قیمت آخرین اهرم", $this->convertToPersianNumbers(number_format($pricing[2]))),
                    $this->getLine('', "فاصله درصدی", $this->convertToPersianNumbers($pricing[1]). "\n"),
                    $this->getLine('', "آخرین به‌روزرسانی", $this->convertToPersianNumbers($pricing[3]))
                ];

                $this->sendMessage($chatId, implode("\n", $messageLines)."\n\n@optionsho_bot");
                break;

            case "تعداد موقعیت‌های باز باقی‌مانده‌ی اهرم":

                $this->sendMessage($chatId, "درحال حاضر این بخش غیر فعال میباشد");
                break;


                try {
                    $positions = $this->remainedPositions2();

                    $filePath = getcwd()."/001.png";

                    $this->createImage($positions, $filePath);

                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage();
                }

                $this->sendPhoto($chatId, $filePath);
                unlink($filePath);

                break;
            case "قابلیت صدور واحدهای صندوق‌ها":
                $funds = $this->fundsNav();

                function persianToFinglish($text) {
                    $map = [
                        'آ' => 'A', 'ا' => 'A', 'ب' => 'B', 'پ' => 'P', 'ت' => 'T', 'ث' => 'S',
                        'ج' => 'J', 'چ' => 'Ch', 'ح' => 'H', 'خ' => 'Kh', 'د' => 'D', 'ذ' => 'Z',
                        'ر' => 'R', 'ز' => 'Z', 'ژ' => 'Zh', 'س' => 'S', 'ش' => 'Sh', 'ص' => 'S',
                        'ض' => 'Z', 'ط' => 'T', 'ظ' => 'Z', 'ع' => 'A', 'غ' => 'Gh', 'ف' => 'F',
                        'ق' => 'Gh', 'ک' => 'K', 'گ' => 'G', 'ل' => 'L', 'م' => 'M', 'ن' => 'N',
                        'و' => 'V', 'ه' => 'H', 'ی' => 'Y', 'ء' => "'", 'َ' => 'A', 'ُ' => 'o', 'ِ' => 'e'
                    ];

                    return strtr($text, $map);
                }

                function convertArrayToFinglish($array) {
                    $convertedArray = [];
                    foreach ($array as $key => $value) {
                        $newKey = persianToFinglish($key);
                        if (is_array($value)) {
                            $convertedArray[$newKey] = convertArrayToFinglish($value);
                        } else {
                            $convertedArray[$newKey] = persianToFinglish($value);
                        }
                    }
                    return $convertedArray;
                }

                $funds = convertArrayToFinglish($funds);



                $filePath = "funds.png";

                $image = imagecreate(500, 300); // یک تصویر موقت ایجاد کنید
                $background = imagecolorallocate($image, 255, 255, 255);
                $textColor = imagecolorallocate($image, 0, 0, 0);

                imagestring($image, 5, 10, 10, json_encode($funds, JSON_UNESCAPED_UNICODE), $textColor);
                imagepng($image, $filePath);
                imagedestroy($image);

                $this->sendPhoto($chatId, $filePath);
                $this->sendMessage($chatId, json_encode($funds, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                break;
            default:
                $this->sendMessage($chatId, "
                
                دستور نامعتبر است.\n
                /قیمت فردایی اهرم
                
                ");
                break;
        }
    }

    public function sendPhoto($chatId, $photoPath, $caption = "") {
        $data = [
            'chat_id' => $chatId,
            'caption' => $caption
        ];

        $postFields = ['photo' => new \CURLFile($photoPath)];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, generateTelegramApiUrl() . "sendPhoto?" . http_build_query($data));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_exec($ch);
        curl_close($ch);
    }

    public function getLinked($title, $url) {
        return "<a href=\"$url\">$title</a>";
    }

    public function getLine($emoji, $title, $value, $link = null) {
        $linkedText = $link ? $this->getLinked("(بیشتر)", $link) : "";
        return "$emoji $title: $value $linkedText";
    }

    public function convertToPersianNumbers($value) {
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($englishNumbers, $persianNumbers, $value);
    }

    public function fetchData($url, $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
//            echo "Request Error: " . curl_error($ch);
            return null;
        }
        curl_close($ch);
        return json_decode($response, true);
    }

    public function zahromLastPrice() {
        $url = "https://landing.irfarabi.com/bot/zAhrom.php?read=true";
        $data = $this->fetchData($url);

        return $data['result'] ?? null;
    }

    public function remainedPositions() {
        $url = "http://79.127.54.62:8000/optionsmonitoring/options/remainedpositions/";
        $data = $this->fetchData($url);
        if ($data) {
            foreach ($data as &$row) {
                $row['diff'] = ($row['diff']);
            }
            return $data;
        }
        return [];
    }

    public function remainedPositions2()
    {
        $url = "http://79.127.54.62:8000/optionsmonitoring/options/remainedpositions/";
        $headers = [
            'accept: application/json',
            'Cookie: csrftoken=mwTXq782RjMU9Ncz3uC0zz3noq9L45ow',
        ];

        // تنظیم درخواست با cURL
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode === 200) {
            $data = json_decode($response, true); // تبدیل به آرایه PHP

            if ($data) {
                // پردازش داده‌ها
                foreach ($data['diff'] as $key => $value) {
                    $data['diff'][$key] = number_format($value); // تبدیل به فرمت اعداد
                }

                // تغییر نام کلیدها
                $renamedKeys = [
                    'remainedDay' => 'روز مانده تا سررسید',
                    'max_open_interest' => 'بیشینه موقعیتهای باز',
                    'max_possible_open_interest' => 'سقف موقعیتها',
                    'diff' => 'تعداد موقعیت‌های مانده',
                    'diffpercent' => 'درصد موقعیتهای مانده',

                ];

                $renamedKeys = [
                    'remainedDay'                => 'remainedDay',
                    'max_open_interest'          => 'max_open_interest',
                    'max_possible_open_interest' => 'max_possible',
                    'diff'                       => 'diff',
                    'diffpercent'                => 'diff percent',
                ];


                // ساخت آرایه خروجی به‌صورت ستونی
                $output = [];
                foreach ($renamedKeys as $key => $name) {
                    $output[$name] = isset($data[$key]) ? $data[$key] : [];
                }

                return $output; // بازگشت آرایه پردازش‌شده
            }
        }

        // بازگشت پیام خطا در صورت عدم موفقیت
        return ["error" => "Failed to fetch data or process response."];
    }

    function createImage($data, $filePath)
    {
        $fontPath = getcwd().'/font.ttf';
        $fontSize = 12;
        $lineHeight = 20;
        $margin = 20;
        $columnSpacing = 150;

        $numColumns = count($data);

        $numRows = count(reset($data));

        $imageWidth = $margin * 2 + $numColumns * $columnSpacing;
        $imageHeight = $margin * 2 + ($numRows + 1) * $lineHeight;

        $image = imagecreatetruecolor($imageWidth, $imageHeight);

        $backgroundColor = imagecolorallocate($image, 255, 255, 255); // سفید
        $textColor = imagecolorallocate($image, 0, 0, 0); // سیاه
        $rowColor1 = imagecolorallocate($image, 230, 230, 230); // خاکستری روشن
        $rowColor2 = imagecolorallocate($image, 255, 255, 255); // سفید

// رسم پس‌زمینه کلی تصویر
        imagefilledrectangle($image, 0, 0, $imageWidth, $imageHeight, $backgroundColor);

        $x = $margin;
        foreach ($data as $columnName => $values) {
            $y = $margin + $lineHeight;

            // رسم عنوان ستون
            imagettftext($image, $fontSize, 0, $x, $margin, $textColor, $fontPath, $columnName);

            // رسم داده‌ها
            foreach ($values as $rowIndex => $value) {
                // انتخاب رنگ ردیف به صورت یکی در میان
                $rowColor = ($rowIndex % 2 === 0) ? $rowColor1 : $rowColor2;

                // رسم پس‌زمینه ردیف
                imagefilledrectangle(
                    $image,
                    $x - $margin,
                    $y - $lineHeight + 5, // تنظیم دقیق‌تر موقعیت
                    $x + $columnSpacing - $margin,
                    $y + 5,
                    $rowColor
                );

                // نوشتن مقدار
                imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, number_format((float)$value, 0, '.', ','));

                $y += $lineHeight;
            }

            $x += $columnSpacing;
        }

        imagepng($image, $filePath);
        imagedestroy($image);
    }

    public function zAhrom() {
        $maturity = new \DateTime('2025-05-21');
        $remainday = $maturity->diff(new \DateTime())->days;
        $strikePrice = 28000;
        $lastPriceData = $this->zahromLastPrice();
        $lastPrice = $lastPriceData['pDrCotVal'] ?? 0;

        $result = $this->REVERSE_BS_CALL($lastPrice, $strikePrice, $remainday / 365);
        $ahrom = $lastPriceData['AhrompClosing'] ?? 0;
        $percentChange = $ahrom ? (($result / $ahrom - 1) * 100) : 0;

        return [$result, round($percentChange, 2), $ahrom, $lastPriceData['updated_at']];
    }

    public function fundsNav() {
        $url = "http://79.127.54.62:8000/optionsmonitoring/options/fund_nav/";
        return $this->fetchData($url);
    }

    public function sendMessage($chatId, $text, $keyboard = null) {

        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        if ($keyboard) {
            $data['reply_markup'] = json_encode(['keyboard' => $keyboard, 'resize_keyboard' => true]);
        }
        file_get_contents(generateTelegramApiUrl() . "sendMessage?" . http_build_query($data));
    }

    private static function erf($x) {
        // Constants
        $a1 =  0.254829592;
        $a2 = -0.284496736;
        $a3 =  1.421413741;
        $a4 = -1.453152027;
        $a5 =  1.061405429;
        $p  =  0.3275911;

        // Save the sign of x
        $sign = ($x < 0) ? -1 : 1;
        $x = abs($x);

        // A&S formula 7.1.26
        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - (((((($a5 * $t + $a4) * $t) + $a3) * $t) + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return $sign * $y;
    }

    public static function normCdf($x) {
        return 0.5 * (1.0 + self::erf($x / sqrt(2.0)));
    }

    public static function BS_CALL($S, $K, $T, $r, $sigma) {
        $d1 = (log($S / $K) + ($r + pow($sigma, 2) / 2) * $T) / ($sigma * sqrt($T));
        $d2 = $d1 - $sigma * sqrt($T);
        $N_d1 = self::normCdf($d1);
        $N_d2 = self::normCdf($d2);

        return $S * $N_d1 - $K * exp(-$r * $T) * $N_d2;
    }

    public static function REVERSE_BS_CALL($p, $K, $T, $r = 0.36, $sigma = 0.36, $alpha = 10) {
        $s = $K + 10 * $alpha;
        $err1 = round(self::BS_CALL($s, $K, $T, $r, $sigma), 0) - $p;

        if ($err1 > 0) {
            $alpha = -1 * $alpha;
        }

        $s += 10 * $alpha;
        $err2 = round(self::BS_CALL($s, $K, $T, $r, $sigma), 0) - $p;

        while ($err1 * $err2 > 0) {
            $s += 10 * $alpha;
            $err1 = $err2;
            $err2 = round(self::BS_CALL($s, $K, $T, $r, $sigma), 0) - $p;
        }

        $prev_s = $s - 10 * $alpha;
        $err = abs(round(self::BS_CALL($prev_s, $K, $T, $r, $sigma), 0) - $p);

        if (abs($err) < abs($err2)) {
            $best = $prev_s;
            $minimum = abs($err);
        } else {
            $best = $s;
            $minimum = abs($err2);
        }

        for ($i = min($s, $prev_s); $i <= max($s, $prev_s); $i += abs($alpha)) {
            $err = abs(round(self::BS_CALL($i, $K, $T, $r, $sigma), 0) - $p);
            if ($err < abs($minimum)) {
                $minimum = $err;
                $best = $i;
            }
        }

        return $best;
    }

}