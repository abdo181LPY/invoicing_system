<?php
/**
 * متحكم المستخدمين
 * يدير عمليات المستخدمين وصلاحياتهم
 */
class UserController {
    private $db;
    private $user;
    private $validator;
    private $session;
    private $auth;
    private $notification;
    
    /**
     * إنشاء كائن المتحكم
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->user = new User();
        $this->validator = Validator::getInstance();
        $this->session = Session::getInstance();
        $this->auth = Auth::getInstance();
        $this->notification = Notification::getInstance();
    }
    
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLogin() {
        // تحميل القالب
        include 'views/auth/login.php';
    }
    
    /**
     * تنفيذ تسجيل الدخول
     */
    public function login() {
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('username', 'اسم المستخدم')
                        ->required('password', 'كلمة المرور');
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: login.php');
            exit;
        }
        
        // تسجيل الدخول
        $result = $this->user->login($_POST['username'], $_POST['password'], $_SERVER['REMOTE_ADDR']);
        
        if (is_array($result) && isset($result['error'])) {
            // فشل تسجيل الدخول
            $this->session->setError($result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: login.php');
            exit;
        } elseif ($result) {
            // نجاح تسجيل الدخول
            // تحديث آخر تسجيل دخول
            $userId = $this->auth->getCurrentUserId();
            $this->user->updateLastLogin($userId);
            
            // إعادة التوجيه إلى لوحة التحكم
            header('Location: dashboard.php');
            exit;
        } else {
            // فشل تسجيل الدخول - اسم المستخدم أو كلمة المرور غير صحيحة
            $this->session->setError('اسم المستخدم أو كلمة المرور غير صحيحة');
            $this->session->setFlash('old', ['username' => $_POST['username']]);
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * تسجيل الخروج
     */
    public function logout() {
        // تسجيل الخروج
        $this->auth->logout();
        
        // إعادة التوجيه إلى صفحة تسجيل الدخول
        $this->session->setSuccess('تم تسجيل الخروج بنجاح');
        header('Location: login.php');
        exit;
    }
    
    /**
     * عرض صفحة التسجيل
     */
    public function showRegister() {
        // تحميل القالب
        include 'views/auth/register.php';
    }
    
    /**
     * تنفيذ التسجيل
     */
    public function register() {
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('username', 'اسم المستخدم')
                        ->required('password', 'كلمة المرور')
                        ->required('password_confirm', 'تأكيد كلمة المرور')
                        ->required('email', 'البريد الإلكتروني')
                        ->required('phone', 'رقم الهاتف')
                        ->minLength('username', 3, 'اسم المستخدم')
                        ->maxLength('username', 50, 'اسم المستخدم')
                        ->minLength('password', 6, 'كلمة المرور')
                        ->matches('password', 'password_confirm', 'كلمة المرور', 'تأكيد كلمة المرور')
                        ->email('email', 'البريد الإلكتروني')
                        ->iraqiPhone('phone', 'رقم الهاتف');
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: register.php');
            exit;
        }
        
        // إنشاء بيانات المستخدم
        $userData = [
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ];
        
        // تسجيل المستخدم
        $result = $this->auth->register($userData);
        
        if (isset($result['error'])) {
            // فشل التسجيل
            $this->session->setError($result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: register.php');
            exit;
        } else {
            // نجاح التسجيل
            $this->session->setSuccess($result['success']);
            
            // إنشاء إشعار للمسؤولين
            $this->notification->notifyNewRegistration($result['registration_id'] ?? 0, $_POST['username']);
            
            // إعادة التوجيه إلى صفحة تسجيل الدخول
            header('Location: login.php');
            exit;
        }
    }
    
    /**
     * عرض طلبات التسجيل
     */
    public function showRegistrationRequests() {
        // التحقق من صلاحية عرض طلبات التسجيل
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لعرض طلبات التسجيل');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على طلبات التسجيل
        $requests = $this->user->getRegistrationRequests('pending');
        
        // تحميل القالب
        include 'views/admin/registration_requests.php';
    }
    
    /**
     * الموافقة على طلب تسجيل
     */
    public function approveRegistration($requestId) {
        // التحقق من صلاحية الموافقة على طلبات التسجيل
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية للموافقة على طلبات التسجيل');
            header('Location: dashboard.php');
            exit;
        }
        
        // الموافقة على الطلب
        $result = $this->user->approveRegistration($requestId);
        
        if (isset($result['success'])) {
            // نجاح الموافقة
            $this->session->setSuccess('تمت الموافقة على طلب التسجيل بنجاح');
        } else {
            // فشل الموافقة
            $this->session->setError('فشل في الموافقة على طلب التسجيل: ' . $result['error']);
        }
        
        // إعادة التوجيه إلى صفحة طلبات التسجيل
        header('Location: registration_requests.php');
        exit;
    }
    
    /**
     * رفض طلب تسجيل
     */
    public function rejectRegistration($requestId) {
        // التحقق من صلاحية رفض طلبات التسجيل
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لرفض طلبات التسجيل');
            header('Location: dashboard.php');
            exit;
        }
        
        // سبب الرفض
        $reason = isset($_POST['reason']) ? $_POST['reason'] : null;
        
        // رفض الطلب
        $result = $this->user->rejectRegistration($requestId, $reason);
        
        if (isset($result['success'])) {
            // نجاح الرفض
            $this->session->setSuccess('تم رفض طلب التسجيل بنجاح');
        } else {
            // فشل الرفض
            $this->session->setError('فشل في رفض طلب التسجيل: ' . $result['error']);
        }
        
        // إعادة التوجيه إلى صفحة طلبات التسجيل
        header('Location: registration_requests.php');
        exit;
    }
    
