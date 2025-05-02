<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- ملفات CSS -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/bootstrap.rtl.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/fontawesome.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/responsive.css'); ?>">
    
    <!-- الخطوط العربية -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    <!-- ملفات JavaScript -->
    <script src="<?php echo base_url('assets/js/jquery.min.js'); ?>" defer></script>
    <script src="<?php echo base_url('assets/js/bootstrap.bundle.min.js'); ?>" defer></script>
    <script src="<?php echo base_url('assets/js/main.js'); ?>" defer></script>
    
    <?php if (isset($includeCharts) && $includeCharts): ?>
    <!-- مكتبة الرسوم البيانية -->
    <script src="<?php echo base_url('assets/js/chart.min.js'); ?>" defer></script>
    <?php endif; ?>
</head>
<body>
    <!-- شريط التنقل العلوي -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo base_url('dashboard.php'); ?>"><?php echo SITE_NAME; ?></a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="تبديل التنقل">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <?php if ($isLoggedIn): ?>
                <!-- القائمة الرئيسية للمستخدمين المسجلين -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo base_url('dashboard.php'); ?>">
                            <i class="fas fa-tachometer-alt"></i> لوحة التحكم
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'calculator.php' ? 'active' : ''; ?>" href="<?php echo base_url('calculator.php'); ?>">
                            <i class="fas fa-calculator"></i> الحاسبة
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'invoice_create.php' ? 'active' : ''; ?>" href="<?php echo base_url('invoice_create.php'); ?>">
                            <i class="fas fa-plus-circle"></i> تثبيت طلب
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'invoice_search.php' ? 'active' : ''; ?>" href="<?php echo base_url('invoice_search.php'); ?>">
                            <i class="fas fa-search"></i> البحث والحذف
                        </a>
                    </li>
                    
                    <?php if ($auth->hasPermission(3)): ?>
                    <!-- قائمة الإدارة (للمدراء فقط) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cogs"></i> الإدارة
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('users.php'); ?>">
                                    <i class="fas fa-users"></i> المستخدمين
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('pages.php'); ?>">
                                    <i class="fas fa-store"></i> الصفحات
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('blacklist.php'); ?>">
                                    <i class="fas fa-ban"></i> القائمة السوداء
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('deleted_invoices.php'); ?>">
                                    <i class="fas fa-trash-restore"></i> الفواتير المحذوفة
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('add_cart_numbers.php'); ?>">
                                    <i class="fas fa-shopping-cart"></i> أرقام السلات
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('registration_requests.php'); ?>">
                                    <i class="fas fa-user-plus"></i> طلبات التسجيل
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('statistics.php'); ?>">
                                    <i class="fas fa-chart-bar"></i> الإحصائيات
                                </a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- قائمة المستخدم -->
                <ul class="navbar-nav ms-auto">
                    <!-- الإشعارات -->
                    <li class="nav-item dropdown notifications-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php 
                            $notificationCount = $notification->countUnreadNotifications($auth->getCurrentUserId());
                            if ($notificationCount > 0):
                            ?>
                            <span class="badge rounded-pill bg-danger"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end notifications-container" aria-labelledby="notificationsDropdown">
                            <?php echo $notification->renderNotifications($auth->getCurrentUserId(), 5); ?>
                        </div>
                    </li>
                    
                    <!-- المستخدم -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('profile.php'); ?>">
                                    <i class="fas fa-user"></i> الملف الشخصي
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('change_password.php'); ?>">
                                    <i class="fas fa-key"></i> تغيير كلمة المرور
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo base_url('logout.php'); ?>">
                                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- محتوى الصفحة -->
    <div class="container-fluid my-3">
        <!-- عرض رسائل الخطأ والنجاح -->
        <?php if (!empty($flashMessages)): ?>
            <?php foreach ($flashMessages as $type => $message): ?>
                <?php if (!empty($message)): ?>
                    <?php $alertClass = ''; ?>
                    <?php if ($type === 'error'): $alertClass = 'alert-danger'; ?>
                    <?php elseif ($type === 'success'): $alertClass = 'alert-success'; ?>
                    <?php elseif ($type === 'warning'): $alertClass = 'alert-warning'; ?>
                    <?php elseif ($type === 'info'): $alertClass = 'alert-info'; ?>
                    <?php endif; ?>
                    
                    <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>