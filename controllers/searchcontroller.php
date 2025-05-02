<?php
class SearchController {
    private $invoice;
    private $customer;
    private $deletedInvoice;
    private $log;
    private $user;
    private $validator;

    public function __construct() {
        $this->invoice = new Invoice();
        $this->customer = new Customer();
        $this->deletedInvoice = new DeletedInvoice();
        $this->log = new Log();
        $this->user = new User();
        $this->validator = new Validator();
    }

    /**
     * عرض صفحة البحث المتقدم
     */
    public function indexAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            Session::set('flash_message', 'يجب تسجيل الدخول للوصول إلى صفحة البحث');
            Session::set('flash_type', 'error');
            Router::redirect('/auth/login');
            return;
        }

        // بيانات الصفحة
        $pageData = [
            'title' => 'البحث المتقدم',
            'governorates' => include(ROOT_PATH . '/includes/governorates.php')
        ];

        // عرض صفحة البحث
        include(ROOT_PATH . '/views/search/index.php');
    }

    /**
     * البحث عن الفواتير
     */
    public function searchInvoicesAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير صحيحة']);
            return;
        }

        // معايير البحث
        $searchCriteria = [
            'keyword' => $_POST['keyword'] ?? '',
            'invoice_id' => $_POST['invoice_id'] ?? '',
            'customer_name' => $_POST['customer_name'] ?? '',
            'customer_phone' => $_POST['customer_phone'] ?? '',
            'status_id' => $_POST['status_id'] ?? '',
            'governorate' => $_POST['governorate'] ?? '',
            'date_from' => $_POST['date_from'] ?? '',
            'date_to' => $_POST['date_to'] ?? '',
            'min_price' => $_POST['min_price'] ?? '',
            'max_price' => $_POST['max_price'] ?? '',
            'created_by' => $_POST['created_by'] ?? '',
            'page_id' => $_POST['page_id'] ?? ''
        ];

        // البحث باستخدام النموذج
        $results = $this->invoice->searchInvoices($searchCriteria);

        // تنسيق النتائج
        $formattedResults = [];
        foreach ($results as $invoice) {
            $formattedResults[] = [
                'id' => $invoice['id'],
                'invoice_number' => $invoice['invoice_number'] ?? 'غير متوفر',
                'customer_name' => $invoice['customer_name'],
                'customer_phone' => $invoice['customer_phone'],
                'governorate' => $invoice['governorate'] ?? 'غير محدد',
                'total_price' => number_format($invoice['total_price']),
                'status' => $invoice['status_name'] ?? 'غير محدد',
                'created_at' => date('Y-m-d H:i', strtotime($invoice['created_at'])),
                'created_by' => $invoice['username'] ?? 'غير معروف',
                'view_url' => '/invoice/view/' . $invoice['id'],
                'can_delete' => $this->canDeleteInvoice($invoice)
            ];
        }

        // تسجيل عملية البحث
        $this->log->addLog(
            Log::TYPE_INFO,
            'بحث عن فواتير',
            'تم البحث باستخدام المعايير: ' . json_encode($searchCriteria, JSON_UNESCAPED_UNICODE),
            Auth::getUserId()
        );

        // إرجاع النتائج بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $formattedResults,
            'count' => count($formattedResults)
        ]);
    }

    /**
     * البحث عن الزبائن
     */
    public function searchCustomersAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // التحقق من وجود معيار البحث
        $searchTerm = $_GET['term'] ?? '';
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            $this->jsonResponse(['error' => 'معيار البحث قصير جداً']);
            return;
        }

        // البحث عن الزبائن
        $results = $this->customer->searchCustomers($searchTerm);

        // تنسيق النتائج
        $formattedResults = [];
        foreach ($results as $customer) {
            $formattedResults[] = [
                'id' => $customer['id'],
                'name' => $customer['name'],
                'phone' => $customer['phone'],
                'phone2' => $customer['phone2'],
                'governorate' => $customer['governorate'],
                'area' => $customer['area'],
                'address' => $customer['address'],
                'rating' => $customer['rating']
            ];
        }

        // إرجاع النتائج بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $formattedResults,
            'count' => count($formattedResults)
        ]);
    }

    /**
     * البحث عن الفواتير المحذوفة
     */
    public function searchDeletedInvoicesAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || !Auth::hasPermission('admin')) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // التحقق من وجود معيار البحث
        $searchTerm = $_GET['term'] ?? '';
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            $this->jsonResponse(['error' => 'معيار البحث قصير جداً']);
            return;
        }

        // البحث عن الفواتير المحذوفة
        $results = $this->deletedInvoice->searchDeletedInvoices($searchTerm);

        // تنسيق النتائج
        $formattedResults = [];
        foreach ($results as $invoice) {
            // استخراج البيانات من JSON
            $invoiceData = is_string($invoice['invoice_data']) 
                ? json_decode($invoice['invoice_data'], true) 
                : $invoice['invoice_data'];

            $formattedResults[] = [
                'id' => $invoice['id'],
                'original_id' => $invoice['original_id'],
                'customer_name' => $invoiceData['customer_name'] ?? 'غير متوفر',
                'customer_phone' => $invoiceData['customer_phone'] ?? 'غير متوفر',
                'total_price' => isset($invoiceData['total_price']) ? number_format($invoiceData['total_price']) : 'غير متوفر',
                'deleted_at' => date('Y-m-d H:i', strtotime($invoice['deleted_at'])),
                'deleted_by' => $invoice['deleted_by_name'] ?? 'غير معروف',
                'view_url' => '/admin/deleted_invoice/' . $invoice['id'],
                'restore_url' => '/admin/restore_invoice/' . $invoice['id']
            ];
        }

        // إرجاع النتائج بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $formattedResults,
            'count' => count($formattedResults)
        ]);
    }

    /**
     * البحث في سجلات النظام
     */
    public function searchLogsAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || !Auth::hasPermission('admin')) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // معايير البحث
        $searchTerm = $_GET['term'] ?? '';
        $type = $_GET['type'] ?? '';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

        // البحث في السجلات
        $results = [];
        if (!empty($searchTerm)) {
            $results = $this->log->searchLogs($searchTerm, $limit);
        } elseif (!empty($type)) {
            $results = $this->log->getLogs($limit, 0, $type);
        } else {
            $results = $this->log->getLogs($limit);
        }

        // تنسيق النتائج
        $formattedResults = [];
        foreach ($results as $log) {
            $formattedResults[] = [
                'id' => $log['id'],
                'type' => $log['type'],
                'action' => $log['action'],
                'details' => $log['details'],
                'username' => $log['username'] ?? 'غير مسجل',
                'ip_address' => $log['ip_address'],
                'created_at' => date('Y-m-d H:i:s', strtotime($log['created_at']))
            ];
        }

        // إرجاع النتائج بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $formattedResults,
            'count' => count($formattedResults)
        ]);
    }

    /**
     * البحث عن المستخدمين
     */
    public function searchUsersAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || !Auth::hasPermission('admin')) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // التحقق من وجود معيار البحث
        $searchTerm = $_GET['term'] ?? '';
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            $this->jsonResponse(['error' => 'معيار البحث قصير جداً']);
            return;
        }

        // البحث عن المستخدمين
        $results = $this->user->searchUsers($searchTerm);

        // تنسيق النتائج
        $formattedResults = [];
        foreach ($results as $user) {
            $formattedResults[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role'],
                'status' => $user['status'],
                'last_login' => $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'لم يسجل دخول',
                'created_at' => date('Y-m-d', strtotime($user['created_at'])),
                'edit_url' => '/admin/edit_user/' . $user['id']
            ];
        }

        // إرجاع النتائج بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $formattedResults,
            'count' => count($formattedResults)
        ]);
    }

    /**
     * البحث الشامل
     */
    public function globalSearchAction() {
        // التحقق من تسجيل الدخول
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // التحقق من وجود معيار البحث
        $searchTerm = $_GET['term'] ?? '';
        if (empty($searchTerm) || strlen($searchTerm) < 2) {
            $this->jsonResponse(['error' => 'معيار البحث قصير جداً']);
            return;
        }

        $results = [
            'invoices' => [],
            'customers' => []
        ];

        // البحث عن الفواتير
        $invoices = $this->invoice->searchInvoices(['keyword' => $searchTerm], 5);
        foreach ($invoices as $invoice) {
            $results['invoices'][] = [
                'id' => $invoice['id'],
                'title' => 'فاتورة #' . ($invoice['invoice_number'] ?? $invoice['id']),
                'description' => $invoice['customer_name'] . ' - ' . number_format($invoice['total_price']) . ' دينار',
                'url' => '/invoice/view/' . $invoice['id']
            ];
        }

        // البحث عن الزبائن
        $customers = $this->customer->searchCustomers($searchTerm, 5);
        foreach ($customers as $customer) {
            $results['customers'][] = [
                'id' => $customer['id'],
                'title' => $customer['name'],
                'description' => $customer['phone'] . ($customer['governorate'] ? ' - ' . $customer['governorate'] : ''),
                'url' => '/customer/view/' . $customer['id']
            ];
        }

        // إذا كان المستخدم مديراً، أضف نتائج إضافية
        if (Auth::hasPermission('admin')) {
            $results['users'] = [];
            
            // البحث عن المستخدمين
            $users = $this->user->searchUsers($searchTerm, 5);
            foreach ($users as $user) {
                $results['users'][] = [
                    'id' => $user['id'],
                    'title' => $user['username'],
                    'description' => $user['full_name'] . ' - ' . $user['role'],
                    'url' => '/admin/edit_user/' . $user['id']
                ];
            }
        }

        // تسجيل عملية البحث
        $this->log->addLog(
            Log::TYPE_INFO,
            'بحث شامل',
            'تم البحث عن: ' . $searchTerm,
            Auth::getUserId()
        );

        // إرجاع النتائج بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * التحقق من إمكانية حذف الفاتورة
     */
    private function canDeleteInvoice($invoice) {
        $user = Auth::getUser();
        
        // المستوى الثالث: يمكن للموظف حذف أي فاتورة بلا استثناءات
        if (Auth::hasPermission('delete_level_3')) {
            return true;
        }
        
        // المستوى الثاني: يمكن للموظف حذف أي فاتورة ما عدا التي تحتوي على رقم سلة
        if (Auth::hasPermission('delete_level_2')) {
            return empty($invoice['cart_number']);
        }
        
        // المستوى الأول: يمكن للموظف حذف الفواتير التي أنشأها فقط
        if (Auth::hasPermission('delete_level_1')) {
            return $invoice['created_by'] == $user['id'];
        }
        
        return false;
    }

    /**
     * إرجاع استجابة JSON
     */
    private function jsonResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
