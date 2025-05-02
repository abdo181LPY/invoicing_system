<?php 
/**
 * صفحة لوحة التحكم للمدراء
 * تعرض ملخص الإحصائيات وإدارة النظام
 */
$pageTitle = 'لوحة التحكم الإدارية';
$includeCharts = true;
include 'views/layout/header.php';
include 'views/layout/sidebar.php';
?>

<!-- عنوان الصفحة -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم الإدارية</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="statistics.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-chart-area me-1"></i>
                تقارير مفصلة
            </a>
            <a href="statistics.php?export=1" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-file-export me-1"></i>
                تصدير البيانات
            </a>
        </div>
        <div class="dropdown">
            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="actionDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة جديد
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown">
                <li><a class="dropdown-item" href="user_create.php"><i class="fas fa-user-plus me-2"></i>مستخدم جديد</a></li>
                <li><a class="dropdown-item" href="page_create.php"><i class="fas fa-store-alt me-2"></i>صفحة جديدة</a></li>
                <li><a class="dropdown-item" href="blacklist_create.php"><i class="fas fa-ban me-2"></i>حظر رقم جديد</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="invoice_create.php"><i class="fas fa-file-invoice me-2"></i>فاتورة جديدة</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- ملخص الإحصائيات -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center">
                <div class="h1 text-primary mb-2">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h5 class="card-title">إجمالي الفواتير</h5>
                <p class="card-text h2"><?php echo $invoiceStats['total']; ?></p>
                <div class="small text-muted">اليوم: <?php echo $invoiceStats['today_count']; ?></div>
            </div>
            <div class="card-footer bg-primary text-white">
                <a href="invoice_search.php" class="text-white text-decoration-none stretched-link">
                    <i class="fas fa-search me-1"></i> عرض التفاصيل
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-success h-100">
            <div class="card-body text-center">
                <div class="h1 text-success mb-2">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h5 class="card-title">إجمالي المبيعات</h5>
                <p class="card-text h2"><?php echo number_format($invoiceStats['total_sales']); ?></p>
                <div class="small text-muted">اليوم: <?php echo number_format($invoiceStats['today_sales']); ?></div>
            </div>
            <div class="card-footer bg-success text-white">
                <a href="statistics.php" class="text-white text-decoration-none stretched-link">
                    <i class="fas fa-chart-bar me-1"></i> المزيد من الإحصائيات
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-info h-100">
            <div class="card-body text-center">
                <div class="h1 text-info mb-2">
                    <i class="fas fa-users"></i>
                </div>
                <h5 class="card-title">المستخدمين</h5>
                <p class="card-text h2"><?php echo $userStats['total']; ?></p>
                <div class="small text-muted">نشط: <?php echo $userStats['active']; ?> | معطل: <?php echo $userStats['inactive']; ?></div>
            </div>
            <div class="card-footer bg-info text-white">
                <a href="users.php" class="text-white text-decoration-none stretched-link">
                    <i class="fas fa-user-cog me-1"></i> إدارة المستخدمين
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center">
                <div class="h1 text-warning mb-2">
                    <i class="fas fa-store"></i>
                </div>
                <h5 class="card-title">الصفحات</h5>
                <p class="card-text h2"><?php echo $pageStats['total']; ?></p>
                <div class="small text-muted">
                    <?php
                    // الحصول على أفضل صفحة من حيث المبيعات
                    $topPage = !empty($pageStats['top_pages']) ? $pageStats['top_pages'][0] : null;
                    if ($topPage) {
                        echo 'الأفضل: ' . htmlspecialchars($topPage['name']);
                    }
                    ?>
                </div>
            </div>
            <div class="card-footer bg-warning text-dark">
                <a href="pages.php" class="text-dark text-decoration-none stretched-link">
                    <i class="fas fa-cog me-1"></i> إدارة الصفحات
                </a>
            </div>
        </div>
    </div>
</div>

