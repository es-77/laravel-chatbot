<?php

namespace EmmanuelSaleem\LaravelChatbot\Http\Controllers\Api;

use EmmanuelSaleem\LaravelChatbot\Http\Controllers\Controller;
use EmmanuelSaleem\LaravelChatbot\Services\Bot\BotQuestionMatcherService;
use EmmanuelSaleem\LaravelChatbot\Services\Bot\VariableSubstitutionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BotMessageController extends Controller
{
    protected $matcherService;
    protected $substitutionService;

    public function __construct(
        BotQuestionMatcherService $matcherService,
        VariableSubstitutionService $substitutionService
    ) {
        $this->matcherService = $matcherService;
        $this->substitutionService = $substitutionService;
    }

    /**
     * Handle POST request to send a message and get bot response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'session_data' => 'sometimes|array',
            'page_url' => 'nullable|string|max:2048',
        ]);

        $message = $request->input('message');
        $sessionData = $this->buildSessionData($request);
        $pageUrl = $request->input('page_url') ?? $request->header('Referer') ?? url()->current();

        // Find matching question
        $matchedQuestion = $this->matcherService->findBestMatch($message, $sessionData, $pageUrl);

        $response = [
            'success' => true,
            'data' => [
                'message' => '',
                'buttons' => [],
                'matched' => false,
            ],
        ];

        if ($matchedQuestion) {
            $user = Auth::guard('sanctum')->user() ?? Auth::user();
            
            // Substitute variables in answer
            $answer = $this->substitutionService->substitute(
                $matchedQuestion->answer,
                $sessionData,
                $user
            );

            $response['data']['message'] = $answer;
            $response['data']['matched'] = true;
            $response['data']['question_id'] = $matchedQuestion->id;

            // Add buttons if available
            if (!empty($matchedQuestion->buttons)) {
                $buttons = $this->substitutionService->substituteButtons(
                    $matchedQuestion->buttons,
                    $sessionData,
                    $user
                );

                // Validate and filter buttons
                $validButtons = array_filter($buttons, function ($button) {
                    return !empty($button['label']) &&
                           !empty($button['url']) &&
                           $this->isValidUrl($button['url']);
                });

                if (!empty($validButtons)) {
                    $response['data']['buttons'] = array_values($validButtons);
                }
            }
        } else {
            $response['data']['message'] = config(
                'laravel-chatbot.default_response',
                'I didn\'t understand that. Can you please rephrase?'
            );
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
        $user = Auth::guard('sanctum')->user() ?? Auth::user();
        if ($user) {
            $sessionData['user'] = [
                'id' => $user->id,
                'name' => $user->name ?? null,
                'email' => $user->email ?? null,
            ];
        }

        // Add session metrics from request or default
        $sessionData['session'] = [
            'deal_count' => $request->input('session_data.deal_count', 0),
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
