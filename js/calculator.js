/**
 * ملف حاسبة التكلفة
 * يدير عمليات حساب التكلفة وعرض النتائج
 */

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة نموذج الحاسبة
    initCalculatorForm();
    
    // تهيئة ميزة النسخ
    initCopyFeature();
    
    // تحديث القيم المحسوبة مباشرة أثناء الإدخال
    initLiveCalculation();
});

/**
 * تهيئة نموذج الحاسبة
 */
function initCalculatorForm() {
    var calculatorForm = document.getElementById('calculatorForm');
    
    if (calculatorForm) {
        calculatorForm.addEventListener('submit', function(e) {
            // التحقق من صحة البيانات قبل الإرسال
            if (!validateCalculatorForm()) {
                e.preventDefault();
            }
        });
    }
}

/**
 * التحقق من صحة بيانات نموذج الحاسبة
 */
function validateCalculatorForm() {
    var pageId = document.getElementById('page_id').value;
    var instagram = document.getElementById('instagram').value;
    var province = document.getElementById('province').value;
    var basketPrice = document.getElementById('basket_price').value;
    var itemCount = document.getElementById('item_count').value;
    
    var errors = [];
    
    if (!pageId) {
        errors.push('الرجاء اختيار الصفحة');
    }
    
    if (!instagram) {
        errors.push('الرجاء إدخال رابط الإنستغرام');
    } else if (!instagram.startsWith('http')) {
        errors.push('رابط الإنستغرام يجب أن يبدأ بـ http');
    }
    
    if (!province) {
        errors.push('الرجاء اختيار المحافظة');
    }
    
    if (!basketPrice) {
        errors.push('الرجاء إدخال سعر السلة');
    } else if (isNaN(basketPrice) || parseFloat(basketPrice) <= 0) {
        errors.push('سعر السلة يجب أن يكون رقماً موجباً');
    }
    
    if (!itemCount) {
        errors.push('الرجاء إدخال عدد القطع');
    } else if (isNaN(itemCount) || parseInt(itemCount) <= 0) {
        errors.push('عدد القطع يجب أن يكون عدداً صحيحاً موجباً');
    }
    
    if (errors.length > 0) {
        var errorMessage = errors.join('\n');
        alert(errorMessage);
        return false;
    }
    
    return true;
}

/**
 * تهيئة ميزة النسخ
 */
function initCopyFeature() {
    var copyBtn = document.querySelector('.copy-btn');
    
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            var textToCopy = this.getAttribute('data-clipboard-text');
            var originalText = this.innerHTML;
            
            if (textToCopy) {
                // نسخ النص إلى الحافظة
                navigator.clipboard.writeText(textToCopy).then(function() {
                    // تغيير نص الزر بعد النسخ
                    copyBtn.innerHTML = '<i class="fas fa-check me-2"></i>تم النسخ';
                    
                    // إعادة النص الأصلي بعد 2 ثانية
                    setTimeout(function() {
                        copyBtn.innerHTML = originalText;
                    }, 2000);
                }).catch(function() {
                    // حدث خطأ أثناء النسخ
                    copyBtn.innerHTML = '<i class="fas fa-times me-2"></i>فشل النسخ';
                    
                    // إعادة النص الأصلي بعد 2 ثانية
                    setTimeout(function() {
                        copyBtn.innerHTML = originalText;
                    }, 2000);
                });
            }
        });
    }
}

/**
 * تهيئة الحساب المباشر
 */
function initLiveCalculation() {
    var basketPriceInput = document.getElementById('basket_price');
    var itemCountInput = document.getElementById('item_count');
    var provinceSelect = document.getElementById('province');
    var liveResultContainer = document.getElementById('liveResult');
    
    // التحقق من وجود عناصر الحساب المباشر
    if (basketPriceInput && itemCountInput && provinceSelect && liveResultContainer) {
        // دالة تحديث الحساب المباشر
        function updateLiveCalculation() {
            var basketPrice = parseFloat(basketPriceInput.value) || 0;
            var itemCount = parseInt(itemCountInput.value) || 0;
            var province = provinceSelect.value;
            
            // التحقق من صحة القيم
            if (basketPrice <= 0 || itemCount <= 0 || !province) {
                liveResultContainer.style.display = 'none';
                return;
            }
            
            // حساب التكلفة
            var calculationData = calculateCost(basketPrice, itemCount, province);
            
            // عرض النتائج
            liveResultContainer.style.display = 'block';
            liveResultContainer.innerHTML = `
                <div class="alert alert-primary">
                    <h5 class="alert-heading">نتائج الحساب المبدئي</h5>
                    <hr>
                    <p><strong>سعر السلة:</strong> ${formatNumber(basketPrice)} دولار</p>
                    <p><strong>مبلغ السلة:</strong> ${formatNumber(calculationData.basketPriceIQD)} دينار</p>
                    <p><strong>تكلفة التوصيل:</strong> ${formatNumber(calculationData.deliveryCost)} دينار</p>
                    <p><strong>المبلغ الإجمالي:</strong> ${formatNumber(calculationData.totalCostIQD)} دينار</p>
                    ${calculationData.depositIQD > 0 ? `
                    <p><strong>العربون المطلوب:</strong> ${formatNumber(calculationData.depositIQD)} دينار</p>
                    <p><strong>المبلغ المتبقي:</strong> ${formatNumber(calculationData.remainingIQD)} دينار</p>
                    ` : ''}
                </div>
            `;
        }
        
        // تعيين مستمعي الأحداث
        basketPriceInput.addEventListener('input', updateLiveCalculation);
        itemCountInput.addEventListener('input', updateLiveCalculation);
        provinceSelect.addEventListener('change', updateLiveCalculation);
    }
}

