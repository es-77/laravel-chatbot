# Chatbot API Usage

## Endpoint

**POST** `/api/chatbot/message`

## Authentication

The API supports both:
- Session-based authentication (web requests)
- Sanctum API tokens (Bearer token)

## Request

### Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer {token} (optional, if using Sanctum)
```

### Body
```json
{
    "message": "Hello, I need help with refunds",
    "session_data": {
        "deal_count": 5,
        "custom_field": "value"
    }
}
```

### Parameters
- `message` (required): The user's message to send to the chatbot
- `session_data` (optional): Additional session data for variable substitution and conditions

## Response

### Success Response (200)
```json
{
    "success": true,
    "data": {
        "message": "Hi John, here are your refund options...",
        "buttons": [
            {
                "label": "View Refunds",
                "url": "https://example.com/refunds",
                "style": "primary",
                "target": "_self"
            }
        ],
        "matched": true,
        "question_id": 1
    }
}
```

### No Match Response (200)
```json
{
    "success": true,
    "data": {
        "message": "I didn't understand that. Can you please rephrase?",
        "buttons": [],
        "matched": false
    }
}
```

### Error Response (422)
```json
{
    "message": "The message field is required.",
    "errors": {
        "message": ["The message field is required."]
    }
}
```

## Example Usage

### cURL
```bash
curl -X POST http://localhost:8000/api/chatbot/message \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "message": "hello",
    "session_data": {
        "deal_count": 3
    }
  }'
```

### JavaScript (Fetch)
```javascript
fetch('/api/chatbot/message', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer YOUR_TOKEN' // Optional
    },
    body: JSON.stringify({
        message: 'Hello, I need help',
        session_data: {
            deal_count: 5
        }
    })
})
.then(response => response.json())
.then(data => {
    console.log(data.data.message);
    if (data.data.buttons) {
        // Handle buttons
    }
});
```

### PHP (Guzzle)
```php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->post('http://localhost:8000/api/chatbot/message', [
    'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => 'Bearer YOUR_TOKEN'
    ],
    'json' => [
        'message' => 'Hello, I need help',
        'session_data' => [
            'deal_count' => 5
        ]
    ]
]);

$data = json_decode($response->getBody(), true);
echo $data['data']['message'];
```

## Variable Substitution

The API supports variable substitution in responses:
- `{{user.name}}` - User's name (if authenticated)
- `{{user.email}}` - User's email (if authenticated)
- `{{session.deal_count}}` - Session data values

## Button Response

Buttons are returned in the response if the matched question has buttons configured:
- `label`: Button text
- `url`: Button URL (supports variable substitution)
- `style`: `primary` or `secondary`
- `target`: `_self` or `_blank`
