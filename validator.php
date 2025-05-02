<?php
/**
 * فئة التحقق من البيانات
 * تستخدم للتحقق من صحة البيانات المدخلة وتنقيتها
 */
class Validator {
    private $errors = [];
    private $data = [];
    private static $instance = null;
    
    /**
     * منع إنشاء كائن مباشرة (نمط Singleton)
     */
    private function __construct() {}
    
    /**
     * منع نسخ الكائن
     */
    private function __clone() {}
    
    /**
     * الحصول على كائن وحيد من المدقق
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * تعيين البيانات للتحقق منها
     */
    public function setData($data) {
        $this->data = $data;
        $this->errors = [];
        return $this;
    }
    
    /**
     * التحقق من مطلوبية الحقل
     */
    public function required($field, $label = null) {
        $label = $label ?: $field;
        
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = "حقل {$label} مطلوب";
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة رقم
     */
    public function numeric($field, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !is_numeric($this->data[$field])) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون رقماً";
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة رقم صحيح
     */
    public function integer($field, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون رقماً صحيحاً";
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة رقم عشري
     */
    public function decimal($field, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!preg_match('/^[0-9]+(\.[0-9]+)?$/', $this->data[$field])) {
                $this->errors[$field] = "حقل {$label} يجب أن يكون رقماً عشرياً";
            }
        }
        
        return $this;
    }
    
    /**
     * التحقق من الحد الأدنى للقيمة الرقمية
     */
    public function min($field, $min, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && $this->data[$field] < $min) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون على الأقل {$min}";
        }
        
        return $this;
    }
    
    /**
     * التحقق من الحد الأقصى للقيمة الرقمية
     */
    public function max($field, $max, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && $this->data[$field] > $max) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون على الأكثر {$max}";
        }
        
        return $this;
    }
    
    /**
     * التحقق من الحد الأدنى لطول النص
     */
    public function minLength($field, $length, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) < $length) {
            $this->errors[$field] = "حقل {$label} يجب أن يحتوي على الأقل {$length} أحرف";
        }
        
        return $this;
    }
    
    /**
     * التحقق من الحد الأقصى لطول النص
     */
    public function maxLength($field, $length, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && mb_strlen($this->data[$field]) > $length) {
            $this->errors[$field] = "حقل {$label} يجب أن يحتوي على الأكثر {$length} أحرف";
        }
        
        return $this;
    }
    
    /**
     * التحقق من صحة البريد الإلكتروني
     */
    public function email($field, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون بريداً إلكترونياً صحيحاً";
        }
        
        return $this;
    }
    
    /**
     * التحقق من صحة عنوان URL
     */
    public function url($field, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون عنوان URL صحيحاً";
        }
        
        return $this;
    }
    
    /**
     * التحقق من تطابق قيمتين
     */
    public function matches($field, $matchField, $label = null, $matchLabel = null) {
        $label = $label ?: $field;
        $matchLabel = $matchLabel ?: $matchField;
        
        if (isset($this->data[$field]) && isset($this->data[$matchField]) && 
            $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = "حقل {$label} يجب أن يتطابق مع حقل {$matchLabel}";
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة تطابق نمط معين
     */
    public function pattern($field, $pattern, $message, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !preg_match($pattern, $this->data[$field])) {
            $this->errors[$field] = $message ?: "حقل {$label} غير صالح";
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة هي رقم هاتف عراقي صحيح
     */
    public function iraqiPhone($field, $label = null) {
        $label = $label ?: $field;
        
        // النمط: يبدأ بـ 07 ويتكون من 11 رقم
        $pattern = '/^07[0-9]{9}$/';
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !preg_match($pattern, $this->data[$field])) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون رقم هاتف عراقي صحيح يبدأ بـ 07 ويتكون من 11 رقم";
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة موجودة في مصفوفة معينة
     */
    public function inArray($field, $array, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !in_array($this->data[$field], $array)) {
            $this->errors[$field] = "قيمة حقل {$label} غير صالحة";
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة هي عنوان IP صحيح
     */
    public function ip($field, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && !filter_var($this->data[$field], FILTER_VALIDATE_IP)) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون عنوان IP صحيحاً";
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة تبدأ بنص معين
     */
    public function startsWith($field, $prefix, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '' && strpos($this->data[$field], $prefix) !== 0) {
            $this->errors[$field] = "حقل {$label} يجب أن يبدأ بـ {$prefix}";
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة غير موجودة في قاعدة البيانات
     */
    public function unique($field, $table, $exceptId = null, $label = null) {
        $label = $label ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $db = Database::getInstance();
            
            $sql = "SELECT 1 FROM {$table} WHERE {$field} = :value";
            $params = ['value' => $this->data[$field]];
            
            if ($exceptId !== null) {
                $sql .= " AND id != :id";
                $params['id'] = $exceptId;
            }
            
            $sql .= " LIMIT 1";
            
            if ($db->exists($sql, $params)) {
                $this->errors[$field] = "قيمة حقل {$label} موجودة بالفعل";
            }
        }
        
        return $this;
    }
    
    /**
     * التحقق من أن القيمة موجودة في قاعدة البيانات
     */
    public function exists($field, $table, $column = null, $label = null) {
        $label = $label ?: $field;
        $column = $column ?: $field;
        
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $db = Database::getInstance();
            
            $sql = "SELECT 1 FROM {$table} WHERE {$column} = :value LIMIT 1";
            $params = ['value' => $this->data[$field]];
            
            if (!$db->exists($sql, $params)) {
                $this->errors[$field] = "قيمة حقل {$label} غير موجودة";
            }
        }
        
        return $this;
    }
    
    /**
     * تنظيف النص من العلامات HTML
     */
    public function sanitize($field) {
        if (isset($this->data[$field])) {
            $this->data[$field] = htmlspecialchars($this->data[$field], ENT_QUOTES, 'UTF-8');
        }
        
        return $this;
    }
    
    /**
     * تنقية البيانات بإزالة المسافات الزائدة
     */
    public function trim($field) {
        if (isset($this->data[$field])) {
            $this->data[$field] = trim($this->data[$field]);
        }
        
        return $this;
    }
    
    /**
     * تنظيف بيانات النموذج بالكامل
     */
    public function sanitizeAll() {
        foreach ($this->data as $field => $value) {
            if (is_string($value)) {
                $this->sanitize($field)->trim($field);
            }
        }
        
        return $this;
    }
    
    /**
     * التحقق من صحة كافة البيانات
     */
    public function validate() {
        return empty($this->errors);
    }
    
    /**
     * الحصول على أخطاء التحقق
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * الحصول على خطأ حقل معين
     */
    public function getError($field) {
        return isset($this->errors[$field]) ? $this->errors[$field] : null;
    }
    
    /**
     * الحصول على البيانات المنقحة
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * الحصول على قيمة حقل معين
     */
    public function getValue($field, $default = null) {
        return isset($this->data[$field]) ? $this->data[$field] : $default;
    }
}