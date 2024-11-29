<?php

namespace Shahil\MSGraph\Services;

use Illuminate\Support\Facades\Http;

class MSGraphClient
{


    public function getMicrosoftToken()
    {
        // Check if token is already in session and not expired
        if (session()->has('microsoft_token') && session('microsoft_token_expires_at') > now()) {
            return session('microsoft_token');
        }

        // Fetch a new token
        $response = Http::asForm()->post('https://login.microsoftonline.com/' . env('MICROSOFT_TENANT_ID') . '/oauth2/v2.0/token', [
            'client_id' => env('MICROSOFT_CLIENT_ID'),
            'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
            'scope' => env('MICROSOFT_SCOPE'),
            'username' => env('MICROSOFT_USERNAME'),
            'password' => env('MICROSOFT_PASSWORD'),
            'grant_type' => 'password',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $accessToken = $data['access_token'];
            $expiresIn = $data['expires_in']; // Default is typically 3600 seconds

            // Store token and expiration time in session
            session([
                'microsoft_token' => $accessToken,
                'microsoft_token_expires_at' => now()->addSeconds($expiresIn - 60), // Buffer of 60 seconds
            ]);

            return $accessToken;
        } else {
            throw new \Exception("Failed to obtain Microsoft OAuth token: " . $response->body());
        }
    }


    public function getUserId($email)
    {
        $token = $this->getMicrosoftToken();

        $response = Http::withToken($token)->get("https://graph.microsoft.com/v1.0/users/{$email}");

        if ($response->successful()) {
            return $response->json()['id'];
        } else {
            throw new \Exception("Failed to retrieve user ID: " . $response->body());
        }
    }

    // if there are large number of chat histories then this function ll behave slow. Added pagination
    public function getChatIdByGroupName($groupName)
    {
        $token = $this->getMicrosoftToken();
        $url = "https://graph.microsoft.com/v1.0/me/chats";

        do {
            // Fetch the current batch of chats
            $response = Http::withToken($token)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $chats = $data['value'];

                // Search for the chat by name
                foreach ($chats as $chat) {
                    if (isset($chat['topic']) && $chat['topic'] === $groupName) {
                        return $chat['id']; // Return the chatId if found
                    }
                }

                // Check for next page of results
                $url = $data['@odata.nextLink'] ?? null;
            } else {
                throw new \Exception("Failed to retrieve chats: " . $response->body());
            }
        } while ($url);

        throw new \Exception("Chat with name '{$groupName}' not found.");
    }

    function getChatId($targetUserId)
    {
        // Get the token for the logged-in user
        $token = $this->getMicrosoftToken();

        // Retrieve the logged-in user's ID using their email
        $loggedInUserId = $this->getUserId(env('MICROSOFT_USERNAME'));

        // Check if a chat already exists between the logged-in user and the target user
        $response = Http::withToken($token)->get("https://graph.microsoft.com/v1.0/me/chats");

        if ($response->successful()) {
            foreach ($response->json()['value'] as $chat) {
                if (
                    $chat['chatType'] === 'oneOnOne' &&
                    isset($chat['members'][1]) &&
                    $chat['members'][1]['id'] === $targetUserId
                ) {
                    return $chat['id'];
                }
            }
        }

        // If no chat exists, create a new one-on-one chat between the logged-in user and the target user
        $response = Http::withToken($token)->post("https://graph.microsoft.com/v1.0/chats", [
            'chatType' => 'oneOnOne',
            'members' => [
                [
                    '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                    'roles' => ['owner'],
                    'user@odata.bind' => 'https://graph.microsoft.com/v1.0/users/' . $targetUserId
                ],
                [
                    '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                    'roles' => ['owner'],
                    'user@odata.bind' => 'https://graph.microsoft.com/v1.0/users/' . $loggedInUserId
                ]
            ]
        ]);

        if ($response->successful()) {
            return $response->json()['id'];
        } else {
            throw new \Exception("Failed to create or find chat: " . $response->body());
        }
    }


    public function sendMessageToUser($email, $messageContent)
    {
        try {
            $targetUserId = $this->getUserId($email);
            $chatId = $this->getChatId($targetUserId);
//        dd($chatId);
            $response = Http::withToken($this->getMicrosoftToken())->post("https://graph.microsoft.com/v1.0/chats/{$chatId}/messages", [
                'body' => [
                    'contentType' => 'html',
                    'content' => $messageContent
                ]
            ]);

            if ($response->successful()) {
//                return $response->json();
                return true;
            } else {
                throw new \Exception("Failed to send message: " . $response->body());
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

    }


    public function sendMessageToGroup($chatId, $messageContent)
    {
        $token = $this->getMicrosoftToken();

        $response = Http::withToken($token)->post("https://graph.microsoft.com/v1.0/chats/{$chatId}/messages", [
            'body' => [
                'contentType' => 'html', // Use 'text' if plain text
                'content' => $messageContent,
            ],
        ]);

        if ($response->successful()) {
            return true; // Message sent successfully
        } else {
            throw new \Exception("Failed to send message to group: " . $response->body());
        }
    }


    public function sendAdaptiveCardToUser($email, $adaptiveCardPayload)
    {
        try {
            $accessToken = $this->getMicrosoftToken(); // Obtain the access token

            // Retrieve the user ID for the email
            $targetUserId = $this->getUserId($email);
            $chatId = $this->getChatId($targetUserId);

            // Send Adaptive Card with a proper marker in the body
            $response = Http::withToken($accessToken)
                ->post("https://graph.microsoft.com/v1.0/chats/{$chatId}/messages", [
                    'body' => [
                        'contentType' => 'html',
                        'content' => '<attachment id="1"></attachment>'
                    ],
                    'attachments' => [
                        [
                            'id' => '1', // Attachment ID must match the marker
                            'contentType' => 'application/vnd.microsoft.card.adaptive',
                            'content' => json_encode($adaptiveCardPayload)
                        ]
                    ]
                ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to send Adaptive Card: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            return [
                'message' => 'Failed to send Adaptive Card!',
                'response' => $e->getMessage()
            ];
        }
    }
}
