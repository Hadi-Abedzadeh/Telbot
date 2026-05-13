<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;
use Classes\SqlSrv;


class JaheshController
{

    private $botName = 'jaheshfund';
    public function crm()
    {
        $data = SqlSrv::getInstance()->raw("SELECT * FROM [$this->botName] WHERE sent_at IS NULL");

        if ($data) {
            foreach ($data as $datum) {
                $name = $datum['fullname'];
                $number = Helper::persianToEnglish($datum['number']);

                Helper::reqCRM('op', $this->botName, [
                    'MobileNumber' => $number,
                    'FullName' => $name,
                    'CustomerNeedID' => '9150',
                    'QuestionCategory' => '9150',
                    'Topic' => 'مارکتینگ – معرفی صندوق جهش',
                ]);
            }
        }
    }
}