<?php

namespace EmmanuelSaleem\LaravelChatbot\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotQuestion extends Model
{
    use HasFactory;

    protected $table = 'bot_questions';

    protected $fillable = [
        'question',
        'keywords',
        'logic_operator',
        'answer',
        'conditions',
        'buttons',
        'page_urls',
        'priority',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'keywords' => 'array',
        'conditions' => 'array',
        'buttons' => 'array',
        'page_urls' => 'array',
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
     * Scope a query to filter by page URL.
     */
    public function scopeForPageUrl($query, ?string $url)
    {
        if (empty($url)) {
            // If no URL provided, return questions with no page_urls (global questions)
            return $query->where(function ($q) {
                $q->whereNull('page_urls')
                  ->orWhere('page_urls', '[]')
                  ->orWhere('page_urls', '');
            });
        }

        // Normalize URL to path
        $path = static::normalizeUrlToPathStatic($url);

        return $query->where(function ($q) use ($path) {
            // Questions with no page_urls (global) or matching path
            $q->where(function ($subQ) {
                $subQ->whereNull('page_urls')
                     ->orWhere('page_urls', '[]')
                     ->orWhere('page_urls', '');
            })
            ->orWhereRaw('JSON_CONTAINS(page_urls, CAST(? AS JSON))', [json_encode($path)]);
        });
    }

    /**
     * Check if this question is applicable for the given URL.
     */
    public function isApplicableForUrl(?string $url): bool
    {
        // If no page_urls set, question is global (applicable everywhere)
        if (empty($this->page_urls) || count($this->page_urls) === 0) {
            return true;
        }

        if (empty($url)) {
            return false;
        }

        $path = $this->normalizeUrlToPath($url);

        // Check if the path matches any of the page_urls
        foreach ($this->page_urls as $pageUrl) {
            $normalizedPageUrl = $this->normalizeUrlToPath($pageUrl);
            if ($normalizedPageUrl === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize URL to path (remove domain, query params, etc.).
     */
    protected function normalizeUrlToPath(string $url): string
    {
        return static::normalizeUrlToPathStatic($url);
    }

    /**
     * Static version of normalizeUrlToPath for use in scopes.
     */
    public static function normalizeUrlToPathStatic(string $url): string
    {
        // Parse URL
        $parsed = parse_url($url);
        
        // Get path (default to /)
        $path = $parsed['path'] ?? '/';
        
        // Remove leading/trailing slashes except for root
        $path = $path === '/' ? '/' : trim($path, '/');
        
        return $path;
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
            foreach ($normalizedKeywords as $keyword) {
                if (strpos($normalizedMessage, $keyword) === false) {
                    return false;
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
