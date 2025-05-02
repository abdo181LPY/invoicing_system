<?php 
/**
 * صفحة إنشاء فاتورة جديدة
 * تتيح للمستخدمين إدخال تفاصيل فاتورة جديدة
 */
$pageTitle = 'تثبيت طلب جديد';
include 'views/layout/header.php';
include 'views/layout/sidebar.php';

// استرجاع البيانات القديمة إن وجدت
$old = $session->getFlash('old') ?: [];

// استخدام بيانات من الحاسبة إن وجدت
if (isset($createOrderData) && is_array($createOrderData)) {
    $formData = $createOrderData['form_data'];
    $calculationData = $createOrderData['data'];
    $pageData = $createOrderData['page'];
    
    // دمج البيانات مع البيانات القديمة
    $old = array_merge($old, $formData);
    $old['page_id'] = $formData['page_id'];
    $old['basket_price'] = $formData['basket_price'] ?? '';
    $old['total_price'] = $calculationData['total_cost_iqd'];
    $old['delivery_cost'] = $calculationData['delivery_cost'];
    $old['deposit_amount'] = $calculationData['deposit_iqd'];
}
?>

<!-- عنوان الصفحة -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-plus-circle me-2"></i>تثبيت طلب جديد</h1>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">بيانات الطلب</h5>
    </div>
    <div class="card-body">
        <form action="invoice_store.php" method="post" enctype="multipart/form-data" id="invoiceForm">
            <!-- بيانات العميل -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>بيانات الزبون</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_name" class="form-label">اسم الزبون <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name" required value="<?php echo isset($old['customer_name']) ? htmlspecialchars($old['customer_name']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="07xxxxxxxx" required value="<?php echo isset($old['phone']) ? htmlspecialchars($old['phone']) : ''; ?>">
                                <button class="btn btn-outline-secondary" type="button" id="checkBlacklist" title="التحقق من القائمة السوداء">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="form-text">يبدأ بـ 07 ويتكون من 11 رقم</div>
                            <div id="blacklistResult"></div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone2" class="form-label">رقم الهاتف الثانوي</label>
                            <input type="text" class="form-control" id="phone2" name="phone2" placeholder="07xxxxxxxx" value="<?php echo isset($old['phone2']) ? htmlspecialchars($old['phone2']) : ''; ?>">
                            <div class="form-text">اختياري</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="province" class="form-label">المحافظة <span class="text-danger">*</span></label>
                            <select class="form-select" id="province" name="province" required>
                                <option value="">اختر المحافظة</option>
                                <?php foreach ($provinces as $province): ?>
                                <option value="<?php echo $province; ?>" <?php echo (isset($old['province']) && $old['province'] === $province) ? 'selected' : ''; ?>>
                                    <?php echo $province; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="area" class="form-label">المنطقة <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="area" name="area" required value="<?php echo isset($old['area']) ? htmlspecialchars($old['area']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label">أقرب نقطة دالة <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="address" name="address" required value="<?php echo isset($old['address']) ? htmlspecialchars($old['address']) : ''; ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- بيانات الطلب -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>بيانات الطلب</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="page_id" class="form-label">الصفحة <span class="text-danger">*</span></label>
                            <select class="form-select" id="page_id" name="page_id" required>
                                <option value="">اختر الصفحة</option>
                                <?php foreach ($pages as $page): ?>
                                <option value="<?php echo $page['id']; ?>" <?php echo (isset($old['page_id']) && $old['page_id'] == $page['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($page['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="cart_link" class="form-label">رابط السلة</label>
                            <input type="url" class="form-control" id="cart_link" name="cart_link" value="<?php echo isset($old['cart_link']) ? htmlspecialchars($old['cart_link']) : ''; ?>">
                            <div class="form-text">اختياري</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="instagram" class="form-label">رابط الإنستغرام <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                <input type="url" class="form-control" id="instagram" name="instagram" required value="<?php echo isset($old['instagram']) ? htmlspecialchars($old['instagram']) : ''; ?>">
                            </div>
                            <div class="form-text">يجب أن يبدأ بـ http</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="item_count" class="form-label">عدد القطع <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="item_count" name="item_count" min="1" required value="<?php echo isset($old['item_count']) ? htmlspecialchars($old['item_count']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="basket_price" class="form-label">سعر السلة بالدولار</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                <input type="number" class="form-control" id="basket_price" name="basket_price" step="0.01" min="0.01" value="<?php echo isset($old['basket_price']) ? htmlspecialchars($old['basket_price']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="delivery_cost" class="form-label">تكلفة التوصيل</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-truck"></i></span>
                                <input type="number" class="form-control" id="delivery_cost" name="delivery_cost" value="<?php echo isset($old['delivery_cost']) ? htmlspecialchars($old['delivery_cost']) : ''; ?>">
                                <span class="input-group-text">دينار</span>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="total_price" class="form-label">المبلغ الإجمالي</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                <input type="number" class="form-control" id="total_price" name="total_price" value="<?php echo isset($old['total_price']) ? htmlspecialchars($old['total_price']) : ''; ?>">
                                <span class="input-group-text">دينار</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- التكرارات والقطع المخصصة -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>التكرارات والقطع المخصصة</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="repeat_details" class="form-label">تفاصيل التكرارات</label>
                            <textarea class="form-control" id="repeat_details" name="repeat_details" rows="3"><?php echo isset($old['repeat_details']) ? htmlspecialchars($old['repeat_details']) : ''; ?></textarea>
                            <div class="form-text">اختياري</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="repeat_images" class="form-label">صور التكرارات</label>
                            <input type="file" class="form-control" id="repeat_images" name="repeat_images[]" multiple accept="image/*">
                            <div class="form-text">يمكن رفع عدة صور (اختياري)</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="custom_details" class="form-label">تفاصيل القطع المخصصة</label>
                            <textarea class="form-control" id="custom_details" name="custom_details" rows="3"><?php echo isset($old['custom_details']) ? htmlspecialchars($old['custom_details']) : ''; ?></textarea>
                            <div class="form-text">اختياري</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="custom_images" class="form-label">صور القطع المخصصة</label>
                            <input type="file" class="form-control" id="custom_images" name="custom_images[]" multiple accept="image/*">
                            <div class="form-text">يمكن رفع عدة صور (اختياري)</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- العربون -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>العربون</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="deposit_method" class="form-label">طريقة دفع العربون</label>
                            <select class="form-select" id="deposit_method" name="deposit_method">
                                <option value="">اختر طريقة الدفع</option>
                                <option value="زين كاش" <?php echo (isset($old['deposit_method']) && $old['deposit_method'] === 'زين كاش') ? 'selected' : ''; ?>>زين كاش</option>
                                <option value="آسيا حوالة" <?php echo (isset($old['deposit_method']) && $old['deposit_method'] === 'آسيا حوالة') ? 'selected' : ''; ?>>آسيا حوالة</option>
                                <option value="تحويل بنكي" <?php echo (isset($old['deposit_method']) && $old['deposit_method'] === 'تحويل بنكي') ? 'selected' : ''; ?>>تحويل بنكي</option>
                                <option value="أخرى" <?php echo (isset($old['deposit_method']) && $old['deposit_method'] === 'أخرى') ? 'selected' : ''; ?>>أخرى</option>
                            </select>
                            <div class="form-text">اختياري</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="deposit_amount" class="form-label">مبلغ العربون</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-money-bill"></i></span>
                                <input type="number" class="form-control" id="deposit_amount" name="deposit_amount" step="1000" min="0" value="<?php echo isset($old['deposit_amount']) ? htmlspecialchars($old['deposit_amount']) : ''; ?>">
                                <span class="input-group-text">دينار</span>
                            </div>
                            <div class="form-text">اختياري</div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="deposit_image" class="form-label">صورة إثبات دفع العربون</label>
                            <input type="file" class="form-control" id="deposit_image" name="deposit_image" accept="image/*">
                            <div class="form-text">اختياري</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ملاحظات إضافية -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>ملاحظات إضافية</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="notes" class="form-label">ملاحظات</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($old['notes']) ? htmlspecialchars($old['notes']) : ''; ?></textarea>
                        <div class="form-text">اختياري</div>
                    </div>
                </div>
            </div>
            
            <!-- أزرار الإجراءات -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-3">
                <button type="reset" class="btn btn-secondary me-md-2">
                    <i class="fas fa-redo me-1"></i>إعادة تعيين
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>حفظ الفاتورة
                </button>
            </div>
        </form>
    </div>
</div>

<!-- نافذة حوار التحقق من القائمة السوداء -->
<div class="modal fade" id="blacklistModal" tabindex="-1" aria-labelledby="blacklistModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="blacklistModalLabel"><i class="fas fa-exclamation-triangle me-2"></i>تنبيه: رقم محظور</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <p>هذا الرقم موجود في القائمة السوداء!</p>
                    <p id="blacklistReason"></p>
                    <p><strong>يجب دفع المبلغ كاملاً مقدماً.</strong></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // التحقق من القائمة السوداء
    const checkBlacklistBtn = document.getElementById('checkBlacklist');
    const phoneInput = document.getElementById('phone');
    const blacklistResult = document.getElementById('blacklistResult');
    const blacklistModal = new bootstrap.Modal(document.getElementById('blacklistModal'));
    const blacklistReason = document.getElementById('blacklistReason');
    
    checkBlacklistBtn.addEventListener('click', function() {
        const phone = phoneInput.value.trim();
        
        if (!phone) {
            blacklistResult.innerHTML = '<div class="alert alert-warning mt-2">يرجى إدخال رقم الهاتف أولاً.</div>';
            return;
        }
        
        // التحقق من صحة رقم الهاتف
        if (!/^07\d{9}$/.test(phone)) {
            blacklistResult.innerHTML = '<div class="alert alert-warning mt-2">يرجى إدخال رقم هاتف عراقي صحيح يبدأ بـ 07 ويتكون من 11 رقم.</div>';
            return;
        }
        
        // إظهار رسالة التحميل
        blacklistResult.innerHTML = '<div class="alert alert-info mt-2">جاري التحقق من الرقم...</div>';
        
        // إرسال طلب التحقق
        fetch('check_blacklist.php?phone=' + encodeURIComponent(phone))
            .then(response => response.json())
            .then(data => {
                if (data.is_blacklisted) {
                    // الرقم محظور
                    blacklistResult.innerHTML = '<div class="alert alert-danger mt-2">هذا الرقم موجود في القائمة السوداء!</div>';
                    
                    // عرض نافذة التنبيه
                    if (data.data && data.data.reason) {
                        blacklistReason.textContent = 'السبب: ' + data.data.reason;
                    } else {
                        blacklistReason.textContent = '';
                    }
                    
                    blacklistModal.show();
                } else {
                    // الرقم غير محظور
                    blacklistResult.innerHTML = '<div class="alert alert-success mt-2">هذا الرقم غير موجود في القائمة السوداء.</div>';
                }
            })
            .catch(error => {
                blacklistResult.innerHTML = '<div class="alert alert-danger mt-2">حدث خطأ أثناء التحقق. يرجى المحاولة مرة أخرى.</div>';
                console.error('Error:', error);
            });
    });
    
    // التحقق عند كتابة رقم الهاتف
    phoneInput.addEventListener('blur', function() {
        const phone = phoneInput.value.trim();
        
        if (phone && /^07\d{9}$/.test(phone)) {
            checkBlacklistBtn.click();
        }
    });
    
    // التحقق من رقم الهاتف الثانوي
    const phone2Input = document.getElementById('phone2');
    
    phone2Input.addEventListener('blur', function() {
        const phone = phone2Input.value.trim();
        
        if (phone && !/^07\d{9}$/.test(phone)) {
            alert('يرجى إدخال رقم هاتف عراقي صحيح يبدأ بـ 07 ويتكون من 11 رقم.');
            phone2Input.focus();
        }
    });
    
    // التحقق من رابط الإنستغرام
    const instagramInput = document.getElementById('instagram');
    
    instagramInput.addEventListener('blur', function() {
        const instagram = instagramInput.value.trim();
        
        if (instagram && !instagram.startsWith('http')) {
            alert('يجب أن يبدأ رابط الإنستغرام بـ http أو https');
            instagramInput.focus();
        }
    });
});
</script>

<?php include 'views/layout/footer.php'; ?>
