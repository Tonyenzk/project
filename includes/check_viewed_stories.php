<?php
// Function to check if a user has viewed all stories from another user
function hasViewedAllStories($pdo, $viewer_id, $story_owner_id) {
    try {
        // First get all active story IDs for the story owner
        $storyStmt = $pdo->prepare("
            SELECT story_id 
            FROM stories 
            WHERE user_id = ? AND expires_at > NOW()
        ");
        $storyStmt->execute([$story_owner_id]);
        $stories = $storyStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If there are no stories, return false (no gray ring needed)
        if (empty($stories)) {
            return false;
        }
        
        // For each story, check if it has been viewed by the current user
        $allViewed = true;
        foreach ($stories as $storyId) {
            $viewedStmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM stories_viewed 
                WHERE story_id = ? AND user_id = ?
            ");
            $viewedStmt->execute([$storyId, $viewer_id]);
            $viewed = $viewedStmt->fetchColumn() > 0;
            
            if (!$viewed) {
                $allViewed = false;
                break; // Exit early if any story wasn't viewed
            }
        }
        
        return $allViewed;
    } catch (PDOException $e) {
        // On error, just return false to show the default colored ring
        error_log("Error checking viewed stories: " . $e->getMessage());
        return false;
    }
}
