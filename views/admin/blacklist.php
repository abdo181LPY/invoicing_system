<?php 
/**
 * صفحة إدارة القائمة السوداء
 * تتيح للإدارة إضافة وتعديل وحذف الأرقام المحظورة
 */
$pageTitle = 'القائمة السوداء';
include 'views/layout/header.php';
include 'views/layout/sidebar.php';
?>

<!-- عنوان الصفحة -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-ban me-2"></i>القائمة السوداء</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="blacklist_create.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة رقم محظور
            </a>
            <a href="blacklist_import.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-file-import me-1"></i>
                استيراد أرقام
            </a>
            <a href="blacklist_export.php" class="btn btn-sm btn-info">
                <i class="fas fa-file-export me-1"></i>
                تصدير الأرقام
            </a>
        </div>
    </div>
</div>

<!-- تنبيه مهم -->
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>تنبيه:</strong> الأرقام الموجودة في القائمة السوداء سيتطلب منها دفع المبلغ كاملاً مقدماً عند تثبيت الطلب.
</div>

<!-- البحث -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="blacklist.php" method="get" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="keyword" placeholder="ابحث برقم الهاتف أو السبب..." value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </div>
            </div>
            
            <div class="col-md-6 text-md-end">
                <span class="text-muted">إجمالي الأرقام المحظورة: <?php echo count($blacklist); ?></span>
            </div>
        </form>
    </div>
</div>

<!-- بطاقة الفحص السريع -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-search me-2"></i>فحص رقم سريع</h5>
    </div>
    <div class="card-body">
        <form id="checkForm" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                    <input type="text" class="form-control" id="checkPhone" name="phone" placeholder="أدخل رقم الهاتف للفحص..." required>
                    <button class="btn btn-dark" type="submit">
                        <i class="fas fa-search"></i> فحص
                    </button>
                </div>
                <div class="form-text">أدخل رقم هاتف عراقي يبدأ بـ 07 ويتكون من 11 رقم</div>
            </div>
            <div class="col-md-6">
                <div id="checkResult"></div>
            </div>
        </form>
    </div>
</div>

<!-- قائمة الأرقام المحظورة -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>رقم الهاتف</th>
                        <th>سبب الحظر</th>
                        <th>تاريخ الإضافة</th>
                        <th>أضيف بواسطة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($blacklist)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">لا توجد أرقام محظورة لعرضها</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($blacklist as $item): ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td dir="ltr"><?php echo htmlspecialchars($item['phone']); ?></td>
                            <td><?php echo !empty($item['reason']) ? htmlspecialchars($item['reason']) : '<span class="text-muted">بدون سبب</span>'; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($item['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($item['added_by_username'] ?? 'غير معروف'); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="blacklist_edit.php?id=<?php echo $item['id']; ?>" class="btn btn-warning" title="تعديل السبب">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger" title="حذف الرقم المحظور"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#confirmModal" 
                                        data-confirm-href="blacklist_delete.php?id=<?php echo $item['id']; ?>"
                                        data-confirm-message="هل أنت متأكد من حذف هذا الرقم من القائمة السوداء؟"
                                        data-confirm-button="حذف"
                                        data-confirm-title="حذف الرقم المحظور"
                                        data-confirm-button-class="btn-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
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

<!-- أسباب الحظر الشائعة -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2 text-danger"></i>أسباب الحظر الشائعة</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        عدم استلام الطلب
                        <button class="btn btn-sm btn-outline-primary copy-reason" data-reason="عدم استلام الطلب">نسخ</button>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        رفض استلام الطلب بدون سبب
                        <button class="btn btn-sm btn-outline-primary copy-reason" data-reason="رفض استلام الطلب بدون سبب">نسخ</button>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        تغيير العنوان بعد الشحن
                        <button class="btn btn-sm btn-outline-primary copy-reason" data-reason="تغيير العنوان بعد الشحن">نسخ</button>
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        تأخير تحويل العربون
                        <button class="btn btn-sm btn-outline-primary copy-reason" data-reason="تأخير تحويل العربون">نسخ</button>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        إلغاء الطلب بعد الشراء
                        <button class="btn btn-sm btn-outline-primary copy-reason" data-reason="إلغاء الطلب بعد الشراء">نسخ</button>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        معلومات اتصال خاطئة
                        <button class="btn btn-sm btn-outline-primary copy-reason" data-reason="معلومات اتصال خاطئة">نسخ</button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-footer bg-light">
        <small class="text-muted">يمكنك النقر على زر "نسخ" لنسخ السبب واستخدامه عند إضافة رقم جديد للقائمة السوداء.</small>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // نموذج فحص الرقم
    const checkForm = document.getElementById('checkForm');
    const checkResult = document.getElementById('checkResult');
    
    if (checkForm) {
        checkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const phone = document.getElementById('checkPhone').value.trim();
            
            // التحقق من صحة رقم الهاتف
            if (!/^07\d{9}$/.test(phone)) {
                checkResult.innerHTML = '<div class="alert alert-danger">الرجاء إدخال رقم هاتف عراقي صحيح</div>';
                return;
            }
            
            // فحص الرقم
            checkResult.innerHTML = '<div class="alert alert-info">جاري التحقق...</div>';
            
            fetch('blacklist_check.php?phone=' + phone)
                .then(response => response.json())
                .then(data => {
                    if (data.is_blacklisted) {
                        let reason = data.data.reason ? data.data.reason : 'غير محدد';
                        checkResult.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-ban me-2"></i>
                                <strong>الرقم محظور!</strong><br>
                                <small>السبب: ${reason}</small>
                            </div>
                        `;
                    } else {
                        checkResult.innerHTML = `
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>الرقم غير محظور.</strong><br>
                                <small>هذا الرقم غير موجود في القائمة السوداء.</small>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    checkResult.innerHTML = '<div class="alert alert-danger">حدث خطأ أثناء التحقق. يرجى المحاولة مرة أخرى.</div>';
                    console.error('Error:', error);
                });
        });
    }
    
    // أزرار نسخ أسباب الحظر
    const copyButtons = document.querySelectorAll('.copy-reason');
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const reason = this.getAttribute('data-reason');
            
            // نسخ السبب إلى الحافظة
            navigator.clipboard.writeText(reason).then(() => {
                // تغيير نص الزر مؤقتًا
                const originalText = this.textContent;
                this.textContent = 'تم النسخ!';
                
                // إعادة النص الأصلي بعد ثانيتين
                setTimeout(() => {
                    this.textContent = originalText;
                }, 2000);
            });
        });
    });
});
</script>

<?php include 'views/layout/footer.php'; ?>
