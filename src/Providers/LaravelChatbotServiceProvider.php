<?php

namespace EmmanuelSaleem\LaravelChatbot\Providers;

use Illuminate\Support\ServiceProvider;

class LaravelChatbotServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-chatbot.php', 'laravel-chatbot');

        $this->app->singleton('laravel-chatbot', function () {
            return new \EmmanuelSaleem\LaravelChatbot\LaravelChatbot;
        });

        // Register services
        $this->app->singleton(
            \EmmanuelSaleem\LaravelChatbot\Services\Bot\BotQuestionMatcherService::class
        );

        $this->app->singleton(
            \EmmanuelSaleem\LaravelChatbot\Services\Bot\VariableSubstitutionService::class
        );
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'laravel-chatbot');

        // Publish migrations
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../database/migrations' => database_path('migrations'),
            ], 'laravel-chatbot-migrations');

            // Publish config
            $this->publishes([
                __DIR__ . '/../../config/laravel-chatbot.php' => config_path('laravel-chatbot.php'),
            ], 'laravel-chatbot-config');
        }
    }
}
