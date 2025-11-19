# Laravel Chatbot (Database-driven Q&A + BotMan)

A Laravel package that lets you manage chatbot Q&A in the database, with keyword matching (AND/OR), conditions, variable substitution, BotMan integration, an admin UI, a minimal web chat, and a floating chat widget.

## Project links and contact
- Repository: [github.com/es-77/laravel-chatbot](https://github.com/es-77/laravel-chatbot)
- Maintainer (LinkedIn): [es77](https://www.linkedin.com/in/es77/?originalSubdomain=pk)
- Email: `emmanuelsaleem098765@gmail.com`

## Requirements
- PHP >= 7.4
- Laravel ^8|^9|^10|^11|^12

## 1) Install the package
```bash
composer require emmanuel-saleem/laravel-chatbot
```

BotMan is included as a dependency. If you need the web driver explicitly:
```bash
composer require botman/driver-web:^1.5
```

## 2) Publish assets (config, migrations, views)
Publish everything:
```bash
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider"
```
Or selectively:
```bash
# Config
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-config
# Migrations
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-migrations
# Views (UI)
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-views
```

## 3) Run migrations
```bash
php artisan migrate
```

## 4) Routes
The package registers routes automatically:
- Web routes: admin pages and a BotMan endpoint
- API route: `POST /api/chatbot/message` for programmatic access

If you are using a test/breeze app, protect admin routes with `auth` as needed.

## 5) Admin UI

### Access Admin Pages
After installation, you can access the admin interface at:
- **All Questions**: `http://your-app.com/admin/bot-questions`
- **Create Question**: `http://your-app.com/admin/bot-questions/create`
- **Import Questions**: `http://your-app.com/admin/bot-questions/import`

Or use route helpers in your code:
```php
route('bot-questions.index')      // List all questions
route('bot-questions.create')    // Create new question
route('bot-questions.import')    // Import questions from JSON
```

**Note:** Protect these routes with authentication middleware in your application. For example, in `routes/web.php`:
```php
Route::middleware(['auth'])->prefix('admin/bot-questions')->group(function () {
    // Package routes are already registered, but you can wrap them
});
```

### Create Questions
1. Navigate to **Create Question** (`/admin/bot-questions/create`)
2. Fill in the form:
   - **Question**: Descriptive text for your reference
   - **Keywords**: Add multiple keywords (press Enter or comma to add each keyword)
   - **Logic Operator**: Choose OR (any keyword matches) or AND (all keywords must match)
   - **Answer**: The bot's response (supports variables like `@{{user.name}}`, `@{{user.email}}`, `@{{session.deal_count}}`)
   - **Buttons** (optional): Add interactive buttons with labels and URLs
   - **Priority**: Higher numbers = higher priority (default: 0)
   - **Status**: Active/Inactive toggle
3. Click **Save** to create the question

### Import Questions
1. Navigate to **Import Questions** (`/admin/bot-questions/import`)
2. Choose import method:
   - **Upload JSON File**: Select a JSON file from your computer
   - **Paste JSON Content**: Paste JSON directly into the textarea
3. Use the provided JSON structure format (see the import page for details)
4. Click **Import Questions** to process

**JSON Structure Example:**
```json
[
  {
    "question": "What is your return policy?",
    "keywords": ["return", "refund", "policy"],
    "logic_operator": "OR",
    "answer": "We offer a 30-day return policy.",
    "priority": 10,
    "is_active": true,
    "buttons": [
      {
        "label": "Learn More",
        "url": "https://example.com/returns",
        "style": "primary",
        "target": "_blank"
      }
    ]
  }
]
```

### Question Features
Each question supports:
- **Keywords**: Tag input (press Enter or comma to add)
- **Logic Operator**: OR (any keyword) or AND (all keywords)
- **Variables in Answers**: `@{{user.name}}`, `@{{user.email}}`, `@{{session.deal_count}}`
- **Conditions** (optional): Match based on session/user data
- **Buttons** (optional): Interactive buttons rendered in chat responses

## 6) Web Chat page (included)
The package includes a simple web chat at:
```php
route('botman.web-chat')
```
This view posts to the BotMan controller and renders replies and buttons.

## 7) Floating chat widget (corner icon)
Include the Blade snippet anywhere (typically at the end of your base layout before `</body>`):
```blade
@include('laravel-chatbot::components.floating-chat')
```
The widget opens a compact chat panel and sends messages to the same BotMan web endpoint. No Tailwind required (uses inline styles).

## 8) API usage
Endpoint:
```http
POST /api/chatbot/message
```
Payload:
```json
{
  "message": "Hi",
  "session_data": {
    "deal_count": 3
  }
}
```
Response (example):
```json
{
  "success": true,
  "data": {
    "matched": true,
    "question_id": 1,
    "message": "Hello John",
    "buttons": [
      { "label": "Join Call", "url": "https://...", "target": "_blank", "style": "primary" }
    ]
  }
}
```
See `API_USAGE.md` for more details.

## 9) Theme (dark/light) in test app
If you use the provided Breeze-based test app layout, a theme toggle is included in `resources/views/components/admin-layout.blade.php`. The theme persists in `localStorage('theme')` and toggles the `<html>.dark` class.

## 10) Development tips
- After editing package views, if using them without publishing, just refresh. If you published views and you want to see updates, republish:
```bash
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-views --force
```
- Clear compiled views if needed:
```bash
php artisan view:clear
```

## 11) Quick copy commands
```bash
# Install
composer require emmanuel-saleem/laravel-chatbot

# Publish all
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider"

# Or publish individually
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-config
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-migrations
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-views

# Migrate
php artisan migrate

# Clear compiled views (useful during dev)
php artisan view:clear
```

## 12) Troubleshooting
- If UI changes don't show after publishing, add `--force` when re-publishing views and clear compiled views:
  ```bash
  php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-views --force
  php artisan view:clear
  ```
- For login-bound variables in the web chat, ensure Ajax requests include cookies. The default chat sends `credentials: 'same-origin'`.
- If you see "Unable to locate component [admin-layout]", the package includes a default admin layout. If you published views, ensure your published `admin-layout.blade.php` exists or remove it to use the package's default.

---

Happy building! If something feels rough, open an issue or send a PR.


