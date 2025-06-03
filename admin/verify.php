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
    
    // Verify user is Master_Admin (only Master_Admin can access this page)
    if (!$user || $user['role'] !== 'Master_Admin') {
        // User doesn't have Master_Admin privileges, redirect to dashboard
        header('Location: ../dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    // Database error, redirect to dashboard for safety
    header('Location: ../dashboard.php');
    exit();
}

// Handle verification request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received: " . print_r($_POST, true));
    
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $request_id = $_POST['request_id'];
        error_log("Processing request ID: " . $request_id);
        
        try {
            if ($_POST['action'] === 'approve_request') {
                error_log("Approving request...");
                // Get user_id from request
                $stmt = $pdo->prepare("SELECT user_id FROM verification_requests WHERE request_id = ?");
                $stmt->execute([$request_id]);
                $request = $stmt->fetch();
                
                if ($request) {
                    error_log("Found request for user_id: " . $request['user_id']);
                    // Update user's verifybadge status
                    $stmt = $pdo->prepare("UPDATE users SET verifybadge = 'true' WHERE user_id = ?");
                    $stmt->execute([$request['user_id']]);
                    error_log("Updated user's verifybadge status");
                    
                    // Debug: Check if the update was successful
                    $checkStmt = $pdo->prepare("SELECT verifybadge FROM users WHERE user_id = ?");
                    $checkStmt->execute([$request['user_id']]);
                    $updatedUser = $checkStmt->fetch();
                    error_log("Updated verifybadge status to: " . $updatedUser['verifybadge']);
                    
                    // Update request status
                    $stmt = $pdo->prepare("UPDATE verification_requests SET status = 'approved', admin_note = ? WHERE request_id = ?");
                    $stmt->execute([$_POST['note'] ?? null, $request_id]);
                    error_log("Updated request status to approved");
                    
                    $_SESSION['message'] = "Cererea de verificare a fost aprobată cu succes.";
                } else {
                    error_log("No request found for ID: " . $request_id);
                }
            } elseif ($_POST['action'] === 'reject_request') {
                // Get user_id from request
                $stmt = $pdo->prepare("SELECT user_id FROM verification_requests WHERE request_id = ?");
                $stmt->execute([$request_id]);
                $request = $stmt->fetch();
                
                if ($request) {
                    // Update user's verifybadge status
                    $stmt = $pdo->prepare("UPDATE users SET verifybadge = 'false' WHERE user_id = ?");
                    $stmt->execute([$request['user_id']]);
                    
                    // Debug: Check if the update was successful
                    $checkStmt = $pdo->prepare("SELECT verifybadge FROM users WHERE user_id = ?");
                    $checkStmt->execute([$request['user_id']]);
                    $updatedUser = $checkStmt->fetch();
                    error_log("Updated verifybadge status to: " . $updatedUser['verifybadge']);
                    
                    // Update request status
                    $stmt = $pdo->prepare("UPDATE verification_requests SET status = 'rejected', rejection_reason = ? WHERE request_id = ?");
                    $stmt->execute([$_POST['reason'], $request_id]);
                    
                    $_SESSION['message'] = "Cererea de verificare a fost respinsă.";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "A apărut o eroare. Vă rugăm să încercați din nou.";
        }
        
        header("Location: verify.php");
        exit();
    }
}

// Fetch verification requests
try {
    $stmt = $pdo->prepare("
        SELECT vr.*, u.username, u.profile_picture 
        FROM verification_requests vr 
        JOIN users u ON vr.user_id = u.user_id 
        ORDER BY vr.request_date DESC
    ");
    $stmt->execute();
    $verificationRequests = $stmt->fetchAll();
    
    // Get counts for stats
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM verification_requests");
    $stmt->execute();
    $totalRequests = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'");
    $stmt->execute();
    $pendingRequests = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM verification_requests WHERE status = 'approved'");
    $stmt->execute();
    $approvedRequests = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM verification_requests WHERE status = 'rejected'");
    $stmt->execute();
    $rejectedRequests = $stmt->fetchColumn();
} catch (PDOException $e) {
    $verificationRequests = [];
    $totalRequests = $pendingRequests = $approvedRequests = $rejectedRequests = 0;
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
    <title>Admin - Cereri de Verificare</title>
    <script>
        // Define an empty function to prevent errors
        function updateCommentsStatus() {
            // This is a placeholder to prevent errors
            console.log('Comment status check disabled in admin panel');
        }
    </script>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        @media (prefers-color-scheme: dark) {
            .stat-card {
                background-color: #000000;
                color: #ffffff;
                box-shadow: 0 2px 5px rgba(255,255,255,0.1);
            }
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #555;
            font-size: 16px;
        }
        
        @media (prefers-color-scheme: dark) {
            .stat-card h3 {
                color: #ffffff;
            }
        }
        
        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
            color: #2196F3;
        }
        
        .requests-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        @media (prefers-color-scheme: dark) {
            .requests-container {
                background-color: #000000;
                color: #ffffff;
                box-shadow: 0 2px 5px rgba(255,255,255,0.1);
            }
        }
        
        .filter-controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-box {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            width: 300px;
        }
        
        .filter-select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        @media (prefers-color-scheme: dark) {
            th {
                background-color: #000000;
                color: #ffffff;
            }
        }
        
        tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        @media (prefers-color-scheme: dark) {
            tbody tr:hover {
                background-color: #111111;
            }
            td {
                color: #ffffff;
            }
        }
        
        .user-actions {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        .action-btn i {
            margin-right: 5px;
        }
        
        .btn-approve {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-reject {
            background-color: #f44336;
            color: white;
        }
        
        .btn-details {
            background-color: #2196F3;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            transition: background-color .3s;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        
        .pagination a.active {
            background-color: #2196F3;
            color: white;
            border: 1px solid #2196F3;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        /* Modal styles */
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
            margin: 10% auto;
            padding: 20px;
            border-radius: 5px;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        @media (prefers-color-scheme: dark) {
            .modal-content {
                background-color: #000000;
                color: #ffffff;
                box-shadow: 0 5px 15px rgba(255,255,255,0.1);
            }
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        @media (prefers-color-scheme: dark) {
            .close {
                color: #ffffff;
            }
        }
        
        .close:hover {
            color: black;
        }
        
        .modal h3 {
            margin-top: 0;
            color: #333;
        }
        
        @media (prefers-color-scheme: dark) {
            .modal h3 {
                color: #ffffff;
            }
        }
        
        .modal-actions {
            margin-top: 20px;
            text-align: right;
        }
        
        .request-details {
            margin: 15px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        
        @media (prefers-color-scheme: dark) {
            .request-details {
                background-color: #111111;
                color: #ffffff;
            }
        }
        
        .nav-tabs {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
            border-bottom: 1px solid #ddd;
        }
        
        .nav-tabs li {
            margin-right: 5px;
        }
        
        .nav-tabs li a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: #555;
            border: 1px solid transparent;
            border-radius: 4px 4px 0 0;
        }
        
        @media (prefers-color-scheme: dark) {
            .nav-tabs li a {
                color: #ffffff;
            }
        }
        
        .nav-tabs li a:hover {
            background-color: #f5f5f5;
        }
        
        .nav-tabs li a.active {
            color: #2196F3;
            border: 1px solid #ddd;
            border-bottom-color: #fff;
            background-color: #fff;
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
                        <h1 class="text-3xl font-bold tracking-tight dark:text-white">Panou de administrare</h1>
                    </div>
                    <?php include 'tabs.php'; ?>
                </div>
                <div class="admin-content border-neutral-200 dark:border-neutral-800 dark:text-white bg-white dark:bg-black rounded-lg p-6 shadow-sm">
                    <div class="admin-header">
                        <h2 class="text-2xl font-bold tracking-tight dark:text-white">Gestionare Cereri de Verificare</h2>
                    </div>
            
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Total Cereri</h3>
                    <div class="number"><?php echo $totalRequests; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>În Așteptare</h3>
                    <div class="number"><?php echo $pendingRequests; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Aprobate</h3>
                    <div class="number"><?php echo $approvedRequests; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Respinse</h3>
                    <div class="number"><?php echo $rejectedRequests; ?></div>
                </div>
            </div>
            
            <!-- Verification Requests -->
            <div class="requests-container">
                <div class="filter-controls">
                    <input type="text" class="search-box" placeholder="Caută după utilizator...">
                    
                    <div>
                        <select class="filter-select" onchange="filterRequests(this.value)">
                            <option value="all">Toate cererile</option>
                            <option value="pending">În așteptare</option>
                            <option value="approved">Aprobate</option>
                            <option value="rejected">Respinse</option>
                        </select>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilizator</th>
                            <th>Data Cererii</th>
                            <th>Status</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($verificationRequests)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Nu există cereri de verificare în prezent</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($verificationRequests as $request): ?>
                            <tr class="request-row" data-status="<?php echo $request['status']; ?>">
                                <td><?php echo $request['request_id']; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?php echo htmlspecialchars($request['profile_picture'] ?: '../images/profile_placeholder.webp'); ?>" 
                                             style="width: 30px; height: 30px; border-radius: 50%;">
                                        <a href="<?php echo $request['user_id'] === $_SESSION['user_id'] ? 'profile.php' : 'profile.php?user_id=' . $request['user_id']; ?>" 
                                           class="text-blue-500 hover:text-blue-700 hover:underline">
                                            <?php echo htmlspecialchars($request['username']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td><?php echo date('d M Y', strtotime($request['request_date'])); ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($request['status']) {
                                        case 'pending':
                                            $statusClass = 'bg-orange-100 text-orange-800';
                                            $statusText = 'În așteptare';
                                            break;
                                        case 'approved':
                                            $statusClass = 'bg-green-100 text-green-800';
                                            $statusText = 'Aprobată';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'bg-red-100 text-red-800';
                                            $statusText = 'Respinse';
                                            break;
                                    }
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-xs <?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td class="user-actions">
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <button class="action-btn btn-approve" onclick="openApproveModal(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['username']); ?>')">
                                        <i class="fas fa-check"></i> Acceptă
                                    </button>
                                    <button class="action-btn btn-reject" onclick="openRejectModal(<?php echo $request['request_id']; ?>, '<?php echo htmlspecialchars($request['username']); ?>')">
                                        <i class="fas fa-times"></i> Refuză
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="pagination">
                    <a href="#">&laquo;</a>
                    <a href="#" class="active">1</a>
                    <a href="#">&raquo;</a>
                </div>
            </div>
        </div>
    </div>
            </div>
        </div>
    </main>
    
    <!-- Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('detailsModal')">&times;</span>
            <h3>Detalii Cerere de Verificare</h3>
            
            <div class="request-details">
                <p><strong>Utilizator:</strong> <span id="detailsModalUsername"></span></p>
                <p><strong>Motiv:</strong> <span id="detailsModalReason"></span></p>
                <p><strong>Data cererii:</strong> <span id="detailsModalDate"></span></p>
                <p><strong>Status:</strong> <span id="detailsModalStatus"></span></p>
                
                <h4 style="margin-top: 15px;">Documente atașate:</h4>
                <div id="detailsModalDocuments">
                    <!-- Documents will be populated here -->
                </div>
                
                <h4 style="margin-top: 15px;">Informații suplimentare:</h4>
                <p id="detailsModalInfo"></p>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeModal('detailsModal')" style="padding: 8px 15px; background-color: #ccc; border: none; border-radius: 4px; cursor: pointer;">Închide</button>
                <button type="button" id="approveBtn" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">Aprobă</button>
                <button type="button" id="rejectBtn" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">Respinge</button>
            </div>
        </div>
    </div>
    
    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('approveModal')">&times;</span>
            <h3>Aprobă Cerere de Verificare</h3>
            <p>Sigur doriți să aprobați cererea de verificare pentru <span id="approveModalUsername" style="font-weight: bold;"></span>?</p>
            <p>Acest utilizator va primi insigna de verificare și va fi afișat ca verificat în întregul sistem.</p>
            
            <form method="POST" action="admin/verify.php">
                <input type="hidden" name="action" value="approve_request">
                <input type="hidden" name="request_id" id="approveModalRequestId" value="">
                
                <div style="margin-top: 15px;">
                    <label for="approveNote">Notă (opțional):</label>
                    <textarea id="approveNote" name="note" rows="3" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('approveModal')" style="padding: 8px 15px; background-color: #ccc; border: none; border-radius: 4px; cursor: pointer;">Anulează</button>
                    <button type="submit" style="padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Confirmă Aprobarea</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('rejectModal')">&times;</span>
            <h3>Respinge Cerere de Verificare</h3>
            <p>Sigur doriți să respingeți cererea de verificare pentru <span id="rejectModalUsername" style="font-weight: bold;"></span>?</p>
            
            <form method="POST" action="verify.php">
                <input type="hidden" name="action" value="reject_request">
                <input type="hidden" name="request_id" id="rejectModalRequestId" value="">
                
                <div style="margin-top: 15px;">
                    <label for="rejectReason">Motiv respingere (va fi vizibil pentru utilizator):</label>
                    <textarea id="rejectReason" name="reason" rows="3" style="width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;" required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal('rejectModal')" style="padding: 8px 15px; background-color: #ccc; border: none; border-radius: 4px; cursor: pointer;">Anulează</button>
                    <button type="submit" style="padding: 8px 15px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">Confirmă Respingerea</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Function to open modals
        function openDetailsModal(requestId, username, reason, date, status, documents, info) {
            document.getElementById('detailsModalUsername').textContent = username;
            document.getElementById('detailsModalReason').textContent = reason;
            document.getElementById('detailsModalDate').textContent = date;
            document.getElementById('detailsModalStatus').textContent = status;
            document.getElementById('detailsModalInfo').textContent = info;
            
            // Clear and populate documents
            const documentsContainer = document.getElementById('detailsModalDocuments');
            documentsContainer.innerHTML = '';
            
            if (documents && documents.length > 0) {
                const ul = document.createElement('ul');
                ul.style.listStyleType = 'none';
                ul.style.padding = '0';
                
                documents.forEach(doc => {
                    const li = document.createElement('li');
                    li.style.margin = '5px 0';
                    const link = document.createElement('a');
                    link.href = doc.url;
                    link.textContent = doc.name;
                    link.target = '_blank';
                    link.style.color = '#2196F3';
                    li.appendChild(link);
                    ul.appendChild(li);
                });
                
                documentsContainer.appendChild(ul);
            } else {
                documentsContainer.textContent = 'Nu există documente atașate.';
            }
            
            // Setup action buttons
            document.getElementById('approveBtn').onclick = function() {
                closeModal('detailsModal');
                openApproveModal(requestId, username);
            };
            
            document.getElementById('rejectBtn').onclick = function() {
                closeModal('detailsModal');
                openRejectModal(requestId, username);
            };
            
            document.getElementById('detailsModal').style.display = 'block';
        }
        
        function openApproveModal(requestId, username) {
            document.getElementById('approveModalUsername').textContent = username;
            document.getElementById('approveModalRequestId').value = requestId;
            document.getElementById('approveModal').style.display = 'block';
        }
        
        function openRejectModal(requestId, username) {
            document.getElementById('rejectModalUsername').textContent = username;
            document.getElementById('rejectModalRequestId').value = requestId;
            document.getElementById('rejectModal').style.display = 'block';
        }
        
        // Function to close modals
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

        function filterRequests(status) {
            const rows = document.querySelectorAll('.request-row');
            rows.forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>