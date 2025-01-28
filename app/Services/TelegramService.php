<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

//service for telegram API
class TelegramService
{
    protected $botToken;
    protected $chatId;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
        $this->chatId = env('TELEGRAM_CHAT_ID');
    }

    public function sendMessage($message)
    {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $response = Http::post($url, [
            'chat_id' => $this->chatId,
            'text' => $message,
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to send message to Telegram');
        }

        return $response->json();
    }
}
