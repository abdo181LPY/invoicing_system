<?php
/**
 * عرض رسائل الفلاش
 * يعرض رسائل النجاح والخطأ والتحذير والمعلومات
 */
if (!empty($flashMessages)) {
    foreach ($flashMessages as $type => $message) {
        if (!empty($message)) {
            // تحديد نوع التنبيه
            $alertClass = '';
            $icon = '';
            
            switch ($type) {
                case 'error':
                    $alertClass = 'alert-danger';
                    $icon = 'fas fa-exclamation-circle';
                    break;
                case 'success':
                    $alertClass = 'alert-success';
                    $icon = 'fas fa-check-circle';
                    break;
                case 'warning':
                    $alertClass = 'alert-warning';
                    $icon = 'fas fa-exclamation-triangle';
                    break;
                case 'info':
                    $alertClass = 'alert-info';
                    $icon = 'fas fa-info-circle';
                    break;
                default:
                    $alertClass = 'alert-secondary';
                    $icon = 'fas fa-bell';
            }
            
            // عرض التنبيه
            ?>
            <div class="alert <?php echo $alertClass; ?> alert-dismissible fade show" role="alert">
                <i class="<?php echo $icon; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>
            <?php
        }
    }
}

// عرض أخطاء التحقق
if ($session->hasFlash('errors')) {
    $errors = $session->getFlash('errors');
    
    if (!empty($errors)) {
        ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading mb-2"><i class="fas fa-exclamation-triangle me-2"></i>يرجى تصحيح الأخطاء التالية:</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $field => $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
        <?php
    }
}
