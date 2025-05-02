<?php 
/**
 * صفحة حاسبة التكلفة
 * تتيح للمستخدمين حساب تكلفة الطلبات وإظهار المعلومات للزبائن
 */
$pageTitle = 'حساب التكلفة';
include 'views/layout/header.php';

// استرجاع البيانات القديمة إن وجدت
$old = $session->getFlash('old') ?: [];
?>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>حساب تكلفة الطلب</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_GET['result'])): ?>
                <!-- عرض نتائج الحساب -->
                <div class="alert alert-success">
                    <h5 class="alert-heading"><i class="fas fa-check-circle me-2"></i>تم حساب التكلفة بنجاح!</h5>
                    <p>يمكنك الآن استخدام الرسالة المنسقة لمشاركتها مع الزبون.</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">تفاصيل الحساب</h6>
                            </div>
                            <div class="card-body">
                                <p><strong>الصفحة:</strong> <?php echo htmlspecialchars($pageData['name']); ?></p>
                                <p><strong>سعر السلة:</strong> <?php echo number_format($calculationData['basket_price_usd'], 2); ?> دولار</p>
                                <p><strong>سعر الصرف:</strong> <?php echo number_format($calculationData['exchange_rate']); ?> دينار</p>
                                <p><strong>عدد القطع:</strong> <?php echo $formData['item_count']; ?></p>
                                <p><strong>المحافظة:</strong> <?php echo $formData['province']; ?></p>
                                <p><strong>مبلغ السلة:</strong> <?php echo number_format($calculationData['basket_price_iqd']); ?> دينار</p>
                                <p><strong>تكلفة التوصيل:</strong> <?php echo number_format($calculationData['delivery_cost']); ?> دينار</p>
                                <p><strong>المبلغ الإجمالي:</strong> <?php echo number_format($calculationData['total_cost_iqd']); ?> دينار</p>
                                <?php if ($calculationData['deposit_iqd'] > 0): ?>
                                <p><strong>العربون المطلوب:</strong> <?php echo number_format($calculationData['deposit_iqd']); ?> دينار</p>
                                <p><strong>المبلغ المتبقي:</strong> <?php echo number_format($calculationData['remaining_iqd']); ?> دينار</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0">الرسالة المنسقة للزبون</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-light border message-container">
                                    <?php echo nl2br(htmlspecialchars($message)); ?>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary btn-sm copy-btn" type="button" data-clipboard-text="<?php echo htmlspecialchars($message); ?>">
                                        <i class="fas fa-copy me-2"></i>نسخ الرسالة
                                    </button>
                                    
                                    <form action="create_order.php" method="post">
                                        <input type="hidden" name="calculation_id" value="<?php echo time(); ?>">
                                        <button type="submit" class="btn btn-success btn-sm w-100">
                                            <i class="fas fa-plus-circle me-2"></i>تثبيت الطلب
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="calculator.php" class="btn btn-primary">
                        <i class="fas fa-calculator me-2"></i>حساب جديد
                    </a>
                </div>
                
                <?php else: ?>
                <!-- نموذج الحساب -->
                <form action="calculate.php" method="post">
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
                            <label for="instagram" class="form-label">رابط الإنستغرام <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                <input type="url" class="form-control" id="instagram" name="instagram" placeholder="أدخل رابط الإنستغرام" required value="<?php echo isset($old['instagram']) ? htmlspecialchars($old['instagram']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="province" class="form-label">المحافظة <span class="text-danger">*</span></label>
                            <select class="form-select" id="province" name="province" required>
                                <option value="">اختر المحافظة</option>
                                <?php foreach ($provinces as $province): ?>
                                <option value="<?php echo $province; ?>" <?php echo (isset($old['province']) && $old['province'] == $province) ? 'selected' : ''; ?>>
                                    <?php echo $province; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="basket_price" class="form-label">سعر السلة بالدولار <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                <input type="number" class="form-control" id="basket_price" name="basket_price" placeholder="أدخل سعر السلة" step="0.01" min="0.01" required value="<?php echo isset($old['basket_price']) ? htmlspecialchars($old['basket_price']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="item_count" class="form-label">عدد القطع الكلي <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-list-ol"></i></span>
                                <input type="number" class="form-control" id="item_count" name="item_count" placeholder="أدخل عدد القطع" min="1" required value="<?php echo isset($old['item_count']) ? htmlspecialchars($old['item_count']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-calculator me-2"></i>حساب التكلفة
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات تهمك</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="fas fa-exchange-alt me-2"></i>سعر الصرف الحالي:</h6>
                    <p class="mb-0"><?php echo number_format(EXCHANGE_RATE); ?> دينار للدولار الواحد</p>
                </div>
                
                <div class="alert alert-light border">
                    <h6><i class="fas fa-truck me-2"></i>أسعار التوصيل:</h6>
                    <ul class="mb-0">
                        <li>بغداد: <?php echo number_format(BAGHDAD_DELIVERY_COST); ?> دينار</li>
                        <li>المحافظات: <?php echo number_format(PROVINCES_DELIVERY_COST); ?> دينار</li>
                    </ul>
                </div>
                
                <div class="alert alert-light border">
                    <h6><i class="fas fa-calculator me-2"></i>طريقة الحساب:</h6>
                    <ul class="mb-0">
                        <li>أكثر من 400 دولار: سعر السلة × <?php echo number_format(EXCHANGE_RATE); ?></li>
                        <li>من 200 إلى 400 دولار: (سعر السلة + ربع عدد القطع) × <?php echo number_format(EXCHANGE_RATE); ?></li>
                        <li>أقل من 200 دولار: (سعر السلة + نصف عدد القطع) × <?php echo number_format(EXCHANGE_RATE); ?></li>
                    </ul>
                </div>
                
                <div class="alert alert-light border">
                    <h6><i class="fas fa-money-bill-wave me-2"></i>قيمة العربون:</h6>
                    <ul class="mb-0">
                        <li>للطلبات العادية: بين ربع وثلث المبلغ</li>
                        <li>للطلبات فوق 400 دولار: نصف المبلغ</li>
                        <li>يتم تقريب العربون لأقرب 5000 دينار للأعلى</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['result'])): ?>
<!-- إضافة مكتبة clipboard.js للنسخ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // تهيئة مكتبة clipboard.js
        var clipboard = new ClipboardJS('.copy-btn');
        
        clipboard.on('success', function(e) {
            const button = e.trigger;
            const originalText = button.innerHTML;
            
            // تغيير نص الزر بعد النسخ
            button.innerHTML = '<i class="fas fa-check me-2"></i>تم النسخ';
            
            // إعادة النص الأصلي بعد 2 ثانية
            setTimeout(function() {
                button.innerHTML = originalText;
            }, 2000);
            
            e.clearSelection();
        });
    });
</script>
<?php endif; ?>

<?php include 'views/layout/footer.php'; ?>