/**
 * حساب التكلفة
 */
function calculateCost(basketPrice, itemCount, province) {
    // قيم ثابتة
    var exchangeRate = 1300; // سعر صرف الدولار
    var baghdadDeliveryCost = 6000; // تكلفة التوصيل لبغداد
    var provincesDeliveryCost = 7000; // تكلفة التوصيل للمحافظات
    
    // تحويل القيم إلى أرقام
    basketPrice = parseFloat(basketPrice);
    itemCount = parseInt(itemCount);
    
    // تكلفة التوصيل
    var deliveryCost = (province === 'بغداد') ? baghdadDeliveryCost : provincesDeliveryCost;
    
    // حساب التكلفة حسب القواعد
    var basketPriceIQD = 0;
    
    if (basketPrice > 400) {
        // أكثر من 400 دولار: سعر السلة × 1300
        basketPriceIQD = basketPrice * exchangeRate;
    } else if (basketPrice >= 200) {
        // بين 200 و400 دولار: (سعر السلة + ربع عدد القطع) × 1300
        basketPriceIQD = (basketPrice + (itemCount * 0.25)) * exchangeRate;
    } else {
        // أقل من 200 دولار: (سعر السلة + نصف عدد القطع) × 1300
        basketPriceIQD = (basketPrice + (itemCount * 0.5)) * exchangeRate;
    }
    
    // إجمالي التكلفة
    var totalCostIQD = basketPriceIQD + deliveryCost;
    
    // حساب قيمة العربون
    var depositIQD = 0;
    if (totalCostIQD > 70000) {
        if (basketPrice > 400) {
            // للطلبات فوق 400 دولار: نصف المبلغ
            depositIQD = totalCostIQD * 0.5;
        } else {
            // للطلبات العادية: بين ربع وثلث المبلغ
            depositIQD = totalCostIQD * 0.3; // استخدام 30% كمتوسط
        }
        
        // تقريب العربون لأقرب 5000 دينار للأعلى
        depositIQD = Math.ceil(depositIQD / 5000) * 5000;
    }
    
    return {
        basketPriceUSD: basketPrice,
        basketPriceIQD: basketPriceIQD,
        deliveryCost: deliveryCost,
        totalCostIQD: totalCostIQD,
        depositIQD: depositIQD,
        remainingIQD: totalCostIQD - depositIQD,
        exchangeRate: exchangeRate
    };
}

/**
 * تنسيق الأرقام بفواصل الآلاف
 */
function formatNumber(number) {
    return number.toLocaleString('en-US');
}

/**
 * إنشاء رسالة للزبون
 */
function createCustomerMessage(calculationData, pageData) {
    var message = "✅ *تم حساب سعر طلبك بنجاح* ✅\n\n";
    message += "🛍️ *تفاصيل الطلب:*\n";
    message += "💲 سعر السلة: " + formatNumber(calculationData.basketPriceUSD) + " دولار\n";
    message += "💵 سعر الصرف: " + formatNumber(calculationData.exchangeRate) + " دينار\n";
    message += "🚚 سعر التوصيل: " + formatNumber(calculationData.deliveryCost) + " دينار\n\n";
    
    message += "💰 *التكلفة الإجمالية:* " + formatNumber(calculationData.totalCostIQD) + " دينار\n\n";
    
    if (calculationData.depositIQD > 0) {
        message += "⚠️ *العربون المطلوب:* " + formatNumber(calculationData.depositIQD) + " دينار\n";
        message += "⏳ *المبلغ المتبقي عند الاستلام:* " + formatNumber(calculationData.remainingIQD) + " دينار\n\n";
    }
    
    message += "📞 *للطلب والاستفسار:*\n";
    message += "📱 " + pageData.phone + "\n";
    
    if (pageData.alternate_phone) {
        message += "📱 " + pageData.alternate_phone + "\n";
    }
    
    message += "📷 " + pageData.instagram + "\n";
    
    return message;
}

/**
 * إنشاء صورة من نص الحساب
 */
function createImageFromText() {
    var messageContainer = document.querySelector('.message-container');
    
    if (messageContainer) {
        // الحصول على النص
        var message = messageContainer.innerText;
        
        // إرسال النص لإنشاء صورة
        fetch('create_image.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // فتح الصورة في نافذة جديدة
                window.open(data.image_path, '_blank');
            } else {
                alert('فشل في إنشاء الصورة: ' + data.error);
            }
        })
        .catch(error => {
            console.error('خطأ في إنشاء الصورة:', error);
            alert('حدث خطأ أثناء إنشاء الصورة');
        });
    }
}
