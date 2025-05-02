<!-- القائمة الجانبية -->
<div class="container-fluid">
    <div class="row">
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo base_url('dashboard.php'); ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>لوحة التحكم</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'calculator.php' ? 'active' : ''; ?>" href="<?php echo base_url('calculator.php'); ?>">
                            <i class="fas fa-calculator"></i>
                            <span>حساب التكلفة</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'invoice_create.php' ? 'active' : ''; ?>" href="<?php echo base_url('invoice_create.php'); ?>">
                            <i class="fas fa-plus-circle"></i>
                            <span>تثبيت طلب</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'invoice_search.php' ? 'active' : ''; ?>" href="<?php echo base_url('invoice_search.php'); ?>">
                            <i class="fas fa-search"></i>
                            <span>البحث والحذف</span>
                        </a>
                    </li>
                </ul>
                
                <!-- حالات الطلبات -->
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>الطلبات حسب الحالة</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <?php foreach ($GLOBALS['ORDER_STATUSES'] as $status): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isset($_GET['status']) && $_GET['status'] === $status ? 'active' : ''; ?>" href="<?php echo base_url('invoices_by_status.php?status=' . urlencode($status)); ?>">
                            <?php 
                            $icon = 'circle';
                            switch ($status) {
                                case 'جديد':
                                    $icon = 'star';
                                    break;
                                case 'ليث':
                                case 'الوسيط':
                                    $icon = 'user';
                                    break;
                                case 'لم يتم الشراء':
                                    $icon = 'times-circle';
                                    break;
                                case 'تم الشراء':
                                    $icon = 'check-circle';
                                    break;
                                case 'تم الشحن':
                                    $icon = 'truck';
                                    break;
                                case 'مرتجع':
                                    $icon = 'undo';
                                    break;
                                case 'مكتمل':
                                    $icon = 'check-double';
                                    break;
                                case 'ملغي':
                                    $icon = 'ban';
                                    break;
                            }
                            ?>
                            <i class="fas fa-<?php echo $icon; ?>"></i>
                            <span><?php echo $status; ?></span>
                            
                            <?php
                            // الحصول على عدد الفواتير بهذه الحالة
                            $invoice = new Invoice();
                            $count = $invoice->count(['status' => $status]);
                            if ($count > 0):
                            ?>
                            <span class="badge bg-secondary rounded-pill"><?php echo $count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($auth->hasPermission(3)): ?>
                <!-- قائمة الإدارة (للمدراء فقط) -->
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>الإدارة</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>" href="<?php echo base_url('users.php'); ?>">
                            <i class="fas fa-users"></i>
                            <span>المستخدمين</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'pages.php' ? 'active' : ''; ?>" href="<?php echo base_url('pages.php'); ?>">
                            <i class="fas fa-store"></i>
                            <span>الصفحات</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'blacklist.php' ? 'active' : ''; ?>" href="<?php echo base_url('blacklist.php'); ?>">
                            <i class="fas fa-ban"></i>
                            <span>القائمة السوداء</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'deleted_invoices.php' ? 'active' : ''; ?>" href="<?php echo base_url('deleted_invoices.php'); ?>">
                            <i class="fas fa-trash-restore"></i>
                            <span>الفواتير المحذوفة</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'add_cart_numbers.php' ? 'active' : ''; ?>" href="<?php echo base_url('add_cart_numbers.php'); ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <span>أرقام السلات</span>
                        </a>
                    </li>
                    
                    <?php
                    // الحصول على عدد طلبات التسجيل المعلقة
                    $user = new User();
                    $pendingRequests = count($user->getRegistrationRequests('pending'));
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'registration_requests.php' ? 'active' : ''; ?>" href="<?php echo base_url('registration_requests.php'); ?>">
                            <i class="fas fa-user-plus"></i>
                            <span>طلبات التسجيل</span>
                            <?php if ($pendingRequests > 0): ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $pendingRequests; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'statistics.php' ? 'active' : ''; ?>" href="<?php echo base_url('statistics.php'); ?>">
                            <i class="fas fa-chart-bar"></i>
                            <span>الإحصائيات</span>
                        </a>
                    </li>
                </ul>
                <?php endif; ?>
                
                <!-- روابط سريعة -->
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>روابط سريعة</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('profile.php'); ?>">
                            <i class="fas fa-user"></i>
                            <span>الملف الشخصي</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('change_password.php'); ?>">
                            <i class="fas fa-key"></i>
                            <span>تغيير كلمة المرور</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('notifications.php'); ?>">
                            <i class="fas fa-bell"></i>
                            <span>الإشعارات</span>
                            <?php 
                            $notificationCount = $notification->countUnreadNotifications($auth->getCurrentUserId());
                            if ($notificationCount > 0):
                            ?>
                            <span class="badge bg-danger rounded-pill"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo base_url('logout.php'); ?>">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>تسجيل الخروج</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <!-- المحتوى الرئيسي -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
