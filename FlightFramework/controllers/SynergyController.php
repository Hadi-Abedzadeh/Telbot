<?php

namespace controllers;

use Classes\Db;
use Classes\Helper;
use Classes\SqlSrv;
use Classes\Telegraph;


class SynergyController
{

    private $botName = 'synergy';
    public function crm()
    {
        exit('check she ghable bahre bardari');
        $data = SqlSrv::getInstance()->raw("SELECT * FROM [$this->botName] WHERE sent_at IS NULL");

        if ($data) {
            foreach ($data as $datum) {
                $name = $datum['name'];
                $number = Helper::persianToEnglish($datum['number']);

                Helper::reqCRM('op', $this->botName, [
                    'MobileNumber'     => $number,
//                    'FullName'         => $name,
                    'CustomerNeedID'   => 4031,
                    'QuestionCategory' => 21561,
                    'Topic'            => 'درخواست مشاوره سینرژی',
                ]);
            }
        }


    }
}
