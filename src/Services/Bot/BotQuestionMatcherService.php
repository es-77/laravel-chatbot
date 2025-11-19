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
            
            // Calculate match ratio (how many keywords matched vs total)
            $matchRatio = $totalKeywords > 0 ? ($matchedKeywords / $totalKeywords) : 0;
            
            // Calculate match score with improved algorithm:
            // 1. Match ratio is most important (multiplied by 50000) - questions with more matched keywords win
            // 2. Priority (multiplied by 1000) - but less important than match ratio
            // 3. Matched keywords count (multiplied by 100) - as tie-breaker
            // 4. Total keywords (multiplied by 10) - prefer more specific questions (fewer keywords = more specific)
            // This ensures that a question matching 3/3 keywords beats one matching 1/4 keywords, even if the latter has higher priority
            $matchScore = ($matchRatio * 50000) + ($question->priority * 1000) + ($matchedKeywords * 100) + ($totalKeywords * 10);
            
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
            $keyword = trim($keyword);
            // For multi-word keywords (phrases), check if all words are present
            if (strpos($keyword, ' ') !== false) {
                $words = explode(' ', $keyword);
                $allWordsPresent = true;
                foreach ($words as $word) {
                    if (strpos($normalizedMessage, trim($word)) === false) {
                        $allWordsPresent = false;
                        break;
                    }
                }
                if ($allWordsPresent) {
                    $matchedCount++;
                }
            } else {
                // Single word keyword - direct match
                if (strpos($normalizedMessage, $keyword) !== false) {
                    $matchedCount++;
                }
            }
        }

        return $matchedCount;
    }
}
