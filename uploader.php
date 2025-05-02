<?php
/**
 * فئة إدارة رفع الملفات
 * تدير عمليات رفع الملفات والتحقق منها ومعالجتها
 */
class Uploader {
    private $uploadPath;
    private $allowedExtensions = [];
    private $maxFileSize = 5242880; // 5 ميجابايت افتراضياً
    private $errors = [];
    private static $instance = null;
    
    /**
     * منع إنشاء كائن مباشرة (نمط Singleton)
     */
    private function __construct() {
        $this->uploadPath = UPLOADS_PATH;
        $this->allowedExtensions = explode(',', ALLOWED_EXTENSIONS);
        $this->maxFileSize = MAX_FILE_SIZE;
        
        // التأكد من وجود مجلدات الرفع
        $this->ensureDirectoriesExist();
    }
    
    /**
     * منع نسخ الكائن
     */
    private function __clone() {}
    
    /**
     * الحصول على كائن وحيد من الرافع
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * ضبط مسار الرفع
     */
    public function setUploadPath($path) {
        $this->uploadPath = $path;
        $this->ensureDirectoriesExist();
        return $this;
    }
    
    /**
     * ضبط الامتدادات المسموحة
     */
    public function setAllowedExtensions($extensions) {
        if (is_string($extensions)) {
            $extensions = explode(',', $extensions);
        }
        
        $this->allowedExtensions = array_map('trim', $extensions);
        return $this;
    }
    
    /**
     * ضبط الحجم الأقصى للملف
     */
    public function setMaxFileSize($size) {
        $this->maxFileSize = $size;
        return $this;
    }
    
    /**
     * التأكد من وجود مجلدات الرفع وإنشائها إذا لم تكن موجودة
     */
    private function ensureDirectoriesExist() {
        $directories = [
            $this->uploadPath,
            $this->uploadPath . '/images',
            $this->uploadPath . '/temp',
            $this->uploadPath . '/documents'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * التحقق من صحة الملف المرفوع
     */
    private function validateFile($file) {
        // التحقق من وجود خطأ في الرفع
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }
        
        // التحقق من حجم الملف
        if ($file['size'] > $this->maxFileSize) {
            $this->errors[] = 'حجم الملف يتجاوز الحد المسموح به (' . ($this->maxFileSize / 1048576) . ' ميجابايت)';
            return false;
        }
        
        // التحقق من نوع الملف
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            $this->errors[] = 'امتداد الملف غير مسموح به. الامتدادات المسموحة: ' . implode(', ', $this->allowedExtensions);
            return false;
        }
        
        return true;
    }
    
