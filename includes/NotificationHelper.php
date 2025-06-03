<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';
use WebSocket\Client as WebSocketClient;

class NotificationHelper {
    private $pdo;
    private $websocketUrl;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "wss" : "ws");
        $this->websocketUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/ws';
    }

    public function createOrUpdateNotification($userId, $actorId, $type, $postId = null, $commentId = null, $storyId = null) {
        error_log("Creating/updating notification: User=$userId, Actor=$actorId, Type=$type, Post=$postId");
        
        try {
            // Get actor's username and profile picture
            $stmt = $this->pdo->prepare("SELECT username, profile_picture FROM users WHERE user_id = ?");
            $stmt->execute([$actorId]);
            $actor = $stmt->fetch();
            
            if (!$actor) {
                error_log("Actor not found: $actorId");
                return false;
            }
            
            // For post-related notifications, check if there's an existing notification
            if ($postId) {
                $stmt = $this->pdo->prepare("
                    SELECT notification_id, created_at 
                    FROM notifications 
                    WHERE user_id = ? AND type = ? AND post_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$userId, $type, $postId]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    // Update existing notification's creation time
                    $stmt = $this->pdo->prepare("
                        UPDATE notifications 
                        SET created_at = NOW() 
                        WHERE notification_id = ?
                    ");
                    $stmt->execute([$existing['notification_id']]);
                    
                    $notification = [
                        'type' => $type,
                        'user_id' => $userId,
                        'actor_id' => $actorId,
                        'actor_username' => $actor['username'],
                        'actor_profile_picture' => $actor['profile_picture'],
                        'count' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'post_id' => $postId
                    ];
                    
                    // Get post image if available
                    $stmt = $this->pdo->prepare("SELECT image_url FROM posts WHERE post_id = ?");
                    $stmt->execute([$postId]);
                    $post = $stmt->fetch();
                    if ($post && $post['image_url']) {
                        $notification['post_image'] = $post['image_url'];
                    }
                    
                    $this->sendWebSocketNotification($userId, $notification);
                    return true;
                }
            }
            
            // For story-related notifications, check if there's an existing notification
            if ($storyId) {
                $stmt = $this->pdo->prepare("
                    SELECT notifications.notification_id, notifications.created_at, stories.image_url
                    FROM notifications
                    LEFT JOIN stories ON notifications.story_id = stories.story_id
                    WHERE notifications.user_id = ? AND notifications.type = ? AND notifications.story_id = ? 
                    ORDER BY notifications.created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$userId, $type, $storyId]);
                $existing = $stmt->fetch();
                if ($existing) {
                    // Update existing notification's creation time
                    $stmt = $this->pdo->prepare("
                        UPDATE notifications 
                        SET created_at = NOW() 
                        WHERE notification_id = ?
                    ");
                    $stmt->execute([$existing['notification_id']]);
                    $notification = [
                        'type' => $type,
                        'user_id' => $userId,
                        'actor_id' => $actorId,
                        'actor_username' => $actor['username'],
                        'actor_profile_picture' => $actor['profile_picture'],
                        'count' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'story_id' => $storyId
                    ];
                    if ($existing['image_url']) {
                        $notification['story_media'] = $existing['image_url'];
                    }
                    $this->sendWebSocketNotification($userId, $notification);
                    return true;
                }
            }
            
            // Create new notification
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, actor_id, type, post_id, comment_id, story_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $actorId, $type, $postId, $commentId, $storyId]);
            
            $notification = [
                'type' => $type,
                'user_id' => $userId,
                'actor_id' => $actorId,
                'actor_username' => $actor['username'],
                'actor_profile_picture' => $actor['profile_picture'],
                'count' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'post_id' => $postId,
                'story_id' => $storyId
            ];
            
            // Get post image if available
            if ($postId) {
                $stmt = $this->pdo->prepare("SELECT image_url FROM posts WHERE post_id = ?");
                $stmt->execute([$postId]);
                $post = $stmt->fetch();
                if ($post && $post['image_url']) {
                    $notification['post_image'] = $post['image_url'];
                }
            }
            
            // Get story media if available
            if ($storyId) {
                $stmt = $this->pdo->prepare("SELECT image_url FROM stories WHERE story_id = ?");
                $stmt->execute([$storyId]);
                $story = $stmt->fetch();
                if ($story && $story['image_url']) {
                    $notification['story_media'] = $story['image_url'];
                }
            }
            
            $this->sendWebSocketNotification($userId, $notification);
            return true;
            
        } catch (PDOException $e) {
            error_log("Error creating/updating notification: " . $e->getMessage());
            return false;
        }
    }

    public function notifyAllUsers($eventId) {
        try {
            // Get event details
            $stmt = $this->pdo->prepare("SELECT name FROM events WHERE event_id = ?");
            $stmt->execute([$eventId]);
            $eventName = $stmt->fetchColumn();

            // Get all users
            $stmt = $this->pdo->prepare("SELECT user_id FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Create notification for each user
            foreach ($users as $userId) {
                $this->createOrUpdateNotification($userId, null, 'event', null, null, null, $eventId);
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error notifying all users: " . $e->getMessage());
            return false;
        }
    }

    public function createFollowRequestNotification($requesterId, $targetUserId) {
        try {
            // Get requester username
            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE user_id = ?");
            $stmt->execute([$requesterId]);
            $requesterUsername = $stmt->fetchColumn();

            if (!$requesterUsername) {
                error_log("Could not find username for user ID: " . $requesterId);
                return false;
            }

            // Send real-time WebSocket notification
            $this->sendWebSocketNotification($targetUserId, [
                'type' => 'follow_request',
                'user_id' => $targetUserId,
                'requester_id' => $requesterId,
                'requester_username' => $requesterUsername,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Error creating follow request notification: " . $e->getMessage());
            return false;
        }
    }

    public function markNotificationsAsRead($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error marking notifications as read: " . $e->getMessage());
            return false;
        }
    }

    public function getUnreadNotificationCount($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting unread notification count: " . $e->getMessage());
            return 0;
        }
    }

    private function sendWebSocketNotification($userId, $notification) {
        try {
            error_log("Attempting to send WebSocket notification to user $userId: " . json_encode($notification));
            
            $client = new WebSocketClient($this->websocketUrl);
            $client->send(json_encode([
                'type' => 'notification',
                'user_id' => $userId,
                'notification' => $notification
            ]));
            $client->close();
            error_log("WebSocket notification sent successfully");
        } catch (Exception $e) {
            error_log("Error sending WebSocket notification: " . $e->getMessage());
        }
    }

    public function formatNotificationMessage($notification) {
        $message = '';
        $count = $notification['count'];
        $lastActor = $notification['last_actor_username'];
        $metadata = isset($notification['metadata']) ? json_decode($notification['metadata'], true) : [];

        switch ($notification['type']) {
            case 'like':
                if ($count === 1) {
                    $message = "$lastActor a apreciat postarea ta";
                } else {
                    $message = "$lastActor si altii " . ($count - 1) . " au apreciat postarea ta";
                }
                break;

            case 'comment':
                if ($count === 1) {
                    $message = "$lastActor a adaugat un comentariu la postarea ta";
                } else {
                    $message = "$lastActor si altii " . ($count - 1) . " au adaugat comentarii la postarea ta";
                }
                break;

            case 'story_like':
                if ($count === 1) {
                    $message = "$lastActor a apreciat povestea ta";
                } else {
                    $message = "$lastActor si altii " . ($count - 1) . " au apreciat povestea ta";
                }
                break;

            case 'comment_reply':
                if ($count === 1) {
                    $message = "$lastActor a raspuns la comentariul tau";
                } else {
                    $message = "$lastActor si altii " . ($count - 1) . " au raspuns la comentariul tau";
                }
                break;

            case 'event':
                $message = "Un nou eveniment a fost postat! " . $notification['event_name'];
                break;

            case 'follow_request':
                $message = "$lastActor vrea să te urmărească";
                break;

            case 'follow':
                if (isset($metadata['is_request_accepted']) && $metadata['is_request_accepted']) {
                    $message = "$lastActor a acceptat cererea de follow";
                } else {
                    $message = "$lastActor a inceput sa te urmareasca";
                }
                break;
        }

        return $message;
    }
} 