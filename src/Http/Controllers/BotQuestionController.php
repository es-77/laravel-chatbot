<?php

namespace EmmanuelSaleem\LaravelChatbot\Http\Controllers;

use EmmanuelSaleem\LaravelChatbot\Models\BotQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
}
