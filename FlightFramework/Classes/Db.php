<?php
namespace Classes;


use PDO;
use PDOException;

/**
 * @property \PDO $conn
 */
class Db
{

    private static $conn = '';

    function __construct()
    {
        $this->connect();
    }

    function connect()
    {
        if (empty($this->conn)) {
            try {
                $servername = $_ENV['DB_HOST'];
                $username   = $_ENV['DB_USER'];
                $password   = $_ENV['DB_PASS'];
                $dbname     = $_ENV['DB_NAME'];
                $attr = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'];
                self::$conn = new PDO('mysql:host=' . $servername . ';dbname=' . $dbname, $username, $password, $attr);
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                echo 'error call administrator';
            }
        }
    }

    function select($sql, $values = array(), $fetch = '', $fetchStyle = PDO::FETCH_ASSOC)
    {
        $stmt = self::$conn->prepare($sql);
        foreach ($values as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }
        $stmt->execute();
        if ($fetch == '') {
            $result = $stmt->fetchAll($fetchStyle);
        } else {
            $result = $stmt->fetch($fetchStyle);
        }
        return $result;
    }

    function query($sql, $values = array(), $Transaction = 0, $commit = 0)
    {
        $stmt = self::$conn->prepare($sql);

        if ($Transaction == 1) {
            self::$conn->beginTransaction();
        }

        foreach ($values as $key => $value) {
            $stmt->bindValue($key + 1, $value);
        }

        $stmt->execute();
        return true;
    }


	function first($sql, $values = array(), $fetchStyle = PDO::FETCH_ASSOC)
	{
		$stmt = self::$conn->prepare($sql);

		foreach ($values as $key => $value) {
			$stmt->bindValue($key + 1, $value);
		}

		$stmt->execute();
		return $stmt->fetch($fetchStyle);
	}


    function insert($sql, $values = array())
    {
        $stmt = self::$conn->prepare($sql);

        foreach ($values as $key => $value) {
            if (is_string($key)) {
                $stmt->bindValue(':' . $key, $value);
            } else {
                $stmt->bindValue($key + 1, $value);
            }
        }

        $stmt->execute();
        $lastId = self::$conn->lastInsertId();
        return $lastId;
    }

    public function conn()
    {
        return self::$conn;
    }

 
}