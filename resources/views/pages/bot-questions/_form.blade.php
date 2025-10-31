@csrf

<div class="space-y-6">
    <!-- Question -->
    <div>
        <label for="question" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Question <span class="text-red-500">*</span>
        </label>
        <textarea name="question" id="question" 
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('question') border-red-500 @enderror" 
                  rows="2" required>{{ old('question', $botQuestion->question ?? '') }}</textarea>
        @error('question')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This is the question this bot response is designed to handle.</p>
    </div>

    <!-- Keywords -->
    <div>
        <label for="keywords" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Keywords <span class="text-red-500">*</span>
        </label>
        <input type="text" name="keywords" id="keywords" 
               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('keywords') border-red-500 @enderror" 
               value="{{ old('keywords', isset($botQuestion) ? implode(', ', $botQuestion->keywords ?? []) : '') }}" 
               placeholder="refund, payment, return" required>
        @error('keywords')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comma-separated keywords that trigger this response.</p>
    </div>

    <!-- Logic Operator -->
    <div>
        <label for="logic_operator" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Logic Operator <span class="text-red-500">*</span>
        </label>
        <select name="logic_operator" id="logic_operator" 
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('logic_operator') border-red-500 @enderror" required>
            <option value="OR" {{ old('logic_operator', $botQuestion->logic_operator ?? 'OR') === 'OR' ? 'selected' : '' }}>OR (any keyword matches)</option>
            <option value="AND" {{ old('logic_operator', $botQuestion->logic_operator ?? 'OR') === 'AND' ? 'selected' : '' }}>AND (all keywords must match)</option>
        </select>
        @error('logic_operator')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- Answer -->
    <div>
        <label for="answer" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Answer <span class="text-red-500">*</span>
        </label>
        <textarea name="answer" id="answer" 
                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('answer') border-red-500 @enderror" 
                  rows="5" required>{{ old('answer', $botQuestion->answer ?? '') }}</textarea>
        @error('answer')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Supports variables: @{{user.name}}, @{{user.email}}, @{{session.deal_count}}, etc.
        </p>
    </div>

    <!-- Buttons -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buttons</label>
        <div id="buttons-container" class="space-y-3">
            @php
                $buttons = old('buttons', isset($botQuestion) && $botQuestion->buttons ? $botQuestion->buttons : [['label' => '', 'url' => '', 'style' => 'primary', 'target' => '_self']]);
                if (empty($buttons)) {
                    $buttons = [['label' => '', 'url' => '', 'style' => 'primary', 'target' => '_self']];
                }
            @endphp
            @foreach($buttons as $index => $button)
                <div class="button-row p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900" data-index="{{ $index }}">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div class="md:col-span-4">
                            <input type="text" name="buttons[{{ $index }}][label]" 
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" 
                                   placeholder="Button Label" value="{{ $button['label'] ?? '' }}">
                        </div>
                        <div class="md:col-span-5">
                            <input type="url" name="buttons[{{ $index }}][url]" 
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" 
                                   placeholder="Button URL (http://...)" value="{{ $button['url'] ?? '' }}">
                        </div>
                        <div class="md:col-span-2">
                            <select name="buttons[{{ $index }}][style]" 
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="primary" {{ ($button['style'] ?? 'primary') === 'primary' ? 'selected' : '' }}>Primary</option>
                                <option value="secondary" {{ ($button['style'] ?? 'primary') === 'secondary' ? 'selected' : '' }}>Secondary</option>
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <button type="button" class="w-full bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm remove-button" onclick="removeButton(this)">×</button>
                        </div>
                    </div>
                    <div class="mt-2">
                        <select name="buttons[{{ $index }}][target]" 
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="_self" {{ ($button['target'] ?? '_self') === '_self' ? 'selected' : '' }}>Same Tab</option>
                            <option value="_blank" {{ ($button['target'] ?? '_self') === '_blank' ? 'selected' : '' }}>New Tab</option>
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="mt-2 bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm" onclick="addButton()">+ Add Button</button>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Add interactive buttons to the bot response. URLs support variables.</p>
    </div>

    <!-- Conditions -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Conditions</label>
        <div id="conditions-container" class="space-y-2">
            @php
                $conditions = old('conditions', []);
                if (empty($conditions) && isset($botQuestion) && $botQuestion->conditions) {
                    foreach ($botQuestion->conditions as $key => $condition) {
                        foreach ($condition as $operator => $value) {
                            $conditions[] = ['key' => $key, 'operator' => $operator, 'value' => $value];
                        }
                    }
                }
                if (empty($conditions)) {
                    $conditions = [['key' => '', 'operator' => '==', 'value' => '']];
                }
            @endphp
            @foreach($conditions as $index => $condition)
                <div class="condition-row p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900" data-index="{{ $index }}">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                        <div class="md:col-span-4">
                            <input type="text" name="conditions[{{ $index }}][key]" 
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" 
                                   placeholder="session.deal_count" value="{{ $condition['key'] ?? '' }}">
                        </div>
                        <div class="md:col-span-3">
                            <select name="conditions[{{ $index }}][operator]" 
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="==" {{ ($condition['operator'] ?? '==') === '==' ? 'selected' : '' }}>==</option>
                                <option value="!=" {{ ($condition['operator'] ?? '==') === '!=' ? 'selected' : '' }}>!=</option>
                                <option value=">" {{ ($condition['operator'] ?? '==') === '>' ? 'selected' : '' }}>></option>
                                <option value=">=" {{ ($condition['operator'] ?? '==') === '>=' ? 'selected' : '' }}>>=</option>
                                <option value="<" {{ ($condition['operator'] ?? '==') === '<' ? 'selected' : '' }}><</option>
                                <option value="<=" {{ ($condition['operator'] ?? '==') === '<=' ? 'selected' : '' }}><=</option>
                            </select>
                        </div>
                        <div class="md:col-span-4">
                            <input type="text" name="conditions[{{ $index }}][value]" 
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" 
                                   placeholder="Value" value="{{ $condition['value'] ?? '' }}">
                        </div>
                        <div class="md:col-span-1">
                            <button type="button" class="w-full bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm" onclick="removeCondition(this)">×</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="mt-2 bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm" onclick="addCondition()">+ Add Condition</button>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Set conditions based on session/user data. Leave empty if no conditions needed.</p>
    </div>

    <!-- Priority -->
    <div>
        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priority</label>
        <input type="number" name="priority" id="priority" 
               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('priority') border-red-500 @enderror" 
               value="{{ old('priority', $botQuestion->priority ?? 0) }}" min="0" required>
        @error('priority')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Higher priority questions are matched first. Default: 0</p>
    </div>

    <!-- Active -->
    <div>
        <label class="flex items-center">
            <input type="checkbox" name="is_active" id="is_active" 
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700" 
                   value="1" {{ old('is_active', isset($botQuestion) ? $botQuestion->is_active : true) ? 'checked' : '' }}>
            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
        </label>
    </div>
