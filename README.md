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
composer require emmanuel-saleem/laravel-chatbot:@dev --prefer-source
```

BotMan is included as a dependency. If you need the web driver explicitly:
```bash
composer require botman/driver-web:^1.5
```

### Prefer installing a tagged (stable) release
After you create a Git tag (see “Release a new version” below) and the version is available on Packagist, install without `@dev`:
```bash
composer require emmanuel-saleem/laravel-chatbot:^0.1.0
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
After publishing views (optional), navigate to your admin routes. If you prefer the package views without publishing, use:
- All Questions: `route('bot-questions.index')`
- Create Question: `route('bot-questions.create')`

Each question supports:
- Keywords: tag input (press Enter or comma)
- Logic operator: OR/AND
- Variables in answers: `@{{user.name}}`, `@{{user.email}}`, `@{{session.deal_count}}`
- Conditions (optional) against session/user data
- Buttons (optional) rendered in web chat responses

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
composer require emmanuel-saleem/laravel-chatbot:@dev --prefer-source

# Publish all
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider"

# Or publish individually
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-config
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-migrations
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelChatbot\Providers\LaravelChatbotServiceProvider" --tag=laravel-chatbot-views

# Migrate
printf "\nApplying migrations...\n" && php artisan migrate

# Clear compiled views (useful during dev)
php artisan view:clear
```

## 12) Troubleshooting
- If Composer fails because of stability: ensure your app allows installing `@dev` or require a specific commit/branch.
- If UI changes don’t show after publishing, add `--force` when re-publishing views and clear compiled views.
- For login-bound variables in the web chat, ensure Ajax requests include cookies. The default chat sends `credentials: 'same-origin'`.

---

Happy building! If something feels rough, open an issue or send a PR.

---

## Maintainers: Release a new version (no @dev installs)

1) Choose the next semantic version (example: v0.1.0):
```bash
git add -A
git commit -m "chore(release): v0.1.0"
git tag v0.1.0
git push origin main --tags
```

2) Ensure the package is on Packagist and auto-updates
- Create/claim on Packagist: `https://packagist.org/packages/submit`
- Package name should be `emmanuel-saleem/laravel-chatbot`
- Enable “auto-update via GitHub webhook” (recommended)

3) Consumers can now install a stable tag (no @dev):
```bash
composer require emmanuel-saleem/laravel-chatbot:^0.1.0
```

### Optional: organization/vendor move
If you plan to publish under an organization (e.g., `es-77/laravel-chatbot`), update `composer.json`:
```json
{
  "name": "es-77/laravel-chatbot",
  "description": "A Laravel Chatbot package.",
  "type": "library"
}
```
Then create the new package on Packagist and deprecate the old one, or set it to “Abandoned: use es-77/laravel-chatbot” in Packagist settings.

### Badges (forks / issues / stars)
Add these to the top of the README (replace `main` if using another branch):
```md
![Stars](https://img.shields.io/github/stars/es-77/laravel-chatbot?style=social)
![Forks](https://img.shields.io/github/forks/es-77/laravel-chatbot?style=social)
![Issues](https://img.shields.io/github/issues/es-77/laravel-chatbot)
```


