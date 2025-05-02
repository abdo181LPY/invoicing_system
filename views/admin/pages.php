<?php 
/**
 * صفحة إدارة الصفحات
 * تتيح للإدارة إضافة وتعديل وحذف الصفحات التي يتم التعامل معها
 */
$pageTitle = 'إدارة الصفحات';
include 'views/layout/header.php';
include 'views/layout/sidebar.php';
?>

<!-- عنوان الصفحة -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-store me-2"></i>إدارة الصفحات</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="page_create.php" class="btn btn-sm btn-primary">
            <i class="fas fa-plus-circle me-1"></i>
            إضافة صفحة جديدة
        </a>
    </div>
</div>

<!-- البحث -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form action="pages.php" method="get" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="keyword" placeholder="ابحث بالاسم، الهاتف، أو الإنستغرام..." value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </div>
            </div>
            
            <div class="col-md-6 text-md-end">
                <span class="text-muted">إجمالي الصفحات: <?php echo count($pages); ?></span>
            </div>
        </form>
    </div>
</div>

<!-- قائمة الصفحات -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>اسم الصفحة</th>
                        <th>رابط الإنستغرام</th>
                        <th>رقم الهاتف</th>
                        <th>الهاتف الثانوي</th>
                        <th>عدد الفواتير</th>
                        <th>الحالة</th>
                        <th>تاريخ الإضافة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pages)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">لا توجد صفحات لعرضها</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><?php echo $page['id']; ?></td>
                            <td><?php echo htmlspecialchars($page['name']); ?></td>
                            <td>
                                <?php if (!empty($page['instagram'])): ?>
                                <a href="<?php echo htmlspecialchars($page['instagram']); ?>" target="_blank" title="فتح الرابط">
                                    <i class="fab fa-instagram"></i> فتح الرابط
                                </a>
                                <?php else: ?>
                                <span class="text-muted">غير متوفر</span>
                                <?php endif; ?>
                            </td>
                            <td dir="ltr"><?php echo htmlspecialchars($page['phone']); ?></td>
                            <td dir="ltr"><?php echo !empty($page['alternate_phone']) ? htmlspecialchars($page['alternate_phone']) : '<span class="text-muted">-</span>'; ?></td>
                            <td><?php echo $page['invoice_count'] ?? 0; ?></td>
                            <td>
                                <?php if ($page['is_active'] == 1): ?>
                                <span class="badge bg-success">نشطة</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">غير نشطة</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($page['created_at'])); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="page_view.php?id=<?php echo $page['id']; ?>" class="btn btn-info" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="page_edit.php?id=<?php echo $page['id']; ?>" class="btn btn-warning" title="تعديل الصفحة">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="post" action="update_page_status.php" class="d-inline" id="statusForm<?php echo $page['id']; ?>">
                                        <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $page['is_active'] ? 0 : 1; ?>">
                                        <button type="button" class="btn <?php echo $page['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                                            onclick="confirmAction(this, function() { document.getElementById('statusForm<?php echo $page['id']; ?>').submit(); })"
                                            data-confirm-message="هل أنت متأكد من تغيير حالة الصفحة؟"
                                            data-confirm-button="<?php echo $page['is_active'] ? 'تعطيل' : 'تفعيل'; ?>"
                                            data-confirm-title="تغيير حالة الصفحة"
                                            data-confirm-button-class="<?php echo $page['is_active'] ? 'btn-secondary' : 'btn-success'; ?>"
                                            title="<?php echo $page['is_active'] ? 'تعطيل الصفحة' : 'تفعيل الصفحة'; ?>">
                                            <i class="fas fa-<?php echo $page['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        </button>
                                    </form>
                                    
                                    <?php if ($page['invoice_count'] == 0): ?>
                                    <button type="button" class="btn btn-danger" title="حذف الصفحة"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#confirmModal" 
                                        data-confirm-href="page_delete.php?id=<?php echo $page['id']; ?>"
                                        data-confirm-message="هل أنت متأكد من حذف الصفحة؟ لا يمكن التراجع عن هذا الإجراء."
                                        data-confirm-button="حذف"
                                        data-confirm-title="حذف الصفحة"
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

<!-- نصائح واقتراحات -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>نصائح واقتراحات</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle me-2"></i>إدارة الصفحات:</h6>
                    <ul class="mb-0">
                        <li>يمكنك إضافة صفحات جديدة للتعامل معها عند تثبيت الطلبات.</li>
                        <li>تعطيل الصفحة سيمنع استخدامها في حاسبة التكلفة وتثبيت الطلبات.</li>
                        <li>لا يمكن حذف الصفحات التي لديها فواتير مرتبطة بها.</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle me-2"></i>ملاحظات مهمة:</h6>
                    <ul class="mb-0">
                        <li>تأكد من صحة روابط الإنستغرام وأرقام الهواتف عند إضافة صفحة جديدة.</li>
                        <li>يتم استخدام بيانات الصفحة في رسائل حاسبة التكلفة للزبائن.</li>
                        <li>قم بتحديث بيانات الصفحات بانتظام للحفاظ على دقة المعلومات.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
