<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;



class FelezController
{

    public function crm()
    {
        $db = Db::getInstance();
        $data = $db->query("SELECT * FROM felezfund WHERE sent_at IS NULL");

        if ($data) {
            foreach ($data as $datum) {
                $name = $datum['name'];
                $number = Helper::persianToEnglish($datum['number']);

                Helper::reqCRM('fo', 'felezfund', [
                    'MobileNumber'   => $number,
                    'Id'             => '6050',
                    'Title'          => 'فالو اپ صندوق فلز',
                    'FullName'       => $name,
                ]);
            }
        }
    }
}