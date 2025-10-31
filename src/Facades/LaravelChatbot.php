<?php

namespace EmmanuelSaleem\LaravelChatbot\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelChatbot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-chatbot';
    }
}