</div>

<script>
function addButton() {
    const container = document.getElementById('buttons-container');
    const index = container.children.length;
    const html = `
        <div class="button-row p-4 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900" data-index="${index}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-4">
                    <input type="text" name="buttons[${index}][label]" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Button Label">
                </div>
                <div class="md:col-span-5">
                    <input type="url" name="buttons[${index}][url]" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Button URL (http://...)">
                </div>
                <div class="md:col-span-2">
                    <select name="buttons[${index}][style]" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="primary">Primary</option>
                        <option value="secondary">Secondary</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <button type="button" class="w-full bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm remove-button" onclick="removeButton(this)">×</button>
                </div>
            </div>
            <div class="mt-2">
                <select name="buttons[${index}][target]" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="_self">Same Tab</option>
                    <option value="_blank">New Tab</option>
                </select>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

function removeButton(btn) {
    btn.closest('.button-row').remove();
    const container = document.getElementById('buttons-container');
    Array.from(container.children).forEach((row, index) => {
        row.querySelectorAll('input, select').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, `[${index}]`);
        });
    });
}

function addCondition() {
    const container = document.getElementById('conditions-container');
    const index = container.children.length;
    const html = `
        <div class="condition-row p-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900" data-index="${index}">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-4">
                    <input type="text" name="conditions[${index}][key]" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="session.deal_count">
                </div>
                <div class="md:col-span-3">
                    <select name="conditions[${index}][operator]" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="==">==</option>
                        <option value="!=">!=</option>
                        <option value=">">></option>
                        <option value=">=">>=</option>
                        <option value="<"><</option>
                        <option value="<="><=</option>
                    </select>
                </div>
                <div class="md:col-span-4">
                    <input type="text" name="conditions[${index}][value]" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Value">
                </div>
                <div class="md:col-span-1">
                    <button type="button" class="w-full bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm" onclick="removeCondition(this)">×</button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
}

function removeCondition(btn) {
    btn.closest('.condition-row').remove();
    const container = document.getElementById('conditions-container');
    Array.from(container.children).forEach((row, index) => {
        row.querySelectorAll('input, select').forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, `[${index}]`);
        });
    });
}
</script>