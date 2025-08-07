


<?php 

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $unreadCount = $data['unreadCount'] ?? 0;
    $unreadNotifications = $data['unreadNotifications'] ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'MCVPro'; ?></title>
  <link href="/mcvpro/public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/mcvpro/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <link href="/mcvpro/public/vendor/fontawesome/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/mcvpro/public/css/sidebar.css" />
</head>
<body>

<button id="openNav" class="btn btn-secondary openbtn" onclick="w3_open()">
  <i class="fas fa-bars"></i>
</button>

<div class="sidebar shadow" id="mySidebar">
  <div class="sidebar-header d-flex justify-content-between align-items-center px-3 py-3 border-bottom">
    <h5 class="text-white mb-0"><i class="fas fa-bars me-2"></i>Menu</h5>
    <button class="btn btn-sm btn-light" onclick="w3_close()"><i class="fas fa-times"></i></button>
  </div>
  <ul class="nav flex-column px-3 py-3">

        <li class="nav-item dropdown mb-2">
    <a class="nav-link text-white dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="fas fa-bell me-2"></i>
      <span>Notifications</span>
      <?php if ($unreadCount > 0): ?>
    <span class="badge rounded-pill bg-danger ms-2" id="notification-badge">
        <?= $unreadCount; ?>
    </span>
<?php endif; ?>
    </a>

    
    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow-sm rounded-3 w-auto" 
    id="notification-dropdown" 
    style="max-width: 100%; min-width: 320px; max-height: 400px; overflow-y: auto; word-wrap: break-word; white-space: normal;">

    <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom border-secondary">
        <strong class="text-white"><i class="fas fa-bell me-2 text-warning"></i> Notifications</strong>
        <?php if (!empty($unreadNotifications)): ?>
            <button class="btn btn-sm btn-outline-light btn-clear-notifications" id="clear-notifications">Clear all</button>
        <?php endif; ?>
    </li>

    <?php if (!empty($unreadNotifications)): ?>
        <?php 
            foreach ($unreadNotifications as $notif): 
                $linkParts = explode('/', $notif['link']);
                $numericId = end($linkParts);
                $secureLink = $notif['link']; 

                if (is_numeric($numericId)) {
                    $secureLink = str_replace($numericId, encodeId($numericId), $notif['link']);
                }
        ?>
        <li class="border-bottom border-secondary-subtle">
            <a class="dropdown-item notification-item"
              href="/mcvpro/public<?= htmlspecialchars($secureLink); ?>"
              data-notification-id="<?= $notif['notification_id']; ?>">
                <div class="fw-semibold"><?= htmlspecialchars($notif['message']); ?></div>
                <div class="text-muted small">
                    <i class="far fa-clock me-1"></i> <?= date('d M Y, H:i', strtotime($notif['created_at'])); ?>
                </div>
            </a>

        </li>
        <?php endforeach; ?>
    <?php else: ?>
        <li>
            <span class="dropdown-item-text text-muted text-center py-3">There are no new notifications.</span>
        </li>
    <?php endif; ?>
</ul>

  </li>


    <li class="nav-item mb-2"><a class="nav-link text-white" href="/mcvpro/public/home"><i class="fas fa-home me-2"></i>Home</a></li>
    <li class="nav-item mb-2"><a class="nav-link text-white" href="/mcvpro/public/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
    <li class="nav-item mb-2"><a class="nav-link text-white" href="/mcvpro/public/customers"><i class="fas fa-users me-2"></i>Customer</a></li>
    <li class="nav-item mb-2"><a class="nav-link text-white" href="/mcvpro/public/projects"><i class="fas fa-folder-open me-2"></i>Project</a></li>
    <?php if ($_SESSION['user_role'] == 1): ?>
    <li class="nav-item mb-2"><a class="nav-link text-white" href="/mcvpro/public/users"><i class="fas fa-user-shield me-2"></i>User</a></li>
    <?php endif; ?>

    <li class="nav-item mb-2">
      <a class="nav-link text-white" data-bs-toggle="collapse" href="#quotationMenu">
        <i class="fas fa-file-invoice me-2"></i>Quotation
      </a>
      <div class="collapse" id="quotationMenu">
        <ul class="nav flex-column ps-3">
          <li><a class="nav-link text-white-50" href="/mcvpro/public/quotations/create">Create</a></li>
          <li><a class="nav-link text-white-50" href="/mcvpro/public/quotations">List</a></li>
        </ul>
      </div>
    </li>
    <li class="nav-item mb-2"><a class="nav-link text-white" href="/mcvpro/public/activities"><i class="fas fa-folder-open me-2"></i>Activities</a></li>
    <li class="nav-item mt-4">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="/mcvpro/public/logout" class="btn btn-outline-light w-100"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
      <?php else: ?>
        <a href="/mcvpro/public/login" class="btn btn-outline-light w-100"><i class="fas fa-sign-in-alt me-2"></i>Sign In</a>
      <?php endif; ?>
    </li>
  </ul>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/mcvpro/public/js/sidebar.js" defer></script>
<script src="/mcvpro/public/js/noti.js" defer></script> 
<script src="/mcvpro/public/js/session-manager.js" defer></script>
</body>
</html>