<?php
/**
 * نظام النسخ الاحتياطي التلقائي
 * يتم تشغيل هذا الملف عن طريق جدولة Cron
 * مثال: 0 1 * * * php /path/to/backup.php
 * (يتم تنفيذه يومياً في الساعة 1 صباحاً)
 */

// تحميل ملفات النظام الرئيسية
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../models/Log.php';

// إنشاء مسار النسخ الاحتياطي إذا لم يكن موجوداً
$backupPath = ROOT_PATH . '/backups';
if (!is_dir($backupPath)) {
    mkdir($backupPath, 0755, true);
}

// تحديد اسم ملف النسخ الاحتياطي (بالتاريخ والوقت)
$timestamp = date('Y-m-d_H-i-s');
$backupFile = $backupPath . "/backup_{$timestamp}.sql";

// إعدادات قاعدة البيانات (استرجاعها من ملف الإعدادات)
$dbHost = DB_HOST;
$dbName = DB_NAME;
$dbUser = DB_USER;
$dbPass = DB_PASS;

// تنفيذ النسخ الاحتياطي باستخدام mysqldump
$command = "mysqldump --host={$dbHost} --user={$dbUser} --password={$dbPass} {$dbName} > {$backupFile} 2>&1";
exec($command, $output, $returnCode);

// التحقق من نجاح العملية
if ($returnCode !== 0) {
    // حدث خطأ في عملية النسخ الاحتياطي
    logError("فشل إنشاء نسخة احتياطية: " . implode("\n", $output));
    exit(1);
}

// ضغط ملف النسخ الاحتياطي
$zipCommand = "gzip {$backupFile}";
exec($zipCommand, $zipOutput, $zipReturnCode);

// التحقق من نجاح عملية الضغط
if ($zipReturnCode !== 0) {
    // حدث خطأ في عملية ضغط الملف
    logError("فشل ضغط ملف النسخة الاحتياطية: " . implode("\n", $zipOutput));
    exit(1);
}

// تحديث اسم الملف بعد الضغط
$backupFile .= '.gz';

// حذف النسخ الاحتياطية القديمة (الاحتفاظ بآخر 7 نسخ فقط)
$files = glob($backupPath . '/backup_*.sql.gz');
if (count($files) > 7) {
    // ترتيب الملفات حسب وقت التعديل (الأقدم أولاً)
    usort($files, function($a, $b) {
        return filemtime($a) - filemtime($b);
    });
    
    // حذف الملفات القديمة
    $filesToRemove = array_slice($files, 0, count($files) - 7);
    foreach ($filesToRemove as $file) {
        unlink($file);
    }
}

// تسجيل نجاح عملية النسخ الاحتياطي
logSuccess("تم إنشاء نسخة احتياطية بنجاح: " . basename($backupFile));

// إرسال إشعار للمدراء (اختياري)
sendBackupNotification(basename($backupFile));

/**
 * تسجيل رسالة خطأ في ملف السجل
 */
function logError($message) {
    // إنشاء سجل الخطأ
    $log = new Log();
    $log->logError("نظام النسخ الاحتياطي: {$message}");
    
    // كتابة الرسالة في ملف سجل النظام
    error_log("[BACKUP ERROR] " . $message);
}

/**
 * تسجيل رسالة نجاح في ملف السجل
 */
function logSuccess($message) {
    // إنشاء سجل النجاح
    $log = new Log();
    $log->addLog(Log::TYPE_INFO, 'نسخ احتياطي تلقائي', $message);
    
    // كتابة الرسالة في ملف سجل النظام
    error_log("[BACKUP SUCCESS] " . $message);
}

/**
 * إرسال إشعار للمدراء بنجاح النسخ الاحتياطي
 */
function sendBackupNotification($filename) {
    // إنشاء اتصال بقاعدة البيانات
    $db = Database::getInstance();
    
    // الحصول على معرفات المستخدمين المدراء
    $stmt = $db->query("SELECT id FROM users WHERE role = 'admin'");
    $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($adminIds)) {
        return;
    }
    
    // إنشاء الإشعار
    $title = 'نسخة احتياطية جديدة';
    $message = "تم إنشاء نسخة احتياطية جديدة بنجاح: {$filename}";
    
    try {
        // إدراج الإشعار
        $stmt = $db->prepare("
            INSERT INTO notifications (
                title, message, type, created_at
            ) VALUES (
                :title, :message, 'info', NOW()
            )
        ");
        
        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);
        $stmt->execute();
        
        $notificationId = $db->lastInsertId();
        
        // إنشاء إشعارات المستخدمين
        $insertValues = [];
        $insertParams = [];
        
        foreach ($adminIds as $index => $adminId) {
            $insertValues[] = "(:notification_id, :user_id_{$index}, 0, NULL)";
            $insertParams[":user_id_{$index}"] = $adminId;
        }
        
        $insertSql = "
            INSERT INTO user_notifications (notification_id, user_id, is_read, read_at)
            VALUES " . implode(', ', $insertValues);
        
        $stmt = $db->prepare($insertSql);
        $stmt->bindParam(':notification_id', $notificationId, PDO::PARAM_INT);
        
        foreach ($insertParams as $param => $value) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("[BACKUP NOTIFICATION ERROR] " . $e->getMessage());
    }
}
