<?php
declare(strict_types=1);

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use Symfony\Component\Dotenv\Dotenv;

if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

$dotenv = new Dotenv();
// loads .env, .env.local, and .env.$APP_ENV.local or .env.$APP_ENV
$dotenv->loadEnv(__DIR__ . '/.env');

$settings = new Settings;
$settings->setAppInfo((new AppInfo)
    ->setApiId((int)$_ENV['TG_API_ID'])
    ->setApiHash($_ENV['TG_API_HASH']));

$madelineProto = new API('bot.sberinvest', $settings);

$madelineProto->botLogin($_ENV['TG_BOT_TOKEN']);
$madelineProto->sendMessage(peer: $_ENV['TG_ADMIN'], message: 'Online test');