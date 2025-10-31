<?php

namespace EmmanuelSaleem\LaravelChatbot\Services\Bot;

use EmmanuelSaleem\LaravelChatbot\Models\BotQuestion;

class BotQuestionMatcherService
{
    /**
     * Find the best matching question for the given message and session data.
     */
    public function findBestMatch(string $message, array $sessionData): ?BotQuestion
    {
        // Normalize message
        $normalizedMessage = strtolower(trim($message));

        // Get all active questions ordered by priority
        $questions = BotQuestion::active()->ordered()->get();

        $matches = [];

        foreach ($questions as $question) {
            // Check keyword match
            if (!$question->matches($normalizedMessage)) {
                continue;
            }

            // Check conditions
            if (!$question->conditionsPass($sessionData)) {
                continue;
            }

            // Calculate match score (priority + keyword count as tie-breaker)
            $keywordCount = count($question->keywords);
            $matchScore = $question->priority * 1000 + $keywordCount;
            
            $matches[] = [
                'question' => $question,
                'score' => $matchScore,
                'keyword_count' => $keywordCount,
            ];
        }

        if (empty($matches)) {
            return null;
        }

        // Sort by score (descending), then by keyword count (descending)
        usort($matches, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['keyword_count'] <=> $a['keyword_count'];
            }
            return $b['score'] <=> $a['score'];
        });

        return $matches[0]['question'];
    }
}
