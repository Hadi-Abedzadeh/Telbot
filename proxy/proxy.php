<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/
const BASE_IRAN_HANDLER = 'https://iranhandler.com/';
const TOKENS_FILE = __DIR__ . '/tokens.json';
const SECRET_KEY = 'MY_SECRET';

/*
|--------------------------------------------------------------------------
| Bootstrap
|--------------------------------------------------------------------------
*/
try {
    $botName = getBotNameFromRequest();

    if ($botName === 'UPDATE') {
        handleTokensUpdate();
    }

    $bots = loadBotTokens();
    $token = getBotToken($botName, $bots);

    $rawInput = file_get_contents('php://input');
    if ($rawInput === false || trim($rawInput) === '') {
        jsonResponse([
            'ok' => false,
            'error' => 'Empty or unreadable Telegram payload',
        ], 400);
    }

    $telegramUpdate = json_decode($rawInput, true);
    if (!is_array($telegramUpdate)) {
        jsonResponse([
            'ok' => false,
            'error' => 'Invalid Telegram payload',
        ], 400);
    }

    $handlerResponse = forwardToIranHandler($botName, $rawInput);
    $messagePayload = validateIranHandlerResponse($handlerResponse);

    $telegramResponse = sendTelegramMessage($token, $messagePayload);

    http_response_code($telegramResponse['status_code']);
    echo $telegramResponse['body'];

} catch (Throwable $e) {
    jsonResponse([
        'ok' => false,
        'error' => $e->getMessage(),
    ], 500);
}

/*
|--------------------------------------------------------------------------
| Main Helpers
|--------------------------------------------------------------------------
*/

function getBotNameFromRequest(): string
{
    $botName = $_GET['bot_name'] ?? null;
    $botName = is_string($botName) ? strtoupper(trim($botName)) : '';

    if ($botName === '') {
        jsonResponse([
            'ok' => false,
            'error' => 'Bot name is required',
        ], 400);
    }

    return $botName;
}

function handleTokensUpdate(): void
{
    $url = rtrim(BASE_IRAN_HANDLER, '/') . '/tokens';
    $response = httpGet($url);

    if ($response['status_code'] < 200 || $response['status_code'] >= 300) {
        jsonResponse([
            'ok' => false,
            'error' => 'Failed to fetch tokens from remote handler',
            'status_code' => $response['status_code'],
            'response' => $response['body'],
        ], 502);
    }

    $written = file_put_contents(TOKENS_FILE, $response['body']);
    if ($written === false) {
        jsonResponse([
            'ok' => false,
            'error' => 'Failed to write tokens file',
            'path' => TOKENS_FILE,
        ], 500);
    }

    jsonResponse([
        'ok' => true,
        'message' => 'Tokens updated successfully',
        'path' => TOKENS_FILE,
    ]);
}

function loadBotTokens(): array
{
    if (!file_exists(TOKENS_FILE)) {
        jsonResponse([
            'ok' => false,
            'error' => 'tokens.json not found',
            'path' => TOKENS_FILE,
        ], 500);
    }

    $content = file_get_contents(TOKENS_FILE);
    if ($content === false) {
        jsonResponse([
            'ok' => false,
            'error' => 'Failed to read tokens file',
            'path' => TOKENS_FILE,
        ], 500);
    }

    $json = json_decode($content, true);
    if (!is_array($json) || !isset($json['data']) || !is_string($json['data'])) {
        jsonResponse([
            'ok' => false,
            'error' => 'Invalid tokens file format',
        ], 500);
    }

    $tokens = decryptData($json['data'], SECRET_KEY);

    if (!is_array($tokens)) {
        jsonResponse([
            'ok' => false,
            'error' => 'Failed to decrypt tokens data',
        ], 500);
    }

    return $tokens;
}

function getBotToken(string $botName, array $bots): string
{
    if (!isset($bots[$botName]) || !is_string($bots[$botName]) || trim($bots[$botName]) === '') {
        jsonResponse([
            'ok' => false,
            'error' => 'Bot not found',
            'bot_name' => $botName,
        ], 404);
    }

    return $bots[$botName];
}

