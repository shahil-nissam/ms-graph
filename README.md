
# MS Graph Integration for Laravel

**shahil/ms-graph** is a Laravel package that simplifies the integration with Microsoft's Graph API, enabling you to send messages, manage chats, send group messages, and utilize Adaptive Cards in your Laravel application effortlessly.

## Features

- Fetch and manage OAuth tokens for Microsoft Graph API.
- Retrieve user details using email addresses.
- Create one-on-one or group chats dynamically.
- Send text messages or Adaptive Cards to users or groups via Microsoft Teams.

---

## Installation

### Requirements
- PHP 8.0+
- Laravel 8.0+
- A registered Azure AD application with required permissions for Microsoft Graph API.

### Steps

1. Install the package via Composer:
   ```bash
   composer require shahil/ms-graph
   ```

2. Publish the configuration file:
   ```bash
   php artisan vendor:publish --tag=msgraph-config
   ```

3. Update the `.env` file with your Azure AD application credentials:
   ```bash
   MICROSOFT_CLIENT_ID=your_client_id
   MICROSOFT_CLIENT_SECRET=your_client_secret
   MICROSOFT_TENANT_ID=your_tenant_id
   MICROSOFT_SCOPE=https://graph.microsoft.com/.default
   MICROSOFT_USERNAME=your_admin_email@example.com
   MICROSOFT_PASSWORD=your_admin_password
   ```

### Configuration

The package provides a configuration file (`config/msgraph.php`) for customizing default values:
```php
return [
    'client_id' => env('MICROSOFT_CLIENT_ID'),
    'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'tenant_id' => env('MICROSOFT_TENANT_ID'),
    'scope' => env('MICROSOFT_SCOPE'),
    'username' => env('MICROSOFT_USERNAME'),
    'password' => env('MICROSOFT_PASSWORD'),
];
```

---

## Usage

1. **Retrieve User ID by Email**
   ```php
   use Shahil\MSGraph\Facades\MSGraph;

   $userId = MSGraph::getUserId('user@example.com');
   ```

2. **Send a Simple Message**
   ```php
   use Shahil\MSGraph\Facades\MSGraph;

   MSGraph::sendMessageToUser('user@example.com', 'Hello from Laravel!');
   ```

3. **Send a Message to a Group**
   ```php
   use Shahil\MSGraph\Facades\MSGraph;

   // Retrieve the group chat ID by its name
   $chatId = MSGraph::getChatIdByGroupName('Your Group Name');

   // Send a message to the group
   MSGraph::sendMessageToGroup($chatId, 'Hello group members!');
   ```

4. **Send an Adaptive Card**
   ```php
   use Shahil\MSGraph\Facades\MSGraph;

   $adaptiveCardPayload = [
       'type' => 'AdaptiveCard',
       'version' => '1.4',
       'body' => [
           ['type' => 'TextBlock', 'text' => 'Welcome to MS Graph Integration!']
       ],
       'actions' => [
           ['type' => 'Action.OpenUrl', 'title' => 'Learn More', 'url' => 'https://learn.microsoft.com/graph/']
       ]
   ];

   MSGraph::sendAdaptiveCardToUser('user@example.com', $adaptiveCardPayload);
   ```

5. **Get Chat ID for Group by Name**
   ```php
   use Shahil\MSGraph\Facades\MSGraph;

   $chatId = MSGraph::getChatIdByGroupName('Your Group Name');
   ```

6. **Get Chat ID for One-on-One Chat**
   ```php
   use Shahil\MSGraph\Facades\MSGraph;

   $targetUserId = MSGraph::getUserId('user@example.com');
   $chatId = MSGraph::getChatId($targetUserId);
   ```

---

## Examples in Controllers

Here’s an example of sending a group message inside a controller:

```php
namespace App\Http\Controllers;

use Shahil\MSGraph\Facades\MSGraph;

class TeamsController extends Controller
{
    public function sendGroupMessage()
    {
        try {
            $chatId = MSGraph::getChatIdByGroupName('Your Group Name');
            MSGraph::sendMessageToGroup($chatId, 'Hello, team!');
            return response()->json(['success' => 'Message sent to the group successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}
```

---

## Testing

Run unit tests for the package:

```bash
php artisan test
```

---

## Contributing

Contributions are welcome! Please fork this repository, create a feature branch, and submit a pull request.

---

## License

This package is open-sourced software licensed under the MIT license.

---

## Contact

For issues, questions, or feedback, please contact Shahil via GitHub or open an issue in this repository.