    /**
     * عرض قائمة المستخدمين
     */
    public function index() {
        // التحقق من صلاحية عرض المستخدمين
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لعرض المستخدمين');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على المستخدمين
        $users = $this->user->getAll();
        
        // تحميل القالب
        include 'views/admin/users.php';
    }
    
    /**
     * عرض نموذج إضافة مستخدم جديد
     */
    public function create() {
        // التحقق من صلاحية إضافة مستخدمين
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لإضافة مستخدمين');
            header('Location: dashboard.php');
            exit;
        }
        
        // تحميل القالب
        include 'views/admin/user_create.php';
    }
    
    /**
     * حفظ مستخدم جديد
     */
    public function store() {
        // التحقق من صلاحية إضافة مستخدمين
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لإضافة مستخدمين');
            header('Location: dashboard.php');
            exit;
        }
        
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('username', 'اسم المستخدم')
                        ->required('password', 'كلمة المرور')
                        ->required('password_confirm', 'تأكيد كلمة المرور')
                        ->required('email', 'البريد الإلكتروني')
                        ->required('phone', 'رقم الهاتف')
                        ->required('permission_level', 'مستوى الصلاحية')
                        ->minLength('username', 3, 'اسم المستخدم')
                        ->maxLength('username', 50, 'اسم المستخدم')
                        ->minLength('password', 6, 'كلمة المرور')
                        ->matches('password', 'password_confirm', 'كلمة المرور', 'تأكيد كلمة المرور')
                        ->email('email', 'البريد الإلكتروني')
                        ->iraqiPhone('phone', 'رقم الهاتف')
                        ->numeric('permission_level', 'مستوى الصلاحية')
                        ->min('permission_level', 1, 'مستوى الصلاحية')
                        ->max('permission_level', 3, 'مستوى الصلاحية');
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: user_create.php');
            exit;
        }
        
        // إنشاء بيانات المستخدم
        $userData = [
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'permission_level' => $_POST['permission_level'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // إنشاء المستخدم
        $result = $this->user->create($userData);
        
        if (isset($result['success'])) {
            // نجاح الإنشاء
            $this->session->setSuccess('تم إنشاء المستخدم بنجاح');
            header('Location: users.php');
            exit;
        } else {
            // فشل الإنشاء
            $this->session->setError('فشل في إنشاء المستخدم: ' . $result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: user_create.php');
            exit;
        }
    }
    
    /**
     * عرض نموذج تعديل مستخدم
     */
    public function edit($id) {
        // التحقق من صلاحية تعديل المستخدمين
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتعديل المستخدمين');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على المستخدم
        $user = $this->user->getById($id);
        
        if (!$user) {
            $this->session->setError('المستخدم غير موجود');
            header('Location: users.php');
            exit;
        }
        
        // تحميل القالب
        include 'views/admin/user_edit.php';
    }
    
    /**
     * تحديث مستخدم
     */
    public function update($id) {
        // التحقق من صلاحية تعديل المستخدمين
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتعديل المستخدمين');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على المستخدم
        $user = $this->user->getById($id);
        
        if (!$user) {
            $this->session->setError('المستخدم غير موجود');
            header('Location: users.php');
            exit;
        }
        
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('username', 'اسم المستخدم')
                        ->required('email', 'البريد الإلكتروني')
                        ->required('phone', 'رقم الهاتف')
                        ->required('permission_level', 'مستوى الصلاحية')
                        ->minLength('username', 3, 'اسم المستخدم')
                        ->maxLength('username', 50, 'اسم المستخدم')
                        ->email('email', 'البريد الإلكتروني')
                        ->iraqiPhone('phone', 'رقم الهاتف')
                        ->numeric('permission_level', 'مستوى الصلاحية')
                        ->min('permission_level', 1, 'مستوى الصلاحية')
                        ->max('permission_level', 3, 'مستوى الصلاحية');
        
        // التحقق من كلمة المرور إذا تم إدخالها
        if (!empty($_POST['password'])) {
            $this->validator->required('password_confirm', 'تأكيد كلمة المرور')
                            ->minLength('password', 6, 'كلمة المرور')
                            ->matches('password', 'password_confirm', 'كلمة المرور', 'تأكيد كلمة المرور');
        }
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: user_edit.php?id=' . $id);
            exit;
        }
        
        // إنشاء بيانات المستخدم
        $userData = [
            'username' => $_POST['username'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'permission_level' => $_POST['permission_level'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // إضافة كلمة المرور إذا تم إدخالها
        if (!empty($_POST['password'])) {
            $userData['password'] = $_POST['password'];
        }
        
        // تحديث المستخدم
        $result = $this->user->update($id, $userData);
        
        if (isset($result['success'])) {
            // نجاح التحديث
            $this->session->setSuccess('تم تحديث المستخدم بنجاح');
            header('Location: users.php');
            exit;
        } else {
            // فشل التحديث
            $this->session->setError('فشل في تحديث المستخدم: ' . $result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: user_edit.php?id=' . $id);
            exit;
        }
    }
    
    /**
     * حذف مستخدم
     */
    public function delete($id) {
        // التحقق من صلاحية حذف المستخدمين
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لحذف المستخدمين');
            header('Location: dashboard.php');
            exit;
        }
        
        // التحقق من عدم حذف المستخدم الحالي
        if ($id == $this->auth->getCurrentUserId()) {
            $this->session->setError('لا يمكن حذف المستخدم الحالي');
            header('Location: users.php');
            exit;
        }
        
        // حذف المستخدم
        $result = $this->user->delete($id);
        
        if (isset($result['success'])) {
            // نجاح الحذف
            $this->session->setSuccess('تم حذف المستخدم بنجاح');
        } else {
            // فشل الحذف
            $this->session->setError('فشل في حذف المستخدم: ' . $result['error']);
        }
        
        // إعادة التوجيه إلى صفحة المستخدمين
        header('Location: users.php');
        exit;
    }
    
    /**
     * تحديث حالة المستخدم (نشط/غير نشط)
     */
    public function updateStatus($id) {
        // التحقق من صلاحية تعديل المستخدمين
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتعديل المستخدمين');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على المستخدم
        $user = $this->user->getById($id);
        
        if (!$user) {
            $this->session->setError('المستخدم غير موجود');
            header('Location: users.php');
            exit;
        }
        
        // تحديث الحالة
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $result = $this->user->updateStatus($id, $isActive);
        
        if ($result) {
            $status = $isActive ? 'نشط' : 'غير نشط';
            $this->session->setSuccess('تم تحديث حالة المستخدم إلى ' . $status . ' بنجاح');
        } else {
            $this->session->setError('فشل في تحديث حالة المستخدم');
        }
        
        // إعادة التوجيه إلى صفحة المستخدمين
        header('Location: users.php');
        exit;
    }
    
    /**
     * تحديث صلاحيات المستخدم
     */
    public function updatePermission($id) {
        // التحقق من صلاحية تعديل صلاحيات المستخدمين
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتعديل صلاحيات المستخدمين');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على المستخدم
        $user = $this->user->getById($id);
        
        if (!$user) {
            $this->session->setError('المستخدم غير موجود');
            header('Location: users.php');
            exit;
        }
        
        // التحقق من صحة مستوى الصلاحية
        $permissionLevel = isset($_POST['permission_level']) ? (int)$_POST['permission_level'] : 0;
        
        if ($permissionLevel < 1 || $permissionLevel > 3) {
            $this->session->setError('مستوى الصلاحية غير صالح');
            header('Location: users.php');
            exit;
        }
        
        // تحديث مستوى الصلاحية
        $result = $this->user->updatePermission($id, $permissionLevel);
        
        if ($result) {
            $this->session->setSuccess('تم تحديث صلاحيات المستخدم بنجاح');
        } else {
            $this->session->setError('فشل في تحديث صلاحيات المستخدم');
        }
        
        // إعادة التوجيه إلى صفحة المستخدمين
        header('Location: users.php');
        exit;
    }
    
    /**
     * عرض صفحة تغيير كلمة المرور
     */
    public function showChangePassword() {
        // تحميل القالب
        include 'views/user/change_password.php';
    }
    
    /**
     * تغيير كلمة المرور
     */
    public function changePassword() {
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('current_password', 'كلمة المرور الحالية')
                        ->required('new_password', 'كلمة المرور الجديدة')
                        ->required('confirm_password', 'تأكيد كلمة المرور')
                        ->minLength('new_password', 6, 'كلمة المرور الجديدة')
                        ->matches('new_password', 'confirm_password', 'كلمة المرور الجديدة', 'تأكيد كلمة المرور');
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            header('Location: change_password.php');
            exit;
        }
        
        // التحقق من كلمة المرور الحالية
        $currentUser = $this->auth->getCurrentUser();
        
        if (!password_verify($_POST['current_password'], $currentUser['password'])) {
            $this->session->setError('كلمة المرور الحالية غير صحيحة');
            header('Location: change_password.php');
            exit;
        }
        
        // تغيير كلمة المرور
        $result = $this->user->changePassword($currentUser['id'], $_POST['new_password']);
        
        if ($result) {
            $this->session->setSuccess('تم تغيير كلمة المرور بنجاح');
            header('Location: dashboard.php');
            exit;
        } else {
            $this->session->setError('فشل في تغيير كلمة المرور');
            header('Location: change_password.php');
            exit;
        }
    }
    
    /**
     * عرض صفحة الملف الشخصي
     */
    public function showProfile() {
        // الحصول على المستخدم الحالي
        $user = $this->auth->getCurrentUser();
        
        // تحميل القالب
        include 'views/user/profile.php';
    }
    
    /**
     * تحديث الملف الشخصي
     */
    public function updateProfile() {
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('email', 'البريد الإلكتروني')
                        ->required('phone', 'رقم الهاتف')
                        ->email('email', 'البريد الإلكتروني')
                        ->iraqiPhone('phone', 'رقم الهاتف');
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: profile.php');
            exit;
        }
        
        // الحصول على المستخدم الحالي
        $currentUser = $this->auth->getCurrentUser();
        
        // بيانات التحديث
        $userData = [
            'email' => $_POST['email'],
            'phone' => $_POST['phone']
        ];
        
        // تحديث الملف الشخصي
        $result = $this->user->update($currentUser['id'], $userData);
        
        if (isset($result['success'])) {
            // نجاح التحديث
            $this->session->setSuccess('تم تحديث الملف الشخصي بنجاح');
            header('Location: profile.php');
            exit;
        } else {
            // فشل التحديث
            $this->session->setError('فشل في تحديث الملف الشخصي: ' . $result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: profile.php');
            exit;
        }
    }
    
    /**
     * عرض سجل نشاط المستخدم
     */
    public function showActivityLog($userId = null) {
        // التحقق من صلاحية عرض سجل النشاط
        if ($userId && !$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لعرض سجل نشاط المستخدمين');
            header('Location: dashboard.php');
            exit;
        }
        
        // إذا لم يتم تحديد معرف المستخدم، استخدم المستخدم الحالي
        if (!$userId) {
            $userId = $this->auth->getCurrentUserId();
        }
        
        // الحصول على المستخدم
        $user = $this->user->getById($userId);
        
        if (!$user) {
            $this->session->setError('المستخدم غير موجود');
            header('Location: users.php');
            exit;
        }
        
        // الحصول على سجل النشاط
        $activityLog = $this->user->getActivityLog($userId);
        
        // تحميل القالب
        include 'views/user/activity_log.php';
    }
}