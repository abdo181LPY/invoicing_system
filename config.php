<?php
/**
 * ملف الإعدادات الرئيسي للنظام
 * يحتوي على جميع الإعدادات الأساسية للنظام
 */
// تعريف المسار الجذر للتطبيق
define('ROOT_PATH', __DIR__);

// معلومات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');  // استبدلها بخادم قاعدة البيانات الخاص بك
define('DB_NAME', 'invoicing_system');  // استبدلها باسم قاعدة البيانات الخاصة بك
define('DB_USER', 'root');  // استبدلها باسم المستخدم الخاص بك
define('DB_PASS', '');  // استبدلها بكلمة المرور الخاصة بك

// إعدادات النظام
define('SITE_NAME', 'نظام إدارة الفواتير');
define('DEFAULT_EXCHANGE_RATE', 1300);  // سعر صرف الدولار مقابل الدينار العراقي الافتراضي
define('DEPOSIT_RATE_DEFAULT', 0.3);  // نسبة العربون الافتراضية (30%)
define('DEPOSIT_RATE_HIGH', 0.5);  // نسبة العربون للطلبات المرتفعة (50%)

// إعدادات الجلسة
// إعدادات الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// تعريف ثوابت خاصة بالتحميل
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);  // 5 ميجابايت
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// ضبط توقيت النظام
date_default_timezone_set('Asia/Baghdad');

// تحميل الدوال المساعدة
require_once ROOT_PATH . '/includes/helpers.php';

// تكوين مصفوفة لإشعارات الخطأ
$errors = [];
$success_messages = [];

// التعامل مع أخطاء PHP
error_reporting(E_ALL);
ini_set('display_errors', 0); // تعطيل عرض الأخطاء للمستخدمين
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/php_errors.log');

// إنشاء مجلد السجلات إذا لم يكن موجودًا
if (!is_dir(ROOT_PATH . '/logs')) {
    mkdir(ROOT_PATH . '/logs', 0755, true);
}

// إنشاء مجلد التحميلات إذا لم يكن موجودًا
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
/////////////////////////////////////////////////////////////
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invoicing_system');
define('DB_CHARSET', 'utf8mb4');

// إعدادات عامة للنظام
define('SITE_NAME', 'نظام إدارة الفواتير');
define('SITE_URL', 'http://localhost/invoicing_system');
define('ADMIN_EMAIL', 'admin@example.com');

