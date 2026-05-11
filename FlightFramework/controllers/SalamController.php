<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;

class SalamController
{
    public function crm()
    {
        $db = Db::getInstance();
        $data = $db->query("SELECT * FROM salam WHERE sent_at IS NULL");

        if ($data) {
            foreach ($data as $datum) {
                $name = $datum['name'];
                $number = Helper::persianToEnglish($datum['number']);

                Helper::reqCRM('op', 'salam', [
                    'MobileNumber'     => $number,
                    'FullName'         => $name,
                    'CustomerNeedID'   => 285,
                    'QuestionCategory' => 285,
                    'Source'           => 'landing',
                    'Topic'            => 'مارکتینگ - درخواست مشاوره لندینگ سلام',
                ]);
            }
        }
    }
}
