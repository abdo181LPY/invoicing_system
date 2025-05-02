<?php
/**
 * فئة إدارة قاعدة البيانات
 * تدير الاتصال بقاعدة البيانات وتوفر طرق للتعامل معها
 */
<?php
// التحقق من عدم تعريف الفئة من قبل
if (!class_exists('Database')) {
    class Database {
        private static $instance = null;
        private $connection;
        
        // منع إنشاء نسخة مباشرة من الكائن
        private function __construct() {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                
            } catch (PDOException $e) {
                error_log("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
                die("فشل الاتصال بقاعدة البيانات. يرجى التحقق من الإعدادات.");
            }
        }
        
        // منع استنساخ الكائن
        private function __clone() {}
        
        // الحصول على نسخة فريدة من الكائن
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            
            return self::$instance->connection;
        }
        
        // تنفيذ استعلام مباشر
        public static function query($sql) {
            return self::getInstance()->query($sql);
        }
        
        // تحضير استعلام مع متغيرات
        public static function prepare($sql) {
            return self::getInstance()->prepare($sql);
        }
        
        // الحصول على آخر معرف تم إدراجه
        public static function lastInsertId() {
            return self::getInstance()->lastInsertId();
        }
        
        // بدء المعاملة
        public static function beginTransaction() {
            return self::getInstance()->beginTransaction();
        }
        
        // تأكيد المعاملة
        public static function commit() {
            return self::getInstance()->commit();
        }
        
        // التراجع عن المعاملة
        public static function rollBack() {
            return self::getInstance()->rollBack();
        }
    }
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////
class Database {
    private $connection;
    private static $instance = null;
    private $queryCount = 0;
    private $transactionLevel = 0;
    
    /**
     * منع إنشاء كائن مباشرة (نمط Singleton)
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * منع نسخ الكائن
     */
    private function __clone() {}
    
    /**
     * الحصول على كائن وحيد من قاعدة البيانات
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * إنشاء اتصال بقاعدة البيانات
     */
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
        }
    }
    
    /**
     * تنفيذ استعلام قاعدة البيانات
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            $this->queryCount++;
            return $stmt;
        } catch (PDOException $e) {
            // تسجيل الخطأ
            $this->logError($e, $sql, $params);
            throw $e;
        }
    }
    
    /**
     * استعلام يعيد عدة صفوف
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * استعلام يعيد صف واحد
     */
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * استعلام يعيد قيمة واحدة
     */
    public function fetchColumn($sql, $params = [], $column = 0) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchColumn($column);
    }
    
    /**
     * إدراج بيانات وإرجاع الرقم التسلسلي
     */
    public function insert($table, $data) {
        // إعداد حقول ومعلمات الاستعلام
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ':' . $field;
        }, $fields);
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }
    
    /**
     * تحديث بيانات في جدول
     */
    public function update($table, $data, $where, $whereParams = []) {
        // إعداد حقول للتحديث
        $setFields = array_map(function($field) {
            return $field . ' = :' . $field;
        }, array_keys($data));
        
        $sql = "UPDATE {$table} SET " . implode(', ', $setFields) . " WHERE {$where}";
        
        // دمج معلمات التحديث مع معلمات الشرط
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * حذف بيانات من جدول
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * بدء معاملة
     */
    public function beginTransaction() {
        if ($this->transactionLevel == 0) {
            $this->connection->beginTransaction();
        }
        $this->transactionLevel++;
        return $this;
    }
    
    /**
     * تأكيد معاملة
     */
    public function commit() {
        if ($this->transactionLevel == 1) {
            $this->connection->commit();
        }
        $this->transactionLevel = max(0, $this->transactionLevel - 1);
        return $this;
    }
    
    /**
     * التراجع عن معاملة
     */
    public function rollback() {
        if ($this->transactionLevel == 1) {
            $this->connection->rollBack();
        }
        $this->transactionLevel = max(0, $this->transactionLevel - 1);
        return $this;
    }
    
    /**
     * التحقق من وجود قيمة في جدول
     */
    public function exists($table, $where, $params = []) {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        $result = $this->fetch($sql, $params);
        return !empty($result);
    }
    
    /**
     * عدد الصفوف المتأثرة بآخر استعلام
     */
    public function affectedRows() {
        return $this->queryCount;
    }
    
    /**
     * الحصول على عدد الاستعلامات المنفذة
     */
    public function getQueryCount() {
        return $this->queryCount;
    }
    
    /**
     * تسجيل أخطاء قاعدة البيانات
     */
    private function logError($exception, $sql, $params) {
        $logFile = ROOT_PATH . '/logs/db_errors.log';
        $timestamp = date('Y-m-d H:i:s');
        $message = "[{$timestamp}] Error: {$exception->getMessage()}\n";
        $message .= "SQL: {$sql}\n";
        $message .= "Params: " . json_encode($params) . "\n";
        $message .= "Trace: {$exception->getTraceAsString()}\n\n";
        
        // إنشاء مجلد السجلات إذا لم يكن موجوداً
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        
        // كتابة السجل
        file_put_contents($logFile, $message, FILE_APPEND);
    }
    
    /**
     * إغلاق الاتصال
     */
    public function __destruct() {
        $this->connection = null;
    }
}
// ===== تعديلات جديدة - نهاية الإعدادات السابقة =====

