<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../config/database.php';

// Security check - verify user is logged in and has admin privileges
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login
    header('Location: ../login.php');
    exit();
}

// Check if user has admin privileges
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !in_array($user['role'], ['Moderator', 'Admin', 'Master_Admin'])) {
        // User doesn't have admin privileges, redirect to dashboard
        header('Location: ../dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    // Database error, redirect to dashboard for safety
    header('Location: ../dashboard.php');
    exit();
}

// User is authenticated and has admin privileges, proceed with the page

// Process actions (role changes, user deletion, etc.)
if (isset($_POST['action'])) {
    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    // Verify the user exists
    $userExists = false;
    if ($userId > 0) {
        $checkUser = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $checkUser->execute([$userId]);
        $userExists = $checkUser->rowCount() > 0;
    }
    
    if ($userExists) {
        switch ($_POST['action']) {
            case 'change_role':
                // Only Master_Admin can change user roles
                if ($user['role'] === 'Master_Admin') {
                    if (isset($_POST['new_role']) && in_array($_POST['new_role'], ['User', 'Moderator', 'Admin', 'Master_Admin'])) {
                        $updateRole = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                        $updateRole->execute([$_POST['new_role'], $userId]);
                        $_SESSION['message'] = "Rol actualizat cu succes pentru utilizatorul ID: {$userId}";
                    }
                } else {
                    $_SESSION['message'] = "Nu aveți permisiunea de a modifica rolurile utilizatorilor.";
                }
                break;
                
            case 'verify_user':
                // Only Master_Admin can change verification status
                if ($user['role'] === 'Master_Admin') {
                    $verify = isset($_POST['verify']) ? 'true' : 'false';
                    $updateVerify = $pdo->prepare("UPDATE users SET verifybadge = ? WHERE user_id = ?");
                    $updateVerify->execute([$verify, $userId]);
                    $_SESSION['message'] = "Starea de verificare actualizată pentru utilizatorul ID: {$userId}";
                } else {
                    $_SESSION['message'] = "Nu aveți permisiunea de a modifica statusul de verificare al utilizatorilor.";
                }                
                break;
                
            case 'delete_user':
                // Only Master_Admin can delete users
                if ($user['role'] === 'Master_Admin') {
                    // Delete user's posts first (due to foreign key constraints)
                    $deletePosts = $pdo->prepare("DELETE FROM posts WHERE user_id = ?");
                    $deletePosts->execute([$userId]);
                    
                    // Then delete the user
                    $deleteUser = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $deleteUser->execute([$userId]);
                    $_SESSION['message'] = "Utilizatorul ID: {$userId} a fost șters împreună cu toate postările.";
                } else {
                    $_SESSION['message'] = "Nu aveți permisiunea de a șterge utilizatori.";
                }
                break;
                
            case 'ban_user':
                // Only Master_Admin can ban/unban users
                if ($user['role'] === 'Master_Admin') {
                    // Get current ban status
                    $checkStatus = $pdo->prepare("SELECT is_banned FROM users WHERE user_id = ?");
                    $checkStatus->execute([$userId]);
                    $currentStatus = $checkStatus->fetch(PDO::FETCH_ASSOC);
                    
                    // Toggle ban status
                    $newStatus = isset($currentStatus['is_banned']) && $currentStatus['is_banned'] ? 0 : 1;
                    
                    // Update user's ban status
                    $updateBan = $pdo->prepare("UPDATE users SET is_banned = ? WHERE user_id = ?");
                    $updateBan->execute([$newStatus, $userId]);
                    
                    $action = $newStatus ? "banat" : "debanat";
                    $_SESSION['message'] = "Utilizatorul ID: {$userId} a fost {$action} cu succes.";
                } else {
                    $_SESSION['message'] = "Nu aveți permisiunea de a bana/debana utilizatori.";
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: users.php');
        exit();
    }
}

// Pagination setup
$usersPerPage = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $usersPerPage;

// Search filter
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchWhere = '';
$searchParams = [];

if (!empty($searchQuery)) {
    $searchWhere = " WHERE username LIKE ? OR email LIKE ? OR role LIKE ? ";
    $searchTerm = "%{$searchQuery}%";
    $searchParams = [$searchTerm, $searchTerm, $searchTerm];
}

// Role filter
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
if (!empty($roleFilter) && in_array($roleFilter, ['User', 'Moderator', 'Admin', 'Master_Admin'])) {
    $searchWhere = empty($searchWhere) ? " WHERE role = ? " : $searchWhere . " AND role = ? ";
    $searchParams[] = $roleFilter;
}

// Verification filter
$verifiedFilter = isset($_GET['verified']) ? intval($_GET['verified']) : -1;
if ($verifiedFilter !== -1) {
    $searchWhere = empty($searchWhere) ? " WHERE verifybadge = ? " : $searchWhere . " AND verifybadge = ? ";
    $searchParams[] = $verifiedFilter;
}

// Get total users count for pagination
try {
    $countQuery = "SELECT COUNT(*) as total FROM users{$searchWhere}";
    $countStmt = $pdo->prepare($countQuery);
    if (!empty($searchParams)) {
        $countStmt->execute($searchParams);
    } else {
        $countStmt->execute();
    }
    $totalUsers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalUsers / $usersPerPage);
} catch (PDOException $e) {
    $totalUsers = 0;
    $totalPages = 1;
}

// Get users for current page
try {
    // Make sure we select the is_banned field as well
    $query = "SELECT * FROM users{$searchWhere} ORDER BY created_at DESC LIMIT {$usersPerPage} OFFSET {$offset}";
    $stmt = $pdo->prepare($query);
    if (!empty($searchParams)) {
        $stmt->execute($searchParams);
    } else {
        $stmt->execute();
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" />
    <base href="../" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/toast.css">
    <title>User Management - Social Land</title>
    <script>
        // Define an empty function to prevent errors
        function updateCommentsStatus() {
            // This is a placeholder to prevent errors
            console.log('Comment status check disabled in admin panel');
        }
    </script>
    <style>
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            .dark & { background: #000; color: white; }
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .user-table th, .user-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            .dark & { border-bottom: 2px solid #333; }
        }
        
        .user-table th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #333;
            .dark & { background-color: #111; color: white; }
        }
        
        .user-table tr:hover {
            background-color: #f5f5f5;
            .dark & { background-color: #333; }
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .btn-role {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-verify {
            background-color: #2196F3;
            color: white;
        }
        
        .btn-delete {
            background-color: #f44336;
            color: white;
        }
        
        .action-btn:hover {
            opacity: 0.8;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a {
            color: #333;
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .pagination a.active {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            .dark & { background: #000; color: white; }
        }
        
        .search-box {
            flex: 1;
            min-width: 200px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            .dark & { background: #111; color: white; border: 1px solid #333; }
        }
        
        .filter-select {
            min-width: 150px;
        }
        
        .filter-select select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            .dark & { background: #111; color: white; border: 1px solid #333; }
        }
        
        .filter-btn {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .filter-btn:hover {
            background-color: #45a049;
        }
        
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            .dark & { background: #000; border: 1px solid #333; color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
            color: #333;
            .dark & { color: white; }
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #4CAF50;
        }
        
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-user {
            background-color: #e0e0e0;
            color: #333;
        }
        
        .role-moderator {
            background-color: #2196F3;
            color: white;
        }
        
        .role-admin {
            background-color: #f44336;
            color: white;
        }
        
        .role-master {
            background-color: #9C27B0;
            color: white;
        }
        
        .verified-badge {
            color: #4CAF50;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe; 
            .dark & { background-color: #000; color: white; }
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 300px;
            border-radius: 8px;
        }
        
        .modal-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
    </style>
</head>
<body>
    <?php 
    // Define a flag to let navbar know we're in admin section
    $isAdminSection = true;
    include '../navbar.php';
    ?>
    <main class="flex-1 transition-all duration-300 ease-in-out md:ml-[88px] lg:ml-[245px] w-full md:w-[calc(100%-88px)] lg:w-[calc(100%-245px)]">
        <div class="flex flex-col min-h-screen bg-white dark:bg-black transition-all duration-300 ease-in-out">
            <div class="flex-1 container max-w-7xl mx-auto px-4 py-8 lg:px-8">
                <div class="border-b border-neutral-200 dark:border-neutral-800 dark:text-white bg-white dark:bg-black rounded-lg p-6 mb-8 shadow-sm">
                    <div class="flex justify-between items-center mb-8">
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Panou de administrare</h1>
                    </div>
                    <?php include 'tabs.php'; ?>
                </div>
                <div class="admin-content border-neutral-200 dark:border-neutral-800 dark:text-white bg-white dark:bg-black rounded-lg p-6 shadow-sm">
                    <div class="admin-header">
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Gestionare utilizatori</h2>
                    </div>
            
                    <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['message']; ?>
                        <?php unset($_SESSION['message']); ?>
                    </div>
                    <?php endif; ?>
            
                    <!-- User Statistics -->
                    <div class="user-stats">
                        <?php
                        // Get user stats
                        try {
                            // Total users
                            $totalStmt = $pdo->query("SELECT COUNT(*) FROM users");
                            $totalCount = $totalStmt->fetchColumn();
                            
                            // Count by role
                            $roleStmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
                            $roleCounts = [];
                            while ($row = $roleStmt->fetch(PDO::FETCH_ASSOC)) {
                                $roleCounts[$row['role']] = $row['count'];
                            }
                            
                            // Verified users
                            $verifiedStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE verifybadge = 1");
                            $verifiedCount = $verifiedStmt->fetchColumn();
                        } catch (PDOException $e) {
                            $totalCount = 0;
                            $roleCounts = [];
                            $verifiedCount = 0;
                        }
                        ?>
                        
                        <div class="stat-card">
                            <h3>Total Utilizatori</h3>
                            <div class="number"><?php echo $totalCount; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Administratori</h3>
                            <div class="number"><?php echo isset($roleCounts['Admin']) ? $roleCounts['Admin'] : 0; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Moderatori</h3>
                            <div class="number"><?php echo isset($roleCounts['Moderator']) ? $roleCounts['Moderator'] : 0; ?></div>
                        </div>
                        
                        <div class="stat-card">
                            <h3>Utilizatori Verificați</h3>
                            <div class="number"><?php echo $verifiedCount; ?></div>
                        </div>
                    </div>
            
                    <!-- Search and Filters -->
                    <form method="GET" action="admin/users.php" class="filters">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="Caută după nume utilizator, email sau rol..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        
                        <div class="filter-select">
                            <select name="role">
                                <option value="">Toate rolurile</option>
                                <option value="User" <?php echo $roleFilter === 'User' ? 'selected' : ''; ?>>Utilizator</option>
                                <option value="Moderator" <?php echo $roleFilter === 'Moderator' ? 'selected' : ''; ?>>Moderator</option>
                                <option value="Admin" <?php echo $roleFilter === 'Admin' ? 'selected' : ''; ?>>Administrator</option>
                                <option value="Master_Admin" <?php echo $roleFilter === 'Master_Admin' ? 'selected' : ''; ?>>Administrator Master</option>
                            </select>
                        </div>
                        
                        <div class="filter-select">
                            <select name="verified">
                                <option value="-1">Toate statusurile de verificare</option>
                                <option value="1" <?php echo $verifiedFilter === 1 ? 'selected' : ''; ?>>Verificat</option>
                                <option value="0" <?php echo $verifiedFilter === 0 ? 'selected' : ''; ?>>Neverificat</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="filter-btn">Aplică filtrele</button>
                        <a href="admin/users.php" class="filter-btn" style="background-color: #607d8b; text-decoration: none; display: inline-block;">Resetează</a>
                    </form>
            
                    <!-- Users Table -->
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Avatar</th>
                                <th>Nume utilizator</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Verificat</th>
                                <th>Înregistrat</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No users found</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($users as $userItem): ?>
                                <tr>
                                    <td><?php echo $userItem['user_id']; ?></td>
                                    <td>
                                        <?php if (!empty($userItem['profile_picture']) && $userItem['profile_picture'] != 'images/profile_placeholder.webp'): ?>
                                            <img src="../<?php echo $userItem['profile_picture']; ?>" alt="<?php echo htmlspecialchars($userItem['username']); ?>" class="user-avatar">
                                        <?php else: ?>
                                            <img src="../images/profile_placeholder.webp" alt="Imagine profil implicit" class="user-avatar">
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($userItem['username']); ?></td>
                                    <td><?php echo htmlspecialchars($userItem['email']); ?></td>
                                    <td>
                                        <?php 
                                        $roleBadgeClass = '';
                                        switch ($userItem['role']) {
                                            case 'Admin':
                                                $roleBadgeClass = 'role-admin';
                                                break;
                                            case 'Moderator':
                                                $roleBadgeClass = 'role-moderator';
                                                break;
                                            case 'Master_Admin':
                                                $roleBadgeClass = 'role-master';
                                                break;
                                            default:
                                                $roleBadgeClass = 'role-user';
                                        }
                                        ?>
                                        <span class="role-badge <?php echo $roleBadgeClass; ?>">
                                            <?php echo htmlspecialchars($userItem['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($userItem['verifybadge'] === 'true'): ?>
                                            <span class="verified-badge" style="color: #4CAF50; font-weight: bold;">Da</span>
                                        <?php else: ?>
                                            <span style="color: #999; font-weight: bold;">Nu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($userItem['created_at'])); ?></td>
                                    <td class="user-actions">
                                        <?php if ($user['role'] === 'Master_Admin'): ?>
                                            <button class="action-btn btn-role" onclick="openRoleModal(<?php echo $userItem['user_id']; ?>, '<?php echo htmlspecialchars($userItem['username']); ?>', '<?php echo htmlspecialchars($userItem['role']); ?>')">
                                                <i class="fas fa-user-tag"></i> Rol
                                            </button>
                                            
                                            <button class="action-btn btn-verify" onclick="openVerifyModal(<?php echo $userItem['user_id']; ?>, '<?php echo htmlspecialchars($userItem['username']); ?>', <?php echo $userItem['verifybadge']; ?>)">
                                                <i class="fas fa-check-circle"></i> Verifică
                                            </button>
                                            
                                            <button class="action-btn <?php echo (isset($userItem['is_banned']) && $userItem['is_banned']) ? 'btn-role' : 'btn-delete'; ?>" onclick="openBanModal(<?php echo $userItem['user_id']; ?>, '<?php echo htmlspecialchars($userItem['username']); ?>', <?php echo (isset($userItem['is_banned']) && $userItem['is_banned']) ? 'true' : 'false'; ?>)">
                                                <i class="fas <?php echo (isset($userItem['is_banned']) && $userItem['is_banned']) ? 'fa-user-check' : 'fa-user-slash'; ?>"></i> 
                                                <?php echo (isset($userItem['is_banned']) && $userItem['is_banned']) ? 'Unban' : 'Ban'; ?>
                                            </button>
                                            
                                            <button class="action-btn btn-delete" onclick="openDeleteModal(<?php echo $userItem['user_id']; ?>, '<?php echo htmlspecialchars($userItem['username']); ?>')">
                                                <i class="fas fa-trash-alt"></i> Șterge
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted" style="color: #999; font-style: italic;">Acces limitat</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="admin/users.php?page=1<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $verifiedFilter !== -1 ? '&verified=' . $verifiedFilter : ''; ?>">Prima</a>
                            <a href="admin/users.php?page=<?php echo $page - 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $verifiedFilter !== -1 ? '&verified=' . $verifiedFilter : ''; ?>">Anterior</a>
                        <?php endif; ?>
                        
                        <?php
                        // Show a range of page numbers
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="admin/users.php?page=<?php echo $i; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $verifiedFilter !== -1 ? '&verified=' . $verifiedFilter : ''; ?>" <?php echo ($i == $page) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="admin/users.php?page=<?php echo $page + 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $verifiedFilter !== -1 ? '&verified=' . $verifiedFilter : ''; ?>">Următor</a>
                            <a href="admin/users.php?page=<?php echo $totalPages; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?><?php echo !empty($roleFilter) ? '&role=' . urlencode($roleFilter) : ''; ?><?php echo $verifiedFilter !== -1 ? '&verified=' . $verifiedFilter : ''; ?>">Ultima</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Role Change Modal -->
    <div id="roleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('roleModal')">&times;</span>
            <h3>Schimbare Rol Utilizator</h3>
            <p>Schimbă rolul pentru <span id="roleModalUsername"></span></p>
            <form method="POST" action="admin/users.php">
                <input type="hidden" name="action" value="change_role">
                <input type="hidden" name="user_id" id="roleModalUserId" value="">
                
                <div style="margin-bottom: 15px;">
                    <label for="new_role">Selectați noul rol:</label>
                    <select name="new_role" id="new_role" style="width: 100%; padding: 8px; margin-top: 5px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="User">Utilizator</option>
                        <option value="Moderator">Moderator</option>
                        <option value="Admin">Administrator</option>
                        <?php if ($user['role'] === 'Master_Admin'): ?>
                        <option value="Master_Admin">Administrator Master</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('roleModal')" style="padding: 8px 15px; background-color: #ccc; border: none; border-radius: 4px; cursor: pointer;">Anulează</button>
                    <button type="submit" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Salvează Modificările</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Verify User Modal -->
    <div id="verifyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('verifyModal')">&times;</span>
            <h3>Schimbare Status Verificare</h3>
            <p>Actualizează verificarea pentru <span id="verifyModalUsername"></span></p>
            <form method="POST" action="admin/users.php">
                <input type="hidden" name="action" value="verify_user">
                <input type="hidden" name="user_id" id="verifyModalUserId" value="">
                
                <div style="margin-bottom: 15px;">
                    <label>
                        <input type="checkbox" name="verify" id="verifyCheckbox" value="1" style="margin-right: 5px;">
                        Utilizatorul este verificat (are insigna de verificare)
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('verifyModal')" style="padding: 8px 15px; background-color: #ccc; border: none; border-radius: 4px; cursor: pointer;">Anulează</button>
                    <button type="submit" style="padding: 8px 15px; background-color: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">Salvează Modificările</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete User Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h3>Ștergere Utilizator</h3>
            <p style="color: #f44336;">Atenție: Această acțiune nu poate fi anulată!</p>
            <p>Sunteți sigur că doriți să ștergeți utilizatorul <span id="deleteModalUsername" style="font-weight: bold;"></span>?</p>
            <p>Această acțiune va șterge definitiv utilizatorul și toate datele asociate, inclusiv:</p>
            <ul style="margin-left: 20px; margin-bottom: 15px;">
                <li>Informațiile contului de utilizator</li>
                <li>Toate postările create de utilizator</li>
                <li>Toate comentariile făcute de utilizator</li>
            </ul>
            
            <form method="POST" action="admin/users.php">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteModalUserId" value="">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('deleteModal')" style="padding: 8px 15px; background-color: #ccc; border: none; border-radius: 4px; cursor: pointer;">Anulează</button>
                    <button type="submit" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">Șterge Utilizatorul</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Ban/Unban User Modal -->
    <div id="banModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('banModal')">&times;</span>
            <h3 id="banModalTitle">Ban Utilizator</h3>
            <p id="banModalDescription" style="color: #f44336;">Atenție: Utilizatorul va fi banat și nu va mai putea accesa contul!</p>
            <p>Sunteți sigur că doriți să <span id="banAction">banați</span> utilizatorul <span id="banModalUsername" style="font-weight: bold;"></span>?</p>
            
            <form method="POST" action="admin/users.php">
                <input type="hidden" name="action" value="ban_user">
                <input type="hidden" name="user_id" id="banModalUserId" value="">
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('banModal')" style="padding: 8px 15px; background-color: #ccc; border: none; border-radius: 4px; cursor: pointer;">Anulează</button>
                    <button type="submit" id="banSubmitButton" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">Ban</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to open the role change modal
        function openRoleModal(userId, username, currentRole) {
            document.getElementById('roleModalUserId').value = userId;
            document.getElementById('roleModalUsername').textContent = username;
            
            // Set the current role in the dropdown
            const roleSelect = document.getElementById('new_role');
            for (let i = 0; i < roleSelect.options.length; i++) {
                if (roleSelect.options[i].value === currentRole) {
                    roleSelect.selectedIndex = i;
                    break;
                }
            }
            
            document.getElementById('roleModal').style.display = 'block';
        }
        
        // Function to open the verify user modal
        function openVerifyModal(userId, username, isVerified) {
            document.getElementById('verifyModalUserId').value = userId;
            document.getElementById('verifyModalUsername').textContent = username;
            document.getElementById('verifyCheckbox').checked = isVerified === 1;
            
            document.getElementById('verifyModal').style.display = 'block';
        }
        
        // Function to open the ban/unban user modal
        function openBanModal(userId, username, isBanned) {
            document.getElementById('banModalUserId').value = userId;
            document.getElementById('banModalUsername').textContent = username;
            
            if (isBanned) {
                document.getElementById('banModalTitle').textContent = 'Deblocare Utilizator';
                document.getElementById('banModalDescription').textContent = 'Utilizatorul va putea accesa din nou contul.';
                document.getElementById('banModalDescription').style.color = '#4CAF50';
                document.getElementById('banAction').textContent = 'deblocați';
                document.getElementById('banSubmitButton').textContent = 'Unban';
                document.getElementById('banSubmitButton').style.backgroundColor = '#4CAF50';
            } else {
                document.getElementById('banModalTitle').textContent = 'Blocare Utilizator';
                document.getElementById('banModalDescription').textContent = 'Atenție: Utilizatorul va fi banat și nu va mai putea accesa contul!';
                document.getElementById('banModalDescription').style.color = '#f44336';
                document.getElementById('banAction').textContent = 'banați';
                document.getElementById('banSubmitButton').textContent = 'Ban';
                document.getElementById('banSubmitButton').style.backgroundColor = '#f44336';
            }
            
            document.getElementById('banModal').style.display = 'block';
        }
        
        // Function to open the delete user modal
        function openDeleteModal(userId, username) {
            document.getElementById('deleteModalUserId').value = userId;
            document.getElementById('deleteModalUsername').textContent = username;
            
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        // Function to close any modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target === modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        };
    </script>
</body>
</html>