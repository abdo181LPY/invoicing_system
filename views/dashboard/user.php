<?php 
/**
 * صفحة لوحة التحكم للمستخدم العادي
 * تعرض ملخص الفواتير والإحصائيات للمستخدم
 */
$pageTitle = 'لوحة التحكم';
$includeCharts = true;
include 'views/layout/header.php';
include 'views/layout/sidebar.php';
?>

<!-- عنوان الصفحة -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="invoice_create.php" class="btn btn-sm btn-primary">
            <i class="fas fa-plus-circle me-1"></i>
            تثبيت طلب جديد
        </a>
    </div>
</div>

<!-- بطاقات الإحصائيات -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <div class="h1 text-primary mb-2">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h5 class="card-title">إجمالي الفواتير</h5>
                <p class="card-text h2"><?php echo $userInvoiceCount; ?></p>
                <a href="user_invoices.php" class="btn btn-outline-primary btn-sm mt-2">عرض التفاصيل</a>
            </div>
        </div>
    </div>

    <?php
    // اختيار 3 حالات فقط للعرض
    $displayStatuses = ['جديد', 'تم الشراء', 'تم الشحن'];
    foreach ($displayStatuses as $status):
        // تحديد لون وأيقونة لكل حالة
        $colorClass = 'primary';
        $icon = 'circle';
        
        switch ($status) {
            case 'جديد':
                $colorClass = 'info';
                $icon = 'star';
                break;
            case 'تم الشراء':
                $colorClass = 'success';
                $icon = 'check-circle';
                break;
            case 'تم الشحن':
                $colorClass = 'warning';
                $icon = 'truck';
                break;
        }
    ?>
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-<?php echo $colorClass; ?> h-100">
            <div class="card-body text-center">
                <div class="h1 text-<?php echo $colorClass; ?> mb-2">
                    <i class="fas fa-<?php echo $icon; ?>"></i>
                </div>
                <h5 class="card-title">طلبات <?php echo $status; ?></h5>
                <p class="card-text h2"><?php echo $userInvoicesByStatus[$status] ?? 0; ?></p>
                <a href="invoices_by_status.php?status=<?php echo urlencode($status); ?>" class="btn btn-outline-<?php echo $colorClass; ?> btn-sm mt-2">عرض التفاصيل</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- بطاقة إحصائيات إضافية -->
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-secondary h-100">
            <div class="card-body text-center">
                <div class="h1 text-secondary mb-2">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h5 class="card-title">إحصائيات شخصية</h5>
                <p class="card-text small">عرض تقرير مفصل عن نشاطك</p>
                <a href="user_statistics.php" class="btn btn-outline-secondary btn-sm mt-2">عرض الإحصائيات</a>
            </div>
        </div>
    </div>
</div>