<!-- إحصائيات حسب الحالة -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>الفواتير حسب الحالة</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" width="400" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>حالة الطلبات</h5>
            </div>
            <div class="card-body pb-0">
                <?php foreach ($invoiceStats['by_status'] as $status => $count): ?>
                <?php 
                    // تحديد لون الحالة
                    $color = 'secondary';
                    switch ($status) {
                        case 'جديد': $color = 'primary'; break;
                        case 'ليث': 
                        case 'الوسيط': $color = 'info'; break;
                        case 'لم يتم الشراء': $color = 'danger'; break;
                        case 'تم الشراء': $color = 'success'; break;
                        case 'تم الشحن': $color = 'warning'; break;
                        case 'مرتجع': $color = 'danger'; break;
                        case 'مكتمل': $color = 'success'; break;
                        case 'ملغي': $color = 'dark'; break;
                    }
                    
                    // حساب النسبة المئوية
                    $percentage = ($invoiceStats['total'] > 0) ? round(($count / $invoiceStats['total']) * 100) : 0;
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span><?php echo $status; ?></span>
                        <span><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-<?php echo $color; ?>" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="card-footer text-center">
                <a href="invoices_by_status.php" class="btn btn-sm btn-outline-primary">عرض كل الحالات</a>
            </div>
        </div>
    </div>
</div>

<!-- أحدث الفواتير والتحركات -->
<div class="row mb-4">
    <div class="col-lg-8 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>أحدث الفواتير</h5>
                    <a href="invoice_search.php" class="btn btn-sm btn-light">عرض الكل</a>
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
                                <th>المستخدم</th>
                                <th>الصفحة</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentInvoices)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">لا توجد فواتير لعرضها</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recentInvoices as $invoice): ?>
                                <tr>
                                    <td><?php echo $invoice['id']; ?></td>
                                    <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['province']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['created_by_username'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['page_name'] ?? ''); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'secondary';
                                        switch ($invoice['status']) {
                                            case 'جديد': $statusClass = 'primary'; break;
                                            case 'ليث': 
                                            case 'الوسيط': $statusClass = 'info'; break;
                                            case 'لم يتم الشراء': $statusClass = 'danger'; break;
                                            case 'تم الشراء': $statusClass = 'success'; break;
                                            case 'تم الشحن': $statusClass = 'warning'; break;
                                            case 'مرتجع': $statusClass = 'danger'; break;
                                            case 'مكتمل': $statusClass = 'success'; break;
                                            case 'ملغي': $statusClass = 'dark'; break;
                                        }
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
    </div>
    
    <div class="col-lg-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-friends me-2"></i>آخر المستخدمين</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($recentUsers)): ?>
                    <div class="list-group-item text-center py-4">لا يوجد مستخدمين لعرضهم</div>
                    <?php else: ?>
                        <?php foreach ($recentUsers as $user): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['is_active']): ?>
                                    <span class="badge bg-success ms-2">نشط</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger ms-2">معطل</span>
                                    <?php endif; ?>
                                    <div class="small text-muted">
                                        <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($user['email']); ?><br>
                                        <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($user['phone']); ?>
                                    </div>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="user_activity.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-info" title="سجل النشاط">
                                        <i class="fas fa-list"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="users.php" class="btn btn-sm btn-outline-primary">إدارة المستخدمين</a>
            </div>
        </div>
    </div>
</div>

<!-- بطاقات وروابط سريعة -->
<div class="row mb-4">
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>أرقام السلات</h5>
            </div>
            <div class="card-body">
                <p class="card-text">يمكنك إضافة أرقام السلات للفواتير وإدارتها من هنا.</p>
                <div class="d-grid">
                    <a href="add_cart_numbers.php" class="btn btn-info">
                        <i class="fas fa-shopping-cart me-2"></i>إدارة أرقام السلات
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="fas fa-ban me-2"></i>القائمة السوداء</h5>
            </div>
            <div class="card-body">
                <p class="card-text">يوجد حالياً <?php echo $blacklistCount; ?> رقم في القائمة السوداء.</p>
                <div class="d-grid">
                    <a href="blacklist.php" class="btn btn-danger">
                        <i class="fas fa-ban me-2"></i>إدارة القائمة السوداء
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-trash-restore me-2"></i>الفواتير المحذوفة</h5>
            </div>
            <div class="card-body">
                <p class="card-text">يمكنك استعادة الفواتير التي تم حذفها من هنا.</p>
                <div class="d-grid">
                    <a href="deleted_invoices.php" class="btn btn-warning">
                        <i class="fas fa-trash-restore me-2"></i>استعادة الفواتير المحذوفة
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مخطط الإحصائيات -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>المبيعات والفواتير</h5>
    </div>
    <div class="card-body">
        <canvas id="salesChart" width="400" height="200"></canvas>
    </div>
