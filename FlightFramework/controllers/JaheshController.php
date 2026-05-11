<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;



class JaheshController
{

    public function crm()
    {
        $db = Db::getInstance();
        $data = $db->query("SELECT * FROM jaheshfund WHERE sent_at IS NULL");

        if ($data) {
            foreach ($data as $datum) {
                $name = $datum['name'];
                $number = Helper::persianToEnglish($datum['number']);

                Helper::reqCRM('op', 'jaheshfund', [
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