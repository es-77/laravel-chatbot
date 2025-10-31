<?php

namespace EmmanuelSaleem\LaravelChatbot\Middleware;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Interfaces\MiddlewareInterface;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use EmmanuelSaleem\LaravelChatbot\Services\Bot\BotQuestionMatcherService;
use EmmanuelSaleem\LaravelChatbot\Services\Bot\VariableSubstitutionService;
use Illuminate\Support\Facades\Auth;

class BotQuestionMiddleware implements MiddlewareInterface
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
     * Handle a captured message.
     *
     * @param IncomingMessage $message
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function captured(IncomingMessage $message, $next, BotMan $bot)
    {
        return $next($message);
    }

    /**
     * Handle an incoming message.
     *
     * @param IncomingMessage $message
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function received(IncomingMessage $message, $next, BotMan $bot)
    {
        // Build session data
        $sessionData = $this->buildSessionData($bot);

        // Try to find a matching question
        $matchedQuestion = $this->matcherService->findBestMatch(
            $message->getText(),
            $sessionData
        );

        if ($matchedQuestion) {
            // Get user for variable substitution
            $user = Auth::user();

            // Substitute variables in answer
            $answer = $this->substitutionService->substitute(
                $matchedQuestion->answer,
                $sessionData,
                $user
            );

            // Send the answer
            $bot->reply($answer);

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
                    // Convert to BotMan quick replies or buttons based on driver
                    $this->addButtonsToResponse($bot, $validButtons);
                }
            }

            // Stop further processing since we handled it
            return null;
        }

        // Continue to next middleware/handler
        return $next($message);
    }

    /**
     * Handle a message that was sent (outgoing).
     *
     * @param \BotMan\BotMan\Messages\Outgoing\OutgoingMessage $message
     * @param callable $next
     * @param BotMan $bot
     *
     * @return mixed
     */
    public function sending($message, $next, BotMan $bot)
    {
        return $next($message);
    }

    /**
     * Add buttons to BotMan response.
     */
    protected function addButtonsToResponse(BotMan $bot, array $buttons): void
    {
        $driver = $bot->getDriver();

        // For web driver, we need to attach buttons to the message
        if ($driver->getName() === 'Web') {
            // Store buttons in user storage so they can be retrieved
            // The web driver or custom view can render these
            $bot->userStorage()->save([
                'last_buttons' => $buttons,
            ]);

            // Also attach as metadata to the response if driver supports it
            // Note: Web driver doesn't natively support buttons, so we'll handle in view
        } else {
            // For other drivers like Facebook, Telegram, etc.
            // Use BotMan's template or quick reply features
            // This can be extended based on driver capabilities
            try {
                // Attempt to use driver-specific button rendering if available
                if (method_exists($driver, 'replyWithButtons')) {
                    // Driver-specific implementation
                }
            } catch (\Exception $e) {
                // Fallback: buttons stored in metadata
            }
        }
    }

    /**
     * Build session data from BotMan conversation.
     */
    protected function buildSessionData(BotMan $bot): array
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

        // Get conversation storage data
        $conversationData = $bot->userStorage()->all();

        // Add session metrics from conversation storage
        $sessionData['session'] = [
            'deal_count' => $conversationData['deal_count'] ?? 0,
            // Add more session metrics as needed
        ];

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
