<?php

namespace controllers;

use PDO;
use PDOException;
use Exception;
use Flight;

class TelegramGoldStatisticController
{
    private $allowed_ips = [
        '79.137.75.159', // n8n IP
        '2.188.164.226',
        '80.210.18.213'
    ];

    private $valid_tokens = [
        'otKVwYNEOAIwcHAJIJOONavtXUqfKL9ZLMlDid7V'
    ];

    private $db;

    public function __construct()
    {
        // اتصال به دیتابیس
        try {
//            $this->db = new PDO("mysql:host=landing.irfarabi.com;dbname=landing_flight", 'landing_remote', 'v7FjgZwXbWbHcXp3qpha');
//            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//            $this->db->exec("SET NAMES 'utf8'");
        } catch (PDOException $e) {
//            $this->sendError('خطا در اتصال به پایگاه داده: ' . $e->getMessage(), 500);
        }
    }

    // ================== ✅ احراز هویت ==================
    private function authenticate()
    {
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!in_array($client_ip, $this->allowed_ips)) {
            $this->sendError('دسترسی از این IP مجاز نیست', 403);
        }

        $token = $this->getToken();
        if (!$token || !in_array($token, $this->valid_tokens)) {
//            $this->sendError('توکن معتبر نیست', 401);
        }
    }

    private function getToken()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization' && preg_match('/Bearer\s(\S+)/', $value, $matches)) {
                return $matches[1];
            }
        }
        return $_GET['token'] ?? null;
    }

    // ================== 🧮 متدهای آماری ==================
    private function getUsersByDate($date, $userStatus)
    {

        $this->validateDate($date);

        if ($userStatus === 'temp') {
            $sql = "SELECT COUNT(*) AS total_count FROM bot_user_states WHERE bot_name = 'gold' AND DATE(created_at) = :date";
        } elseif ($userStatus === 'completed') {
            $sql = "SELECT COUNT(*) AS total_count FROM gold WHERE origin = 'Telegram' AND DATE(created_at) = :date";
        } else {
            throw new Exception('وضعیت فرم نامعتبر است');
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'date' => $date,
            'count' => (int) $result['total_count']
        ];
    }

    private function getUsersByDateRange($startDate, $endDate, $userStatus)
    {
        $this->validateDate($startDate);
        $this->validateDate($endDate);

        if ($userStatus === 'temp') {
            $sql = "SELECT DATE(created_at) AS date, COUNT(*) AS count
                    FROM bot_user_states
                    WHERE bot_name = 'gold' AND DATE(created_at) BETWEEN :start_date AND :end_date
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";
        } elseif ($userStatus === 'completed') {
            $sql = "SELECT DATE(created_at) AS date, COUNT(*) AS count
                    FROM gold
                    WHERE origin = 'Telegram' AND DATE(created_at) BETWEEN :start_date AND :end_date
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";
        } else {
            throw new Exception('وضعیت فرم نامعتبر است');
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start_date', $startDate, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $endDate, PDO::PARAM_STR);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total = array_sum(array_column($results, 'count'));

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_count' => $total,
            'daily_stats' => $results
        ];
    }

    private function validateDate($date)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new Exception('فرمت تاریخ نامعتبر است');
        }
    }

    // ================== 🎯 کنترلر اصلی ==================
    public function index()
    {
        try {
            $this->authenticate();

            $action = $_GET['action'] ?? 'yesterday';
            $date = $_GET['date'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $formStatus = $_GET['form_status'] ?? null;

            $allowedStatuses = ['completed', 'temp'];
            if (empty($formStatus) || !in_array($formStatus, $allowedStatuses)) {
                throw new Exception('پارامتر form_status الزامی است');
            }

            switch ($action) {
                case 'yesterday':
                    $response = $this->getUsersByDate(date('Y-m-d', strtotime('-1 day')), $formStatus);
                    break;
                case 'date':
                    if (!$date) throw new Exception('پارامتر date الزامی است');
                    $response = $this->getUsersByDate($date, $formStatus);
                    break;
                case 'date_range':
                    if (!$startDate || !$endDate) throw new Exception('پارامترهای start_date و end_date الزامی هستند');
                    $response = $this->getUsersByDateRange($startDate, $endDate, $formStatus);
                    break;
                default:
                    throw new Exception('عملیات نامعتبر است');
            }

            Flight::json([
                'success' => true,
                'data' => $response
            ]);

        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    // ================== ⚠️ هندلر خطا ==================
    private function sendError($message, $code = 400)
    {
        Flight::json([
            'success' => false,
            'message' => $message
        ], $code);
        exit;
    }
}