<?php
/**
 * فئة إدارة قاعدة البيانات
 * تدير الاتصال بقاعدة البيانات وتوفر طرق للتعامل معها
 */
// التحقق من عدم تعريف الفئة من قبل
if (!class_exists('Database')) {
    class Database {
        private $connection;
        private static $instance = null;
        private $queryCount = 0;
        private $transactionLevel = 0;
        
        /**
         * منع إنشاء كائن مباشرة (نمط Singleton)
         */
        private function __construct() {
            $this->connect();
        }
        
        /**
         * منع نسخ الكائن
         */
        private function __clone() {}
        
        /**
         * الحصول على كائن وحيد من قاعدة البيانات
         */
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        /**
         * الحصول على اتصال قاعدة البيانات
         */
        public function getConnection() {
            return $this->connection;
        }
        
        /**
         * إنشاء اتصال بقاعدة البيانات
         */
        private function connect() {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
            }
        }
        
        /**
         * تنفيذ استعلام قاعدة البيانات
         */
        public function query($sql, $params = []) {
            try {
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($params);
                $this->queryCount++;
                return $stmt;
            } catch (PDOException $e) {
                // تسجيل الخطأ
                $this->logError($e, $sql, $params);
                throw $e;
            }
        }
        
        /**
         * استعلام يعيد عدة صفوف
         */
        public function fetchAll($sql, $params = []) {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll();
        }
        
        /**
         * استعلام يعيد صف واحد
         */
        public function fetch($sql, $params = []) {
            $stmt = $this->query($sql, $params);
            return $stmt->fetch();
        }
        
        /**
         * استعلام يعيد قيمة واحدة
         */
        public function fetchColumn($sql, $params = [], $column = 0) {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchColumn($column);
        }
        
        /**
         * إدراج بيانات وإرجاع الرقم التسلسلي
         */
        public function insert($table, $data) {
            // إعداد حقول ومعلمات الاستعلام
            $fields = array_keys($data);
            $placeholders = array_map(function($field) {
                return ':' . $field;
            }, $fields);
            
            $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $this->query($sql, $data);
            return $this->connection->lastInsertId();
        }
        
        /**
         * تحديث بيانات في جدول
         */
        public function update($table, $data, $where, $whereParams = []) {
            // إعداد حقول للتحديث
            $setFields = array_map(function($field) {
                return $field . ' = :' . $field;
            }, array_keys($data));
            
            $sql = "UPDATE {$table} SET " . implode(', ', $setFields) . " WHERE {$where}";
            
            // دمج معلمات التحديث مع معلمات الشرط
            $params = array_merge($data, $whereParams);
            
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        }
        
        /**
         * حذف بيانات من جدول
         */
        public function delete($table, $where, $params = []) {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            $stmt = $this->query($sql, $params);
            return $stmt->rowCount();
        }
        
        /**
         * بدء معاملة
         */
        public function beginTransaction() {
            if ($this->transactionLevel == 0) {
                $this->connection->beginTransaction();
            }
            $this->transactionLevel++;
            return $this;
        }
        
        /**
         * تأكيد معاملة
         */
        public function commit() {
            if ($this->transactionLevel == 1) {
                $this->connection->commit();
            }
            $this->transactionLevel = max(0, $this->transactionLevel - 1);
            return $this;
        }
        
        /**
         * التراجع عن معاملة
         */
        public function rollback() {
            if ($this->transactionLevel == 1) {
                $this->connection->rollBack();
            }
            $this->transactionLevel = max(0, $this->transactionLevel - 1);
            return $this;
        }
        
        /**
         * التحقق من وجود قيمة في جدول
         */
        public function exists($table, $where, $params = []) {
            $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
            $result = $this->fetch($sql, $params);
            return !empty($result);
        }
        
        /**
         * عدد الصفوف المتأثرة بآخر استعلام
         */
        public function affectedRows() {
            return $this->queryCount;
        }
        
        /**
         * الحصول على عدد الاستعلامات المنفذة
         */
        public function getQueryCount() {
            return $this->queryCount;
        }
        
        /**
         * تسجيل أخطاء قاعدة البيانات
         */
        private function logError($exception, $sql, $params) {
            $logFile = ROOT_PATH . '/logs/db_errors.log';
            $timestamp = date('Y-m-d H:i:s');
            $message = "[{$timestamp}] Error: {$exception->getMessage()}\n";
            $message .= "SQL: {$sql}\n";
            $message .= "Params: " . json_encode($params) . "\n";
            $message .= "Trace: {$exception->getTraceAsString()}\n\n";
            
            // إنشاء مجلد السجلات إذا لم يكن موجوداً
            if (!file_exists(dirname($logFile))) {
                mkdir(dirname($logFile), 0755, true);
            }
            
            // كتابة السجل
            file_put_contents($logFile, $message, FILE_APPEND);
        }
        
        /**
         * إغلاق الاتصال
         */
        public function __destruct() {
            $this->connection = null;
        }
    }
}