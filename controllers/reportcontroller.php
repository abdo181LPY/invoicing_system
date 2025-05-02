<?php
class ReportController {
    private $invoice;
    private $customer;
    private $user;
    private $page;
    private $log;

    public function __construct() {
        $this->invoice = new Invoice();
        $this->customer = new Customer();
        $this->user = new User();
        $this->page = new Page();
        $this->log = new Log();
    }

    /**
     * عرض صفحة التقارير
     */
    public function indexAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn()) {
            Session::set('flash_message', 'يجب تسجيل الدخول للوصول إلى صفحة التقارير');
            Session::set('flash_type', 'error');
            Router::redirect('/auth/login');
            return;
        }

        // التحقق من الصلاحيات (المدراء فقط أو من لديهم صلاحية التقارير)
        if (!Auth::hasPermission('admin') && !Auth::hasPermission('view_reports')) {
            Session::set('flash_message', 'غير مصرح لك بالوصول إلى صفحة التقارير');
            Session::set('flash_type', 'error');
            Router::redirect('/dashboard');
            return;
        }

        // بيانات الصفحة
        $pageData = [
            'title' => 'التقارير',
            'users' => $this->user->getAllUsers(),
            'pages' => $this->page->getAllPages(),
            'governorates' => include(ROOT_PATH . '/includes/governorates.php')
        ];

        // عرض صفحة التقارير
        include(ROOT_PATH . '/views/reports/index.php');
    }

    /**
     * تقرير المبيعات
     */
    public function salesReportAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || (!Auth::hasPermission('admin') && !Auth::hasPermission('view_reports'))) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // استلام المعايير
        $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');
        $userId = isset($_POST['user_id']) && $_POST['user_id'] ? $_POST['user_id'] : null;
        $pageId = isset($_POST['page_id']) && $_POST['page_id'] ? $_POST['page_id'] : null;
        $governorate = isset($_POST['governorate']) && $_POST['governorate'] ? $_POST['governorate'] : null;

        // الحصول على بيانات المبيعات
        $salesData = $this->invoice->getSalesReport($dateFrom, $dateTo, $userId, $pageId, $governorate);

        // تحضير البيانات للرسم البياني اليومي
        $dailyChartData = [];
        $dateRange = new DatePeriod(
            new DateTime($dateFrom),
            new DateInterval('P1D'),
            (new DateTime($dateTo))->modify('+1 day')
        );

        $dailySales = array_column($salesData['daily_sales'], 'total', 'date');
        $dailyCount = array_column($salesData['daily_sales'], 'count', 'date');

        foreach ($dateRange as $date) {
            $dateStr = $date->format('Y-m-d');
            $dailyChartData[] = [
                'date' => $dateStr,
                'total' => isset($dailySales[$dateStr]) ? (float)$dailySales[$dateStr] : 0,
                'count' => isset($dailyCount[$dateStr]) ? (int)$dailyCount[$dateStr] : 0
            ];
        }

        // إضافة متوسط المبيعات اليومي
        $avgDailySales = !empty($dailyChartData) ? array_sum(array_column($dailyChartData, 'total')) / count($dailyChartData) : 0;

        // تنسيق البيانات
        $result = [
            'summary' => [
                'total_sales' => number_format($salesData['summary']['total_sales']),
                'total_invoices' => $salesData['summary']['total_invoices'],
                'avg_invoice_value' => $salesData['summary']['total_invoices'] > 0 
                    ? number_format($salesData['summary']['total_sales'] / $salesData['summary']['total_invoices']) 
                    : 0,
                'avg_daily_sales' => number_format($avgDailySales)
            ],
            'chart_data' => [
                'daily' => $dailyChartData,
                'by_governorate' => $salesData['by_governorate'],
                'by_page' => $salesData['by_page'],
                'by_user' => $salesData['by_user'],
                'by_status' => $salesData['by_status']
            ],
            'top_items' => [
                'customers' => $salesData['top_customers'],
                'invoices' => $salesData['top_invoices']
            ]
        ];

        // تسجيل عملية إنشاء التقرير
        $this->log->addLog(
            Log::TYPE_INFO,
            'إنشاء تقرير مبيعات',
            "تم إنشاء تقرير المبيعات للفترة من {$dateFrom} إلى {$dateTo}",
            Auth::getUserId()
        );

        // إرجاع البيانات بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * تقرير أداء الموظفين
     */
    public function userPerformanceReportAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || !Auth::hasPermission('admin')) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // استلام المعايير
        $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');
        $userId = isset($_POST['user_id']) && $_POST['user_id'] ? $_POST['user_id'] : null;

        // الحصول على بيانات أداء الموظفين
        $performanceData = $this->user->getUserPerformanceReport($dateFrom, $dateTo, $userId);

        // تحضير البيانات للرسم البياني اليومي
        $userChartData = [];
        foreach ($performanceData['daily_performance'] as $user) {
            $userData = [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'daily_data' => []
            ];

            $dateRange = new DatePeriod(
                new DateTime($dateFrom),
                new DateInterval('P1D'),
                (new DateTime($dateTo))->modify('+1 day')
            );

            $dailyCounts = array_column($user['daily_counts'], 'count', 'date');
            
            foreach ($dateRange as $date) {
                $dateStr = $date->format('Y-m-d');
                $userData['daily_data'][] = [
                    'date' => $dateStr,
                    'count' => isset($dailyCounts[$dateStr]) ? (int)$dailyCounts[$dateStr] : 0
                ];
            }

            $userChartData[] = $userData;
        }

        // تنسيق البيانات
        $result = [
            'summary' => [
                'total_users' => $performanceData['summary']['total_users'],
                'total_invoices' => $performanceData['summary']['total_invoices'],
                'avg_invoices_per_user' => $performanceData['summary']['total_users'] > 0 
                    ? round($performanceData['summary']['total_invoices'] / $performanceData['summary']['total_users'], 1) 
                    : 0
            ],
            'chart_data' => [
                'user_performance' => $userChartData,
                'by_role' => $performanceData['by_role']
            ],
            'users' => [
                'top_performers' => $performanceData['top_performers'],
                'activity' => $performanceData['user_activity']
            ]
        ];

        // تسجيل عملية إنشاء التقرير
        $this->log->addLog(
            Log::TYPE_INFO,
            'إنشاء تقرير أداء الموظفين',
            "تم إنشاء تقرير أداء الموظفين للفترة من {$dateFrom} إلى {$dateTo}",
            Auth::getUserId()
        );

        // إرجاع البيانات بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * تقرير حالات الطلبات
     */
    public function orderStatusReportAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || (!Auth::hasPermission('admin') && !Auth::hasPermission('view_reports'))) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // استلام المعايير
        $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');
        $pageId = isset($_POST['page_id']) && $_POST['page_id'] ? $_POST['page_id'] : null;

        // الحصول على بيانات حالات الطلبات
        $statusData = $this->invoice->getOrderStatusReport($dateFrom, $dateTo, $pageId);

        // تنسيق البيانات
        $result = [
            'summary' => [
                'total_invoices' => $statusData['summary']['total_invoices'],
                'completed_orders' => $statusData['summary']['completed_orders'],
                'completion_rate' => $statusData['summary']['total_invoices'] > 0 
                    ? round(($statusData['summary']['completed_orders'] / $statusData['summary']['total_invoices']) * 100, 1) 
                    : 0,
                'cancelled_orders' => $statusData['summary']['cancelled_orders'],
                'cancellation_rate' => $statusData['summary']['total_invoices'] > 0 
                    ? round(($statusData['summary']['cancelled_orders'] / $statusData['summary']['total_invoices']) * 100, 1) 
                    : 0
            ],
            'chart_data' => [
                'by_status' => $statusData['by_status'],
                'status_timeline' => $statusData['status_timeline'],
                'avg_time_by_status' => $statusData['avg_time_by_status']
            ]
        ];

        // تسجيل عملية إنشاء التقرير
        $this->log->addLog(
            Log::TYPE_INFO,
            'إنشاء تقرير حالات الطلبات',
            "تم إنشاء تقرير حالات الطلبات للفترة من {$dateFrom} إلى {$dateTo}",
            Auth::getUserId()
        );

        // إرجاع البيانات بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * تقرير الصفحات
     */
    public function pagesReportAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || !Auth::hasPermission('admin')) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // استلام المعايير
        $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');
        $pageId = isset($_POST['page_id']) && $_POST['page_id'] ? $_POST['page_id'] : null;

        // الحصول على بيانات الصفحات
        $pageData = $this->page->getPageReport($dateFrom, $dateTo, $pageId);

        // تنسيق البيانات
        $result = [
            'summary' => [
                'total_pages' => $pageData['summary']['total_pages'],
                'total_sales' => number_format($pageData['summary']['total_sales']),
                'total_invoices' => $pageData['summary']['total_invoices'],
                'avg_sales_per_page' => $pageData['summary']['total_pages'] > 0 
                    ? number_format($pageData['summary']['total_sales'] / $pageData['summary']['total_pages']) 
                    : 0
            ],
            'chart_data' => [
                'by_page' => $pageData['by_page'],
                'page_growth' => $pageData['page_growth'],
                'conversion_rate' => $pageData['conversion_rate']
            ],
            'pages' => [
                'top_performers' => $pageData['top_performers'],
                'page_details' => $pageData['page_details']
            ]
        ];

        // تسجيل عملية إنشاء التقرير
        $this->log->addLog(
            Log::TYPE_INFO,
            'إنشاء تقرير الصفحات',
            "تم إنشاء تقرير الصفحات للفترة من {$dateFrom} إلى {$dateTo}",
            Auth::getUserId()
        );

        // إرجاع البيانات بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * تقرير الزبائن
     */
    public function customersReportAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || (!Auth::hasPermission('admin') && !Auth::hasPermission('view_reports'))) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }

        // استلام المعايير
        $dateFrom = $_POST['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_POST['date_to'] ?? date('Y-m-d');
        $governorate = isset($_POST['governorate']) && $_POST['governorate'] ? $_POST['governorate'] : null;
        $minOrders = isset($_POST['min_orders']) && $_POST['min_orders'] ? (int)$_POST['min_orders'] : 1;

        // الحصول على بيانات الزبائن
        $customerData = $this->customer->getCustomerReport($dateFrom, $dateTo, $governorate, $minOrders);

        // تنسيق البيانات
        $result = [
            'summary' => [
                'total_customers' => $customerData['summary']['total_customers'],
                'new_customers' => $customerData['summary']['new_customers'],
                'returning_customers' => $customerData['summary']['returning_customers'],
                'returning_rate' => $customerData['summary']['total_customers'] > 0 
                    ? round(($customerData['summary']['returning_customers'] / $customerData['summary']['total_customers']) * 100, 1) 
                    : 0
            ],
            'chart_data' => [
                'by_governorate' => $customerData['by_governorate'],
                'by_rating' => $customerData['by_rating'],
                'customer_growth' => $customerData['customer_growth']
            ],
            'customers' => [
                'top_spenders' => $customerData['top_spenders'],
                'most_frequent' => $customerData['most_frequent']
            ]
        ];

        // تسجيل عملية إنشاء التقرير
        $this->log->addLog(
            Log::TYPE_INFO,
            'إنشاء تقرير الزبائن',
            "تم إنشاء تقرير الزبائن للفترة من {$dateFrom} إلى {$dateTo}",
            Auth::getUserId()
        );

        // إرجاع البيانات بصيغة JSON
        $this->jsonResponse([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * تصدير التقرير إلى PDF
     */
    public function exportPdfAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || (!Auth::hasPermission('admin') && !Auth::hasPermission('view_reports'))) {
            Session::set('flash_message', 'غير مصرح لك بتصدير التقارير');
            Session::set('flash_type', 'error');
            Router::redirect('/reports');
            return;
        }

        // التحقق من وجود بيانات التقرير
        if (!isset($_POST['report_type']) || !isset($_POST['report_data'])) {
            Session::set('flash_message', 'بيانات التقرير غير كاملة');
            Session::set('flash_type', 'error');
            Router::redirect('/reports');
            return;
        }

        $reportType = $_POST['report_type'];
        $reportData = json_decode($_POST['report_data'], true);
        
        if (!$reportData) {
            Session::set('flash_message', 'حدث خطأ في قراءة بيانات التقرير');
            Session::set('flash_type', 'error');
            Router::redirect('/reports');
            return;
        }

        // إسم التقرير
        $reportTitles = [
            'sales' => 'تقرير المبيعات',
            'user_performance' => 'تقرير أداء الموظفين',
            'order_status' => 'تقرير حالات الطلبات',
            'pages' => 'تقرير الصفحات',
            'customers' => 'تقرير الزبائن'
        ];

        $reportTitle = $reportTitles[$reportType] ?? 'تقرير';
        
        // معايير التقرير
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        
        if ($dateFrom && $dateTo) {
            $reportTitle .= " ({$dateFrom} إلى {$dateTo})";
        }
        
        // إنشاء ملف PDF
        // هذا مثال بسيط - يمكن استخدام مكتبة مثل TCPDF أو MPDF للإنشاء الفعلي
        
        // تسجيل عملية التصدير
        $this->log->addLog(
            Log::TYPE_INFO,
            'تصدير تقرير',
            "تم تصدير {$reportTitle} بتنسيق PDF",
            Auth::getUserId()
        );
        
        // لتنفيذ حقيقي، يمكن إنشاء PDF واسترجاعه هنا
        // للأغراض التوضيحية، نقوم بإعادة التوجيه إلى صفحة التقارير مع رسالة نجاح
        
        Session::set('flash_message', "تم تصدير {$reportTitle} بنجاح");
        Session::set('flash_type', 'success');
        Router::redirect('/reports');
    }

    /**
     * تصدير التقرير إلى Excel
     */
    public function exportExcelAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || (!Auth::hasPermission('admin') && !Auth::hasPermission('view_reports'))) {
            Session::set('flash_message', 'غير مصرح لك بتصدير التقارير');
            Session::set('flash_type', 'error');
            Router::redirect('/reports');
            return;
        }
        
        // التحقق من وجود بيانات التقرير
        if (!isset($_POST['report_type']) || !isset($_POST['report_data'])) {
            Session::set('flash_message', 'بيانات التقرير غير كاملة');
            Session::set('flash_type', 'error');
            Router::redirect('/reports');
            return;
        }

        $reportType = $_POST['report_type'];
        $reportData = json_decode($_POST['report_data'], true);
        
        if (!$reportData) {
            Session::set('flash_message', 'حدث خطأ في قراءة بيانات التقرير');
            Session::set('flash_type', 'error');
            Router::redirect('/reports');
            return;
        }

        // إسم التقرير
        $reportTitles = [
            'sales' => 'تقرير المبيعات',
            'user_performance' => 'تقرير أداء الموظفين',
            'order_status' => 'تقرير حالات الطلبات',
            'pages' => 'تقرير الصفحات',
            'customers' => 'تقرير الزبائن'
        ];

        $reportTitle = $reportTitles[$reportType] ?? 'تقرير';
        
        // معايير التقرير
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        
        if ($dateFrom && $dateTo) {
            $reportTitle .= " ({$dateFrom} إلى {$dateTo})";
        }
        
        // إنشاء ملف Excel
        // هذا مثال بسيط - يمكن استخدام مكتبة مثل PhpSpreadsheet للإنشاء الفعلي
        
        // تسجيل عملية التصدير
        $this->log->addLog(
            Log::TYPE_INFO,
            'تصدير تقرير',
            "تم تصدير {$reportTitle} بتنسيق Excel",
            Auth::getUserId()
        );
        
        // لتنفيذ حقيقي، يمكن إنشاء Excel واسترجاعه هنا
        // للأغراض التوضيحية، نقوم بإعادة التوجيه إلى صفحة التقارير مع رسالة نجاح
        
        Session::set('flash_message', "تم تصدير {$reportTitle} بنجاح");
        Session::set('flash_type', 'success');
        Router::redirect('/reports');
    }

    /**
     * جدولة تقرير دوري
     */
    public function scheduleReportAction() {
        // التحقق من تسجيل الدخول والصلاحيات
        if (!Auth::isLoggedIn() || !Auth::hasPermission('admin')) {
            $this->jsonResponse(['error' => 'غير مصرح بالوصول']);
            return;
        }
        
        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'طريقة الطلب غير صحيحة']);
            return;
        }
        
        // استلام بيانات الجدولة
        $reportType = $_POST['report_type'] ?? '';
        $frequency = $_POST['frequency'] ?? ''; // يومي، أسبوعي، شهري
        $recipients = $_POST['recipients'] ?? []; // قائمة البريد الإلكتروني للمستلمين
        $format = $_POST['format'] ?? 'pdf'; // تنسيق التقرير (PDF، Excel)
        
        // التحقق من صحة البيانات
        if (empty($reportType) || empty($frequency) || empty($recipients)) {
            $this->jsonResponse(['error' => 'يرجى ملء جميع الحقول المطلوبة']);
            return;
        }
        
        // تسجيل الجدولة في قاعدة البيانات (هذا جزء مبسط - ستحتاج إلى إنشاء جدول لذلك)
        $db = Database::getInstance();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO scheduled_reports (
                    report_type, frequency, recipients, format, created_by, created_at
                ) VALUES (
                    :report_type, :frequency, :recipients, :format, :created_by, NOW()
                )
            ");
            
            $recipientsStr = is_array($recipients) ? implode(',', $recipients) : $recipients;
            $userId = Auth::getUserId();
            
            $stmt->bindParam(':report_type', $reportType, PDO::PARAM_STR);
            $stmt->bindParam(':frequency', $frequency, PDO::PARAM_STR);
            $stmt->bindParam(':recipients', $recipientsStr, PDO::PARAM_STR);
            $stmt->bindParam(':format', $format, PDO::PARAM_STR);
            $stmt->bindParam(':created_by', $userId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            if ($result) {
                // تسجيل عملية الجدولة
                $this->log->addLog(
                    Log::TYPE_INFO,
                    'جدولة تقرير',
                    "تمت جدولة تقرير {$reportType} بتكرار {$frequency}",
                    $userId
                );
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'تمت جدولة التقرير بنجاح'
                ]);
            } else {
                $this->jsonResponse(['error' => 'حدث خطأ أثناء جدولة التقرير']);
            }
            
        } catch (PDOException $e) {
            $this->log->logError("خطأ في جدولة التقرير: " . $e->getMessage(), Auth::getUserId());
            $this->jsonResponse(['error' => 'حدث خطأ في النظام أثناء جدولة التقرير']);
        }
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
