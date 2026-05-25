<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;
use Classes\SqlSrv;



class FelezController
{

    public function crm()
    {
        $data = SqlSrv::getInstance()->raw("SELECT * FROM felezfund WHERE sent_at IS NULL");

        if ($data) {
            foreach ($data as $datum) {
                $name = $datum['fullname'];
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