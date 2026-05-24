<?php

namespace Classes;


class Helper
{
    public static function persianToEnglish($input)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '٤', '۵', '٥', '٦', '۶', '۷', '۸', '۹'];
        $english = [ 0 ,  1 ,  2 ,  3 ,  4 ,  4 ,  5 ,  5 ,  6 ,  6 ,  7 ,  8 ,  9 ];
        return str_replace($persian, $english, $input);
    }

    public static function reqCRM($type, $db_name, $payload)
    {
        switch ($type) {
            case 'fo': $url = "https://crmapi.irfarabi.net:444/api/Case/CreateFollowUp"; break;
            case 'op': $url = "https://crmapi.irfarabi.net:444/api/Case/CreateOpportunity"; break;
        }

        if (preg_match('/(تست|test)/i', $payload['FullName'])) {
            return false;
        }

        try
        {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    "Content-Type: application/json",
                    "Authorization: Basic " . $_ENV['CRM_AUTH_TOKEN']
                ],
            ]);

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            $updateData = [
                'number' => $payload['MobileNumber'],
                'crm_response' => curl_errno($curl) ? curl_error($curl) : $response,
                'http_code' => $http_code
            ];

            $query = "UPDATE $db_name SET crm_response = ?, http_code = ?";

            $params = [
                $updateData['crm_response'],
                $updateData['http_code']
            ];

            if (curl_errno($curl)) {
                $query .= ", crm_retry = crm_retry + 1";
            } elseif ($http_code == 200) {
                $query .= ", sent_at = GETDATE()";
            }

            $query .= " WHERE number = ?";
            $params[] = $updateData['number'];
            curl_close($curl);
            SqlSrv::getInstance()->raw($query, $params);

        }
        catch (Exception $e)
        {
            SqlSrv::getInstance()->raw("UPDATE $db_name SET crm_retry = crm_retry + 1, crm_response = ? WHERE number = ?", [
                'exeption: ' . $e->getMessage(),
                $payload['MobileNumber']
            ]);
        }
    }


    public static function generateTelegramApiUrl($currentUrl)
    {
        $tokens = [
            'credit'       => $_ENV['TOKEN_CREDIT'],
            'labkhand'     => $_ENV['TOKEN_LABKHAND'],
            'gold'         => $_ENV['TOKEN_GOLD'],
            'bale/consult' => $_ENV['TOKEN_CONSULT'],
        ];

        foreach ($tokens as $key => $token) {
            if (strpos($currentUrl, $key) !== false) {
                if (strpos($key, 'bale') !== false) {
                    return $_ENV['API_URL_BALE'] . $token . '/';
                } else {
                    return $_ENV['API_URL_TLG']. $token . '/';
                }
            }
        }

        throw new Exception("No matching token found for the provided URL.");
    }

    public static function env($filePath = '.env') {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignore comments and empty lines
            if (str_starts_with(trim($line), '#') || empty(trim($line))) {
                continue;
            }

            // Parse the line into key and value
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes from value if present
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            putenv(sprintf('%s=%s', $key, $value));
        }
        return true;
    }
}