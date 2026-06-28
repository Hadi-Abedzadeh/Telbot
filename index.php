<?php

require 'FlightFramework/flight/Flight.php';

use \controllers\CreditController;
use \controllers\CommonController;
use \controllers\GoldController;
use \Classes\Helper;
use \controllers\Bale\SolarController;

date_default_timezone_set('Asia/Tehran');
set_time_limit(180);


Flight::before('start', function () {
    Helper::env(__DIR__ . '/.env');
});

Flight::route('GET /', function () {
    \Flight::json(['Working']);
});

$creditController = new CreditController;
Flight::route('POST /credit', [$creditController, 'index']);
Flight::route('GET /credit/crm', [$creditController, 'crm']);

$commonController = new CommonController;
Flight::route('GET /common/leaved/@bot_name', [$commonController, 'leaved']);
Flight::route('GET /common/notCompleted/@bot_name', [$commonController, 'addToDb']);

$goldController = new GoldController();
Flight::route('POST /gold', [$goldController, 'index']);
Flight::route('GET /gold/crm', [$goldController, 'crm']);

$solarController = new SolarController();
Flight::route('POST /bale/solar', [$solarController, 'index']);

Flight::start();