<!-- حالة الطلبات -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>حالة الطلبات</h5>
    </div>
    <div class="card-body">
        <div class="progress" style="height: 30px;">
            <?php
            // تحديد الألوان لكل حالة
            $statusColors = [
                'جديد' => 'primary',
                'ليث' => 'info',
                'الوسيط' => 'secondary',
                'لم يتم الشراء' => 'danger',
                'تم الشراء' => 'success',
                'تم الشحن' => 'warning',
                'مرتجع' => 'danger',
                'مكتمل' => 'success',
                'ملغي' => 'dark'
            ];
            
            // حساب النسب المئوية لكل حالة
            foreach ($GLOBALS['ORDER_STATUSES'] as $status) {
                $count = $userInvoicesByStatus[$status] ?? 0;
                $percentage = ($userInvoiceCount > 0) ? round(($count / $userInvoiceCount) * 100) : 0;
                
                if ($percentage > 0) {
                    $color = $statusColors[$status] ?? 'secondary';
                    echo '<div class="progress-bar bg-' . $color . '" role="progressbar" style="width: ' . $percentage . '%" 
                          aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100" 
                          title="' . $status . ': ' . $count . ' (' . $percentage . '%)">' . $status . ' ' . $percentage . '%</div>';
                }
            }
            ?>
        </div>
        
        <div class="row mt-3">
            <?php foreach ($GLOBALS['ORDER_STATUSES'] as $status): ?>
            <?php 
                $count = $userInvoicesByStatus[$status] ?? 0;
                $color = $statusColors[$status] ?? 'secondary';
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                <div class="d-flex align-items-center">
                    <div class="me-2" style="width: 20px; height: 20px; background-color: var(--bs-<?php echo $color; ?>);"></div>
                    <span><?php echo $status; ?>: <?php echo $count; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- آخر الفواتير -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>آخر الفواتير</h5>
            <a href="user_invoices.php" class="btn btn-sm btn-light">عرض الكل</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم الزبون</th>
                        <th>المحافظة</th>
                        <th>رقم الهاتف</th>
                        <th>الصفحة</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($userInvoices)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">لا توجد فواتير لعرضها</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($userInvoices as $invoice): ?>
                        <tr>
                            <td><?php echo $invoice['id']; ?></td>
                            <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['province']); ?></td>
                            <td dir="ltr"><?php echo htmlspecialchars($invoice['phone']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['page_name'] ?? ''); ?></td>
                            <td>
                                <?php
                                $statusClass = $statusColors[$invoice['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($invoice['status']); ?></span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($invoice['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="invoice_view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-info" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="invoice_print.php?id=<?php echo $invoice['id']; ?>" class="btn btn-secondary" title="طباعة الفاتورة" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <?php if ($auth->canDeleteInvoice($invoice['id'])): ?>
                                    <button type="button" class="btn btn-danger" title="حذف الفاتورة"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#confirmModal" 
                                        data-confirm-href="invoice_delete.php?id=<?php echo $invoice['id']; ?>"
                                        data-confirm-message="هل أنت متأكد من حذف الفاتورة رقم <?php echo $invoice['id']; ?>؟"
                                        data-confirm-button="حذف"
                                        data-confirm-button-class="btn-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- الروابط السريعة -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card bg-light h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-calculator me-2 text-primary"></i>حساب التكلفة</h5>
                <p class="card-text">قم بحساب تكلفة الطلبات وإظهار المعلومات للزبائن بسهولة.</p>
                <a href="calculator.php" class="btn btn-primary">
                    <i class="fas fa-calculator me-2"></i>حساب التكلفة
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card bg-light h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-plus-circle me-2 text-success"></i>تثبيت طلب</h5>
                <p class="card-text">قم بإدخال المعلومات الأساسية لتثبيت طلب جديد لزبائنك.</p>
                <a href="invoice_create.php" class="btn btn-success">
                    <i class="fas fa-plus-circle me-2"></i>تثبيت طلب
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card bg-light h-100">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-search me-2 text-info"></i>البحث والحذف</h5>
                <p class="card-text">ابحث عن الفواتير بسهولة وإمكانية حذفها حسب صلاحياتك.</p>
                <a href="invoice_search.php" class="btn btn-info">
                    <i class="fas fa-search me-2"></i>البحث والحذف
                </a>
            </div>
        </div>
    </div>
</div>

<!-- مخطط الفواتير -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>إحصائيات الفواتير</h5>
    </div>
    <div class="card-body">
        <canvas id="invoicesChart" width="400" height="200"></canvas>
    </div>
</div>

<!-- سكريبت الرسم البياني -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة مخطط الفواتير
    var ctx = document.getElementById('invoicesChart').getContext('2d');
    
    // تحضير البيانات
    var statuses = <?php echo json_encode(array_keys($userInvoicesByStatus)); ?>;
    var counts = <?php echo json_encode(array_values($userInvoicesByStatus)); ?>;
    
    // الألوان للرسم البياني
    var backgroundColors = [];
    var borderColors = [];
    
    // تحديد الألوان حسب الحالة
    statuses.forEach(function(status) {
        var color;
        switch (status) {
            case 'جديد':
                color = '54, 162, 235'; // أزرق
                break;
            case 'ليث':
            case 'الوسيط':
                color = '75, 192, 192'; // أزرق فاتح
                break;
            case 'لم يتم الشراء':
                color = '255, 99, 132'; // أحمر
                break;
            case 'تم الشراء':
                color = '75, 192, 92'; // أخضر
                break;
            case 'تم الشحن':
                color = '255, 206, 86'; // أصفر
                break;
            case 'مرتجع':
                color = '255, 99, 71'; // أحمر فاتح
                break;
            case 'مكتمل':
                color = '50, 205, 50'; // أخضر فاتح
                break;
            case 'ملغي':
                color = '128, 128, 128'; // رمادي
                break;
            default:
                color = '153, 102, 255'; // بنفسجي
        }
        
        backgroundColors.push('rgba(' + color + ', 0.5)');
        borderColors.push('rgba(' + color + ', 1)');
    });
    
    var invoicesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: statuses,
            datasets: [{
                label: 'عدد الفواتير',
                data: counts,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    precision: 0
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'عدد الفواتير: ' + context.raw;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'views/layout/footer.php'; ?>
