<?php

namespace EmmanuelSaleem\LaravelChatbot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotQuestion extends Model
{
    use HasFactory;

    protected $table = 'es_bot_questions';

    protected $fillable = [
        'question',
        'keywords',
        'logic_operator',
        'answer',
        'conditions',
        'buttons',
        'priority',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'keywords' => 'array',
        'conditions' => 'array',
        'buttons' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the user who created this question.
     */
    public function creator(): BelongsTo
    {
        $userClass = config('auth.providers.users.model', \App\Models\User::class);
        return $this->belongsTo($userClass, 'created_by');
    }

    /**
     * Scope a query to only include active questions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order by priority (descending).
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('id', 'desc');
    }

    /**
     * Check if the question matches the given message.
     */
    public function matches(string $message): bool
    {
        if (empty($this->keywords)) {
            return false;
        }

        $normalizedMessage = strtolower(trim($message));
        $normalizedKeywords = array_map('strtolower', array_filter($this->keywords));

        if (empty($normalizedKeywords)) {
            return false;
        }

        if ($this->logic_operator === 'AND') {
            // All keywords must be present
            // For multi-word keywords (phrases), check if all words in the phrase are present
            foreach ($normalizedKeywords as $keyword) {
                $keyword = trim($keyword);
                // If keyword contains spaces (multi-word), check if all words are present
                if (strpos($keyword, ' ') !== false) {
                    $words = explode(' ', $keyword);
                    $allWordsPresent = true;
                    foreach ($words as $word) {
                        if (strpos($normalizedMessage, trim($word)) === false) {
                            $allWordsPresent = false;
                            break;
                        }
                    }
                    if (!$allWordsPresent) {
                        return false;
                    }
                } else {
                    // Single word keyword - direct match
                    if (strpos($normalizedMessage, $keyword) === false) {
                        return false;
                    }
                }
            }
            return true;
        } else {
            // OR: At least one keyword must be present
            foreach ($normalizedKeywords as $keyword) {
                if (strpos($normalizedMessage, $keyword) !== false) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Check if conditions pass for the given session data.
     */
    public function conditionsPass(array $sessionData): bool
    {
        if (empty($this->conditions)) {
            return true; // No conditions means always pass
        }

        foreach ($this->conditions as $key => $condition) {
            $actualValue = $this->getNestedValue($sessionData, $key);

            // Support condition format: { "variable.key": { "operator": value } }
            if (is_array($condition)) {
                foreach ($condition as $operator => $expectedValue) {
                    if (!$this->evaluateCondition($actualValue, $operator, $expectedValue)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get nested value from array using dot notation.
     */
    protected function getNestedValue(array $data, string $key)
    {
        $keys = explode('.', $key);
        $value = $data;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return null;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Evaluate a condition.
     */
    protected function evaluateCondition($actualValue, string $operator, $expectedValue): bool
    {
        switch ($operator) {
            case '==':
                return $actualValue == $expectedValue;
            case '===':
                return $actualValue === $expectedValue;
            case '!=':
                return $actualValue != $expectedValue;
            case '!==':
                return $actualValue !== $expectedValue;
            case '>':
                return $actualValue > $expectedValue;
            case '>=':
                return $actualValue >= $expectedValue;
            case '<':
                return $actualValue < $expectedValue;
            case '<=':
                return $actualValue <= $expectedValue;
            default:
                return false;
        }
    }
}