// إعدادات المسارات
define('ROOT_PATH', dirname(__FILE__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('IMAGES_PATH', UPLOADS_PATH . '/images');
define('TEMP_PATH', UPLOADS_PATH . '/temp');

// إعدادات رفع الملفات
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 ميجابايت
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf');

// أسعار الصرف
define('EXCHANGE_RATE', 1300); // سعر صرف الدولار بالدينار العراقي

// تكاليف التوصيل
define('BAGHDAD_DELIVERY_COST', 6000); // تكلفة التوصيل لبغداد
define('PROVINCES_DELIVERY_COST', 7000); // تكلفة التوصيل للمحافظات

// تكوين المحافظات
$PROVINCES = [
    'بغداد',
    'البصرة',
    'نينوى',
    'أربيل',
    'النجف',
    'كربلاء',
    'كركوك',
    'ديالى',
    'الأنبار',
    'بابل',
    'ذي قار',
    'صلاح الدين',
    'السليمانية',
    'واسط',
    'ميسان',
    'المثنى',
    'دهوك',
    'القادسية'
];

// تكوين حالات الطلبات
$ORDER_STATUSES = [
    'جديد',
    'ليث',
    'الوسيط',
    'لم يتم الشراء',
    'تم الشراء',
    'تم الشحن',
    'مرتجع',
    'مكتمل',
    'ملغي'
];

// ضبط المنطقة الزمنية
date_default_timezone_set('Asia/Baghdad');

// ضبط إعدادات التقارير والأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تكوين الجلسات
// الترتيب الصحيح
/*
ini_set('session.cookie_httponly', 1); // أي إعدادات جلسة تريدها
session_start();*/
// دالة لتحميل الملفات تلقائياً
session_start();
spl_autoload_register(function ($className) {
    // تحويل اسم الفئة إلى مسار ملف
    $classFile = ROOT_PATH . '/' . str_replace('\\', '/', $className) . '.php';
    
    // تحميل الملف إذا كان موجوداً
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});




// ===== تعديلات جديدة - نهاية الإعدادات السابقة =====

/**
 * ملف الإعدادات الرئيسي للنظام
 * يحتوي على جميع الإعدادات الأساسية للنظام
 */
// تعريف المسار الجذر للتطبيق
/**define('ROOT_PATH', __DIR__);

// معلومات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invoicing_system');
define('DB_CHARSET', 'utf8mb4');

// إعدادات عامة للنظام
define('SITE_NAME', 'نظام إدارة الفواتير');
define('SITE_URL', 'http://localhost/invoicing_system');
define('ADMIN_EMAIL', 'admin@example.com');

// إعدادات المسارات
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('IMAGES_PATH', UPLOADS_PATH . '/images');
define('TEMP_PATH', UPLOADS_PATH . '/temp');

// إعدادات رفع الملفات
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 ميجابايت
define('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,pdf');

// أسعار الصرف والتوصيل
define('EXCHANGE_RATE', 1300); // سعر صرف الدولار بالدينار العراقي
define('DEPOSIT_RATE_DEFAULT', 0.3);  // نسبة العربون الافتراضية (30%)
define('DEPOSIT_RATE_HIGH', 0.5);  // نسبة العربون للطلبات المرتفعة (50%)
define('BAGHDAD_DELIVERY_COST', 6000); // تكلفة التوصيل لبغداد
define('PROVINCES_DELIVERY_COST', 7000); // تكلفة التوصيل للمحافظات
**/
// تكوين المحافظات
$PROVINCES = [
    'بغداد',
    'البصرة',
    'نينوى',
    'أربيل',
    'النجف',
    'كربلاء',
    'كركوك',
    'ديالى',
    'الأنبار',
    'بابل',
    'ذي قار',
    'صلاح الدين',
    'السليمانية',
    'واسط',
    'ميسان',
    'المثنى',
    'دهوك',
    'القادسية'
];

// تكوين حالات الطلبات
$ORDER_STATUSES = [
    'جديد',
    'ليث',
    'الوسيط',
    'لم يتم الشراء',
    'تم الشراء',
    'تم الشحن',
    'مرتجع',
    'مكتمل',
    'ملغي'
];

// ضبط المنطقة الزمنية
date_default_timezone_set('Asia/Baghdad');

// التعامل مع أخطاء PHP
error_reporting(E_ALL);
ini_set('display_errors', 0); // تعطيل عرض الأخطاء للمستخدمين
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/php_errors.log');

// إعدادات الجلسة
/*
ini_set('session.cookie_httponly', 1);
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}*/

// إنشاء مجلد السجلات إذا لم يكن موجودًا
if (!is_dir(ROOT_PATH . '/logs')) {
    mkdir(ROOT_PATH . '/logs', 0755, true);
}

// إنشاء مجلدات التحميلات إذا لم تكن موجودة
if (!is_dir(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}
if (!is_dir(IMAGES_PATH)) {
    mkdir(IMAGES_PATH, 0755, true);
}
if (!is_dir(TEMP_PATH)) {
    mkdir(TEMP_PATH, 0755, true);
}

// تحميل الدوال المساعدة إذا كان الملف موجودًا
if (file_exists(ROOT_PATH . '/includes/helpers.php')) {
    require_once ROOT_PATH . '/includes/helpers.php';
}

// دالة مساعدة للتعامل مع المسارات
function base_url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

// دالة مساعدة للتحويل بين الدولار والدينار
function usd_to_iqd($amount) {
    return $amount * EXCHANGE_RATE;
}

// دالة مساعدة لتقريب المبلغ لأقرب 5000 دينار للأعلى
function round_up_to_nearest_5000($amount) {
    return ceil($amount / 5000) * 5000;
}

// دالة لتحميل الملفات تلقائياً
spl_autoload_register(function ($className) {
    // تحويل اسم الفئة إلى مسار ملف
    if (file_exists(ROOT_PATH . '/' . $className . '.php')) {
        require_once ROOT_PATH . '/' . $className . '.php';
    } elseif (file_exists(ROOT_PATH . '/models/' . $className . '.php')) {
        require_once ROOT_PATH . '/models/' . $className . '.php';
    } elseif (file_exists(ROOT_PATH . '/controllers/' . $className . '.php')) {
        require_once ROOT_PATH . '/controllers/' . $className . '.php';
    }
});