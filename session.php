<?php
/**
 * فئة إدارة جلسات المستخدمين
 * تتعامل مع الجلسات وتتبع نشاط المستخدمين
 */
class Session {
    private static $instance = null;
    private $timeout = 3600; // مدة الجلسة بالثواني (ساعة واحدة)
    
    /**
     * منع إنشاء كائن مباشرة (نمط Singleton)
     */
    private function __construct() {
        // بدء الجلسة إذا لم تكن مبدوءة
       /* if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        */
        // تحقق من انتهاء الجلسة
        $this->checkSessionTimeout();
    }
    
    /**
     * منع نسخ الكائن
     */
    private function __clone() {}
    
    /**
     * الحصول على كائن وحيد من الجلسة
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تحقق من انتهاء مدة الجلسة
     */
    private function checkSessionTimeout() {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $this->timeout)) {
            // انتهت مدة الجلسة، تسجيل الخروج
            $this->destroy();
            header('Location: ' . base_url('login.php?timeout=1'));
            exit;
        }
        
        // تحديث وقت آخر نشاط
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * وضع قيمة في الجلسة
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
        return $this;
    }
    
    /**
     * الحصول على قيمة من الجلسة
     */
    public function get($key, $default = null) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    /**
     * التحقق من وجود قيمة في الجلسة
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * حذف قيمة من الجلسة
     */
    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        return $this;
    }
    
    /**
     * وضع رسالة فلاش (تظهر مرة واحدة فقط)
     */
    public function setFlash($key, $message) {
        $_SESSION['flash'][$key] = $message;
        return $this;
    }
    
    /**
     * الحصول على رسالة فلاش
     */
    public function getFlash($key, $default = null) {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return $default;
    }
    
    /**
     * التحقق من وجود رسالة فلاش
     */
    public function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }
    
    /**
     * الحصول على جميع رسائل الفلاش وحذفها
     */
    public function getAllFlash() {
        $flash = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
        unset($_SESSION['flash']);
        return $flash;
    }
    
    /**
     * وضع رسالة خطأ في الجلسة
     */
    public function setError($message) {
        return $this->setFlash('error', $message);
    }
    
    /**
     * وضع رسالة نجاح في الجلسة
     */
    public function setSuccess($message) {
        return $this->setFlash('success', $message);
    }
    
    /**
     * وضع رسالة تنبيه في الجلسة
     */
    public function setWarning($message) {
        return $this->setFlash('warning', $message);
    }
    
    /**
     * وضع رسالة معلومات في الجلسة
     */
    public function setInfo($message) {
        return $this->setFlash('info', $message);
    }
    
    /**
     * الحصول على كل بيانات الجلسة
     */
    public function all() {
        return $_SESSION;
    }
    
    /**
     * تدمير الجلسة الحالية
     */
    public function destroy() {
        // حذف كل البيانات في الجلسة
        $_SESSION = [];
        
        // حذف ملف تعريف الارتباط للجلسة إذا كان موجوداً
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // تدمير الجلسة
        session_destroy();
        
        return $this;
    }
    
    /**
     * إعادة توليد معرف الجلسة
     */
    public function regenerate() {
        session_regenerate_id(true);
        return $this;
    }
    
    /**
     * تعيين مدة انتهاء الجلسة
     */
    public function setTimeout($seconds) {
        $this->timeout = $seconds;
        return $this;
    }
    
    /**
     * الحصول على مدة انتهاء الجلسة
     */
    public function getTimeout() {
        return $this->timeout;
    }
    
    /**
     * تعيين بيانات مستخدم في الجلسة
     */
    public function setUser($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['permission_level'] = $user['permission_level'];
        $_SESSION['last_activity'] = time();
        
        return $this;
    }
}
    
    /**
     * تدمير