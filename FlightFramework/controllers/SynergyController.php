<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;
use Classes\Telegraph;


class SynergyController
{
    public function crm()
    {
        $db = Db::getInstance();
        $data = $db->query("SELECT * FROM synergy_v2 WHERE sent_at IS NULL LIMIT 1");

        if ($data) {
            foreach ($data as $datum) {
                $name = $datum['name'];
                $number = Helper::persianToEnglish($datum['number']);

                Helper::reqCRM('op', 'synergy_v2', [
                    'MobileNumber'     => $number,
                    'FullName'         => $name,
                    'CustomerNeedID'   => 4031,
                    'QuestionCategory' => 21561,
                    'Topic'            => 'درخواست مشاوره سینرژی',
                ]);
            }
        }


    }
}
