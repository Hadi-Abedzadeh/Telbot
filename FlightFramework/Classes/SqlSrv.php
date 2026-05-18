<?php
namespace Classes;

class SqlSrv
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        $this->conn = sqlsrv_connect($_ENV['DB_HOST'], [
            'Database'     => $_ENV['DB_NAME'],
            'Uid'          => $_ENV['DB_USER'],
            'PWD'          => $_ENV['DB_PASS'],
            'CharacterSet' => "UTF-8"
        ]);

        if (!$this->conn) {
            die(print_r(sqlsrv_errors(), true));
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function raw($sql, $params = [])
    {
        try {

            $stmt = sqlsrv_query($this->conn, $sql, $params);

            if ($stmt === false) {
                throw new \Exception(print_r(sqlsrv_errors(), true));
            }

            $queryType = strtolower(strtok(trim($sql), " "));

            switch ($queryType) {

                case 'select':

                    $data = [];

                    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                        $data[] = $row;
                    }

                    sqlsrv_free_stmt($stmt);

                    return $data;

                case 'insert':

                    $affected = sqlsrv_rows_affected($stmt);

                    sqlsrv_free_stmt($stmt);

                    return [
                        'success' => true,
                        'type' => 'insert',
                        'affected_rows' => $affected
                    ];

                case 'update':

                case 'delete':

                    $affected = sqlsrv_rows_affected($stmt);

                    sqlsrv_free_stmt($stmt);

                    return [
                        'success' => true,
                        'type' => $queryType,
                        'affected_rows' => $affected
                    ];

                default:

                    sqlsrv_free_stmt($stmt);

                    return [
                        'success' => true,
                        'type' => 'query'
                    ];
            }

        } catch (\Throwable $e) {

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sql' => $sql
            ];
        }
    }

    public function first($sql, $params = [])
    {
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
}