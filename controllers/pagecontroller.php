<?php
/**
 * متحكم الصفحات
 * يدير عمليات الصفحات التي يتم التعامل معها
 */
class PageController {
    private $db;
    private $page;
    private $validator;
    private $session;
    private $auth;
    
    /**
     * إنشاء كائن المتحكم
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->page = new Page();
        $this->validator = Validator::getInstance();
        $this->session = Session::getInstance();
        $this->auth = Auth::getInstance();
    }
    
    /**
     * عرض قائمة الصفحات
     */
    public function index() {
        // التحقق من صلاحية عرض الصفحات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لعرض الصفحات');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على قائمة الصفحات
        $pages = $this->page->getAll();
        
        // تحميل القالب
        include 'views/admin/pages.php';
    }
    
    /**
     * عرض نموذج إضافة صفحة جديدة
     */
    public function create() {
        // التحقق من صلاحية إضافة صفحات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لإضافة صفحات');
            header('Location: dashboard.php');
            exit;
        }
        
        // تحميل القالب
        include 'views/admin/page_create.php';
    }
    
    /**
     * حفظ صفحة جديدة
     */
    public function store() {
        // التحقق من صلاحية إضافة صفحات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لإضافة صفحات');
            header('Location: dashboard.php');
            exit;
        }
        
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('name', 'اسم الصفحة')
                        ->required('instagram', 'رابط الإنستغرام')
                        ->required('phone', 'رقم الهاتف')
                        ->minLength('name', 2, 'اسم الصفحة')
                        ->maxLength('name', 100, 'اسم الصفحة')
                        ->iraqiPhone('phone', 'رقم الهاتف');
        
        // التحقق من رقم الهاتف الثانوي إذا تم إدخاله
        if (isset($_POST['alternate_phone']) && !empty($_POST['alternate_phone'])) {
            $this->validator->iraqiPhone('alternate_phone', 'رقم الهاتف الثانوي');
        }
        
        // التحقق من صحة رابط الإنستغرام
        if (isset($_POST['instagram']) && !empty($_POST['instagram']) && strpos($_POST['instagram'], 'http') !== 0) {
            $this->validator->errors['instagram'] = 'رابط الإنستغرام يجب أن يبدأ بـ http';
        }
        
        // التحقق من صحة رابط الفيسبوك إذا تم إدخاله
        if (isset($_POST['facebook']) && !empty($_POST['facebook']) && strpos($_POST['facebook'], 'http') !== 0) {
            $this->validator->errors['facebook'] = 'رابط الفيسبوك يجب أن يبدأ بـ http';
        }
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: page_create.php');
            exit;
        }
        
        // إنشاء بيانات الصفحة
        $pageData = [
            'name' => $_POST['name'],
            'instagram' => $_POST['instagram'],
            'facebook' => isset($_POST['facebook']) ? $_POST['facebook'] : null,
            'phone' => $_POST['phone'],
            'alternate_phone' => isset($_POST['alternate_phone']) ? $_POST['alternate_phone'] : null,
            'description' => isset($_POST['description']) ? $_POST['description'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // إنشاء الصفحة
        $result = $this->page->create($pageData);
        
        if (isset($result['success'])) {
            // نجاح الإنشاء
            $this->session->setSuccess('تم إنشاء الصفحة بنجاح');
            header('Location: pages.php');
            exit;
        } else {
            // فشل الإنشاء
            $this->session->setError('فشل في إنشاء الصفحة: ' . $result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: page_create.php');
            exit;
        }
    }
    
    /**
     * عرض نموذج تعديل صفحة
     */
    public function edit($id) {
        // التحقق من صلاحية تعديل الصفحات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتعديل الصفحات');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الصفحة
        $page = $this->page->getById($id);
        
        if (!$page) {
            $this->session->setError('الصفحة غير موجودة');
            header('Location: pages.php');
            exit;
        }
        
        // تحميل القالب
        include 'views/admin/page_edit.php';
    }
    
    /**
     * تحديث صفحة
     */
    public function update($id) {
        // التحقق من صلاحية تعديل الصفحات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتعديل الصفحات');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الصفحة
        $page = $this->page->getById($id);
        
        if (!$page) {
            $this->session->setError('الصفحة غير موجودة');
            header('Location: pages.php');
            exit;
        }
        
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('name', 'اسم الصفحة')
                        ->required('instagram', 'رابط الإنستغرام')
                        ->required('phone', 'رقم الهاتف')
                        ->minLength('name', 2, 'اسم الصفحة')
                        ->maxLength('name', 100, 'اسم الصفحة')
                        ->iraqiPhone('phone', 'رقم الهاتف');
        
        // التحقق من رقم الهاتف الثانوي إذا تم إدخاله
        if (isset($_POST['alternate_phone']) && !empty($_POST['alternate_phone'])) {
            $this->validator->iraqiPhone('alternate_phone', 'رقم الهاتف الثانوي');
        }
        
        // التحقق من صحة رابط الإنستغرام
        if (isset($_POST['instagram']) && !empty($_POST['instagram']) && strpos($_POST['instagram'], 'http') !== 0) {
            $this->validator->errors['instagram'] = 'رابط الإنستغرام يجب أن يبدأ بـ http';
        }
        
        // التحقق من صحة رابط الفيسبوك إذا تم إدخاله
        if (isset($_POST['facebook']) && !empty($_POST['facebook']) && strpos($_POST['facebook'], 'http') !== 0) {
            $this->validator->errors['facebook'] = 'رابط الفيسبوك يجب أن يبدأ بـ http';
        }
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: page_edit.php?id=' . $id);
            exit;
        }
        
        // إنشاء بيانات الصفحة
        $pageData = [
            'name' => $_POST['name'],
            'instagram' => $_POST['instagram'],
            'facebook' => isset($_POST['facebook']) ? $_POST['facebook'] : null,
            'phone' => $_POST['phone'],
            'alternate_phone' => isset($_POST['alternate_phone']) ? $_POST['alternate_phone'] : null,
            'description' => isset($_POST['description']) ? $_POST['description'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // تحديث الصفحة
        $result = $this->page->update($id, $pageData);
        
        if (isset($result['success'])) {
            // نجاح التحديث
            $this->session->setSuccess('تم تحديث الصفحة بنجاح');
            header('Location: pages.php');
            exit;
        } else {
            // فشل التحديث
            $this->session->setError('فشل في تحديث الصفحة: ' . $result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: page_edit.php?id=' . $id);
            exit;
        }
    }
    
    /**
     * حذف صفحة
     */
    public function delete($id) {
        // التحقق من صلاحية حذف الصفحات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لحذف الصفحات');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الصفحة
        $page = $this->page->getById($id);
        
        if (!$page) {
            $this->session->setError('الصفحة غير موجودة');
            header('Location: pages.php');
            exit;
        }
        
        // حذف الصفحة
        $result = $this->page->delete($id);
        
        if (isset($result['success'])) {
            // نجاح الحذف
            $this->session->setSuccess('تم حذف الصفحة بنجاح');
        } else {
            // فشل الحذف
            $this->session->setError('فشل في حذف الصفحة: ' . $result['error']);
        }
        
        // إعادة التوجيه إلى صفحة الصفحات
        header('Location: pages.php');
        exit;
    }
    
    /**
     * تحديث حالة الصفحة (نشطة/غير نشطة)
     */
    public function updateStatus($id) {
        // التحقق من صلاحية تعديل الصفحات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتعديل الصفحات');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الصفحة
        $page = $this->page->getById($id);
        
        if (!$page) {
            $this->session->setError('الصفحة غير موجودة');
            header('Location: pages.php');
            exit;
        }
        
        // تحديث الحالة
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $result = $this->page->update($id, ['is_active' => $isActive]);
        
        if (isset($result['success'])) {
            $status = $isActive ? 'نشطة' : 'غير نشطة';
            $this->session->setSuccess('تم تحديث حالة الصفحة إلى ' . $status . ' بنجاح');
        } else {
            $this->session->setError('فشل في تحديث حالة الصفحة');
        }
        
        // إعادة التوجيه إلى صفحة الصفحات
        header('Location: pages.php');
        exit;
    }
    
    /**
     * عرض تفاصيل الصفحة
     */
    public function view($id) {
        // التحقق من صلاحية عرض الصفحات
        if (!$this->auth->hasPermission(2)) {
            $this->session->setError('ليس لديك صلاحية لعرض تفاصيل الصفحات');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الصفحة
        $page = $this->page->getById($id);
        
        if (!$page) {
            $this->session->setError('الصفحة غير موجودة');
            header('Location: pages.php');
            exit;
        }
        
        // الحصول على إحصائيات الصفحة
        $invoiceCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoices WHERE page_id = :page_id", 
            ['page_id' => $id]
        );
        
        $totalSales = $this->db->fetchColumn(
            "SELECT SUM(total_price) FROM invoices WHERE page_id = :page_id", 
            ['page_id' => $id]
        );
        
        // الحصول على آخر الفواتير للصفحة
        $recentInvoices = $this->db->fetchAll(
            "SELECT * FROM invoices WHERE page_id = :page_id ORDER BY created_at DESC LIMIT 10", 
            ['page_id' => $id]
        );
        
        // تحميل القالب
        include 'views/admin/page_view.php';
    }
    
    /**
     * البحث عن الصفحات
     */
    public function search() {
        // التحقق من صلاحية عرض الصفحات
        if (!$this->auth->hasPermission(2)) {
            $this->session->setError('ليس لديك صلاحية للبحث عن الصفحات');
            header('Location: dashboard.php');
            exit;
        }
        
        // مصطلح البحث
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        
        // البحث عن الصفحات
        $pages = $this->page->search($keyword);
        
        // تحميل قالب نتائج البحث
        include 'views/admin/page_search_results.php';
    }
}