<?php
/**
 * متحكم لوحة التحكم
 * يدير عرض لوحة التحكم والإحصائيات
 */
class DashboardController {
    private $db;
    private $invoice;
    private $user;
    private $page;
    private $blacklist;
    private $auth;
    private $session;
    
    /**
     * إنشاء كائن المتحكم
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->invoice = new Invoice();
        $this->user = new User();
        $this->page = new Page();
        $this->blacklist = new Blacklist();
        $this->auth = Auth::getInstance();
        $this->session = Session::getInstance();
    }
    
    /**
     * عرض لوحة التحكم الرئيسية
     */
    public function index() {
        // التحقق من صلاحية عرض لوحة التحكم
        if (!$this->auth->hasPermission(3)) {
            // عرض لوحة تحكم محدودة للمستخدمين العاديين
            $this->showUserDashboard();
            return;
        }
        
        // الحصول على إحصائيات الفواتير
        $invoiceStats = $this->invoice->getStatistics();
        
        // الحصول على إحصائيات المستخدمين
        $userStats = $this->user->getStatistics();
        
        // الحصول على إحصائيات الصفحات
        $pageStats = $this->page->getStatistics();
        
        // الحصول على عدد الأرقام المحظورة
        $blacklistCount = $this->blacklist->count();
        
        // الحصول على آخر الفواتير
        $recentInvoices = $this->invoice->getAll(10);
        
        // الحصول على آخر المستخدمين
        $recentUsers = $this->user->getAll(5);
        
        // تحميل قالب لوحة التحكم
        include 'views/dashboard/admin.php';
    }
    
    /**
     * عرض لوحة التحكم للمستخدمين العاديين
     */
    private function showUserDashboard() {
        // الحصول على معرف المستخدم الحالي
        $userId = $this->auth->getCurrentUserId();
        
        // الحصول على آخر الفواتير للمستخدم
        $userInvoices = $this->invoice->getUserInvoices($userId, 10);
        
        // الحصول على عدد الفواتير للمستخدم
        $userInvoiceCount = $this->invoice->count(['created_by' => $userId]);
        
        // الحصول على عدد الفواتير حسب الحالة للمستخدم
        $userInvoicesByStatus = [];
        foreach ($GLOBALS['ORDER_STATUSES'] as $status) {
            $userInvoicesByStatus[$status] = $this->invoice->count([
                'created_by' => $userId,
                'status' => $status
            ]);
        }
        
        // تحميل قالب لوحة تحكم المستخدم
        include 'views/dashboard/user.php';
    }
    
    /**
     * عرض الإحصائيات
     */
    public function statistics() {
        // التحقق من صلاحية عرض الإحصائيات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لعرض الإحصائيات');
            header('Location: dashboard.php');
            exit;
        }
        
        // نوع الإحصائيات
        $type = isset($_GET['type']) ? $_GET['type'] : 'general';
        
        // الفترة الزمنية
        $period = isset($_GET['period']) ? $_GET['period'] : 'month';
        
        // الإحصائيات العامة
        $generalStats = $this->getGeneralStatistics();
        
        // إحصائيات الفترة المحددة
        $periodStats = $this->getPeriodStatistics($period);
        
        // إحصائيات حسب النوع
        $typeStats = $this->getTypeStatistics($type, $period);
        
