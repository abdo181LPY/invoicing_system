<?php
/**
 * متحكم الفواتير
 * يدير عمليات إنشاء وعرض وتعديل وحذف الفواتير
 */
class InvoiceController {
    private $db;
    private $invoice;
    private $page;
    private $blacklist;
    private $validator;
    private $uploader;
    private $session;
    private $auth;
    private $notification;
    
    /**
     * إنشاء كائن المتحكم
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->invoice = new Invoice();
        $this->page = new Page();
        $this->blacklist = new Blacklist();
        $this->validator = Validator::getInstance();
        $this->uploader = Uploader::getInstance();
        $this->session = Session::getInstance();
        $this->auth = Auth::getInstance();
        $this->notification = Notification::getInstance();
    }
    
    /**
     * عرض صفحة تثبيت طلب جديد
     */
    public function create() {
        // الحصول على بيانات الحساب من الجلسة إذا كانت موجودة
        $createOrderData = $this->session->getFlash('create_order_data');
        
        // الحصول على قائمة الصفحات للقائمة المنسدلة
        $pages = $this->page->getDropdownList();
        $provinces = $GLOBALS['PROVINCES'];
        
        // تحميل القالب
        include 'views/invoice/create.php';
    }
    
    /**
     * حفظ طلب جديد
     */
    public function store() {
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('customer_name', 'اسم الزبون')
                        ->required('phone', 'رقم الهاتف')
                        ->required('province', 'المحافظة')
                        ->required('area', 'المنطقة')
                        ->required('address', 'العنوان')
                        ->required('page_id', 'الصفحة')
                        ->required('instagram', 'رابط الإنستغرام')
                        ->required('item_count', 'عدد القطع')
                        ->iraqiPhone('phone', 'رقم الهاتف')
                        ->inArray('province', $GLOBALS['PROVINCES'], 'المحافظة')
                        ->numeric('item_count', 'عدد القطع')
                        ->integer('item_count', 'عدد القطع')
                        ->min('item_count', 1, 'عدد القطع');
        
        // التحقق من صحة البيانات الإضافية إذا تم إدخالها
        if (isset($_POST['phone2']) && !empty($_POST['phone2'])) {
            $this->validator->iraqiPhone('phone2', 'رقم الهاتف الثانوي');
        }
        
        if (isset($_POST['basket_price']) && !empty($_POST['basket_price'])) {
            $this->validator->numeric('basket_price', 'سعر السلة')
                            ->min('basket_price', 0.01, 'سعر السلة');
        }
        
        if (isset($_POST['deposit_amount']) && !empty($_POST['deposit_amount'])) {
            $this->validator->numeric('deposit_amount', 'مبلغ العربون')
                            ->min('deposit_amount', 0.01, 'مبلغ العربون');
        }
        
        // التحقق من صحة رابط الإنستغرام
        if (isset($_POST['instagram']) && !empty($_POST['instagram']) && strpos($_POST['instagram'], 'http') !== 0) {
            $this->validator->errors['instagram'] = 'رابط الإنستغرام يجب أن يبدأ بـ http';
        }
        
        // التحقق من وجود الصفحة
        if (isset($_POST['page_id']) && !$this->page->exists(['id' => $_POST['page_id']])) {
            $this->validator->errors['page_id'] = 'الصفحة غير موجودة';
        }
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: invoice_create.php');
            exit;
        }
        
        // التحقق من وجود رقم الهاتف في البلاك ليست
        $isBlacklisted = $this->blacklist->exists($_POST['phone']);
        
        if ($isBlacklisted) {
            $this->session->setWarning('تنبيه: رقم الهاتف موجود في القائمة السوداء! يجب دفع المبلغ كاملاً مقدماً.');
        }
        
        // بيانات الفاتورة
        $invoiceData = [
            'customer_name' => $_POST['customer_name'],
            'phone' => $_POST['phone'],
            'phone2' => isset($_POST['phone2']) ? $_POST['phone2'] : null,
            'province' => $_POST['province'],
            'area' => $_POST['area'],
            'address' => $_POST['address'],
            'page_id' => $_POST['page_id'],
            'cart_link' => isset($_POST['cart_link']) ? $_POST['cart_link'] : null,
            'instagram' => $_POST['instagram'],
            'item_count' => $_POST['item_count'],
            'repeat_details' => isset($_POST['repeat_details']) ? $_POST['repeat_details'] : null,
            'custom_details' => isset($_POST['custom_details']) ? $_POST['custom_details'] : null,
            'deposit_amount' => isset($_POST['deposit_amount']) ? $_POST['deposit_amount'] : null,
            'deposit_method' => isset($_POST['deposit_method']) ? $_POST['deposit_method'] : null,
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : null,
            'status' => 'جديد',
            'requires_full_payment' => $isBlacklisted ? 1 : 0
        ];
        
