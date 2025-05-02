<?php
/**
 * نقطة الدخول الرئيسية للتطبيق
 * يقوم بتحميل الملفات الأساسية وتوجيه الطلبات
 */

// تحميل ملف الإعدادات
require_once 'config.php';

// تجنب تكرار صفحة تسجيل الدخول إذا كان المستخدم مسجل الدخول
$auth = Auth::getInstance();
$session = Session::getInstance();

// متغيرات عامة للاستخدام في الصفحات
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = $auth->isLoggedIn();
$currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;
$flashMessages = $session->getAllFlash();

// التحقق من IP العميل إذا كان مسجل دخول
if ($isLoggedIn && $currentPage !== 'logout.php') {
    $userIp = $_SERVER['REMOTE_ADDR'];
    if (!empty($currentUser['ip_address']) && $currentUser['ip_address'] !== $userIp) {
        // عنوان IP مختلف، تسجيل خروج
        $auth->logout();
        $session->setError('تم تسجيل خروجك لأن عنوان IP الحالي مختلف عن العنوان المسجل.');
        header('Location: login.php');
        exit;
    }
}

// توجيه الصفحات حسب حالة تسجيل الدخول
if ($isLoggedIn) {
    // المستخدم مسجل الدخول
    if (in_array($currentPage, ['login.php', 'register.php'])) {
        header('Location: dashboard.php');
        exit;
    }
} else {
    // المستخدم غير مسجل الدخول
    if (!in_array($currentPage, ['login.php', 'register.php', 'forgot_password.php', 'reset_password.php']) && $currentPage !== 'index.php') {
        $session->setInfo('يرجى تسجيل الدخول للوصول إلى هذه الصفحة.');
        header('Location: login.php');
        exit;
    }
}

// تحديد الصفحة الحالية
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// قائمة الصفحات المسموح بها حسب مستوى صلاحيات المستخدم
$allowedPages = [
    0 => ['login', 'register', 'forgot_password', 'reset_password'], // الزوار
    1 => ['home', 'calculator', 'invoice_create', 'invoice_search', 'invoice_view', 'profile', 'notifications'], // المستخدمون العاديون
    2 => ['all_invoices', 'users', 'blacklist'], // المستوى الثاني
    3 => ['dashboard', 'admin', 'pages', 'statistics', 'deleted_invoices', 'cart_numbers', 'permissions', 'order_status', 'registration_requests'] // المستوى الثالث (الإدارة)
];

// تحديد مستوى الصلاحيات الحالي
$permissionLevel = $isLoggedIn ? $currentUser['permission_level'] : 0;

// بناء قائمة الصفحات المسموح بها للمستخدم الحالي
$userAllowedPages = $allowedPages[0]; // الصفحات المسموحة للجميع
for ($i = 1; $i <= $permissionLevel; $i++) {
    $userAllowedPages = array_merge($userAllowedPages, $allowedPages[$i]);
}

// التحقق من صلاحية الوصول للصفحة
if (!in_array($page, $userAllowedPages)) {
    $session->setError('ليس لديك صلاحية للوصول إلى هذه الصفحة.');
    header('Location: ' . ($isLoggedIn ? 'dashboard.php' : 'login.php'));
    exit;
}

// إعداد مسار القالب
$templatePath = 'views/';

// تحميل رأس الصفحة
include $templatePath . 'layout/header.php';

// تحميل الصفحة المطلوبة
if ($isLoggedIn) {
    // تحميل القائمة الجانبية للمستخدمين المسجلين
    include $templatePath . 'layout/sidebar.php';
}

// عرض رسائل الفلاش
if (!empty($flashMessages)) {
    include $templatePath . 'layout/flash_messages.php';
}

// تحميل محتوى الصفحة
$pagePath = $templatePath . $page . '.php';
if (file_exists($pagePath)) {
    include $pagePath;
} else {
    echo '<div class="alert alert-danger">الصفحة المطلوبة غير موجودة.</div>';
}

// تحميل تذييل الصفحة
include $templatePath . 'layout/footer.php';
////////////////////////////////////////////////////////////////////////+3
/**
 * نقطة الدخول الرئيسية للتطبيق
 * يقوم بتحميل الملفات الأساسية وتوجيه الطلبات
 */

// تحميل ملف الإعدادات
require_once 'config.php';

