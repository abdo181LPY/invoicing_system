<?php include_once(ROOT_PATH . '/views/layout/header.php'); ?>

<div class="dashboard-container rtl">
    <?php include_once(ROOT_PATH . '/views/layout/sidebar.php'); ?>

    <div class="content-area">
        <div class="content-header">
            <div class="content-title">
                <h1>التقارير</h1>
                <p>إنشاء وعرض التقارير المختلفة للنظام</p>
            </div>
            <div class="content-actions">
                <div class="btn-group">
                    <button id="export-pdf-btn" class="btn btn-secondary" disabled>
                        <i class="fas fa-file-pdf"></i> تصدير PDF
                    </button>
                    <button id="export-excel-btn" class="btn btn-secondary" disabled>
                        <i class="fas fa-file-excel"></i> تصدير Excel
                    </button>
                </div>
                <?php if (Auth::hasPermission('admin')): ?>
                <button id="schedule-report-btn" class="btn btn-outline-primary">
                    <i class="fas fa-clock"></i> جدولة تقرير
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-body">
            <!-- أنواع التقارير -->
            <div class="reports-tabs">
                <ul class="nav nav-tabs" id="reportsTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="sales-tab" data-toggle="tab" href="#sales" role="tab">
                            <i class="fas fa-chart-line"></i> تقرير المبيعات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab">
                            <i class="fas fa-users"></i> تقرير الموظفين
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="orders-tab" data-toggle="tab" href="#orders" role="tab">
                            <i class="fas fa-shopping-cart"></i> تقرير الطلبات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pages-tab" data-toggle="tab" href="#pages" role="tab">
                            <i class="fas fa-store"></i> تقرير الصفحات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="customers-tab" data-toggle="tab" href="#customers" role="tab">
                            <i class="fas fa-user-friends"></i> تقرير الزبائن
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content" id="reportsTabContent">
                    <!-- تقرير المبيعات -->
                    <div class="tab-pane fade show active" id="sales" role="tabpanel">
                        <div class="filter-section">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="sales-date-from">من تاريخ:</label>
                                    <input type="date" id="sales-date-from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="sales-date-to">إلى تاريخ:</label>
                                    <input type="date" id="sales-date-to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="sales-user">الموظف:</label>
                                    <select id="sales-user" class="form-control">
                                        <option value="">جميع الموظفين</option>
                                        <?php foreach ($pageData['users'] as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo $user['username']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="sales-page">الصفحة:</label>
                                    <select id="sales-page" class="form-control">
                                        <option value="">جميع الصفحات</option>
                                        <?php foreach ($pageData['pages'] as $page): ?>
                                        <option value="<?php echo $page['id']; ?>"><?php echo $page['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="sales-governorate">المحافظة:</label>
                                    <select id="sales-governorate" class="form-control">
                                        <option value="">جميع المحافظات</option>
                                        <?php foreach ($pageData['governorates'] as $code => $name): ?>
                                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <button id="generate-sales-report" class="btn btn-primary">إنشاء التقرير</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="report-container" id="sales-report-container">
                            <div class="report-loading text-center d-none">
                                <i class="fas fa-spinner fa-pulse fa-3x"></i>
                                <p>جاري إنشاء التقرير...</p>
                            </div>
                            
                            <div class="report-content d-none">
                                <!-- ملخص التقرير -->
                                <div class="report-summary">
                                    <div class="summary-cards">
                                        <div class="summary-card">
                                            <div class="summary-icon bg-primary">
                                                <i class="fas fa-dollar-sign"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="total-sales">0</h3>
                                                <p>إجمالي المبيعات</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-success">
                                                <i class="fas fa-file-invoice"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="total-invoices">0</h3>
                                                <p>عدد الفواتير</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-info">
                                                <i class="fas fa-calculator"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="avg-invoice-value">0</h3>
                                                <p>متوسط قيمة الفاتورة</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-warning">
                                                <i class="fas fa-chart-bar"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="avg-daily-sales">0</h3>
                                                <p>متوسط المبيعات اليومي</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- الرسوم البيانية -->
                                <div class="report-charts">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="chart-container">
                                                <h3>المبيعات اليومية</h3>
                                                <canvas id="daily-sales-chart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="chart-container">
                                                <h3>المبيعات حسب المحافظة</h3>
                                                <canvas id="governorate-sales-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <h3>المبيعات حسب الصفحة</h3>
                                                <canvas id="page-sales-chart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <h3>المبيعات حسب الموظف</h3>
                                                <canvas id="user-sales-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- جداول البيانات -->
                                <div class="report-tables">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="table-container">
                                                <h3>أعلى الزبائن</h3>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>الاسم</th>
                                                                <th>رقم الهاتف</th>
                                                                <th>عدد الطلبات</th>
                                                                <th>إجمالي المبلغ</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="top-customers-table">
                                                            <tr>
                                                                <td colspan="4" class="text-center">لا توجد بيانات</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="table-container">
                                                <h3>أعلى الفواتير</h3>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>رقم الفاتورة</th>
                                                                <th>الزبون</th>
                                                                <th>المحافظة</th>
                                                                <th>المبلغ</th>
                                                                <th>التاريخ</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="top-invoices-table">
                                                            <tr>
                                                                <td colspan="5" class="text-center">لا توجد بيانات</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تقرير الموظفين -->
                    <div class="tab-pane fade" id="users" role="tabpanel">
                        <div class="filter-section">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="users-date-from">من تاريخ:</label>
                                    <input type="date" id="users-date-from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="users-date-to">إلى تاريخ:</label>
                                    <input type="date" id="users-date-to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="users-selected">الموظف:</label>
                                    <select id="users-selected" class="form-control">
                                        <option value="">جميع الموظفين</option>
                                        <?php foreach ($pageData['users'] as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo $user['username']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <button id="generate-users-report" class="btn btn-primary">إنشاء التقرير</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="report-container" id="users-report-container">
                            <div class="report-loading text-center d-none">
                                <i class="fas fa-spinner fa-pulse fa-3x"></i>
                                <p>جاري إنشاء التقرير...</p>
                            </div>
                            
                            <div class="report-content d-none">
                                <!-- ملخص التقرير -->
                                <div class="report-summary">
                                    <div class="summary-cards">
                                        <div class="summary-card">
                                            <div class="summary-icon bg-primary">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="total-users">0</h3>
                                                <p>إجمالي الموظفين</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-success">
                                                <i class="fas fa-file-invoice"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="total-user-invoices">0</h3>
                                                <p>إجمالي الفواتير</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-info">
                                                <i class="fas fa-calculator"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="avg-invoices-per-user">0</h3>
                                                <p>متوسط الفواتير لكل موظف</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- الرسوم البيانية -->
                                <div class="report-charts">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="chart-container">
                                                <h3>نشاط الموظفين</h3>
                                                <canvas id="user-performance-chart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="chart-container">
                                                <h3>الفواتير حسب الدور</h3>
                                                <canvas id="role-invoices-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- جداول البيانات -->
                                <div class="report-tables">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="table-container">
                                                <h3>أفضل الموظفين أداءً</h3>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>اسم المستخدم</th>
                                                                <th>الاسم الكامل</th>
                                                                <th>عدد الفواتير</th>
                                                                <th>إجمالي المبيعات</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="top-performers-table">
                                                            <tr>
                                                                <td colspan="4" class="text-center">لا توجد بيانات</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="table-container">
                                                <h3>نشاط الموظفين</h3>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>اسم المستخدم</th>
                                                                <th>آخر تسجيل دخول</th>
                                                                <th>عدد الفواتير اليوم</th>
                                                                <th>نشاط اليوم</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="user-activity-table">
                                                            <tr>
                                                                <td colspan="4" class="text-center">لا توجد بيانات</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تقرير الطلبات -->
                    <div class="tab-pane fade" id="orders" role="tabpanel">
                        <div class="filter-section">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="orders-date-from">من تاريخ:</label>
                                    <input type="date" id="orders-date-from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="orders-date-to">إلى تاريخ:</label>
                                    <input type="date" id="orders-date-to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="orders-page">الصفحة:</label>
                                    <select id="orders-page" class="form-control">
                                        <option value="">جميع الصفحات</option>
                                        <?php foreach ($pageData['pages'] as $page): ?>
                                        <option value="<?php echo $page['id']; ?>"><?php echo $page['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <button id="generate-orders-report" class="btn btn-primary">إنشاء التقرير</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="report-container" id="orders-report-container">
                            <div class="report-loading text-center d-none">
                                <i class="fas fa-spinner fa-pulse fa-3x"></i>
                                <p>جاري إنشاء التقرير...</p>
                            </div>
                            
                            <div class="report-content d-none">
                                <!-- ملخص التقرير -->
                                <div class="report-summary">
                                    <div class="summary-cards">
                                        <div class="summary-card">
                                            <div class="summary-icon bg-primary">
                                                <i class="fas fa-shopping-cart"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="total-orders-count">0</h3>
                                                <p>إجمالي الطلبات</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-success">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="completed-orders">0</h3>
                                                <p>الطلبات المكتملة</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-info">
                                                <i class="fas fa-percentage"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="completion-rate">0%</h3>
                                                <p>نسبة الإكمال</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-danger">
                                                <i class="fas fa-times-circle"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="cancelled-orders">0</h3>
                                                <p>الطلبات الملغاة</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- الرسوم البيانية -->
                                <div class="report-charts">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <h3>الطلبات حسب الحالة</h3>
                                                <canvas id="status-chart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <h3>وقت معالجة الطلبات</h3>
                                                <canvas id="processing-time-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- خط زمني للحالات -->
                                <div class="status-timeline">
                                    <h3>خط زمني لحالات الطلبات</h3>
                                    <div id="status-timeline-chart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تقرير الصفحات -->
                    <div class="tab-pane fade" id="pages" role="tabpanel">
                        <div class="filter-section">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="pages-date-from">من تاريخ:</label>
                                    <input type="date" id="pages-date-from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="pages-date-to">إلى تاريخ:</label>
                                    <input type="date" id="pages-date-to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="pages-selected">الصفحة:</label>
                                    <select id="pages-selected" class="form-control">
                                        <option value="">جميع الصفحات</option>
                                        <?php foreach ($pageData['pages'] as $page): ?>
                                        <option value="<?php echo $page['id']; ?>"><?php echo $page['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <button id="generate-pages-report" class="btn btn-primary">إنشاء التقرير</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="report-container" id="pages-report-container">
                            <div class="report-loading text-center d-none">
                                <i class="fas fa-spinner fa-pulse fa-3x"></i>
                                <p>جاري إنشاء التقرير...</p>
                            </div>
                            
                            <div class="report-content d-none">
                                <!-- ملخص التقرير -->
                                <div class="report-summary">
                                    <div class="summary-cards">
                                        <div class="summary-card">
                                            <div class="summary-icon bg-primary">
                                                <i class="fas fa-store"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="total-pages">0</h3>
                                                <p>إجمالي الصفحات</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-success">
                                                <i class="fas fa-dollar-sign"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="total-pages-sales">0</h3>
                                                <p>إجمالي المبيعات</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-info">
                                                <i class="fas fa-file-invoice"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="total-pages-invoices">0</h3>
                                                <p>إجمالي الفواتير</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-warning">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="avg-sales-per-page">0</h3>
                                                <p>متوسط المبيعات لكل صفحة</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- الرسوم البيانية -->
                                <div class="report-charts">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <h3>المبيعات حسب الصفحة</h3>
                                                <canvas id="sales-by-page-chart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <h3>نمو الصفحات</h3>
                                                <canvas id="page-growth-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- جداول البيانات -->
                                <div class="report-tables">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="table-container">
                                                <h3>أفضل الصفحات أداءً</h3>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>اسم الصفحة</th>
                                                                <th>عدد الفواتير</th>
                                                                <th>إجمالي المبيعات</th>
                                                                <th>معدل التحويل</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="top-pages-table">
                                                            <tr>
                                                                <td colspan="4" class="text-center">لا توجد بيانات</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="table-container">
                                                <h3>تفاصيل الصفحات</h3>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>اسم الصفحة</th>
                                                                <th>تاريخ الإنشاء</th>
                                                                <th>عدد المنتجات</th>
                                                                <th>آخر تحديث</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="page-details-table">
                                                            <tr>
                                                                <td colspan="4" class="text-center">لا توجد بيانات</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- تقرير الزبائن -->
                    <div class="tab-pane fade" id="customers" role="tabpanel">
                        <div class="filter-section">
                            <div class="filter-row">
                                <div class="filter-group">
                                    <label for="customers-date-from">من تاريخ:</label>
                                    <input type="date" id="customers-date-from" class="form-control" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="customers-date-to">إلى تاريخ:</label>
                                    <input type="date" id="customers-date-to" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="filter-group">
                                    <label for="customers-governorate">المحافظة:</label>
                                    <select id="customers-governorate" class="form-control">
                                        <option value="">جميع المحافظات</option>
                                        <?php foreach ($pageData['governorates'] as $code => $name): ?>
                                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="filter-group">
                                    <label for="customers-min-orders">الحد الأدنى للطلبات:</label>
                                    <input type="number" id="customers-min-orders" class="form-control" value="1" min="1">
                                </div>
                                <div class="filter-group">
                                    <button id="generate-customers-report" class="btn btn-primary">إنشاء التقرير</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="report-container" id="customers-report-container">
                            <div class="report-loading text-center d-none">
                                <i class="fas fa-spinner fa-pulse fa-3x"></i>
                                <p>جاري إنشاء التقرير...</p>
                            </div>
                            
                            <div class="report-content d-none">
                                <!-- ملخص التقرير -->
                                <div class="report-summary">
                                    <div class="summary-cards">
                                        <div class="summary-card">
                                            <div class="summary-icon bg-primary">
                                                <i class="fas fa-user-friends"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="total-customers">0</h3>
                                                <p>إجمالي الزبائن</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-success">
                                                <i class="fas fa-user-plus"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="new-customers">0</h3>
                                                <p>الزبائن الجدد</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-info">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="returning-customers">0</h3>
                                                <p>الزبائن العائدون</p>
                                            </div>
                                        </div>
                                        <div class="summary-card">
                                            <div class="summary-icon bg-warning">
                                                <i class="fas fa-percentage"></i>
                                            </div>
                                            <div class="summary-details">
                                                <h3 id="returning-rate">0%</h3>
                                                <p>نسبة العودة</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- الرسوم البيانية -->
                                <div class="report-charts">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="chart-container">
                                                <h3>الزبائن حسب المحافظة</h3>
                                                <canvas id="customers-by-governorate-chart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="chart-container">
                                                <h3>الزبائن حسب التصنيف</h3>
                                                <canvas id="customers-by-rating-chart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="chart-container">
                                                <h3>نمو قاعدة الزبائن</h3>
                                                <canvas id="customer-growth-chart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- جداول البيانات -->
                                <div class="report-tables">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="table-container">
                                                <h3>أعلى الزبائن إنفاقاً</h3>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>الاسم</th>
                                                                <th>رقم الهاتف</th>
                                                                <th>المحافظة</th>
                                                                <th>إجمالي الإنفاق</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="top-spenders-table">
                                                            <tr>
                                                                <td colspan="4" class="text-center">لا توجد بيانات</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="table-container">
                                                <h3>الزبائن الأكثر نشاطاً</h3>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>الاسم</th>
                                                                <th>رقم الهاتف</th>
                                                                <th>عدد الطلبات</th>
                                                                <th>آخر طلب</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="most-active-customers-table">
                                                            <tr>
                                                                <td colspan="4" class="text-center">لا توجد بيانات</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نموذج جدولة التقرير -->
<?php if (Auth::hasPermission('admin')): ?>
<div class="modal fade" id="schedule-report-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">جدولة تقرير دوري</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="schedule-report-form">
                    <div class="form-group">
                        <label for="schedule-report-type">نوع التقرير</label>
                        <select id="schedule-report-type" name="report_type" class="form-control" required>
                            <option value="sales">تقرير المبيعات</option>
                            <option value="users">تقرير الموظفين</option>
                            <option value="orders">تقرير الطلبات</option>
                            <option value="pages">تقرير الصفحات</option>
                            <option value="customers">تقرير الزبائن</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule-frequency">التكرار</label>
                        <select id="schedule-frequency" name="frequency" class="form-control" required>
                            <option value="daily">يومي</option>
                            <option value="weekly">أسبوعي</option>
                            <option value="monthly">شهري</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule-format">صيغة التقرير</label>
                        <select id="schedule-format" name="format" class="form-control" required>
                            <option value="pdf">PDF</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule-recipients">المستلمون (بريد إلكتروني)</label>
                        <textarea id="schedule-recipients" name="recipients" class="form-control" rows="3" required placeholder="أدخل عناوين البريد الإلكتروني مفصولة بفواصل"></textarea>
                        <small class="form-text text-muted">أدخل عناوين البريد الإلكتروني مفصولة بفواصل</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <button type="button" id="save-schedule-btn" class="btn btn-primary">حفظ الجدولة</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- تحميل مكتبة Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

<!-- تحميل ملف الرسوم البيانية المخصص -->
<script src="/js/charts.js"></script>

<script>
$(document).ready(function() {
    // المتغيرات العامة
    var currentReport = null;
    var reportData = null;
    
    // تهيئة الأحداث
    initEvents();
    
    // زر التصدير إلى PDF
    $('#export-pdf-btn').click(function() {
        if (!currentReport || !reportData) return;
        
        exportReport('pdf');
    });
    
    // زر التصدير إلى Excel
    $('#export-excel-btn').click(function() {
        if (!currentReport || !reportData) return;
        
        exportReport('excel');
    });
    
    // زر جدولة التقرير
    $('#schedule-report-btn').click(function() {
        $('#schedule-report-modal').modal('show');
    });
    
    // حفظ جدولة التقرير
    $('#save-schedule-btn').click(function() {
        var form = $('#schedule-report-form');
        
        // التحقق من صحة البيانات
        if (!form[0].checkValidity()) {
            form[0].reportValidity();
            return;
        }
        
        var formData = {
            report_type: $('#schedule-report-type').val(),
            frequency: $('#schedule-frequency').val(),
            format: $('#schedule-format').val(),
            recipients: $('#schedule-recipients').val()
        };
        
        // إرسال الطلب
        $.ajax({
            url: '/reports/scheduleReport',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'تم جدولة التقرير بنجاح');
                    $('#schedule-report-modal').modal('hide');
                    form[0].reset();
                } else {
                    showAlert('danger', response.error || 'حدث خطأ أثناء جدولة التقرير');
                }
            },
            error: function() {
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    });
    
    // تبديل علامات التبويب
    $('#reportsTab a').on('shown.bs.tab', function(e) {
        currentReport = $(e.target).attr('id').replace('-tab', '');
        
        // إعادة تعيين حالة أزرار التصدير
        $('#export-pdf-btn, #export-excel-btn').prop('disabled', true);
        
        // إخفاء محتوى التقرير
        $('.report-content').addClass('d-none');
    });
    
    // تهيئة الأحداث
    function initEvents() {
        // تقرير المبيعات
        $('#generate-sales-report').click(function() {
            generateSalesReport();
        });
        
        // تقرير الموظفين
        $('#generate-users-report').click(function() {
            generateUsersReport();
        });
        
        // تقرير الطلبات
        $('#generate-orders-report').click(function() {
            generateOrdersReport();
        });
        
        // تقرير الصفحات
        $('#generate-pages-report').click(function() {
            generatePagesReport();
        });
        
        // تقرير الزبائن
        $('#generate-customers-report').click(function() {
            generateCustomersReport();
        });
    }
    
    // إنشاء تقرير المبيعات
    function generateSalesReport() {
        currentReport = 'sales';
        
        var container = $('#sales-report-container');
        var loadingElement = container.find('.report-loading');
        var contentElement = container.find('.report-content');
        
        // إظهار التحميل وإخفاء المحتوى
        contentElement.addClass('d-none');
        loadingElement.removeClass('d-none');
        
        // جمع معايير التقرير
        var params = {
            date_from: $('#sales-date-from').val(),
            date_to: $('#sales-date-to').val(),
            user_id: $('#sales-user').val(),
            page_id: $('#sales-page').val(),
            governorate: $('#sales-governorate').val()
        };
        
        // طلب البيانات
        $.ajax({
            url: '/reports/salesReport',
            type: 'POST',
            data: params,
            dataType: 'json',
            success: function(response) {
                loadingElement.addClass('d-none');
                
                if (response.success) {
                    reportData = response.data;
                    
                    // تعبئة بيانات الملخص
                    $('#total-sales').text(reportData.summary.total_sales);
                    $('#total-invoices').text(reportData.summary.total_invoices);
                    $('#avg-invoice-value').text(reportData.summary.avg_invoice_value);
                    $('#avg-daily-sales').text(reportData.summary.avg_daily_sales);
                    
                    // إنشاء الرسوم البيانية
                    createSalesCharts(reportData);
                    
                    // تعبئة الجداول
                    populateSalesTables(reportData);
                    
                    // تمكين أزرار التصدير
                    $('#export-pdf-btn, #export-excel-btn').prop('disabled', false);
                    
                    // إظهار المحتوى
                    contentElement.removeClass('d-none');
                } else {
                    showAlert('danger', response.error || 'حدث خطأ أثناء إنشاء التقرير');
                }
            },
            error: function() {
                loadingElement.addClass('d-none');
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    }
    
    // إنشاء تقرير الموظفين
    function generateUsersReport() {
        currentReport = 'users';
        
        var container = $('#users-report-container');
        var loadingElement = container.find('.report-loading');
        var contentElement = container.find('.report-content');
        
        // إظهار التحميل وإخفاء المحتوى
        contentElement.addClass('d-none');
        loadingElement.removeClass('d-none');
        
        // جمع معايير التقرير
        var params = {
            date_from: $('#users-date-from').val(),
            date_to: $('#users-date-to').val(),
            user_id: $('#users-selected').val()
        };
        
        // طلب البيانات
        $.ajax({
            url: '/reports/userPerformanceReport',
            type: 'POST',
            data: params,
            dataType: 'json',
            success: function(response) {
                loadingElement.addClass('d-none');
                
                if (response.success) {
                    reportData = response.data;
                    
                    // تعبئة بيانات الملخص
                    $('#total-users').text(reportData.summary.total_users);
                    $('#total-user-invoices').text(reportData.summary.total_invoices);
                    $('#avg-invoices-per-user').text(reportData.summary.avg_invoices_per_user);
                    
                    // إنشاء الرسوم البيانية
                    createUsersCharts(reportData);
                    
                    // تعبئة الجداول
                    populateUsersTables(reportData);
                    
                    // تمكين أزرار التصدير
                    $('#export-pdf-btn, #export-excel-btn').prop('disabled', false);
                    
                    // إظهار المحتوى
                    contentElement.removeClass('d-none');
                } else {
                    showAlert('danger', response.error || 'حدث خطأ أثناء إنشاء التقرير');
                }
            },
            error: function() {
                loadingElement.addClass('d-none');
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    }
    
    // إنشاء تقرير الطلبات
    function generateOrdersReport() {
        currentReport = 'orders';
        
        var container = $('#orders-report-container');
        var loadingElement = container.find('.report-loading');
        var contentElement = container.find('.report-content');
        
        // إظهار التحميل وإخفاء المحتوى
        contentElement.addClass('d-none');
        loadingElement.removeClass('d-none');
        
        // جمع معايير التقرير
        var params = {
            date_from: $('#orders-date-from').val(),
            date_to: $('#orders-date-to').val(),
            page_id: $('#orders-page').val()
        };
        
        // طلب البيانات
        $.ajax({
            url: '/reports/orderStatusReport',
            type: 'POST',
            data: params,
            dataType: 'json',
            success: function(response) {
                loadingElement.addClass('d-none');
                
                if (response.success) {
                    reportData = response.data;
                    
                    // تعبئة بيانات الملخص
                    $('#total-orders-count').text(reportData.summary.total_invoices);
                    $('#completed-orders').text(reportData.summary.completed_orders);
                    $('#completion-rate').text(reportData.summary.completion_rate + '%');
                    $('#cancelled-orders').text(reportData.summary.cancelled_orders);
                    
                    // إنشاء الرسوم البيانية
                    createOrdersCharts(reportData);
                    
                    // تمكين أزرار التصدير
                    $('#export-pdf-btn, #export-excel-btn').prop('disabled', false);
                    
                    // إظهار المحتوى
                    contentElement.removeClass('d-none');
                } else {
                    showAlert('danger', response.error || 'حدث خطأ أثناء إنشاء التقرير');
                }
            },
            error: function() {
                loadingElement.addClass('d-none');
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    }
    
    // إنشاء تقرير الصفحات
    function generatePagesReport() {
        currentReport = 'pages';
        
        var container = $('#pages-report-container');
        var loadingElement = container.find('.report-loading');
        var contentElement = container.find('.report-content');
        
        // إظهار التحميل وإخفاء المحتوى
        contentElement.addClass('d-none');
        loadingElement.removeClass('d-none');
        
        // جمع معايير التقرير
        var params = {
            date_from: $('#pages-date-from').val(),
            date_to: $('#pages-date-to').val(),
            page_id: $('#pages-selected').val()
        };
        
        // طلب البيانات
        $.ajax({
            url: '/reports/pagesReport',
            type: 'POST',
            data: params,
            dataType: 'json',
            success: function(response) {
                loadingElement.addClass('d-none');
                
                if (response.success) {
                    reportData = response.data;
                    
                    // تعبئة بيانات الملخص
                    $('#total-pages').text(reportData.summary.total_pages);
                    $('#total-pages-sales').text(reportData.summary.total_sales);
                    $('#total-pages-invoices').text(reportData.summary.total_invoices);
                    $('#avg-sales-per-page').text(reportData.summary.avg_sales_per_page);
                    
                    // إنشاء الرسوم البيانية
                    createPagesCharts(reportData);
                    
                    // تعبئة الجداول
                    populatePagesTables(reportData);
                    
                    // تمكين أزرار التصدير
                    $('#export-pdf-btn, #export-excel-btn').prop('disabled', false);
                    
                    // إظهار المحتوى
                    contentElement.removeClass('d-none');
                } else {
                    showAlert('danger', response.error || 'حدث خطأ أثناء إنشاء التقرير');
                }
            },
            error: function() {
                loadingElement.addClass('d-none');
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    }
    
    // إنشاء تقرير الزبائن
    function generateCustomersReport() {
        currentReport = 'customers';
        
        var container = $('#customers-report-container');
        var loadingElement = container.find('.report-loading');
        var contentElement = container.find('.report-content');
        
        // إظهار التحميل وإخفاء المحتوى
        contentElement.addClass('d-none');
        loadingElement.removeClass('d-none');
        
        // جمع معايير التقرير
        var params = {
            date_from: $('#customers-date-from').val(),
            date_to: $('#customers-date-to').val(),
            governorate: $('#customers-governorate').val(),
            min_orders: $('#customers-min-orders').val()
        };
        
        // طلب البيانات
        $.ajax({
            url: '/reports/customersReport',
            type: 'POST',
            data: params,
            dataType: 'json',
            success: function(response) {
                loadingElement.addClass('d-none');
                
                if (response.success) {
                    reportData = response.data;
                    
                    // تعبئة بيانات الملخص
                    $('#total-customers').text(reportData.summary.total_customers);
                    $('#new-customers').text(reportData.summary.new_customers);
                    $('#returning-customers').text(reportData.summary.returning_customers);
                    $('#returning-rate').text(reportData.summary.returning_rate + '%');
                    
                    // إنشاء الرسوم البيانية
                    createCustomersCharts(reportData);
                    
                    // تعبئة الجداول
                    populateCustomersTables(reportData);
                    
                    // تمكين أزرار التصدير
                    $('#export-pdf-btn, #export-excel-btn').prop('disabled', false);
                    
                    // إظهار المحتوى
                    contentElement.removeClass('d-none');
                } else {
                    showAlert('danger', response.error || 'حدث خطأ أثناء إنشاء التقرير');
                }
            },
            error: function() {
                loadingElement.addClass('d-none');
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    }
    
    // إنشاء رسوم تقرير المبيعات
    function createSalesCharts(data) {
        // الرسم البياني للمبيعات اليومية
        if (window.dailySalesChart) {
            window.dailySalesChart.destroy();
        }
        window.dailySalesChart = window.chartUtils.setupDailySalesChart('daily-sales-chart', data.chart_data.daily);
        
        // الرسم البياني للمبيعات حسب المحافظة
        if (window.governorateSalesChart) {
            window.governorateSalesChart.destroy();
        }
        var governorateLabels = data.chart_data.by_governorate.map(item => item.governorate);
        var governorateData = data.chart_data.by_governorate.map(item => item.total);
        var governorateChartData = window.chartUtils.preparePieChartData(governorateLabels, governorateData);
        window.governorateSalesChart = window.chartUtils.createPieChart('governorate-sales-chart', governorateChartData, {
            title: 'المبيعات حسب المحافظة'
        });
        
        // الرسم البياني للمبيعات حسب الصفحة
        if (window.pageSalesChart) {
            window.pageSalesChart.destroy();
        }
        var pageLabels = data.chart_data.by_page.map(item => item.page_name);
        var pageData = data.chart_data.by_page.map(item => item.total);
        var pageChartData = window.chartUtils.prepareChartData(pageLabels, [{
            label: 'المبيعات',
            data: pageData
        }]);
        window.pageSalesChart = window.chartUtils.createBarChart('page-sales-chart', pageChartData, {
            title: 'المبيعات حسب الصفحة'
        });
        
        // الرسم البياني للمبيعات حسب الموظف
        if (window.userSalesChart) {
            window.userSalesChart.destroy();
        }
        var userLabels = data.chart_data.by_user.map(item => item.username);
        var userData = data.chart_data.by_user.map(item => item.total);
        var userChartData = window.chartUtils.prepareChartData(userLabels, [{
            label: 'المبيعات',
            data: userData
        }]);
        window.userSalesChart = window.chartUtils.createBarChart('user-sales-chart', userChartData, {
            title: 'المبيعات حسب الموظف'
        });
    }
function populateSalesTables(data) {
        // جدول أعلى الزبائن
        var topCustomersTable = $('#top-customers-table');
        topCustomersTable.empty();
        
        if (data.top_items.customers.length === 0) {
            topCustomersTable.append('<tr><td colspan="4" class="text-center">لا توجد بيانات</td></tr>');
        } else {
            data.top_items.customers.forEach(function(customer) {
                topCustomersTable.append(
                    '<tr>' +
                    '<td>' + customer.customer_name + '</td>' +
                    '<td>' + customer.customer_phone + '</td>' +
                    '<td>' + customer.order_count + '</td>' +
                    '<td>' + customer.total_amount + '</td>' +
                    '</tr>'
                );
            });
        }
        
        // جدول أعلى الفواتير
        var topInvoicesTable = $('#top-invoices-table');
        topInvoicesTable.empty();
        
        if (data.top_items.invoices.length === 0) {
            topInvoicesTable.append('<tr><td colspan="5" class="text-center">لا توجد بيانات</td></tr>');
        } else {
            data.top_items.invoices.forEach(function(invoice) {
                topInvoicesTable.append(
                    '<tr>' +
                    '<td><a href="/invoice/view/' + invoice.id + '">' + (invoice.invoice_number || invoice.id) + '</a></td>' +
                    '<td>' + invoice.customer_name + '</td>' +
                    '<td>' + (invoice.governorate || 'غير محدد') + '</td>' +
                    '<td>' + invoice.total_price + '</td>' +
                    '<td>' + formatDate(invoice.created_at) + '</td>' +
                    '</tr>'
                );
            });
        }
    }
    
    // إنشاء رسوم تقرير الموظفين
    function createUsersCharts(data) {
        // الرسم البياني لأداء الموظفين
        if (window.userPerformanceChart) {
            window.userPerformanceChart.destroy();
        }
        window.userPerformanceChart = window.chartUtils.setupUserPerformanceChart('user-performance-chart', data.chart_data.user_performance);
        
        // الرسم البياني للفواتير حسب الدور
        if (window.roleInvoicesChart) {
            window.roleInvoicesChart.destroy();
        }
        var roleLabels = data.chart_data.by_role.map(item => item.role);
        var roleData = data.chart_data.by_role.map(item => item.count);
        var roleChartData = window.chartUtils.preparePieChartData(roleLabels, roleData);
        window.roleInvoicesChart = window.chartUtils.createDoughnutChart('role-invoices-chart', roleChartData, {
            title: 'الفواتير حسب الدور'
        });
    }
    
    // تعبئة جداول تقرير الموظفين
    function populateUsersTables(data) {
        // جدول أفضل الموظفين أداءً
        var topPerformersTable = $('#top-performers-table');
        topPerformersTable.empty();
        
        if (data.users.top_performers.length === 0) {
            topPerformersTable.append('<tr><td colspan="4" class="text-center">لا توجد بيانات</td></tr>');
        } else {
            data.users.top_performers.forEach(function(user) {
                topPerformersTable.append(
                    '<tr>' +
                    '<td>' + user.username + '</td>' +
                    '<td>' + user.full_name + '</td>' +
                    '<td>' + user.invoice_count + '</td>' +
                    '<td>' + user.total_sales + '</td>' +
                    '</tr>'
                );
            });
        }
        
        // جدول نشاط الموظفين
        var userActivityTable = $('#user-activity-table');
        userActivityTable.empty();
        
        if (data.users.activity.length === 0) {
            userActivityTable.append('<tr><td colspan="4" class="text-center">لا توجد بيانات</td></tr>');
        } else {
            data.users.activity.forEach(function(user) {
                userActivityTable.append(
                    '<tr>' +
                    '<td>' + user.username + '</td>' +
                    '<td>' + formatDate(user.last_login) + '</td>' +
                    '<td>' + user.today_invoices + '</td>' +
                    '<td>' + user.activity_level + '</td>' +
                    '</tr>'
                );
            });
        }
    }
    
    // إنشاء رسوم تقرير الطلبات
    function createOrdersCharts(data) {
        // الرسم البياني للطلبات حسب الحالة
        if (window.statusChart) {
            window.statusChart.destroy();
        }
        var statusLabels = data.chart_data.by_status.map(item => item.status_name);
        var statusData = data.chart_data.by_status.map(item => item.count);
        var statusChartData = window.chartUtils.preparePieChartData(statusLabels, statusData);
        window.statusChart = window.chartUtils.createDoughnutChart('status-chart', statusChartData, {
            title: 'الطلبات حسب الحالة'
        });
        
        // الرسم البياني لوقت معالجة الطلبات
        if (window.processingTimeChart) {
            window.processingTimeChart.destroy();
        }
        var timeLabels = data.chart_data.avg_time_by_status.map(item => item.status_name);
        var timeData = data.chart_data.avg_time_by_status.map(item => item.avg_hours);
        var timeChartData = window.chartUtils.prepareChartData(timeLabels, [{
            label: 'متوسط الوقت (بالساعات)',
            data: timeData
        }]);
        window.processingTimeChart = window.chartUtils.createBarChart('processing-time-chart', timeChartData, {
            title: 'وقت معالجة الطلبات'
        });
        
        // خط زمني لحالات الطلبات
        var timelineContainer = document.getElementById('status-timeline-chart');
        timelineContainer.innerHTML = '';
        
        data.chart_data.status_timeline.forEach(function(item, index) {
            var timelineItem = document.createElement('div');
            timelineItem.className = 'timeline-item';
            
            var timelineIcon = document.createElement('div');
            timelineIcon.className = 'timeline-icon';
            timelineIcon.innerHTML = '<i class="fas fa-circle"></i>';
            
            var timelineContent = document.createElement('div');
            timelineContent.className = 'timeline-content';
            
            var timelineTitle = document.createElement('h4');
            timelineTitle.textContent = item.status_name;
            
            var timelineDate = document.createElement('p');
            timelineDate.className = 'timeline-date';
            timelineDate.textContent = formatDate(item.date);
            
            var timelineDetails = document.createElement('p');
            timelineDetails.textContent = item.count + ' طلب';
            
            timelineContent.appendChild(timelineTitle);
            timelineContent.appendChild(timelineDate);
            timelineContent.appendChild(timelineDetails);
            
            timelineItem.appendChild(timelineIcon);
            timelineItem.appendChild(timelineContent);
            
            timelineContainer.appendChild(timelineItem);
        });
    }
    
    // إنشاء رسوم تقرير الصفحات
    function createPagesCharts(data) {
        // الرسم البياني للمبيعات حسب الصفحة
        if (window.salesByPageChart) {
            window.salesByPageChart.destroy();
        }
        var pageLabels = data.chart_data.by_page.map(item => item.page_name);
        var pageSalesData = data.chart_data.by_page.map(item => item.total);
        var pageInvoicesData = data.chart_data.by_page.map(item => item.count);
        var pageChartData = window.chartUtils.prepareChartData(pageLabels, [
            {
                label: 'المبيعات',
                data: pageSalesData
            },
            {
                label: 'عدد الفواتير',
                data: pageInvoicesData
            }
        ]);
        window.salesByPageChart = window.chartUtils.createBarChart('sales-by-page-chart', pageChartData, {
            title: 'المبيعات حسب الصفحة'
        });
        
        // الرسم البياني لنمو الصفحات
        if (window.pageGrowthChart) {
            window.pageGrowthChart.destroy();
        }
        var growthLabels = data.chart_data.page_growth.map(item => item.month);
        var growthData = data.chart_data.page_growth.map(item => item.count);
        var growthChartData = window.chartUtils.prepareChartData(growthLabels, [{
            label: 'عدد الصفحات الجديدة',
            data: growthData
        }]);
        window.pageGrowthChart = window.chartUtils.createLineChart('page-growth-chart', growthChartData, {
            title: 'نمو الصفحات'
        });
    }
    
    // تعبئة جداول تقرير الصفحات
    function populatePagesTables(data) {
        // جدول أفضل الصفحات أداءً
        var topPagesTable = $('#top-pages-table');
        topPagesTable.empty();
        
        if (data.pages.top_performers.length === 0) {
            topPagesTable.append('<tr><td colspan="4" class="text-center">لا توجد بيانات</td></tr>');
        } else {
            data.pages.top_performers.forEach(function(page) {
                topPagesTable.append(
                    '<tr>' +
                    '<td>' + page.page_name + '</td>' +
                    '<td>' + page.invoice_count + '</td>' +
                    '<td>' + page.total_sales + '</td>' +
                    '<td>' + page.conversion_rate + '%</td>' +
                    '</tr>'
                );
            });
        }
        
        // جدول تفاصيل الصفحات
        var pageDetailsTable = $('#page-details-table');
        pageDetailsTable.empty();
        
        if (data.pages.page_details.length === 0) {
            pageDetailsTable.append('<tr><td colspan="4" class="text-center">لا توجد بيانات</td></tr>');
        } else {
            data.pages.page_details.forEach(function(page) {
                pageDetailsTable.append(
                    '<tr>' +
                    '<td>' + page.page_name + '</td>' +
                    '<td>' + formatDate(page.created_at) + '</td>' +
                    '<td>' + page.product_count + '</td>' +
                    '<td>' + formatDate(page.updated_at) + '</td>' +
                    '</tr>'
                );
            });
        }
    }
    
    // إنشاء رسوم تقرير الزبائن
    function createCustomersCharts(data) {
        // الرسم البياني للزبائن حسب المحافظة
        if (window.customersByGovernorateChart) {
            window.customersByGovernorateChart.destroy();
        }
        var govLabels = data.chart_data.by_governorate.map(item => item.governorate);
        var govData = data.chart_data.by_governorate.map(item => item.count);
        var govChartData = window.chartUtils.preparePieChartData(govLabels, govData);
        window.customersByGovernorateChart = window.chartUtils.createPieChart('customers-by-governorate-chart', govChartData, {
            title: 'الزبائن حسب المحافظة'
        });
        
        // الرسم البياني للزبائن حسب التصنيف
        if (window.customersByRatingChart) {
            window.customersByRatingChart.destroy();
        }
        var ratingLabels = data.chart_data.by_rating.map(function(item) {
            var ratings = ['ضعيف', 'أقل من المتوسط', 'متوسط', 'جيد', 'ممتاز'];
            return ratings[item.rating - 1] || 'غير مصنف';
        });
        var ratingData = data.chart_data.by_rating.map(item => item.count);
        var ratingChartData = window.chartUtils.preparePieChartData(ratingLabels, ratingData);
        window.customersByRatingChart = window.chartUtils.createDoughnutChart('customers-by-rating-chart', ratingChartData, {
            title: 'الزبائن حسب التصنيف'
        });
        
        // الرسم البياني لنمو قاعدة الزبائن
        if (window.customerGrowthChart) {
            window.customerGrowthChart.destroy();
        }
        var growthLabels = data.chart_data.customer_growth.map(item => item.month);
        var growthData = data.chart_data.customer_growth.map(item => item.count);
        var growthChartData = window.chartUtils.prepareChartData(growthLabels, [{
            label: 'عدد الزبائن الجدد',
            data: growthData
        }]);
        window.customerGrowthChart = window.chartUtils.createLineChart('customer-growth-chart', growthChartData, {
            title: 'نمو قاعدة الزبائن'
        });
    }
    
    // تعبئة جداول تقرير الزبائن
    function populateCustomersTables(data) {
        // جدول أعلى الزبائن إنفاقاً
        var topSpendersTable = $('#top-spenders-table');
        topSpendersTable.empty();
        
        if (data.customers.top_spenders.length === 0) {
            topSpendersTable.append('<tr><td colspan="4" class="text-center">لا توجد بيانات</td></tr>');
        } else {
            data.customers.top_spenders.forEach(function(customer) {
                topSpendersTable.append(
                    '<tr>' +
                    '<td>' + customer.customer_name + '</td>' +
                    '<td>' + customer.customer_phone + '</td>' +
                    '<td>' + (customer.governorate || 'غير محدد') + '</td>' +
                    '<td>' + customer.total_spending + '</td>' +
                    '</tr>'
                );
            });
        }
        
        // جدول الزبائن الأكثر نشاطاً
        var mostActiveTable = $('#most-active-customers-table');
        mostActiveTable.empty();
        
        if (data.customers.most_frequent.length === 0) {
            mostActiveTable.append('<tr><td colspan="4" class="text-center">لا توجد بيانات</td></tr>');
        } else {
            data.customers.most_frequent.forEach(function(customer) {
                mostActiveTable.append(
                    '<tr>' +
                    '<td>' + customer.customer_name + '</td>' +
                    '<td>' + customer.customer_phone + '</td>' +
                    '<td>' + customer.order_count + '</td>' +
                    '<td>' + formatDate(customer.last_order_date) + '</td>' +
                    '</tr>'
                );
            });
        }
    }
    
    // تصدير التقرير
    function exportReport(format) {
        if (!currentReport || !reportData) return;
        
        var formData = new FormData();
        formData.append('report_type', currentReport);
        formData.append('report_data', JSON.stringify(reportData));
        
        // إضافة معايير التصفية للتقرير
        switch (currentReport) {
            case 'sales':
                formData.append('date_from', $('#sales-date-from').val());
                formData.append('date_to', $('#sales-date-to').val());
                break;
                
            case 'users':
                formData.append('date_from', $('#users-date-from').val());
                formData.append('date_to', $('#users-date-to').val());
                break;
                
            case 'orders':
                formData.append('date_from', $('#orders-date-from').val());
                formData.append('date_to', $('#orders-date-to').val());
                break;
                
            case 'pages':
                formData.append('date_from', $('#pages-date-from').val());
                formData.append('date_to', $('#pages-date-to').val());
                break;
                
            case 'customers':
                formData.append('date_from', $('#customers-date-from').val());
                formData.append('date_to', $('#customers-date-to').val());
                break;
        }
        
        // تحديد عملية التصدير
        var exportUrl = format === 'pdf' ? '/reports/exportPdf' : '/reports/exportExcel';
        
        // إنشاء نموذج مخفي وإرساله
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = exportUrl;
        form.style.display = 'none';
        
        // إضافة حقول النموذج
        for (var pair of formData.entries()) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = pair[0];
            input.value = pair[1];
            form.appendChild(input);
        }
        
        // إضافة النموذج للصفحة وإرساله
        document.body.appendChild(form);
        form.submit();
        
        // حذف النموذج بعد الإرسال
        setTimeout(function() {
            document.body.removeChild(form);
        }, 100);
    }
    
    // تنسيق التاريخ
    function formatDate(dateString) {
        if (!dateString) return 'غير محدد';
        
        var date = new Date(dateString);
        return date.toLocaleDateString('ar-IQ');
    }
    
    // عرض رسالة تنبيه
    function showAlert(type, message) {
        var alertElement = $('<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                            message +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                            '<span aria-hidden="true">&times;</span>' +
                            '</button>' +
                            '</div>');
        
        $('.content-header').after(alertElement);
        
        // إخفاء التنبيه تلقائيًا بعد 5 ثوانٍ
        setTimeout(function() {
            alertElement.alert('close');
        }, 5000);
    }
});
</script>

<?php include_once(ROOT_PATH . '/views/layout/footer.php'); ?>