    /**
     * الحصول على رسالة خطأ الرفع
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'حجم الملف يتجاوز الحد المسموح به';
            case UPLOAD_ERR_PARTIAL:
                return 'تم رفع جزء من الملف فقط';
            case UPLOAD_ERR_NO_FILE:
                return 'لم يتم اختيار ملف للرفع';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'المجلد المؤقت غير موجود';
            case UPLOAD_ERR_CANT_WRITE:
                return 'فشل في كتابة الملف على القرص';
            case UPLOAD_ERR_EXTENSION:
                return 'توقف الرفع بواسطة امتداد PHP';
            default:
                return 'حدث خطأ غير معروف أثناء رفع الملف';
        }
    }
    
    /**
     * رفع ملف واحد
     */
    public function upload($file, $customName = null, $subDirectory = '') {
        $this->errors = [];
        
        // التحقق من الملف
        if (!$this->validateFile($file)) {
            return false;
        }
        
        // تحديد اسم الملف
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = $customName ? $customName . '.' . $extension : $this->generateUniqueFileName($file['name']);
        
        // تحديد مسار الرفع
        $uploadPath = $this->uploadPath;
        if ($subDirectory) {
            $uploadPath .= '/' . trim($subDirectory, '/');
            
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
        }
        
        $destination = $uploadPath . '/' . $fileName;
        
        // نقل الملف
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'name' => $fileName,
                'path' => $destination,
                'url' => str_replace(ROOT_PATH, SITE_URL, $destination),
                'size' => $file['size'],
                'type' => $file['type'],
                'extension' => $extension
            ];
        } else {
            $this->errors[] = 'فشل في نقل الملف المرفوع';
            return false;
        }
    }
    
    /**
     * رفع عدة ملفات
     */
    public function uploadMultiple($files, $subDirectory = '') {
        $results = [];
        $hasErrors = false;
        
        // إعادة ترتيب مصفوفة الملفات
        $filesArray = [];
        for ($i = 0; $i < count($files['name']); $i++) {
            $filesArray[] = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
        }
        
        // رفع كل ملف
        foreach ($filesArray as $file) {
            $result = $this->upload($file, null, $subDirectory);
            
            if ($result !== false) {
                $results[] = $result;
            } else {
                $hasErrors = true;
            }
        }
        
        return [
            'files' => $results,
            'hasErrors' => $hasErrors,
            'errors' => $this->errors
        ];
    }
    
    /**
     * رفع صورة
     */
    public function uploadImage($file, $customName = null) {
        // حفظ الامتدادات المسموحة الأصلية
        $originalExtensions = $this->allowedExtensions;
        
        // ضبط الامتدادات المسموحة للصور فقط
        $this->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
        
        // رفع الصورة
        $result = $this->upload($file, $customName, 'images');
        
        // استعادة الامتدادات المسموحة الأصلية
        $this->setAllowedExtensions($originalExtensions);
        
        return $result;
    }
    
    /**
     * رفع عدة صور
     */
    public function uploadMultipleImages($files) {
        // حفظ الامتدادات المسموحة الأصلية
        $originalExtensions = $this->allowedExtensions;
        
        // ضبط الامتدادات المسموحة للصور فقط
        $this->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
        
        // رفع الصور
        $result = $this->uploadMultiple($files, 'images');
        
        // استعادة الامتدادات المسموحة الأصلية
        $this->setAllowedExtensions($originalExtensions);
        
        return $result;
    }
    
    /**
     * إنشاء اسم ملف فريد
     */
    private function generateUniqueFileName($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $basename = preg_replace('/[^a-z0-9_-]/', '', strtolower($basename));
        
        return $basename . '_' . uniqid() . '.' . $extension;
    }
    
    /**
     * حذف ملف
     */
    public function deleteFile($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }
    
    /**
     * الحصول على أخطاء الرفع
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * ضغط صورة وتغيير حجمها
     */
    public function resizeImage($sourceFile, $targetFile, $maxWidth = 1200, $maxHeight = 1200, $quality = 80) {
        // الحصول على معلومات الصورة
        list($width, $height, $type) = getimagesize($sourceFile);
        
        // حساب الأبعاد الجديدة مع الحفاظ على النسبة
        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = $width * $ratio;
            $newHeight = $height * $ratio;
        } else {
            // لا داعي لتغيير الحجم
            $newWidth = $width;
            $newHeight = $height;
        }
        
        // إنشاء صورة فارغة بالأبعاد الجديدة
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // إنشاء الصورة المصدر حسب النوع
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($sourceFile);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($sourceFile);
                // الحفاظ على الشفافية
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($sourceFile);
                break;
            default:
                return false;
        }
        
        // نسخ وتغيير حجم الصورة
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // حفظ الصورة حسب النوع
        $result = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($newImage, $targetFile, $quality);
                break;
            case IMAGETYPE_PNG:
                // ضبط جودة PNG (0-9)
                $pngQuality = 9 - round(($quality / 100) * 9);
                $result = imagepng($newImage, $targetFile, $pngQuality);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($newImage, $targetFile);
                break;
        }
        
        // تحرير الذاكرة
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return $result;
    }
    
    /**
     * تغيير حجم الصور المرفوعة تلقائياً
     */
    public function autoResizeUploadedImage($uploadedImage, $maxWidth = 1200, $maxHeight = 1200) {
        if (!$uploadedImage || !file_exists($uploadedImage['path'])) {
            return false;
        }
        
        // يتم تغيير حجم الصورة في نفس المسار
        return $this->resizeImage($uploadedImage['path'], $uploadedImage['path'], $maxWidth, $maxHeight);
    }
}