        // حساب التكلفة إذا تم إدخال سعر السلة
        if (isset($_POST['basket_price']) && !empty($_POST['basket_price'])) {
            $calculation = $this->invoice->calculateCost(
                $_POST['basket_price'],
                $_POST['item_count'],
                $_POST['province']
            );
            
            $invoiceData['basket_price'] = $_POST['basket_price'];
            $invoiceData['total_price'] = $calculation['total_cost_iqd'];
            $invoiceData['delivery_cost'] = $calculation['delivery_cost'];
        }
        
        // معالجة الصور المرفقة
        $repeatImages = [];
        $customImages = [];
        $depositImage = null;
        
        // صور التكرارات
        if (isset($_FILES['repeat_images']) && !empty($_FILES['repeat_images']['name'][0])) {
            $result = $this->uploader->uploadMultipleImages($_FILES['repeat_images']);
            
            if ($result['hasErrors']) {
                $this->session->setError('حدث خطأ أثناء رفع صور التكرارات: ' . implode(', ', $result['errors']));
            } else {
                foreach ($result['files'] as $file) {
                    $repeatImages[] = $file['path'];
                    
                    // ضغط الصور
                    $this->uploader->autoResizeUploadedImage($file);
                }
            }
        }
        
        // صور القطع المخصصة
        if (isset($_FILES['custom_images']) && !empty($_FILES['custom_images']['name'][0])) {
            $result = $this->uploader->uploadMultipleImages($_FILES['custom_images']);
            
            if ($result['hasErrors']) {
                $this->session->setError('حدث خطأ أثناء رفع صور القطع المخصصة: ' . implode(', ', $result['errors']));
            } else {
                foreach ($result['files'] as $file) {
                    $customImages[] = $file['path'];
                    
                    // ضغط الصور
                    $this->uploader->autoResizeUploadedImage($file);
                }
            }
        }
        
        // صورة العربون
        if (isset($_FILES['deposit_image']) && !empty($_FILES['deposit_image']['name'])) {
            $result = $this->uploader->uploadImage($_FILES['deposit_image']);
            
            if ($result === false) {
                $this->session->setError('حدث خطأ أثناء رفع صورة العربون: ' . implode(', ', $this->uploader->getErrors()));
            } else {
                $depositImage = $result['path'];
                
                // ضغط الصورة
                $this->uploader->autoResizeUploadedImage($result);
            }
        }
        
        // إضافة الصور إلى بيانات الفاتورة
        if (!empty($repeatImages)) {
            $invoiceData['repeat_images'] = $repeatImages;
        }
        
        if (!empty($customImages)) {
            $invoiceData['custom_images'] = $customImages;
        }
        
        if ($depositImage) {
            $invoiceData['deposit_image'] = $depositImage;
        }
        
        // إنشاء الفاتورة
        $result = $this->invoice->create($invoiceData);
        
        if (isset($result['success'])) {
            $this->session->setSuccess('تم تثبيت الطلب بنجاح برقم #' . $result['invoice_id']);
            header('Location: invoice_view.php?id=' . $result['invoice_id']);
            exit;
        } else {
            $this->session->setError('فشل في تثبيت الطلب: ' . $result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: invoice_create.php');
            exit;
        }
    }
    
    /**
     * عرض صفحة البحث والحذف
     */
    public function search() {
        // تحميل القالب
        include 'views/invoice/search.php';
    }
    
    /**
     * تنفيذ البحث عن الفواتير
     */
    public function doSearch() {
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $limit = 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        
        // البحث عن الفواتير
        $invoices = $this->invoice->search($keyword, $limit, $offset);
        
        // الحصول على العدد الإجمالي للفواتير
        $totalInvoices = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM invoices WHERE id LIKE :keyword OR customer_name LIKE :keyword OR phone LIKE :keyword OR province LIKE :keyword OR cart_number LIKE :keyword", 
            ['keyword' => '%' . $keyword . '%']
        );
        
        // حساب عدد الصفحات
        $totalPages = ceil($totalInvoices / $limit);
        
