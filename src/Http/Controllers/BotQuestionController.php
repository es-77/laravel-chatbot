<?php

namespace EmmanuelSaleem\LaravelChatbot\Http\Controllers;

use EmmanuelSaleem\LaravelChatbot\Models\BotQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;

class BotQuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BotQuestion::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter by page URL
        if ($request->filled('page_url')) {
            $query->forPageUrl($request->page_url);
        }

        $questions = $query->ordered()->paginate(15);

        return view('laravel-chatbot::pages.bot-questions.index', compact('questions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('laravel-chatbot::pages.bot-questions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateQuestion($request);

        $validated['created_by'] = Auth::id();
        $validated['keywords'] = $this->parseKeywords($validated['keywords']);
        $validated['buttons'] = $this->parseButtons($request->input('buttons', []));
        $validated['conditions'] = $this->parseConditions($request->input('conditions', []));
        $validated['page_urls'] = $this->parsePageUrls($request->input('page_urls', ''));

        BotQuestion::create($validated);

        return redirect()->route('bot-questions.index')
            ->with('success', 'Bot question created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BotQuestion $botQuestion)
    {
        return view('laravel-chatbot::pages.bot-questions.show', compact('botQuestion'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BotQuestion $botQuestion)
    {
        return view('laravel-chatbot::pages.bot-questions.edit', compact('botQuestion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BotQuestion $botQuestion)
    {
        $validated = $this->validateQuestion($request, $botQuestion->id);

        $validated['keywords'] = $this->parseKeywords($validated['keywords']);
        $validated['buttons'] = $this->parseButtons($request->input('buttons', []));
        $validated['conditions'] = $this->parseConditions($request->input('conditions', []));
        $validated['page_urls'] = $this->parsePageUrls($request->input('page_urls', ''));

        $botQuestion->update($validated);

        return redirect()->route('bot-questions.index')
            ->with('success', 'Bot question updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BotQuestion $botQuestion)
    {
        $botQuestion->delete();

        return redirect()->route('bot-questions.index')
            ->with('success', 'Bot question deleted successfully.');
    }

    /**
     * Toggle the active status of a question.
     */
    public function toggleStatus(BotQuestion $botQuestion)
    {
        $botQuestion->update(['is_active' => !$botQuestion->is_active]);

        return redirect()->route('bot-questions.index')
            ->with('success', 'Status updated successfully.');
    }

    /**
     * Show the import form.
     */
    public function import()
    {
        // Ensure $errors variable is available to the view
        // Laravel's ShareErrorsFromSession middleware should handle this,
        // but we ensure it exists to prevent undefined variable errors
        $errors = session('errors') ?: new ViewErrorBag();
        return view('laravel-chatbot::pages.bot-questions.import', ['errors' => $errors]);
    }

    /**
     * Process the import of questions from JSON.
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'import_type' => 'required|in:file,paste',
            'json_file' => 'required_if:import_type,file|file|mimes:json,txt|max:10240',
            'json_content' => 'required_if:import_type,paste|string',
        ]);

        try {
            $jsonData = null;

            if ($request->import_type === 'file') {
                $file = $request->file('json_file');
                $content = file_get_contents($file->getRealPath());
                $jsonData = json_decode($content, true);
            } else {
                $jsonData = json_decode($request->json_content, true);
            }

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['json_content' => 'Invalid JSON format: ' . json_last_error_msg()])->withInput();
            }

            if (!is_array($jsonData)) {
                return back()->withErrors(['json_content' => 'JSON must be an array of questions.'])->withInput();
            }

            $imported = 0;
            $errors = [];

            foreach ($jsonData as $index => $item) {
                try {
                    $validated = $this->validateImportedQuestion($item, $index);
                    
                    $validated['created_by'] = Auth::id();
                    $validated['keywords'] = is_array($validated['keywords']) 
                        ? $validated['keywords'] 
                        : $this->parseKeywords($validated['keywords'] ?? '');
                    $validated['buttons'] = $this->parseButtons($validated['buttons'] ?? []);
                    $validated['conditions'] = $this->parseImportedConditions($validated['conditions'] ?? []);
                    $validated['page_urls'] = $this->parseImportedPageUrls($validated['page_urls'] ?? []);

                    BotQuestion::create($validated);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Question #" . ($index + 1) . ": " . $e->getMessage();
                }
            }

            $message = "Successfully imported {$imported} question(s).";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " error(s) occurred.";
            }

            return redirect()->route('bot-questions.index')
                ->with('success', $message)
                ->with('import_errors', $errors);
        } catch (\Exception $e) {
            return back()->withErrors(['json_content' => 'Import failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Validate imported question data.
     */
    protected function validateImportedQuestion(array $data, int $index): array
    {
        $validator = Validator::make($data, [
            'question' => 'required|string|max:65535',
            'keywords' => 'required',
            'logic_operator' => 'nullable|in:AND,OR',
            'answer' => 'required|string|max:65535',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'buttons' => 'nullable|array',
            'buttons.*.label' => 'nullable|string|max:255',
            'buttons.*.url' => 'nullable|url|max:2048',
            'buttons.*.style' => 'nullable|in:primary,secondary',
            'buttons.*.target' => 'nullable|in:_blank,_self',
            'conditions' => 'nullable',
            'page_urls' => 'nullable',
        ]);

        if ($validator->fails()) {
            throw new \Exception(implode(', ', $validator->errors()->all()));
        }

        $validated = $validator->validated();
        
        // Set defaults
        $validated['logic_operator'] = $validated['logic_operator'] ?? 'OR';
        $validated['priority'] = $validated['priority'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        return $validated;
    }

    /**
     * Validate question data.
     */
    protected function validateQuestion(Request $request, $id = null): array
    {
        return Validator::make($request->all(), [
            'question' => 'required|string|max:65535',
            'keywords' => 'required|string',
            'logic_operator' => 'required|in:AND,OR',
            'answer' => 'required|string|max:65535',
            'priority' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'buttons.*.label' => 'nullable|string|max:255',
            'buttons.*.url' => 'nullable|url|max:2048',
            'buttons.*.style' => 'nullable|in:primary,secondary',
            'buttons.*.target' => 'nullable|in:_blank,_self',
            'conditions.*.key' => 'nullable|string|max:255',
            'conditions.*.operator' => 'nullable|in:==,!=,>,>=,<,<=',
            'conditions.*.value' => 'nullable|string|max:255',
            'page_urls' => 'nullable|string',
        ])->validate();
    }

    /**
     * Parse keywords from comma-separated string.
     */
    protected function parseKeywords($keywords): array
    {
        if (is_string($keywords)) {
            return array_map('trim', explode(',', $keywords));
        }
        return is_array($keywords) ? $keywords : [];
    }

    /**
     * Parse and validate buttons.
     */
    protected function parseButtons(array $buttons): ?array
    {
        $parsed = [];
        
        foreach ($buttons as $button) {
            if (!empty($button['label']) && !empty($button['url'])) {
                $parsed[] = [
                    'label' => $button['label'],
                    'url' => $button['url'],
                    'style' => $button['style'] ?? 'primary',
                    'target' => $button['target'] ?? '_self',
                ];
            }
        }

        return empty($parsed) ? null : $parsed;
    }

    /**
     * Parse conditions.
     */
    protected function parseConditions(array $conditions): ?array
    {
        $parsed = [];
        
        foreach ($conditions as $condition) {
            if (!empty($condition['key']) && !empty($condition['operator'])) {
                $parsed[$condition['key']] = [
                    $condition['operator'] => $condition['value'] ?? null,
                ];
            }
        }

        return empty($parsed) ? null : $parsed;
    }

    /**
     * Parse conditions from imported JSON.
     * Handles both formats:
     * 1. Object format: { "user.role": { "==": "premium" } }
     * 2. Array format: [ { "key": "user.role", "operator": "==", "value": "premium" } ]
     */
    protected function parseImportedConditions($conditions): ?array
    {
        if (empty($conditions)) {
            return null;
        }

        // If it's already in the correct object format (from JSON import)
        if (is_array($conditions) && !isset($conditions[0])) {
            // Check if it's an associative array (object format)
            $parsed = [];
            foreach ($conditions as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $parsed[$key] = $value;
                }
            }
            return empty($parsed) ? null : $parsed;
        }

        // Otherwise, use the form parser (array format)
        return $this->parseConditions($conditions);
    }

    /**
     * Parse page URLs from comma-separated string or newline-separated.
     */
    protected function parsePageUrls($pageUrls): ?array
    {
        if (empty($pageUrls)) {
            return null;
        }

        if (is_array($pageUrls)) {
            $urls = $pageUrls;
        } else {
            // Support both comma and newline separation
            $urls = preg_split('/[,\n\r]+/', $pageUrls);
        }

        $parsed = [];
        foreach ($urls as $url) {
            $url = trim($url);
            if (!empty($url)) {
                // Normalize URL to path
                $parsed[] = \EmmanuelSaleem\LaravelChatbot\Models\BotQuestion::normalizeUrlToPathStatic($url);
            }
        }

        return empty($parsed) ? null : array_unique($parsed);
    }

    /**
     * Parse page URLs from imported JSON.
     */
    protected function parseImportedPageUrls($pageUrls): ?array
    {
        if (empty($pageUrls)) {
            return null;
        }

        if (is_array($pageUrls)) {
            $urls = $pageUrls;
        } else {
            $urls = [$pageUrls];
        }

        $parsed = [];
        foreach ($urls as $url) {
            $url = trim($url);
            if (!empty($url)) {
                // Normalize URL to path
                $parsed[] = \EmmanuelSaleem\LaravelChatbot\Models\BotQuestion::normalizeUrlToPathStatic($url);
            }
        }

        return empty($parsed) ? null : array_unique($parsed);
    }
}