// تحميل الملفات الأساسية
require_once 'Database.php';
require_once 'Session.php';
require_once 'Auth.php';

// تجنب تكرار صفحة تسجيل الدخول إذا كان المستخدم مسجل الدخول
$auth = Auth::getInstance();
$session = Session::getInstance();

// متغيرات عامة للاستخدام في الصفحات
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = $auth->isLoggedIn();
$currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;
$flashMessages = $session->getAllFlash();

// التحقق من IP العميل إذا كان مسجل دخول
if ($isLoggedIn && $currentPage !== 'logout.php') {
    $userIp = $_SERVER['REMOTE_ADDR'];
    if (!empty($currentUser['ip_address']) && $currentUser['ip_address'] !== $userIp) {
        // عنوان IP مختلف، تسجيل خروج
        $auth->logout();
        $session->setError('تم تسجيل خروجك لأن عنوان IP الحالي مختلف عن العنوان المسجل.');
        header('Location: login.php');
        exit;
    }
}

// توجيه الصفحات حسب حالة تسجيل الدخول
if ($isLoggedIn) {
    // المستخدم مسجل الدخول
    if (in_array($currentPage, ['login.php', 'register.php'])) {
        header('Location: dashboard.php');
        exit;
    }
} else {
    // المستخدم غير مسجل الدخول
    if (!in_array($currentPage, ['login.php', 'register.php', 'forgot_password.php', 'reset_password.php']) && $currentPage !== 'index.php') {
        $session->setInfo('يرجى تسجيل الدخول للوصول إلى هذه الصفحة.');
        header('Location: login.php');
        exit;
    }
}

// تحديد الصفحة الحالية
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// قائمة الصفحات المسموح بها حسب مستوى صلاحيات المستخدم
$allowedPages = [
    0 => ['login', 'register', 'forgot_password', 'reset_password'], // الزوار
    1 => ['home', 'calculator', 'invoice_create', 'invoice_search', 'invoice_view', 'profile', 'notifications'], // المستخدمون العاديون
    2 => ['all_invoices', 'users', 'blacklist'], // المستوى الثاني
    3 => ['dashboard', 'admin', 'pages', 'statistics', 'deleted_invoices', 'cart_numbers', 'permissions', 'order_status', 'registration_requests'] // المستوى الثالث (الإدارة)
];

// تحديد مستوى الصلاحيات الحالي
$permissionLevel = $isLoggedIn ? $currentUser['permission_level'] : 0;

// بناء قائمة الصفحات المسموح بها للمستخدم الحالي
$userAllowedPages = $allowedPages[0]; // الصفحات المسموحة للجميع
for ($i = 1; $i <= $permissionLevel; $i++) {
    $userAllowedPages = array_merge($userAllowedPages, $allowedPages[$i]);
}

// التحقق من صلاحية الوصول للصفحة
if (!in_array($page, $userAllowedPages)) {
    $session->setError('ليس لديك صلاحية للوصول إلى هذه الصفحة.');
    header('Location: ' . ($isLoggedIn ? 'dashboard.php' : 'login.php'));
    exit;
}

// إعداد مسار القالب
$templatePath = 'views/';

// تحميل رأس الصفحة
if (file_exists($templatePath . 'layout/header.php')) {
    include $templatePath . 'layout/header.php';

    // تحميل الصفحة المطلوبة
    if ($isLoggedIn && file_exists($templatePath . 'layout/sidebar.php')) {
        // تحميل القائمة الجانبية للمستخدمين المسجلين
        include $templatePath . 'layout/sidebar.php';
    }

    // عرض رسائل الفلاش
    if (!empty($flashMessages) && file_exists($templatePath . 'layout/flash_messages.php')) {
        include $templatePath . 'layout/flash_messages.php';
    }

    // تحميل محتوى الصفحة
    $pagePath = $templatePath . $page . '.php';
    if (file_exists($pagePath)) {
        include $pagePath;
    } else {
        echo '<div class="alert alert-danger">الصفحة المطلوبة غير موجودة.</div>';
    }

    // تحميل تذييل الصفحة
    if (file_exists($templatePath . 'layout/footer.php')) {
        include $templatePath . 'layout/footer.php';
    }
} else {
    echo '<div class="alert alert-danger">قوالب النظام غير موجودة. يرجى التحقق من التثبيت.</div>';
}