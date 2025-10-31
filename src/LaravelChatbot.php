<?php

namespace EmmanuelSaleem\LaravelChatbot;

class LaravelChatbot
{
    public function message(string $input): string
    {
        return "🤖 " . config('laravel-chatbot.bot_name') . " says: You said '{$input}'";
    }
}
