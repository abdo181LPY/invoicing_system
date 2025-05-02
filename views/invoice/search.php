<?php 
/**
 * صفحة البحث عن الفواتير
 * تتيح للمستخدمين البحث عن الفواتير وعرضها وحذفها
 */
$pageTitle = 'البحث عن الفواتير';
include 'views/layout/header.php';
include 'views/layout/sidebar.php';
?>

<!-- عنوان الصفحة -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-search me-2"></i>البحث عن الفواتير</h1>
</div>

<!-- بطاقة البحث -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>معايير البحث</h5>
    </div>
    <div class="card-body">
        <form id="searchForm" action="invoice_search_results.php" method="get">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="keyword" class="form-label">بحث عام</label>
                    <input type="text" class="form-control" id="keyword" name="keyword" placeholder="رقم الفاتورة، اسم الزبون، رقم الهاتف..." value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="invoice_id" class="form-label">رقم الفاتورة</label>
                    <input type="number" class="form-control" id="invoice_id" name="invoice_id" placeholder="رقم الفاتورة" value="<?php echo isset($_GET['invoice_id']) ? htmlspecialchars($_GET['invoice_id']) : ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="customer_name" class="form-label">اسم الزبون</label>
                    <input type="text" class="form-control" id="customer_name" name="customer_name" placeholder="اسم الزبون" value="<?php echo isset($_GET['customer_name']) ? htmlspecialchars($_GET['customer_name']) : ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="phone" class="form-label">رقم الهاتف</label>
                    <input type="text" class="form-control" id="phone" name="phone" placeholder="رقم الهاتف" value="<?php echo isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : ''; ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="province" class="form-label">المحافظة</label>
                    <select class="form-select" id="province" name="province">
                        <option value="">جميع المحافظات</option>
                        <?php foreach ($GLOBALS['PROVINCES'] as $province): ?>
                        <option value="<?php echo $province; ?>" <?php echo (isset($_GET['province']) && $_GET['province'] == $province) ? 'selected' : ''; ?>>
                            <?php echo $province; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="status" class="form-label">حالة الطلب</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">جميع الحالات</option>
                        <?php foreach ($GLOBALS['ORDER_STATUSES'] as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo (isset($_GET['status']) && $_GET['status'] == $status) ? 'selected' : ''; ?>>
                            <?php echo $status; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="page_id" class="form-label">الصفحة</label>
                    <select class="form-select" id="page_id" name="page_id">
                        <option value="">جميع الصفحات</option>
                        <?php 
                        $page = new Page();
                        $pages = $page->getDropdownList();
                        foreach ($pages as $p): 
                        ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo (isset($_GET['page_id']) && $_GET['page_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="cart_number" class="form-label">رقم السلة</label>
                    <input type="text" class="form-control" id="cart_number" name="cart_number" placeholder="رقم السلة" value="<?php echo isset($_GET['cart_number']) ? htmlspecialchars($_GET['cart_number']) : ''; ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="date_from" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="date_to" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="created_by" class="form-label">أنشئت بواسطة</label>
                    <select class="form-select" id="created_by" name="created_by">
                        <option value="">الجميع</option>
                        <option value="current" <?php echo (isset($_GET['created_by']) && $_GET['created_by'] == 'current') ? 'selected' : ''; ?>>فواتيري فقط</option>
                        <?php if ($auth->hasPermission(3)): ?>
                        <?php 
                        $user = new User();
                        $users = $user->getAll();
                        foreach ($users as $u): 
                        ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo (isset($_GET['created_by']) && $_GET['created_by'] == $u['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['username']); ?>
                        </option>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="limit" class="form-label">عدد النتائج</label>
                    <select class="form-select" id="limit" name="limit">
                        <option value="20" <?php echo (!isset($_GET['limit']) || $_GET['limit'] == '20') ? 'selected' : ''; ?>>20</option>
                        <option value="50" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '50') ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo (isset($_GET['limit']) && $_GET['limit'] == '100') ? 'selected' : ''; ?>>100</option>
                        <option value="all" <?php echo (isset($_GET['limit']) && $_GET['limit'] == 'all') ? 'selected' : ''; ?>>الكل</option>
                    </select>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>بحث
                </button>
                <button type="button" class="btn btn-secondary ms-2" id="resetSearch">
                    <i class="fas fa-redo me-2"></i>إعادة تعيين
                </button>
                
                <?php if ($auth->hasPermission(3)): ?>
                <button type="button" class="btn btn-success ms-2" id="exportResults">
                    <i class="fas fa-file-export me-2"></i>تصدير النتائج
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- نتائج البحث (تظهر بعد النقر على "بحث") -->
<div id="searchResults" class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>نتائج البحث</h5>
            <span id="resultCount" class="badge bg-light text-dark">0 نتيجة</span>
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
                        <th>رقم السلة</th>
                        <th>الحالة</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="resultsTable">
                    <tr>
                        <td colspan="9" class="text-center py-4">يرجى استخدام نموذج البحث أعلاه للبحث عن الفواتير</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0" id="pagination">
                <!-- أزرار الصفحات ستضاف هنا بواسطة JavaScript -->
            </ul>
        </nav>
    </div>
</div>

<!-- البحث السريع عن رقم فاتورة -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>بحث سريع عن فاتورة</h5>
    </div>
    <div class="card-body">
        <form id="quickSearchForm" action="invoice_view.php" method="get" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                    <input type="number" class="form-control" id="quick_invoice_id" name="id" placeholder="أدخل رقم الفاتورة للانتقال إليها مباشرة">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="text" class="form-control" id="quick_phone" placeholder="بحث بواسطة رقم الهاتف">
                    <button type="button" class="btn btn-secondary" id="phoneSearchBtn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="form-text">أدخل رقم الهاتف للبحث عن جميع فواتير هذا الزبون</div>
            </div>
        </form>
    </div>
</div>

<!-- سكريبت البحث والفلترة -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // إعادة تعيين نموذج البحث
    document.getElementById('resetSearch').addEventListener('click', function() {
        document.getElementById('searchForm').reset();
    });
    
    // البحث بواسطة رقم الهاتف
    document.getElementById('phoneSearchBtn').addEventListener('click', function() {
        const phone = document.getElementById('quick_phone').value.trim();
        if (phone) {
            document.getElementById('keyword').value = '';
            document.getElementById('phone').value = phone;
            document.getElementById('searchForm').submit();
        }
    });
    
    // تفعيل البحث المباشر (إذا كانت هناك معايير بحث)
    if (window.location.search.includes('keyword=') || window.location.search.includes('phone=') || window.location.search.includes('invoice_id=')) {
        // إرسال طلب أجاكس للبحث
        // هنا يمكن إضافة كود أجاكس للبحث الفعلي، ولكن في هذا المثال سنفترض أن النموذج يرسل إلى صفحة منفصلة
    }
    
    // تصدير النتائج
    document.getElementById('exportResults')?.addEventListener('click', function() {
        // إنشاء نموذج مؤقت للتصدير
        const exportForm = document.createElement('form');
        exportForm.method = 'post';
        exportForm.action = 'export_invoices.php';
        
        // نسخ معايير البحث
        const formData = new FormData(document.getElementById('searchForm'));
        for (const [key, value] of formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            exportForm.appendChild(input);
        }
        
        // إضافة حقل للإشارة إلى التصدير
        const exportInput = document.createElement('input');
        exportInput.type = 'hidden';
        exportInput.name = 'export';
        exportInput.value = '1';
        exportForm.appendChild(exportInput);
        
        // إرسال النموذج
        document.body.appendChild(exportForm);
        exportForm.submit();
        document.body.removeChild(exportForm);
    });
});
</script>

<?php include 'views/layout/footer.php'; ?>
