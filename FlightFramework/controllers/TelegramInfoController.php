<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;
use Classes\Telegraph;
// ALISH

class TelegramInfoController
{

    public function index()
    {
        $channel = \Flight::request()->query['channel'] ?? '';
        $postCode = \Flight::request()->query['postCode'] ?? '';
        $postId = \Flight::request()->query['postId'] ?? '';
        $token = \Flight::request()->query['token'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

        try {
            $token = trim(preg_replace('/^Bearer\s+/i', '', $token));
            $validTokens = ['pW8fQ32bMdK7AzCjgvx9Lu6YhN1TW0Ee'];
            if (empty($token) || !in_array($token, $validTokens, true)) {
                throw new \RuntimeException("توکن نامعتبر است یا ارائه نشده.", 401);
            }

            if (!preg_match('/^[a-zA-Z0-9_]+$/', $channel)) {
                throw new \InvalidArgumentException("کانال نامعتبر است.");
            }
            if (!preg_match('/^\d+$/', $postCode)) {
                throw new \InvalidArgumentException("آیدی پست نامعتبر است.");
            }

            $url = "https://t.me/$channel/$postCode?embed=1";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Linux; Android 10; Mobile)',
                CURLOPT_HTTPHEADER => [
                    'x-requested-with: XMLHttpRequest',
                    "referer: $url",
                ],
            ]);
            $html = curl_exec($ch);
            $err = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            if ($html === false || $errno !== 0 || strpos($html, '<title>Telegram Widget</title>') === false) {
                throw new \RuntimeException("دریافت HTML با مشکل مواجه شد. $err", 500);
            }

            $extract = function (string $pattern, callable $handler) use ($html) {
                if (preg_match($pattern, $html, $matches)) {
                    return $handler($matches[1]);
                }
                return is_int($handler('')) ? 0 : '';
            };

            $isNotFound = preg_match('/Post not found/i', $html) === 1;

            if ($isNotFound) {
                \Flight::json([
                    'status' => 200,
                    'data' => [
                        'channel' => $channel,
                        'postCode' => $postCode,
                        'postId' => $postId,
                        'messageText' => '',
                        'views' => 0,
                        'date' => '',
                        'isFound' => false,
                    ]
                ]);
            } else {
                $messageText = $extract(
                    '/<div class="tgme_widget_message_text[^>]*"[^>]*>(.*?)<\/div>/s',
                    fn($v) => trim(strip_tags(html_entity_decode($v)))
                );

                $views = $extract(
                    '/<span class="tgme_widget_message_views">([\d\.KM]+)<\/span>/',
                    function ($v) {
                        $v = strtoupper(trim($v));
                        if (preg_match('/^([\d\.]+)([KMB]?)$/', $v, $m)) {
                            $num = (float)$m[1];
                            return match ($m[2]) {
                                'K' => $num * 1_000,
                                'M' => $num * 1_000_000,
                                'B' => $num * 1_000_000_000,
                                default => (int)$num,
                            };
                        }
                        return 0;
                    }
                );

                $date = $extract(
                    '/<time[^>]*datetime="([^"]+)"/',
                    fn($v) => $v
                );

                \Flight::json([
                    'status' => 200,
                    'data' => [
                        'channel' => $channel,
                        'postCode' => $postCode,
                        'postId' => $postId,
                        'messageText' => $messageText,
                        'views' => $views,
                        'date' => $date,
                        'isFound' => true,
                    ]
                ]);
            }
        } catch (\Exception $e) {
            \Flight::json([
                'status' => $e->getCode() ?: 400,
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }
}