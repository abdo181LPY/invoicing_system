<?php
/**
 * متحكم القائمة السوداء
 * يدير عمليات الأرقام المحظورة في القائمة السوداء
 */
class BlacklistController {
    private $db;
    private $blacklist;
    private $validator;
    private $session;
    private $auth;
    private $uploader;
    
    /**
     * إنشاء كائن المتحكم
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->blacklist = new Blacklist();
        $this->validator = Validator::getInstance();
        $this->session = Session::getInstance();
        $this->auth = Auth::getInstance();
        $this->uploader = Uploader::getInstance();
    }
    
    /**
     * عرض قائمة الأرقام المحظورة
     */
    public function index() {
        // التحقق من صلاحية عرض القائمة السوداء
        if (!$this->auth->hasPermission(2)) {
            $this->session->setError('ليس لديك صلاحية لعرض القائمة السوداء');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على قائمة الأرقام المحظورة
        $blacklist = $this->blacklist->getAll();
        
        // تحميل القالب
        include 'views/admin/blacklist.php';
    }
    
    /**
     * عرض نموذج إضافة رقم محظور
     */
    public function create() {
        // التحقق من صلاحية إضافة أرقام محظورة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لإضافة أرقام محظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // تحميل القالب
        include 'views/admin/blacklist_create.php';
    }
    
    /**
     * حفظ رقم محظور جديد
     */
    public function store() {
        // التحقق من صلاحية إضافة أرقام محظورة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لإضافة أرقام محظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('phone', 'رقم الهاتف')
                        ->iraqiPhone('phone', 'رقم الهاتف');
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: blacklist_create.php');
            exit;
        }
        
        // إضافة الرقم المحظور
        $result = $this->blacklist->add($_POST['phone'], isset($_POST['reason']) ? $_POST['reason'] : null);
        
        if (isset($result['success'])) {
            // نجاح الإضافة
            $this->session->setSuccess('تم إضافة الرقم المحظور بنجاح');
            header('Location: blacklist.php');
            exit;
        } else {
            // فشل الإضافة
            $this->session->setError('فشل في إضافة الرقم المحظور: ' . $result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: blacklist_create.php');
            exit;
        }
    }
    
    /**
     * عرض نموذج تعديل رقم محظور
     */
    public function edit($id) {
        // التحقق من صلاحية تعديل الأرقام المحظورة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتعديل الأرقام المحظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الرقم المحظور
        $blacklistItem = $this->blacklist->getById($id);
        
        if (!$blacklistItem) {
            $this->session->setError('الرقم المحظور غير موجود');
            header('Location: blacklist.php');
            exit;
        }
        
        // تحميل القالب
        include 'views/admin/blacklist_edit.php';
    }
    
    /**
     * تحديث رقم محظور
     */
    public function update($id) {
        // التحقق من صلاحية تعديل الأرقام المحظورة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتعديل الأرقام المحظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الرقم المحظور
        $blacklistItem = $this->blacklist->getById($id);
        
        if (!$blacklistItem) {
            $this->session->setError('الرقم المحظور غير موجود');
            header('Location: blacklist.php');
            exit;
        }
        
        // تحديث سبب الحظر
        $reason = isset($_POST['reason']) ? $_POST['reason'] : null;
        $result = $this->blacklist->updateReason($id, $reason);
        
        if ($result) {
            // نجاح التحديث
            $this->session->setSuccess('تم تحديث سبب الحظر بنجاح');
            header('Location: blacklist.php');
            exit;
        } else {
            // فشل التحديث
            $this->session->setError('فشل في تحديث سبب الحظر');
            $this->session->setFlash('old', $_POST);
            header('Location: blacklist_edit.php?id=' . $id);
            exit;
        }
    }
    
    /**
     * حذف رقم محظور
     */
    public function delete($id) {
        // التحقق من صلاحية حذف الأرقام المحظورة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لحذف الأرقام المحظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الرقم المحظور
        $blacklistItem = $this->blacklist->getById($id);
        
        if (!$blacklistItem) {
            $this->session->setError('الرقم المحظور غير موجود');
            header('Location: blacklist.php');
            exit;
        }
        
        // حذف الرقم المحظور
        $result = $this->blacklist->remove($id);
        
        if (isset($result['success'])) {
            // نجاح الحذف
            $this->session->setSuccess('تم حذف الرقم المحظور بنجاح');
        } else {
            // فشل الحذف
            $this->session->setError('فشل في حذف الرقم المحظور: ' . $result['error']);
        }
        
        // إعادة التوجيه إلى صفحة القائمة السوداء
        header('Location: blacklist.php');
        exit;
    }
    
    /**
     * البحث عن رقم هاتف
     */
    public function search() {
        // التحقق من صلاحية البحث عن الأرقام المحظورة
        if (!$this->auth->hasPermission(2)) {
            $this->session->setError('ليس لديك صلاحية للبحث عن الأرقام المحظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // مصطلح البحث
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        
        // البحث عن الأرقام المحظورة
        $blacklist = $this->blacklist->search($keyword);
        
        // تحميل قالب نتائج البحث
        include 'views/admin/blacklist_search_results.php';
    }
    
    /**
     * التحقق من رقم هاتف
     */
    public function check() {
        // التحقق من صلاحية التحقق من الأرقام المحظورة
        if (!$this->auth->hasPermission(1)) {
            $this->session->setError('ليس لديك صلاحية للتحقق من الأرقام المحظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // رقم الهاتف
        $phone = isset($_GET['phone']) ? $_GET['phone'] : '';
        
        // التحقق من صحة الرقم
        if (empty($phone) || !preg_match('/^07[0-9]{9}$/', $phone)) {
            echo json_encode(['error' => 'رقم الهاتف غير صالح']);
            exit;
        }
        
        // التحقق من وجود الرقم في القائمة السوداء
        $isBlacklisted = $this->blacklist->exists($phone);
        
        if ($isBlacklisted) {
            // الحصول على بيانات الرقم المحظور
            $blacklistItem = $this->blacklist->getByPhone($phone);
            
            echo json_encode([
                'is_blacklisted' => true,
                'data' => $blacklistItem
            ]);
        } else {
            echo json_encode([
                'is_blacklisted' => false
            ]);
        }
        
        exit;
    }
    
    /**
     * استيراد أرقام محظورة من ملف
     */
    public function import() {
        // التحقق من صلاحية استيراد الأرقام المحظورة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لاستيراد الأرقام المحظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // التحقق من وجود ملف
        if (!isset($_FILES['import_file']) || empty($_FILES['import_file']['name'])) {
            $this->session->setError('يرجى اختيار ملف للاستيراد');
            header('Location: blacklist_import.php');
            exit;
        }
        
        // رفع الملف
        $file = $_FILES['import_file'];
        $allowedExtensions = ['txt', 'csv'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $allowedExtensions)) {
            $this->session->setError('صيغة الملف غير مدعومة. الصيغ المدعومة هي: ' . implode(', ', $allowedExtensions));
            header('Location: blacklist_import.php');
            exit;
        }
        
        // رفع الملف
        $uploadPath = TEMP_PATH . '/' . uniqid() . '.' . $fileExt;
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $this->session->setError('فشل في رفع الملف');
            header('Location: blacklist_import.php');
            exit;
        }
        
        // استيراد الأرقام
        $reason = isset($_POST['reason']) ? $_POST['reason'] : null;
        $result = $this->blacklist->importFromFile($uploadPath, $reason);
        
        // حذف الملف المؤقت
        unlink($uploadPath);
        
        if (isset($result['success'])) {
            // نجاح الاستيراد
            $this->session->setSuccess('تم استيراد الأرقام المحظورة بنجاح. تم إضافة: ' . $result['added'] . ', تم تخطي: ' . $result['skipped'] . ', الإجمالي: ' . $result['total']);
            header('Location: blacklist.php');
            exit;
        } else {
            // فشل الاستيراد
            $this->session->setError('فشل في استيراد الأرقام المحظورة: ' . $result['error']);
            header('Location: blacklist_import.php');
            exit;
        }
    }
    
    /**
     * تصدير الأرقام المحظورة إلى ملف
     */
    public function export() {
        // التحقق من صلاحية تصدير الأرقام المحظورة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتصدير الأرقام المحظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // تصدير الأرقام المحظورة
        $exportPath = TEMP_PATH . '/blacklist_export_' . date('Y-m-d_H-i-s') . '.csv';
        $result = $this->blacklist->exportToFile($exportPath);
        
        if (isset($result['success'])) {
            // نجاح التصدير
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="blacklist_export_' . date('Y-m-d_H-i-s') . '.csv"');
            
            // قراءة الملف
            readfile($exportPath);
            
            // حذف الملف المؤقت
            unlink($exportPath);
            exit;
        } else {
            // فشل التصدير
            $this->session->setError('فشل في تصدير الأرقام المحظورة: ' . $result['error']);
            header('Location: blacklist.php');
            exit;
        }
    }
    
    /**
     * عرض صفحة استيراد الأرقام المحظورة
     */
    public function showImport() {
        // التحقق من صلاحية استيراد الأرقام المحظورة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لاستيراد الأرقام المحظورة');
            header('Location: dashboard.php');
            exit;
        }
        
        // تحميل القالب
        include 'views/admin/blacklist_import.php';
    }
}