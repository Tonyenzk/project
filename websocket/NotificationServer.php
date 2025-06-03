<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class NotificationServer implements MessageComponentInterface {
    public $clients;
    protected $userConnections;
    protected $pdo;
    protected $lastPing;
    protected $pingInterval = 30; // 30 seconds

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->lastPing = [];
        
        try {
            // Initialize database connection
            global $pdo;
            $this->pdo = $pdo;
            
            if (!$this->pdo) {
                throw new Exception("Database connection failed");
            }
        } catch (Exception $e) {
            error_log("Error initializing database connection: " . $e->getMessage());
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        $conn->lastPing = time();
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if ($data === null) {
                throw new Exception("Invalid JSON message received");
            }
            
            if ($data['type'] === 'auth') {
                // Store user connection
                $this->userConnections[$data['user_id']] = $from;
                $from->userId = $data['user_id']; // Store user ID in connection object
                echo "User {$data['user_id']} authenticated\n";
                return;
            }
            
            if ($data['type'] === 'ping') {
                $from->lastPing = time();
                $from->send(json_encode(['type' => 'pong']));
                return;
            }
            
            if ($data['type'] === 'notification') {
                $notification = $data['notification'];
                $targetUserId = $notification['user_id'];
                
                // Get the target user's connection
                $targetConnection = $this->userConnections[$targetUserId] ?? null;
                
                if ($targetConnection) {
                    try {
                        // Send notification to target user
                        $targetConnection->send(json_encode([
                            'type' => $notification['type'],
                            'user_id' => $targetUserId,
                            'requester_id' => $notification['requester_id'] ?? null,
                            'requester_username' => $notification['requester_username'] ?? null,
                            'actor_username' => $notification['actor_username'] ?? $notification['requester_username'] ?? null,
                            'created_at' => $notification['created_at'] ?? date('Y-m-d H:i:s')
                        ]));
                        echo "Notification sent to user {$targetUserId}\n";
                    } catch (\Exception $e) {
                        echo "Error sending notification: {$e->getMessage()}\n";
                        // Remove the connection if it's no longer valid
                        unset($this->userConnections[$targetUserId]);
                    }
                } else {
                    echo "User {$targetUserId} is not connected\n";
                }
            }
        } catch (Exception $e) {
            echo "Error processing message: {$e->getMessage()}\n";
            error_log("WebSocket error: " . $e->getMessage());
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        // Remove from user connections if it was authenticated
        if (isset($conn->userId)) {
            unset($this->userConnections[$conn->userId]);
            echo "User {$conn->userId} disconnected\n";
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        error_log("WebSocket error: " . $e->getMessage());
        
        // Remove from user connections if it was authenticated
        if (isset($conn->userId)) {
            unset($this->userConnections[$conn->userId]);
        }
        
        $conn->close();
    }

    public function sendNotification($userId, $notification) {
        error_log("Sending notification to user $userId: " . json_encode($notification));
        
        if (!isset($this->userConnections[$userId])) {
            error_log("User $userId is not connected");
            return;
        }
        
        // Ensure all required fields are present
        $notificationData = [
            'type' => $notification['type'],
            'actor_username' => $notification['actor_username'],
            'actor_profile_picture' => $notification['actor_profile_picture'] ?? null,
            'count' => $notification['count'] ?? 1,
            'last_actor_username' => $notification['last_actor_username'] ?? $notification['actor_username'],
            'created_at' => $notification['created_at'] ?? date('Y-m-d H:i:s'),
            'post_id' => $notification['post_id'] ?? null,
            'post_image' => $notification['post_image'] ?? null,
            'story_id' => $notification['story_id'] ?? null,
            'story_media' => $notification['story_media'] ?? null,
            'event_id' => $notification['event_id'] ?? null,
            'event_name' => $notification['event_name'] ?? null
        ];
        
        $message = json_encode([
            'type' => 'notification',
            'notification' => $notificationData
        ]);
        
        error_log("Sending WebSocket message: $message");
        $this->userConnections[$userId]->send($message);
    }

    public function sendFollowRequest($userId, $requesterId, $requesterUsername) {
        error_log("Sending follow request notification to user $userId from $requesterUsername");
        
        if (!isset($this->userConnections[$userId])) {
            error_log("User $userId is not connected");
            return;
        }
        
        try {
            $message = json_encode([
                'type' => 'follow_request',
                'requester_id' => $requesterId,
                'requester_username' => $requesterUsername
            ]);
            
            error_log("Sending WebSocket message: $message");
            $this->userConnections[$userId]->send($message);
        } catch (Exception $e) {
            error_log("Error sending follow request: " . $e->getMessage());
            // Remove the connection if it's no longer valid
            unset($this->userConnections[$userId]);
        }
    }

    public function getClients() {
        return $this->clients;
    }
}

// Create the WebSocket server
$notificationServer = new NotificationServer();
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            $notificationServer
        )
    ),
    8080
);

// Enable keep-alive
$server->loop->addPeriodicTimer(30, function() use ($notificationServer) {
    try {
        $now = time();
        foreach ($notificationServer->getClients() as $client) {
            if (isset($client->lastPing) && ($now - $client->lastPing) > 60) {
                // Close connection if no ping received for more than 60 seconds
                $client->close();
            }
        }
    } catch (Exception $e) {
        error_log("Error in keep-alive timer: " . $e->getMessage());
    }
});

echo "WebSocket server started on port 8080\n";
$server->run(); 