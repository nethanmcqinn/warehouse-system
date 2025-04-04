<?php
require_once 'notifications.php';

$unreadNotifications = [];
if (isset($_SESSION['customer_id'])) {
    $unreadNotifications = getUserNotifications($_SESSION['customer_id'], $conn);
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Olympus Warehouse</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 0): ?>
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php">Manage Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_products.php">Manage Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="inventory.php">Inventory</a></li>
                    <li class="nav-item"><a class="nav-link" href="orders.php">Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                <?php elseif (isset($_SESSION['customer_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="my_orders.php">My Orders</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_profile.php">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="request_delivery.php">Request Delivery</a></li>
                    <li class="nav-item"><a class="nav-link" href="view_cart.php">
                        Cart <span class="badge bg-primary"><?php echo count($_SESSION['cart'] ?? []); ?></span>
                    </a></li>
                    <!-- Notifications Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <span id="notification-badge" class="badge rounded-pill bg-danger" <?php echo count($unreadNotifications) === 0 ? 'style="display: none;"' : ''; ?>>
                                <?php echo count($unreadNotifications); ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationsDropdown" id="notificationsList">
                            <?php if (empty($unreadNotifications)): ?>
                                <li><span class="dropdown-item text-muted">No new notifications</span></li>
                            <?php else: ?>
                                <?php foreach ($unreadNotifications as $notification): ?>
                                    <li class="notification-item" data-notification-id="<?php echo $notification['id']; ?>">
                                        <a class="dropdown-item" href="<?php echo $notification['link']; ?>" 
                                           onclick="markNotificationAsRead(event, <?php echo $notification['id']; ?>)">
                                            <div class="notification-content">
                                                <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                                </small>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="warehouse_logout.php">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="customer_register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
.notification-dropdown {
    min-width: 300px;
    max-height: 400px;
    overflow-y: auto;
    padding: 0;
    margin: 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.notification-item {
    padding: 0;
    border-bottom: 1px solid #eee;
    transition: background-color 0.3s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item .dropdown-item {
    padding: 12px 15px;
    white-space: normal;
}

.notification-content p {
    margin: 0;
    font-size: 0.9rem;
    color: #333;
    line-height: 1.4;
}

.notification-content small {
    display: block;
    margin-top: 5px;
    font-size: 0.75rem;
}

.badge {
    position: absolute;
    top: 0;
    right: 0;
    transform: translate(50%, -50%);
    font-size: 0.7rem;
    padding: 0.35em 0.65em;
}

.nav-item {
    position: relative;
}

#notificationsDropdown {
    padding-right: 1rem;
}

.notification-empty {
    padding: 1rem;
    text-align: center;
    color: #6c757d;
}
</style>

<script>
function markNotificationAsRead(event, notificationId) {
    event.preventDefault(); // Prevent the default link behavior
    
    fetch('mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the notification from the list
            const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notificationElement) {
                notificationElement.remove();
            }
            
            // Update the notification count
            const badge = document.getElementById('notification-badge');
            const currentCount = parseInt(badge.textContent) - 1;
            
            if (currentCount <= 0) {
                badge.style.display = 'none';
                // If no more notifications, show the empty message
                const notificationsList = document.getElementById('notificationsList');
                notificationsList.innerHTML = '<li><span class="dropdown-item text-muted">No new notifications</span></li>';
            } else {
                badge.textContent = currentCount;
            }
            
            // Navigate to the notification link
            const link = event.currentTarget.getAttribute('href');
            window.location.href = link;
        } else {
            console.error('Error marking notification as read:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Optional: Add a function to periodically check for new notifications
function checkNewNotifications() {
    fetch('get_notifications.php')
    .then(response => response.json())
    .then(data => {
        if (data.notifications) {
            updateNotifications(data.notifications);
        }
    })
    .catch(error => console.error('Error checking notifications:', error));
}

// Update notifications in the dropdown
function updateNotifications(notifications) {
    const badge = document.getElementById('notification-badge');
    const notificationsList = document.getElementById('notificationsList');
    
    if (notifications.length === 0) {
        badge.style.display = 'none';
        notificationsList.innerHTML = '<li><span class="dropdown-item text-muted">No new notifications</span></li>';
        return;
    }
    
    badge.style.display = 'block';
    badge.textContent = notifications.length;
    
    let notificationsHtml = notifications.map(notification => `
        <li class="notification-item" data-notification-id="${notification.id}">
            <a class="dropdown-item" href="${notification.link}" 
               onclick="markNotificationAsRead(event, ${notification.id})">
                <div class="notification-content">
                    <p class="mb-1">${notification.message}</p>
                    <small class="text-muted">
                        ${new Date(notification.created_at).toLocaleString()}
                    </small>
                </div>
            </a>
        </li>
    `).join('');
    
    notificationsList.innerHTML = notificationsHtml;
}

// Check for new notifications every 30 seconds
setInterval(checkNewNotifications, 30000);
</script> 