        // عرض نتائج البحث
        include 'views/invoice/search_results.php';
    }
    
    /**
     * عرض تفاصيل الفاتورة
     */
    public function view($id) {
        // الحصول على الفاتورة
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            $this->session->setError('الفاتورة غير موجودة');
            header('Location: invoice_search.php');
            exit;
        }
        
        // الحصول على صور الفاتورة
        $images = $this->invoice->getInvoiceImages($id);
        
        // تصنيف الصور حسب النوع
        $repeatImages = [];
        $customImages = [];
        $depositImage = null;
        
        foreach ($images as $image) {
            if ($image['type'] === 'repeat') {
                $repeatImages[] = $image;
            } elseif ($image['type'] === 'custom') {
                $customImages[] = $image;
            } elseif ($image['type'] === 'deposit') {
                $depositImage = $image;
            }
        }
        
        // الحصول على معلومات المستخدم الذي أنشأ الفاتورة
        $createdBy = null;
        if ($invoice['created_by']) {
            $user = new User();
            $createdBy = $user->getById($invoice['created_by']);
        }
        
        // التحقق من صلاحية الحذف
        $canDelete = $this->auth->canDeleteInvoice($id);
        
        // التحقق من صلاحية تغيير الحالة
        $canChangeStatus = $this->auth->hasPermission(2);
        
        // الحصول على قائمة حالات الطلبات
        $orderStatuses = $GLOBALS['ORDER_STATUSES'];
        
        // تحميل القالب
        include 'views/invoice/view.php';
    }
    
    /**
     * تعديل فاتورة
     */
    public function edit($id) {
        // الحصول على الفاتورة
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            $this->session->setError('الفاتورة غير موجودة');
            header('Location: invoice_search.php');
            exit;
        }
        
        // التحقق من صلاحية التعديل
        if (!$this->auth->hasPermission(2)) {
            $this->session->setError('ليس لديك صلاحية لتعديل الفواتير');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
        
        // الحصول على صور الفاتورة
        $images = $this->invoice->getInvoiceImages($id);
        
        // تصنيف الصور حسب النوع
        $repeatImages = [];
        $customImages = [];
        $depositImage = null;
        
        foreach ($images as $image) {
            if ($image['type'] === 'repeat') {
                $repeatImages[] = $image;
            } elseif ($image['type'] === 'custom') {
                $customImages[] = $image;
            } elseif ($image['type'] === 'deposit') {
                $depositImage = $image;
            }
        }
        
        // الحصول على قائمة الصفحات للقائمة المنسدلة
        $pages = $this->page->getDropdownList();
        $provinces = $GLOBALS['PROVINCES'];
        
        // تحميل القالب
        include 'views/invoice/edit.php';
    }
    
    /**
     * تحديث فاتورة
     */
    public function update($id) {
        // الحصول على الفاتورة
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            $this->session->setError('الفاتورة غير موجودة');
            header('Location: invoice_search.php');
            exit;
        }
        
        // التحقق من صلاحية التعديل
        if (!$this->auth->hasPermission(2)) {
            $this->session->setError('ليس لديك صلاحية لتعديل الفواتير');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
        
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('customer_name', 'اسم الزبون')
                        ->required('phone', 'رقم الهاتف')
                        ->required('province', 'المحافظة')
                        ->required('area', 'المنطقة')
                        ->required('address', 'العنوان')
                        ->required('page_id', 'الصفحة')
                        ->required('instagram', 'رابط الإنستغرام')
                        ->required('item_count', 'عدد القطع')
                        ->iraqiPhone('phone', 'رقم الهاتف')
                        ->inArray('province', $GLOBALS['PROVINCES'], 'المحافظة')
                        ->numeric('item_count', 'عدد القطع')
                        ->integer('item_count', 'عدد القطع')
                        ->min('item_count', 1, 'عدد القطع');
        
        // التحقق من صحة البيانات الإضافية إذا تم إدخالها
        if (isset($_POST['phone2']) && !empty($_POST['phone2'])) {
            $this->validator->iraqiPhone('phone2', 'رقم الهاتف الثانوي');
        }
        
        if (isset($_POST['basket_price']) && !empty($_POST['basket_price'])) {
            $this->validator->numeric('basket_price', 'سعر السلة')
                            ->min('basket_price', 0.01, 'سعر السلة');
        }
        
        if (isset($_POST['deposit_amount']) && !empty($_POST['deposit_amount'])) {
            $this->validator->numeric('deposit_amount', 'مبلغ العربون')
                            ->min('deposit_amount', 0.01, 'مبلغ العربون');
        }
        
        // التحقق من صحة رابط الإنستغرام
        if (isset($_POST['instagram']) && !empty($_POST['instagram']) && strpos($_POST['instagram'], 'http') !== 0) {
            $this->validator->errors['instagram'] = 'رابط الإنستغرام يجب أن يبدأ بـ http';
        }
        
        // التحقق من وجود الصفحة
        if (isset($_POST['page_id']) && !$this->page->exists(['id' => $_POST['page_id']])) {
            $this->validator->errors['page_id'] = 'الصفحة غير موجودة';
        }
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: invoice_edit.php?id=' . $id);
            exit;
        }
        
        // التحقق من وجود رقم الهاتف في البلاك ليست
        $isBlacklisted = $this->blacklist->exists($_POST['phone']);
        
        // بيانات الفاتورة
        $invoiceData = [
            'customer_name' => $_POST['customer_name'],
            'phone' => $_POST['phone'],
            'phone2' => isset($_POST['phone2']) ? $_POST['phone2'] : null,
            'province' => $_POST['province'],
            'area' => $_POST['area'],
            'address' => $_POST['address'],
            'page_id' => $_POST['page_id'],
            'cart_link' => isset($_POST['cart_link']) ? $_POST['cart_link'] : null,
            'instagram' => $_POST['instagram'],
            'item_count' => $_POST['item_count'],
            'repeat_details' => isset($_POST['repeat_details']) ? $_POST['repeat_details'] : null,
            'custom_details' => isset($_POST['custom_details']) ? $_POST['custom_details'] : null,
            'deposit_amount' => isset($_POST['deposit_amount']) ? $_POST['deposit_amount'] : null,
            'deposit_method' => isset($_POST['deposit_method']) ? $_POST['deposit_method'] : null,
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : null,
            'requires_full_payment' => $isBlacklisted ? 1 : 0
        ];
        
        // حساب التكلفة إذا تم إدخال سعر السلة
        if (isset($_POST['basket_price']) && !empty($_POST['basket_price'])) {
            $calculation = $this->invoice->calculateCost(
                $_POST['basket_price'],
                $_POST['item_count'],
                $_POST['province']
            );
            
            $invoiceData['basket_price'] = $_POST['basket_price'];
            $invoiceData['total_price'] = $calculation['total_cost_iqd'];
            $invoiceData['delivery_cost'] = $calculation['delivery_cost'];
        }
        
        // معالجة الصور المرفقة
        $repeatImages = [];
        $customImages = [];
        $depositImage = null;
        
        // صور التكرارات
        if (isset($_FILES['repeat_images']) && !empty($_FILES['repeat_images']['name'][0])) {
            $result = $this->uploader->uploadMultipleImages($_FILES['repeat_images']);
            
            if ($result['hasErrors']) {
                $this->session->setError('حدث خطأ أثناء رفع صور التكرارات: ' . implode(', ', $result['errors']));
            } else {
                foreach ($result['files'] as $file) {
                    $repeatImages[] = $file['path'];
                    
                    // ضغط الصور
                    $this->uploader->autoResizeUploadedImage($file);
                }
            }
        }
        
        // صور القطع المخصصة
        if (isset($_FILES['custom_images']) && !empty($_FILES['custom_images']['name'][0])) {
            $result = $this->uploader->uploadMultipleImages($_FILES['custom_images']);
            
            if ($result['hasErrors']) {
                $this->session->setError('حدث خطأ أثناء رفع صور القطع المخصصة: ' . implode(', ', $result['errors']));
            } else {
                foreach ($result['files'] as $file) {
                    $customImages[] = $file['path'];
                    
                    // ضغط الصور
                    $this->uploader->autoResizeUploadedImage($file);
                }
            }
        }
        
        // صورة العربون
        if (isset($_FILES['deposit_image']) && !empty($_FILES['deposit_image']['name'])) {
            $result = $this->uploader->uploadImage($_FILES['deposit_image']);
            
            if ($result === false) {
                $this->session->setError('حدث خطأ أثناء رفع صورة العربون: ' . implode(', ', $this->uploader->getErrors()));
            } else {
                $depositImage = $result['path'];
                
                // ضغط الصورة
                $this->uploader->autoResizeUploadedImage($result);
            }
        }
        
        // إضافة الصور إلى بيانات الفاتورة إذا تم تحميلها
        if (!empty($repeatImages)) {
            $invoiceData['repeat_images'] = $repeatImages;
        }
        
        if (!empty($customImages)) {
            $invoiceData['custom_images'] = $customImages;
        }
        
        if ($depositImage) {
            $invoiceData['deposit_image'] = $depositImage;
        }
        
        // تحديث الفاتورة
        $result = $this->invoice->update($id, $invoiceData);
        
        if (isset($result['success'])) {
            $this->session->setSuccess('تم تحديث الفاتورة بنجاح');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        } else {
            $this->session->setError('فشل في تحديث الفاتورة: ' . $result['error']);
            $this->session->setFlash('old', $_POST);
            header('Location: invoice_edit.php?id=' . $id);
            exit;
        }
    }
    
    /**
     * حذف فاتورة
     */
    public function delete($id) {
        // الحصول على الفاتورة
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            $this->session->setError('الفاتورة غير موجودة');
            header('Location: invoice_search.php');
            exit;
        }
        
        // التحقق من صلاحية الحذف
        if (!$this->auth->canDeleteInvoice($id)) {
            $this->session->setError('ليس لديك صلاحية لحذف هذه الفاتورة');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
        
        // حذف الفاتورة
        $result = $this->invoice->delete($id);
        
        if (isset($result['success'])) {
            $this->session->setSuccess('تم حذف الفاتورة بنجاح');
            header('Location: invoice_search.php');
            exit;
        } else {
            $this->session->setError('فشل في حذف الفاتورة: ' . $result['error']);
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
    }
    
    /**
     * تحديث حالة الفاتورة
     */
    public function updateStatus($id) {
        // الحصول على الفاتورة
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            $this->session->setError('الفاتورة غير موجودة');
            header('Location: invoice_search.php');
            exit;
        }
        
        // التحقق من صلاحية تغيير الحالة
        if (!$this->auth->hasPermission(2)) {
            $this->session->setError('ليس لديك صلاحية لتغيير حالة الفاتورة');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
        
        // التحقق من صحة الحالة
        $status = isset($_POST['status']) ? $_POST['status'] : null;
        
        if (!$status || !in_array($status, $GLOBALS['ORDER_STATUSES'])) {
            $this->session->setError('حالة الطلب غير صالحة');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
        
        // تحديث الحالة
        $result = $this->invoice->updateStatus($id, $status);
        
        if (isset($result['success'])) {
            $this->session->setSuccess('تم تحديث حالة الفاتورة بنجاح');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        } else {
            $this->session->setError('فشل في تحديث حالة الفاتورة: ' . $result['error']);
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
    }
    
    /**
     * إضافة رقم سلة للفاتورة
     */
    public function addCartNumber($id) {
        // الحصول على الفاتورة
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            $this->session->setError('الفاتورة غير موجودة');
            header('Location: invoice_search.php');
            exit;
        }
        
        // التحقق من صلاحية إضافة رقم سلة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لإضافة رقم سلة');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
        
        // التحقق من صحة رقم السلة
        $cartNumber = isset($_POST['cart_number']) ? $_POST['cart_number'] : null;
        
        if (!$cartNumber) {
            $this->session->setError('رقم السلة مطلوب');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
        
        // إضافة رقم السلة
        $result = $this->invoice->addCartNumber($id, $cartNumber);
        
        if ($result) {
            $this->session->setSuccess('تم إضافة رقم السلة بنجاح');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        } else {
            $this->session->setError('فشل في إضافة رقم السلة');
            header('Location: invoice_view.php?id=' . $id);
            exit;
        }
    }
    
    /**
     * طباعة الفاتورة
     */
    public function print($id) {
        // الحصول على الفاتورة
        $invoice = $this->invoice->getById($id);
        
        if (!$invoice) {
            $this->session->setError('الفاتورة غير موجودة');
            header('Location: invoice_search.php');
            exit;
        }
        
        // الحصول على صور الفاتورة
        $images = $this->invoice->getInvoiceImages($id);
        
        // تصنيف الصور حسب النوع
        $repeatImages = [];
        $customImages = [];
        $depositImage = null;
        
        foreach ($images as $image) {
            if ($image['type'] === 'repeat') {
                $repeatImages[] = $image;
            } elseif ($image['type'] === 'custom') {
                $customImages[] = $image;
            } elseif ($image['type'] === 'deposit') {
                $depositImage = $image;
            }
        }
        
        // تحميل قالب الطباعة
        include 'views/invoice/print.php';
    }
    
    /**
     * استعادة فاتورة محذوفة
     */
    public function restore($id) {
        // التحقق من صلاحية استعادة الفواتير
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لاستعادة الفواتير المحذوفة');
            header('Location: deleted_invoices.php');
            exit;
        }
        
        // استعادة الفاتورة
        $result = $this->invoice->restore($id);
        
        if (isset($result['success'])) {
            $this->session->setSuccess('تم استعادة الفاتورة بنجاح');
            header('Location: deleted_invoices.php');
            exit;
        } else {
            $this->session->setError('فشل في استعادة الفاتورة: ' . $result['error']);
            header('Location: deleted_invoices.php');
            exit;
        }
    }
    
    /**
     * عرض الفواتير المحذوفة
     */
    public function showDeleted() {
        // التحقق من صلاحية عرض الفواتير المحذوفة
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لعرض الفواتير المحذوفة');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الفواتير المحذوفة
        $limit = 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        
        $deletedInvoices = $this->invoice->getDeleted($limit, $offset);
        
        // الحصول على العدد الإجمالي للفواتير المحذوفة
        $totalInvoices = $this->db->fetchColumn("SELECT COUNT(*) FROM deleted_invoices");
        
        // حساب عدد الصفحات
        $totalPages = ceil($totalInvoices / $limit);
        
        // تحميل القالب
        include 'views/invoice/deleted.php';
    }
    
    /**
     * إضافة أرقام سلات لمجموعة من الفواتير
     */
    public function addCartNumbers() {
        // التحقق من صلاحية إضافة أرقام سلات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لإضافة أرقام سلات');
            header('Location: dashboard.php');
            exit;
        }
        
        // التحقق من صحة البيانات
        $startId = isset($_POST['start_id']) ? (int)$_POST['start_id'] : 0;
        $endId = isset($_POST['end_id']) ? (int)$_POST['end_id'] : 0;
        $startCartNumber = isset($_POST['start_cart_number']) ? (int)$_POST['start_cart_number'] : 0;
        
        if ($startId <= 0 || $endId <= 0 || $startCartNumber <= 0) {
            $this->session->setError('البيانات المدخلة غير صالحة');
            header('Location: add_cart_numbers.php');
            exit;
        }
        
        if ($startId > $endId) {
            $this->session->setError('رقم الفاتورة البداية يجب أن يكون أقل من أو يساوي رقم الفاتورة النهاية');
            header('Location: add_cart_numbers.php');
            exit;
        }
        
        // إضافة أرقام السلات
        $result = $this->invoice->addCartNumbers($startId, $endId, $startCartNumber);
        
        if ($result['success'] > 0) {
            $this->session->setSuccess('تم إضافة أرقام السلات بنجاح. نجح: ' . $result['success'] . ', فشل: ' . $result['failed'] . ', الإجمالي: ' . $result['total']);
        } else {
            $this->session->setError('فشل في إضافة أرقام السلات. حاول مراجعة أرقام الفواتير المدخلة.');
        }
        
        header('Location: add_cart_numbers.php');
        exit;
    }
    
    /**
     * عرض الفواتير حسب الحالة
     */
    public function showByStatus($status) {
        // التحقق من صحة الحالة
        if (!in_array($status, $GLOBALS['ORDER_STATUSES'])) {
            $this->session->setError('حالة الطلب غير صالحة');
            header('Location: dashboard.php');
            exit;
        }
        
        // الحصول على الفواتير حسب الحالة
        $limit = 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        
        $invoices = $this->invoice->getByStatus($status, $limit, $offset);
        
        // الحصول على العدد الإجمالي للفواتير بهذه الحالة
        $totalInvoices = $this->invoice->count(['status' => $status]);
        
        // حساب عدد الصفحات
        $totalPages = ceil($totalInvoices / $limit);
        
        // تحميل القالب
        include 'views/invoice/by_status.php';
    }
    
    /**
     * عرض فواتير المستخدم الحالي
     */
    public function showUserInvoices() {
        // الحصول على المستخدم الحالي
        $userId = $this->auth->getCurrentUserId();
        
        if (!$userId) {
            $this->session->setError('يجب تسجيل الدخول لعرض الفواتير الخاصة بك');
            header('Location: login.php');
            exit;
        }
        
        // الحصول على فواتير المستخدم
        $limit = 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        
        $invoices = $this->invoice->getUserInvoices($userId, $limit, $offset);
        
        // الحصول على العدد الإجمالي لفواتير المستخدم
        $totalInvoices = $this->invoice->count(['created_by' => $userId]);
        
        // حساب عدد الصفحات
        $totalPages = ceil($totalInvoices / $limit);
        
        // تحميل القالب
        include 'views/invoice/user_invoices.php';
    }
}