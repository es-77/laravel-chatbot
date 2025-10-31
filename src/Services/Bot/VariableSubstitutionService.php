<?php

namespace EmmanuelSaleem\LaravelChatbot\Services\Bot;

use Illuminate\Contracts\Auth\Authenticatable;

class VariableSubstitutionService
{
    /**
     * Substitute variables in text with values from session data and user.
     *
     * Supports {{user.*}} and {{session.*}} patterns.
     */
    public function substitute(string $text, array $sessionData, ?Authenticatable $user = null): string
    {
        // Replace user variables (allow spaces around brackets for flexibility)
        if ($user) {
            $text = preg_replace_callback('/\{\{\s*user\.([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', function ($matches) use ($user) {
                $attribute = $matches[1];
                $value = $user->getAttribute($attribute);
                return $value !== null ? (string) $value : $matches[0];
            }, $text);
        } else {
            // If no user, replace with a placeholder or empty
            $text = preg_replace_callback('/\{\{\s*user\.([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', function ($matches) {
                return 'User'; // Fallback when user is not authenticated
            }, $text);
        }

        // Replace session variables
        $text = preg_replace_callback('/\{\{\s*session\.([a-zA-Z_][a-zA-Z0-9_.]*)\s*\}\}/', function ($matches) use ($sessionData) {
            $key = $matches[1];
            $value = $this->getNestedValue($sessionData, $key);
            return $value !== null ? (string) $value : $matches[0];
        }, $text);

        return $text;
    }

    /**
     * Substitute variables in an array of buttons.
     */
    public function substituteButtons(array $buttons, array $sessionData, ?Authenticatable $user = null): array
    {
        return array_map(function ($button) use ($sessionData, $user) {
            if (isset($button['label'])) {
                $button['label'] = $this->substitute($button['label'], $sessionData, $user);
            }
            if (isset($button['url'])) {
                $button['url'] = $this->substitute($button['url'], $sessionData, $user);
            }
            return $button;
        }, $buttons);
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
}