        // تحميل قالب الإحصائيات
        include 'views/dashboard/statistics.php';
    }
    
    /**
     * الحصول على الإحصائيات العامة
     */
    private function getGeneralStatistics() {
        $stats = [
            'total_invoices' => $this->invoice->count(),
            'total_users' => $this->user->count(),
            'total_pages' => $this->page->count(),
            'total_blacklist' => $this->blacklist->count(),
            'invoices_by_status' => [],
            'invoices_by_province' => []
        ];
        
        // عدد الفواتير حسب الحالة
        foreach ($GLOBALS['ORDER_STATUSES'] as $status) {
            $stats['invoices_by_status'][$status] = $this->invoice->count(['status' => $status]);
        }
        
        // عدد الفواتير حسب المحافظة
        foreach ($GLOBALS['PROVINCES'] as $province) {
            $stats['invoices_by_province'][$province] = $this->invoice->count(['province' => $province]);
        }
        
        return $stats;
    }
    
    /**
     * الحصول على إحصائيات الفترة الزمنية
     */
    private function getPeriodStatistics($period) {
        $stats = [
            'labels' => [],
            'invoices' => [],
            'sales' => [],
            'period' => $period
        ];
        
        // تحديد الفترة
        $today = date('Y-m-d');
        $format = '';
        $interval = '';
        $limit = 0;
        
        switch ($period) {
            case 'day':
                // الأيام الأخيرة
                $format = 'Y-m-d';
                $interval = '-1 day';
                $limit = 30;
                break;
            case 'week':
                // الأسابيع الأخيرة
                $format = 'Y-W';
                $interval = '-1 week';
                $limit = 12;
                break;
            case 'month':
                // الأشهر الأخيرة
                $format = 'Y-m';
                $interval = '-1 month';
                $limit = 12;
                break;
            case 'year':
                // السنوات الأخيرة
                $format = 'Y';
                $interval = '-1 year';
                $limit = 5;
                break;
            default:
                // الأيام الأخيرة (افتراضي)
                $format = 'Y-m-d';
                $interval = '-1 day';
                $limit = 30;
        }
        
        // إنشاء البيانات
        $date = new DateTime($today);
        $sql = "SELECT COUNT(*) as invoice_count, SUM(total_price) as total_sales 
                FROM invoices 
                WHERE DATE_FORMAT(created_at, :format) = :period";
        
        for ($i = 0; $i < $limit; $i++) {
            $periodLabel = $date->format($format);
            
            // الحصول على البيانات
            $periodData = $this->db->fetch($sql, [
                'format' => $format,
                'period' => $periodLabel
            ]);
            
            // إضافة البيانات
            $stats['labels'][] = $this->formatPeriodLabel($periodLabel, $period);
            $stats['invoices'][] = $periodData['invoice_count'] ?: 0;
            $stats['sales'][] = $periodData['total_sales'] ?: 0;
            
            // الانتقال إلى الفترة السابقة
            $date->modify($interval);
        }
        
        // عكس البيانات لعرضها بالترتيب الصحيح
        $stats['labels'] = array_reverse($stats['labels']);
        $stats['invoices'] = array_reverse($stats['invoices']);
        $stats['sales'] = array_reverse($stats['sales']);
        
        return $stats;
    }
    
    /**
     * تنسيق تسمية الفترة الزمنية
     */
    private function formatPeriodLabel($label, $period) {
        switch ($period) {
            case 'day':
                // اليوم
                $date = DateTime::createFromFormat('Y-m-d', $label);
                return $date->format('d/m');
            case 'week':
                // الأسبوع
                list($year, $week) = explode('-', $label);
                return 'أسبوع ' . $week . ', ' . $year;
            case 'month':
                // الشهر
                $date = DateTime::createFromFormat('Y-m', $label);
                return $date->format('m/Y');
            case 'year':
                // السنة
                return $label;
            default:
                return $label;
        }
    }
    
    /**
     * الحصول على إحصائيات حسب النوع
     */
    private function getTypeStatistics($type, $period) {
        $stats = [];
        
        switch ($type) {
            case 'users':
                // إحصائيات المستخدمين
                $stats = $this->getUserStatistics($period);
                break;
            case 'pages':
                // إحصائيات الصفحات
                $stats = $this->getPageStatistics($period);
                break;
            case 'provinces':
                // إحصائيات المحافظات
                $stats = $this->getProvinceStatistics($period);
                break;
            default:
                // الإحصائيات العامة (افتراضي)
                $stats = $this->getGeneralStatistics();
        }
        
        return $stats;
    }
    
    /**
     * الحصول على إحصائيات المستخدمين
     */
    private function getUserStatistics($period) {
        $stats = [
            'top_users' => [],
            'user_activity' => []
        ];
        
        // تحديد الفترة
        $whereClause = $this->getPeriodWhereClause($period);
        
        // المستخدمين الأكثر نشاطاً
        $sql = "SELECT u.id, u.username, COUNT(i.id) as invoice_count, SUM(i.total_price) as total_sales
                FROM users u
                LEFT JOIN invoices i ON u.id = i.created_by
                WHERE {$whereClause}
                GROUP BY u.id, u.username
                ORDER BY invoice_count DESC
                LIMIT 10";
        
        $stats['top_users'] = $this->db->fetchAll($sql);
        
        // نشاط المستخدمين حسب الوقت
        $sql = "SELECT DATE_FORMAT(created_at, '%H:00') as hour, COUNT(*) as activity_count
                FROM activity_logs
                WHERE action = 'تسجيل دخول' AND {$whereClause}
                GROUP BY hour
                ORDER BY hour ASC";
        
        $stats['user_activity'] = $this->db->fetchAll($sql);
        
        return $stats;
    }
    
    /**
     * الحصول على إحصائيات الصفحات
     */
    private function getPageStatistics($period) {
        $stats = [
            'top_pages' => [],
            'page_sales' => []
        ];
        
        // تحديد الفترة
        $whereClause = $this->getPeriodWhereClause($period);
        
        // الصفحات الأكثر مبيعاً
        $sql = "SELECT p.id, p.name, COUNT(i.id) as invoice_count, SUM(i.total_price) as total_sales
                FROM pages p
                LEFT JOIN invoices i ON p.id = i.page_id
                WHERE {$whereClause}
                GROUP BY p.id, p.name
                ORDER BY total_sales DESC
                LIMIT 10";
        
        $stats['top_pages'] = $this->db->fetchAll($sql);
        
        // مبيعات الصفحات عبر الوقت
        if ($period == 'month') {
            $sql = "SELECT p.name, DATE_FORMAT(i.created_at, '%Y-%m') as month, SUM(i.total_price) as total_sales
                    FROM pages p
                    JOIN invoices i ON p.id = i.page_id
                    WHERE {$whereClause}
                    GROUP BY p.name, month
                    ORDER BY p.name ASC, month ASC";
            
            $pageMonths = $this->db->fetchAll($sql);
            
            // تنظيم البيانات للرسم البياني
            $pageSales = [];
            $months = [];
            
            foreach ($pageMonths as $row) {
                if (!in_array($row['month'], $months)) {
                    $months[] = $row['month'];
                }
                
                $pageSales[$row['name']][$row['month']] = $row['total_sales'];
            }
            
            $stats['page_sales'] = [
                'pages' => array_keys($pageSales),
                'months' => $months,
                'data' => $pageSales
            ];
        }
        
        return $stats;
    }
    
    /**
     * الحصول على إحصائيات المحافظات
     */
    private function getProvinceStatistics($period) {
        $stats = [
            'province_sales' => [],
            'province_counts' => []
        ];
        
        // تحديد الفترة
        $whereClause = $this->getPeriodWhereClause($period);
        
        // المبيعات حسب المحافظة
        $sql = "SELECT province, COUNT(*) as invoice_count, SUM(total_price) as total_sales
                FROM invoices
                WHERE {$whereClause}
                GROUP BY province
                ORDER BY total_sales DESC";
        
        $provinceSales = $this->db->fetchAll($sql);
        
        // تنظيم البيانات للرسوم البيانية
        $labels = [];
        $counts = [];
        $sales = [];
        
        foreach ($provinceSales as $row) {
            $labels[] = $row['province'];
            $counts[] = $row['invoice_count'];
            $sales[] = $row['total_sales'];
        }
        
        $stats['province_sales'] = [
            'labels' => $labels,
            'data' => $sales
        ];
        
        $stats['province_counts'] = [
            'labels' => $labels,
            'data' => $counts
        ];
        
        return $stats;
    }
    
    /**
     * الحصول على شرط الفترة الزمنية للاستعلامات
     */
    private function getPeriodWhereClause($period) {
        // تحديد الفترة
        $today = date('Y-m-d');
        
        switch ($period) {
            case 'day':
                // اليوم
                return "DATE(created_at) = '{$today}'";
            case 'week':
                // الأسبوع
                return "YEARWEEK(created_at) = YEARWEEK('{$today}')";
            case 'month':
                // الشهر
                return "YEAR(created_at) = YEAR('{$today}') AND MONTH(created_at) = MONTH('{$today}')";
            case 'year':
                // السنة
                return "YEAR(created_at) = YEAR('{$today}')";
            case 'all':
                // كل الوقت
                return "1=1";
            default:
                // اليوم (افتراضي)
                return "DATE(created_at) = '{$today}'";
        }
    }
    
    /**
     * تصدير الإحصائيات
     */
    public function exportStatistics() {
        // التحقق من صلاحية تصدير الإحصائيات
        if (!$this->auth->hasPermission(3)) {
            $this->session->setError('ليس لديك صلاحية لتصدير الإحصائيات');
            header('Location: dashboard.php');
            exit;
        }
        
        // نوع الإحصائيات
        $type = isset($_GET['type']) ? $_GET['type'] : 'general';
        
        // الفترة الزمنية
        $period = isset($_GET['period']) ? $_GET['period'] : 'month';
        
        // تنسيق التصدير
        $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
        
        // الإحصائيات العامة
        $generalStats = $this->getGeneralStatistics();
        
        // إحصائيات الفترة المحددة
        $periodStats = $this->getPeriodStatistics($period);
        
        // إحصائيات حسب النوع
        $typeStats = $this->getTypeStatistics($type, $period);
        
        // تصدير البيانات حسب التنسيق
        switch ($format) {
            case 'csv':
                $this->exportStatisticsCSV($generalStats, $periodStats, $typeStats, $type, $period);
                break;
            case 'pdf':
                $this->exportStatisticsPDF($generalStats, $periodStats, $typeStats, $type, $period);
                break;
            default:
                $this->session->setError('تنسيق التصدير غير مدعوم');
                header('Location: statistics.php');
                exit;
        }
    }
    
    /**
     * تصدير الإحصائيات بتنسيق CSV
     */
    private function exportStatisticsCSV($generalStats, $periodStats, $typeStats, $type, $period) {
        // اسم الملف
        $filename = 'statistics_' . $type . '_' . $period . '_' . date('Y-m-d') . '.csv';
        
        // إعداد الملف
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // فتح المخرج
        $output = fopen('php://output', 'w');
        
        // إضافة BOM لدعم Unicode
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // كتابة العناوين
        fputcsv($output, ['الإحصائيات العامة', date('Y-m-d')]);
        fputcsv($output, ['']);
        
        // كتابة الإحصائيات العامة
        fputcsv($output, ['إجمالي الفواتير', $generalStats['total_invoices']]);
        fputcsv($output, ['إجمالي المستخدمين', $generalStats['total_users']]);
        fputcsv($output, ['إجمالي الصفحات', $generalStats['total_pages']]);
        fputcsv($output, ['إجمالي البلاك ليست', $generalStats['total_blacklist']]);
        fputcsv($output, ['']);
        
        // كتابة الفواتير حسب الحالة
        fputcsv($output, ['الفواتير حسب الحالة']);
        foreach ($generalStats['invoices_by_status'] as $status => $count) {
            fputcsv($output, [$status, $count]);
        }
        fputcsv($output, ['']);
        
        // كتابة الإحصائيات حسب الفترة
        fputcsv($output, ['الإحصائيات حسب الفترة (' . $period . ')']);
        fputcsv($output, array_merge(['الفترة'], $periodStats['labels']));
        fputcsv($output, array_merge(['عدد الفواتير'], $periodStats['invoices']));
        fputcsv($output, array_merge(['إجمالي المبيعات'], $periodStats['sales']));
        fputcsv($output, ['']);
        
        // كتابة الإحصائيات حسب النوع
        switch ($type) {
            case 'users':
                // المستخدمين الأكثر نشاطاً
                fputcsv($output, ['المستخدمين الأكثر نشاطاً']);
                fputcsv($output, ['اسم المستخدم', 'عدد الفواتير', 'إجمالي المبيعات']);
                foreach ($typeStats['top_users'] as $user) {
                    fputcsv($output, [$user['username'], $user['invoice_count'], $user['total_sales']]);
                }
                break;
            case 'pages':
                // الصفحات الأكثر مبيعاً
                fputcsv($output, ['الصفحات الأكثر مبيعاً']);
                fputcsv($output, ['اسم الصفحة', 'عدد الفواتير', 'إجمالي المبيعات']);
                foreach ($typeStats['top_pages'] as $page) {
                    fputcsv($output, [$page['name'], $page['invoice_count'], $page['total_sales']]);
                }
                break;
            case 'provinces':
                // المبيعات حسب المحافظة
                fputcsv($output, ['المبيعات حسب المحافظة']);
                fputcsv($output, ['المحافظة', 'عدد الفواتير', 'إجمالي المبيعات']);
                for ($i = 0; $i < count($typeStats['province_sales']['labels']); $i++) {
                    fputcsv($output, [
                        $typeStats['province_sales']['labels'][$i],
                        $typeStats['province_counts']['data'][$i],
                        $typeStats['province_sales']['data'][$i]
                    ]);
                }
                break;
        }
        
        // إغلاق المخرج
        fclose($output);
        exit;
    }
    
    /**
     * تصدير الإحصائيات بتنسيق PDF
     */
    private function exportStatisticsPDF($generalStats, $periodStats, $typeStats, $type, $period) {
        // هذه الدالة تتطلب مكتبة PDF مثل FPDF أو TCPDF
        // سيتم تنفيذها لاحقاً
        
        $this->session->setError('تصدير PDF غير مدعوم حالياً');
        header('Location: statistics.php');
        exit;
    }
}