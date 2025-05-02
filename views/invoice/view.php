<?php 
/**
 * صفحة عرض تفاصيل الفاتورة
 * تعرض جميع تفاصيل الفاتورة والصور المرفقة
 */
$pageTitle = 'عرض الفاتورة #' . $invoice['id'];
include 'views/layout/header.php';
include 'views/layout/sidebar.php';
?>

<!-- عنوان الصفحة والإجراءات -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-file-invoice me-2"></i>
        فاتورة <span class="text-primary">#<?php echo $invoice['id']; ?></span>
        <?php if (!empty($invoice['cart_number'])): ?>
            <span class="badge bg-success ms-2">رقم السلة: <?php echo $invoice['cart_number']; ?></span>
        <?php endif; ?>
    </h1>
    
    <div class="btn-toolbar mb-2 mb-md-0">
        <!-- زر الطباعة -->
        <a href="invoice_print.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-secondary ms-2" target="_blank">
            <i class="fas fa-print me-1"></i> طباعة
        </a>
        
        <!-- زر التعديل (للمدراء فقط) -->
        <?php if ($auth->hasPermission(2)): ?>
        <a href="invoice_edit.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
            <i class="fas fa-edit me-1"></i> تعديل
        </a>
        <?php endif; ?>
        
        <!-- زر الحذف (حسب الصلاحيات) -->
        <?php if ($canDelete): ?>
        <button type="button" class="btn btn-sm btn-outline-danger ms-2" 
                data-bs-toggle="modal" 
                data-bs-target="#confirmModal" 
                data-confirm-href="invoice_delete.php?id=<?php echo $invoice['id']; ?>"
                data-confirm-message="هل أنت متأكد من حذف الفاتورة رقم <?php echo $invoice['id']; ?>؟"
                data-confirm-button="حذف"
                data-confirm-button-class="btn-danger">
            <i class="fas fa-trash-alt me-1"></i> حذف
        </button>
        <?php endif; ?>
        
        <!-- زر العودة -->
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-right me-1"></i> عودة
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- بيانات الفاتورة -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات الفاتورة</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- بيانات الزبون -->
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">بيانات الزبون</h6>
                        
                        <div class="mb-2">
                            <strong><i class="fas fa-user me-2"></i>اسم الزبون:</strong>
                            <span><?php echo htmlspecialchars($invoice['customer_name']); ?></span>
                        </div>
                        
                        <div class="mb-2">
                            <strong><i class="fas fa-phone me-2"></i>رقم الهاتف:</strong>
                            <span dir="ltr"><?php echo htmlspecialchars($invoice['phone']); ?></span>
                            <a href="tel:<?php echo $invoice['phone']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fas fa-phone-alt"></i>
                            </a>
                            
                            <?php
                            // التحقق من وجود الرقم في البلاك ليست
                            $blacklist = new Blacklist();
                            $isBlacklisted = $blacklist->exists($invoice['phone']);
                            if ($isBlacklisted):
                            ?>
                            <span class="badge bg-danger ms-2" title="هذا الرقم موجود في القائمة السوداء">
                                <i class="fas fa-ban"></i> محظور
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($invoice['phone2'])): ?>
                        <div class="mb-2">
                            <strong><i class="fas fa-phone-alt me-2"></i>رقم الهاتف الثانوي:</strong>
                            <span dir="ltr"><?php echo htmlspecialchars($invoice['phone2']); ?></span>
                            <a href="tel:<?php echo $invoice['phone2']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                <i class="fas fa-phone-alt"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <strong><i class="fas fa-map-marker-alt me-2"></i>المحافظة:</strong>
                            <span><?php echo htmlspecialchars($invoice['province']); ?></span>
                        </div>
                        
                        <div class="mb-2">
                            <strong><i class="fas fa-map-signs me-2"></i>المنطقة:</strong>
                            <span><?php echo htmlspecialchars($invoice['area']); ?></span>
                        </div>
                        
                        <div class="mb-2">
                            <strong><i class="fas fa-location-arrow me-2"></i>العنوان:</strong>
                            <span><?php echo htmlspecialchars($invoice['address']); ?></span>
                        </div>
                    </div>
                    
                    <!-- بيانات الطلب -->
                    <div class="col-md-6">
                        <h6 class="border-bottom pb-2 mb-3">بيانات الطلب</h6>
                        
                        <div class="mb-2">
                            <strong><i class="fas fa-store me-2"></i>الصفحة:</strong>
                            <span><?php echo htmlspecialchars($invoice['page_name'] ?? 'غير محدد'); ?></span>
                        </div>
                        
                        <div class="mb-2">
                            <strong><i class="fab fa-instagram me-2"></i>رابط الإنستغرام:</strong>
                            <?php if (!empty($invoice['instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($invoice['instagram']); ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;"><?php echo htmlspecialchars($invoice['instagram']); ?></a>
                            <?php else: ?>
                            <span class="text-muted">غير متوفر</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-2">
                            <strong><i class="fas fa-shopping-basket me-2"></i>رابط السلة:</strong>
                            <?php if (!empty($invoice['cart_link'])): ?>
                            <a href="<?php echo htmlspecialchars($invoice['cart_link']); ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;"><?php echo htmlspecialchars($invoice['cart_link']); ?></a>
                            <?php else: ?>
                            <span class="text-muted">غير متوفر</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-2">
                            <strong><i class="fas fa-list-ol me-2"></i>عدد القطع:</strong>
                            <span><?php echo $invoice['item_count']; ?></span>
                        </div>
                        
                        <?php if (!empty($invoice['basket_price'])): ?>
                        <div class="mb-2">
                            <strong><i class="fas fa-dollar-sign me-2"></i>سعر السلة:</strong>
                            <span><?php echo number_format($invoice['basket_price'], 2); ?> دولار</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($invoice['total_price'])): ?>
                        <div class="mb-2">
                            <strong><i class="fas fa-money-bill-wave me-2"></i>التكلفة الإجمالية:</strong>
                            <span><?php echo number_format($invoice['total_price']); ?> دينار</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($invoice['delivery_cost'])): ?>
                        <div class="mb-2">
                            <strong><i class="fas fa-truck me-2"></i>تكلفة التوصيل:</strong>
                            <span><?php echo number_format($invoice['delivery_cost']); ?> دينار</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- تفاصيل الطلب -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="border-bottom pb-2 mb-3">تفاصيل إضافية</h6>
                        
                        <?php if (!empty($invoice['repeat_details'])): ?>
                        <div class="mb-3">
                            <strong><i class="fas fa-retweet me-2"></i>تفاصيل التكرارات:</strong>
                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($invoice['repeat_details'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($invoice['custom_details'])): ?>
                        <div class="mb-3">
                            <strong><i class="fas fa-magic me-2"></i>تفاصيل القطع المخصصة:</strong>
                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($invoice['custom_details'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($invoice['notes'])): ?>
                        <div class="mb-3">
                            <strong><i class="fas fa-sticky-note me-2"></i>ملاحظات:</strong>
                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- معلومات العربون -->
                <?php if (!empty($invoice['deposit_amount']) || !empty($invoice['deposit_method'])): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="border-bottom pb-2 mb-3">معلومات العربون</h6>
                        
                        <?php if (!empty($invoice['deposit_amount'])): ?>
                        <div class="mb-2">
                            <strong><i class="fas fa-coins me-2"></i>مبلغ العربون:</strong>
                            <span><?php echo number_format($invoice['deposit_amount']); ?> دينار</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($invoice['deposit_method'])): ?>
                        <div class="mb-2">
                            <strong><i class="fas fa-credit-card me-2"></i>طريقة الدفع:</strong>
                            <span><?php echo htmlspecialchars($invoice['deposit_method']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- الصور المرفقة -->
        <?php if (!empty($repeatImages) || !empty($customImages) || !empty($depositImage)): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-images me-2"></i>الصور المرفقة</h5>
            </div>
            <div class="card-body">
                <!-- صور التكرارات -->
                <?php if (!empty($repeatImages)): ?>
                <h6 class="border-bottom pb-2 mb-3">صور التكرارات</h6>
                <div class="row mb-4">
                    <?php foreach ($repeatImages as $image): ?>
                    <div class="col-md-3 col-sm-4 col-6 mb-3">
                        <a href="#" class="d-block" data-bs-toggle="modal" data-bs-target="#imageModal" data-image-url="<?php echo str_replace(ROOT_PATH, SITE_URL, $image['image_path']); ?>" data-image-title="صورة تكرار">
                            <img src="<?php echo str_replace(ROOT_PATH, SITE_URL, $image['image_path']); ?>" class="img-thumbnail" alt="صورة تكرار">
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- صور القطع المخصصة -->
                <?php if (!empty($customImages)): ?>
                <h6 class="border-bottom pb-2 mb-3">صور القطع المخصصة</h6>
                <div class="row mb-4">
                    <?php foreach ($customImages as $image): ?>
                    <div class="col-md-3 col-sm-4 col-6 mb-3">
                        <a href="#" class="d-block" data-bs-toggle="modal" data-bs-target="#imageModal" data-image-url="<?php echo str_replace(ROOT_PATH, SITE_URL, $image['image_path']); ?>" data-image-title="صورة قطعة مخصصة">
                            <img src="<?php echo str_replace(ROOT_PATH, SITE_URL, $image['image_path']); ?>" class="img-thumbnail" alt="صورة قطعة مخصصة">
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- صورة العربون -->
                <?php if (!empty($depositImage)): ?>
                <h6 class="border-bottom pb-2 mb-3">صورة تحويل العربون</h6>
                <div class="row">
                    <div class="col-md-6">
                        <a href="#" class="d-block" data-bs-toggle="modal" data-bs-target="#imageModal" data-image-url="<?php echo str_replace(ROOT_PATH, SITE_URL, $depositImage['image_path']); ?>" data-image-title="صورة تحويل العربون">
                            <img src="<?php echo str_replace(ROOT_PATH, SITE_URL, $depositImage['image_path']); ?>" class="img-thumbnail" alt="صورة تحويل العربون">
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-4">
        <!-- معلومات الحالة والإنشاء -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات الحالة</h5>
            </div>
            <div class="card-body">
                <!-- حالة الطلب الحالية -->
                <div class="mb-3">
                    <h6><i class="fas fa-tasks me-2"></i>حالة الطلب الحالية:</h6>
                    <?php
                    $statusClass = 'secondary';
                    switch ($invoice['status']) {
                        case 'جديد':
                            $statusClass = 'primary';
                            break;
                        case 'ليث':
                        case 'الوسيط':
                            $statusClass = 'info';
                            break;
                        case 'لم يتم الشراء':
                            $statusClass = 'danger';
                            break;
                        case 'تم الشراء':
                            $statusClass = 'success';
                            break;
                        case 'تم الشحن':
                            $statusClass = 'warning';
                            break;
                        case 'مرتجع':
                            $statusClass = 'danger';
                            break;
                        case 'مكتمل':
                            $statusClass = 'success';
                            break;
                        case 'ملغي':
                            $statusClass = 'dark';
                            break;
                    }
                    ?>
                    <div class="text-center mt-2">
                        <span class="badge bg-<?php echo $statusClass; ?> fs-6 p-2 w-100"><?php echo htmlspecialchars($invoice['status']); ?></span>
                    </div>
                </div>
                
                <!-- تغيير حالة الطلب (للمدراء فقط) -->
                <?php if ($canChangeStatus): ?>
                <form action="update_invoice_status.php" method="post" class="mb-4">
                    <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">تغيير الحالة إلى:</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="">-- اختر الحالة --</option>
                            <?php foreach ($orderStatuses as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo ($invoice['status'] == $status) ? 'selected' : ''; ?>>
                                <?php echo $status; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>تحديث الحالة
                        </button>
                    </div>
                </form>
                <?php endif; ?>
                
                <!-- إضافة رقم سلة (للمدراء فقط) -->
                <?php if ($auth->hasPermission(3) && empty($invoice['cart_number'])): ?>
                <div class="border-top pt-3 mb-3">
                    <form action="add_cart_number.php" method="post">
                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                        
                        <div class="mb-3">
                            <label for="cart_number" class="form-label">إضافة رقم سلة:</label>
                            <input type="text" class="form-control" id="cart_number" name="cart_number" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-shopping-cart me-2"></i>إضافة رقم السلة
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- معلومات الإنشاء والتحديث -->
                <div class="border-top pt-3 mt-3">
                    <h6 class="mb-3"><i class="fas fa-history me-2"></i>معلومات الإنشاء والتحديث:</h6>
                    
                    <div class="mb-2">
                        <strong>أنشئت بواسطة:</strong>
                        <span><?php echo $createdBy ? htmlspecialchars($createdBy['username']) : 'غير معروف'; ?></span>
                    </div>
                    
                    <div class="mb-2">
                        <strong>تاريخ الإنشاء:</strong>
                        <span><?php echo date('Y-m-d H:i', strtotime($invoice['created_at'])); ?></span>
                    </div>
                    
                    <?php if (!empty($invoice['updated_at']) && $invoice['updated_at'] != $invoice['created_at']): ?>
                    <div class="mb-2">
                        <strong>آخر تحديث:</strong>
                        <span><?php echo date('Y-m-d H:i', strtotime($invoice['updated_at'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- طلبات أخرى للزبون -->
        <?php
        // البحث عن طلبات أخرى لنفس رقم الهاتف
        $otherInvoices = $db->fetchAll(
            "SELECT id, status, created_at FROM invoices WHERE phone = :phone AND id != :current_id ORDER BY id DESC LIMIT 5",
            ['phone' => $invoice['phone'], 'current_id' => $invoice['id']]
        );
        
        if (!empty($otherInvoices)):
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>طلبات أخرى للزبون</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($otherInvoices as $otherInvoice): ?>
                    <?php
                    $otherStatusClass = 'secondary';
                    switch ($otherInvoice['status']) {
                        case 'جديد':
                            $otherStatusClass = 'primary';
                            break;
                        case 'تم الشراء':
                            $otherStatusClass = 'success';
                            break;
                        case 'لم يتم الشراء':
                        case 'مرتجع':
                            $otherStatusClass = 'danger';
                            break;
                        case 'تم الشحن':
                            $otherStatusClass = 'warning';
                            break;
                        case 'مكتمل':
                            $otherStatusClass = 'success';
                            break;
                        case 'ملغي':
                            $otherStatusClass = 'dark';
                            break;
                    }
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="invoice_view.php?id=<?php echo $otherInvoice['id']; ?>" class="text-decoration-none">
                            <i class="fas fa-file-invoice me-2"></i>
                            فاتورة #<?php echo $otherInvoice['id']; ?>
                            <small class="text-muted ms-2"><?php echo date('Y-m-d', strtotime($otherInvoice['created_at'])); ?></small>
                        </a>
                        <span class="badge bg-<?php echo $otherStatusClass; ?>"><?php echo $otherInvoice['status']; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (count($otherInvoices) >= 5): ?>
                <div class="card-footer text-center">
                    <a href="invoice_search.php?phone=<?php echo urlencode($invoice['phone']); ?>" class="btn btn-sm btn-outline-info">
                        عرض جميع طلبات الزبون
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
