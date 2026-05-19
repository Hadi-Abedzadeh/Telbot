<?php

require 'FlightFramework/flight/Flight.php';

use \controllers\CreditController;
use \controllers\LabkhandController;
use \controllers\SynergyController;
use \controllers\SalamController;
use \controllers\CommonController;
use \controllers\FelezController;
use \controllers\TelegramInfoController;
use \controllers\JaheshController;
use \controllers\GoldController;
use \controllers\TelegramGoldStatisticController;
use \controllers\Bale\ConsultController;
use \Classes\Helper;

date_default_timezone_set('Asia/Tehran');
set_time_limit(180);


Flight::before('start', function () {
    Helper::env(__DIR__ . '/.env');
});

Flight::route('GET /', function () {
    \Flight::json(['Working']);
});

// Developed by Alish
$telegramInfoController = new TelegramInfoController();
Flight::route('GET /telegram-info', [$telegramInfoController, 'index']);

$telegramGoldStatisticController = new TelegramGoldStatisticController();
Flight::route('GET /telegram/gold/statistics', [$telegramGoldStatisticController, 'index']);

$felezController = new FelezController();
Flight::route('GET /felez/crm', [$felezController, 'crm']);

$creditController = new CreditController;
Flight::route('POST /credit', [$creditController, 'index']);
Flight::route('GET /credit/crm', [$creditController, 'crm']);

$salamController = new SalamController;
Flight::route('GET /salam/crm', [$salamController, 'crm']);

$labkhandController = new LabkhandController;
Flight::route('POST /labkhand', [$labkhandController, 'index']);
Flight::route('GET /labkhand/crm', [$labkhandController, 'crm']);

$synergyController = new SynergyController;
Flight::route('GET /synergy/crm', [$synergyController, 'crm']);

$jaheshController = new JaheshController();
Flight::route('GET /jahesh/crm', [$jaheshController, 'crm']);

$commonController = new CommonController;
Flight::route('GET /common/leaved/@bot_name', [$commonController, 'leaved']);
Flight::route('GET /common/notCompleted/@bot_name', [$commonController, 'addToDb']);

$goldController = new GoldController();
Flight::route('POST /gold', [$goldController, 'index']);
Flight::route('GET /gold/crm', [$goldController, 'crm']);

$baleConsultController = new ConsultController();
Flight::route('POST /bale/consult2', [$baleConsultController, 'index']);
Flight::route('GET /bale/consult/crm', [$baleConsultController, 'crm']);

Flight::start();
