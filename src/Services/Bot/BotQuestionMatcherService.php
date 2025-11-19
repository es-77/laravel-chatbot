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

            // Calculate how many keywords actually matched
            $matchedKeywords = $this->countMatchedKeywords($question, $normalizedMessage);
            $totalKeywords = count($question->keywords);
            
            // Calculate match score:
            // - Priority (multiplied by 10000 to give it high weight)
            // - Matched keywords ratio (multiplied by 1000 to prioritize questions with more matched keywords)
            // - Total keywords (as tie-breaker, prefer questions with more keywords)
            $matchRatio = $totalKeywords > 0 ? ($matchedKeywords / $totalKeywords) : 0;
            $matchScore = $question->priority * 10000 + ($matchRatio * 1000) + $matchedKeywords;
            
            $matches[] = [
                'question' => $question,
                'score' => $matchScore,
                'matched_keywords' => $matchedKeywords,
                'total_keywords' => $totalKeywords,
            ];
        }

        if (empty($matches)) {
            return null;
        }

        // Sort by score (descending), then by matched keywords (descending)
        usort($matches, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $b['matched_keywords'] <=> $a['matched_keywords'];
            }
            return $b['score'] <=> $a['score'];
        });

        return $matches[0]['question'];
    }

    /**
     * Count how many keywords from the question are present in the message.
     */
    protected function countMatchedKeywords(BotQuestion $question, string $normalizedMessage): int
    {
        $normalizedKeywords = array_map('strtolower', array_filter($question->keywords));
        $matchedCount = 0;

        foreach ($normalizedKeywords as $keyword) {
            if (strpos($normalizedMessage, $keyword) !== false) {
                $matchedCount++;
            }
        }

        return $matchedCount;
    }
}
