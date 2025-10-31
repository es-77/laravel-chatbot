<?php

namespace EmmanuelSaleem\LaravelChatbot\Http\Controllers;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Web\WebDriver;
use EmmanuelSaleem\LaravelChatbot\Middleware\BotQuestionMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BotManController extends Controller
{
    /**
     * Handle web chat requests using BotMan.
     */
    public function handle(Request $request)
    {
        // Load BotMan drivers
        DriverManager::loadDriver(WebDriver::class);

        // Create BotMan instance
        $config = [
            'user_cache_time' => 720,
            'config' => [
                'conversation_cache_time' => 720,
                'user_cache_time' => 720,
            ],
        ];

        $botman = BotManFactory::create($config);

        // Apply our Q&A middleware first
        $botman->middleware(new BotQuestionMiddleware(
            app(\EmmanuelSaleem\LaravelChatbot\Services\Bot\BotQuestionMatcherService::class),
            app(\EmmanuelSaleem\LaravelChatbot\Services\Bot\VariableSubstitutionService::class)
        ));

        // Fallback handler if no Q&A match
        $botman->fallback(function (BotMan $bot) {
            $bot->reply(config('laravel-chatbot.default_response', 'I didn\'t understand that. Can you please rephrase?'));
        });

        // Handle the message
        $botman->listen();

        return response();
    }

    /**
     * Handle web chat requests (legacy endpoint for compatibility).
     * This method works with the custom web chat UI that expects JSON responses.
     */
    public function webChat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        // Build session data for response
        $sessionData = $this->buildSessionData($request);
        $message = $request->input('message');

        // Use the matcher service directly for JSON API response
        $matcherService = app(\EmmanuelSaleem\LaravelChatbot\Services\Bot\BotQuestionMatcherService::class);
        $substitutionService = app(\EmmanuelSaleem\LaravelChatbot\Services\Bot\VariableSubstitutionService::class);

        $matchedQuestion = $matcherService->findBestMatch($message, $sessionData);

        $response = ['text' => '', 'buttons' => []];

        if ($matchedQuestion) {
            $user = Auth::user();
            $response['text'] = $substitutionService->substitute(
                $matchedQuestion->answer,
                $sessionData,
                $user
            );

            if (!empty($matchedQuestion->buttons)) {
                $buttons = $substitutionService->substituteButtons(
                    $matchedQuestion->buttons,
                    $sessionData,
                    $user
                );

                $validButtons = array_filter($buttons, function ($button) {
                    return !empty($button['label']) &&
                           !empty($button['url']) &&
                           $this->isValidUrl($button['url']);
                });

                if (!empty($validButtons)) {
                    $response['buttons'] = array_values($validButtons);
                }
            }
        } else {
            $response['text'] = config('laravel-chatbot.default_response', 'I didn\'t understand that. Can you please rephrase?');
        }

        return response()->json($response);
    }

    /**
     * Build session data from request.
     */
    protected function buildSessionData(Request $request): array
    {
        $sessionData = [];

        // Add user data if authenticated
        if ($user = Auth::user()) {
            $sessionData['user'] = [
                'id' => $user->id,
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
            ];
        }

        // Add session metrics (customize based on your app)
        $sessionData['session'] = [
            'deal_count' => $request->input('session.deal_count', 0),
            // Add more session metrics as needed
        ];

        // Merge any additional session data from request
        if ($request->has('session_data')) {
            $sessionData['session'] = array_merge(
                $sessionData['session'],
                $request->input('session_data', [])
            );
        }

        return $sessionData;
    }

    /**
     * Validate URL.
     */
    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false &&
               (substr($url, 0, 7) === 'http://' || substr($url, 0, 8) === 'https://');
    }
}