function forwardToIranHandler(string $botName, string $rawInput): string
{
    $url = rtrim(BASE_IRAN_HANDLER, '/') . '/' . strtolower($botName);

    $response = httpPostJson($url, $rawInput);

    if ($response['curl_error'] !== null) {
        jsonResponse([
            'ok' => false,
            'error' => 'IRAN handler request failed',
            'details' => $response['curl_error'],
        ], 502);
    }

    if ($response['status_code'] < 200 || $response['status_code'] >= 300) {
        jsonResponse([
            'ok' => false,
            'error' => 'IRAN handler returned non-success status',
            'status_code' => $response['status_code'],
            'response' => $response['body'],
        ], 502);
    }

    return $response['body'];
}

function validateIranHandlerResponse(string $responseBody): array
{
    $payload = json_decode($responseBody, true);

    if (!is_array($payload)) {
        jsonResponse([
            'ok' => false,
            'error' => 'Invalid JSON response from Iran handler',
        ], 500);
    }

    if (!isset($payload['chat_id'], $payload['text'])) {
        jsonResponse([
            'ok' => false,
            'error' => 'Iran handler response must contain chat_id and text',
        ], 500);
    }

    $message = [
        'chat_id' => $payload['chat_id'],
        'text' => $payload['text'],
    ];

    $optionalFields = [
        'parse_mode',
        'reply_markup',
        'disable_web_page_preview',
        'disable_notification',
        'reply_to_message_id',
        'protect_content',
    ];

    foreach ($optionalFields as $field) {
        if (array_key_exists($field, $payload)) {
            $message[$field] = is_array($payload[$field])
                ? json_encode($payload[$field], JSON_UNESCAPED_UNICODE)
                : $payload[$field];
        }
    }

    return $message;
}

function sendTelegramMessage(string $token, array $params): array
{
    $url = "https://api.telegram.org/bot{$token}/sendMessage";

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);

    $body = curl_exec($ch);
    $curlError = curl_error($ch) ?: null;
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($body === false) {
        jsonResponse([
            'ok' => false,
            'error' => 'Telegram request failed',
            'details' => $curlError,
        ], 502);
    }

    return [
        'status_code' => $statusCode > 0 ? $statusCode : 200,
        'body' => $body,
    ];
}

/*
|--------------------------------------------------------------------------
| HTTP Helpers
|--------------------------------------------------------------------------
*/

function httpGet(string $url): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
    ]);

    $body = curl_exec($ch);
    $curlError = curl_error($ch) ?: null;
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        'body' => $body === false ? '' : $body,
        'status_code' => $statusCode,
        'curl_error' => $curlError,
    ];
}

function httpPostJson(string $url, string $jsonBody): array
{
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $jsonBody,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonBody),
        ],
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
    ]);

    $body = curl_exec($ch);
    $curlError = curl_error($ch) ?: null;
    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return [
        'body' => $body === false ? '' : $body,
        'status_code' => $statusCode,
        'curl_error' => $curlError,
    ];
}

/*
|--------------------------------------------------------------------------
| Crypto
|--------------------------------------------------------------------------
*/

function decryptData(string $encryptedData, string $key): ?array
{
    $cipher = 'AES-256-CBC';
    $binaryKey = hash('sha256', $key, true);

    $data = base64_decode($encryptedData, true);
    if ($data === false) {
        return null;
    }

    $ivLength = openssl_cipher_iv_length($cipher);
    if ($ivLength === false || strlen($data) <= $ivLength) {
        return null;
    }

    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);

    $decrypted = openssl_decrypt(
        $encrypted,
        $cipher,
        $binaryKey,
        OPENSSL_RAW_DATA,
        $iv
    );

    if ($decrypted === false) {
        return null;
    }

    $decoded = json_decode($decrypted, true);

    return is_array($decoded) ? $decoded : null;
}

/*
|--------------------------------------------------------------------------
| Response Helper
|--------------------------------------------------------------------------
*/

function jsonResponse(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