</div>

<!-- سكريبت الرسوم البيانية -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // مخطط حالة الطلبات
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    
    // تحضير البيانات
    var statuses = [];
    var counts = [];
    var colors = [];
    
    <?php
    foreach ($invoiceStats['by_status'] as $status => $count) {
        echo "statuses.push('" . $status . "');\n";
        echo "counts.push(" . $count . ");\n";
        
        // اختيار اللون المناسب
        $color = 'rgba(160, 160, 160, 0.7)'; // افتراضي
        switch ($status) {
            case 'جديد': $color = 'rgba(54, 162, 235, 0.7)'; break;
            case 'ليث': 
            case 'الوسيط': $color = 'rgba(75, 192, 192, 0.7)'; break;
            case 'لم يتم الشراء': $color = 'rgba(255, 99, 132, 0.7)'; break;
            case 'تم الشراء': $color = 'rgba(75, 192, 92, 0.7)'; break;
            case 'تم الشحن': $color = 'rgba(255, 206, 86, 0.7)'; break;
            case 'مرتجع': $color = 'rgba(255, 99, 71, 0.7)'; break;
            case 'مكتمل': $color = 'rgba(50, 205, 50, 0.7)'; break;
            case 'ملغي': $color = 'rgba(128, 128, 128, 0.7)'; break;
        }
        echo "colors.push('" . $color . "');\n";
    }
    ?>
    
    var statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: statuses,
            datasets: [{
                data: counts,
                backgroundColor: colors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            var value = context.raw || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = Math.round((value / total) * 100);
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    
    // مخطط المبيعات
    var salesCtx = document.getElementById('salesChart').getContext('2d');
    
    var salesData = <?php
        // استعراض 12 شهر الماضية
        $months = [];
        $invoiceData = [];
        $salesData = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = new DateTime();
            $date->modify("-{$i} month");
            $year = $date->format('Y');
            $month = $date->format('m');
            $label = $date->format('M Y');
            
            $months[] = $label;
            
            $monthInvoices = $db->fetchColumn(
                "SELECT COUNT(*) FROM invoices WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month",
                ['year' => $year, 'month' => $month]
            );
            
            $monthSales = $db->fetchColumn(
                "SELECT SUM(total_price) FROM invoices WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month",
                ['year' => $year, 'month' => $month]
            );
            
            $invoiceData[] = $monthInvoices;
            $salesData[] = $monthSales ?: 0;
        }
        
        // تصدير البيانات كـ JSON
        echo json_encode([
            'labels' => $months,
            'invoices' => $invoiceData,
            'sales' => $salesData
        ]);
    ?>;
    
    var salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: salesData.labels,
            datasets: [
                {
                    label: 'المبيعات (بالدينار)',
                    data: salesData.sales,
                    borderColor: 'rgba(75, 192, 92, 1)',
                    backgroundColor: 'rgba(75, 192, 92, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'عدد الفواتير',
                    data: salesData.invoices,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'المبيعات (بالدينار)'
                    },
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                    title: {
                        display: true,
                        text: 'عدد الفواتير'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let value = context.raw;
                            
                            if (label.includes('المبيعات')) {
                                return label + ': ' + value.toLocaleString() + ' دينار';
                            } else {
                                return label + ': ' + value;
                            }
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'views/layout/footer.php'; ?>
