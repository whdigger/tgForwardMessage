<?php

declare(strict_types=1);

// PHP 8.2.4+ is required.
// Run via CLI (recommended: `screen php bot.php`) or via web.
// To reduce RAM usage, follow these instructions: https://docs.madelineproto.xyz/docs/DATABASE.html

use danog\MadelineProto\EventHandler\Attributes\Handler;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Plugin\RestartPlugin;
use danog\MadelineProto\EventHandler\SimpleFilter\Incoming;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\SimpleEventHandler;
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

class BasicEventHandler extends SimpleEventHandler
{
    private string $stopWords = '';
    private array $chatIds = [];

    /**
     * Get peer(s) where to report errors.
     */
    public function getReportPeers()
    {
        if ($_ENV['TG_ADMIN']) {
            return [$_ENV['TG_ADMIN']];
        }

        return [];
    }

    /**
     * Returns a set of plugins to activate.
     *
     * See here for more info on plugins: https://docs.madelineproto.xyz/docs/PLUGINS.html
     */
    public static function getPlugins(): array
    {
        return [
            // Offers a /restart command to admins that can be used to restart the bot, applying changes.
            // Make sure to run in a bash while loop when running via CLI to allow self-restarts.
            RestartPlugin::class,
        ];
    }

    /**
     * Returns a list of names for properties that will be automatically saved to the session database (MySQL/postgres/redis if configured, the session file otherwise).
     */
    /*
    public function __sleep(): array
    {
        return ['notifiedChats'];
    }
    */

    /**
     * Initialization logic.
     */
    public function onStart(): void
    {
        if ($_ENV['FILE_STOP_WORDS'] && file_exists($_ENV['FILE_STOP_WORDS'])) {
            $contents = Amp\File\read($_ENV['FILE_STOP_WORDS']);
            $words = explode("\n", $contents);
            array_map('trim', $words);
            $this->stopWords = '(' . implode('|', $words) . ')';
        }

        if ($_ENV['PARSE_CHAT_IDS'] && !empty($_ENV['PARSE_CHAT_IDS'])) {
            $chatIds = explode(',', $_ENV['PARSE_CHAT_IDS']);
            array_map('intval', $chatIds);
            $this->chatIds = $chatIds;
        }
    }

    /**
     * Handle incoming updates from users, chats and channels.
     */
    #[Handler]
    public function handleMessage(Incoming&Message $message): void
    {
        if ($this->canForward($message)) {
            $this->logger("INCOME MESSAGE: {$message->chatId} : {$message->id}");
            $this->messages->forwardMessages(
                silent: false,
                drop_author: true,
                from_peer: $message->chatId,
                id: [$message->id],
                to_peer: $_ENV['PEER_FORWARD'],
            );
        }
    }

    private function canForward(Incoming&Message $message)
    {
        return
            !empty($this->stopWords)
            && !empty($this->chatIds)
            && !empty($_ENV['PEER_FORWARD'])
            && in_array(
                $message->chatId,
                $this->chatIds
            )
            && preg_match("/\b{$this->stopWords}\b/iu", $message->message);
    }
}


BasicEventHandler::startAndLoop('bot.madeline', $settings);
