/**
 * ملف إدارة الفواتير
 * يدير عمليات إنشاء وتعديل وحذف الفواتير
 */

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة نموذج إنشاء الفاتورة
    initInvoiceForm();
    
    // تهيئة ميزة البحث
    initInvoiceSearch();
    
    // تهيئة عناصر عرض الفاتورة
    initInvoiceView();
    
    // تهيئة طباعة الفاتورة
    initInvoicePrint();
});

/**
 * تهيئة نموذج إنشاء الفاتورة
 */
function initInvoiceForm() {
    var invoiceForm = document.getElementById('invoiceForm');
    
    if (invoiceForm) {
        // التحقق من وجود رقم الهاتف في القائمة السوداء
        var phoneInput = document.getElementById('phone');
        var blacklistAlert = document.getElementById('blacklistAlert');
        
        if (phoneInput && blacklistAlert) {
            phoneInput.addEventListener('blur', function() {
                var phone = phoneInput.value.trim();
                
                if (phone && validateIraqiPhone(phone)) {
                    // التحقق من القائمة السوداء
                    checkBlacklist(phone, function(data) {
                        if (data.is_blacklisted) {
                            blacklistAlert.classList.remove('d-none');
                            blacklistAlert.innerHTML = '<strong>تنبيه!</strong> رقم الهاتف موجود في القائمة السوداء! يجب دفع المبلغ كاملاً مقدماً.';
                            
                            // تعيين حقل "يتطلب دفع كامل المبلغ" إلى نعم
                            var requiresFullPayment = document.getElementById('requires_full_payment');
                            if (requiresFullPayment) {
                                requiresFullPayment.checked = true;
                            }
                        } else {
                            blacklistAlert.classList.add('d-none');
                            
                            // إعادة تعيين حقل "يتطلب دفع كامل المبلغ" إلى لا
                            var requiresFullPayment = document.getElementById('requires_full_payment');
                            if (requiresFullPayment) {
                                requiresFullPayment.checked = false;
                            }
                        }
                    });
                } else {
                    blacklistAlert.classList.add('d-none');
                }
            });
        }
        
        // إضافة حقول تكرار جديدة
        var addRepeatBtn = document.getElementById('addRepeatBtn');
        var repeatContainer = document.getElementById('repeatContainer');
        
        if (addRepeatBtn && repeatContainer) {
            addRepeatBtn.addEventListener('click', function() {
                var repeatCount = document.querySelectorAll('.repeat-item').length + 1;
                
                var repeatHtml = `
                <div class="repeat-item border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">تكرار #${repeatCount}</h6>
                        <button type="button" class="btn btn-sm btn-danger remove-repeat">حذف</button>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label">رقم القطعة</label>
                            <input type="text" class="form-control" name="repeat_items[${repeatCount}][item_number]" placeholder="رقم القطعة">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label">الكمية</label>
                            <input type="number" class="form-control" name="repeat_items[${repeatCount}][quantity]" placeholder="الكمية" value="1" min="1">
                        </div>
                        <div class="col-12 mb-2">
                            <label class="form-label">تفاصيل</label>
                            <textarea class="form-control" name="repeat_items[${repeatCount}][details]" placeholder="تفاصيل التكرار" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">صورة (اختياري)</label>
                            <input type="file" class="form-control" name="repeat_images[]" accept="image/*">
                        </div>
                    </div>
                </div>
                `;
                
                repeatContainer.insertAdjacentHTML('beforeend', repeatHtml);
                
                // تعيين سلوك زر الحذف
                var removeButtons = document.querySelectorAll('.remove-repeat');
                var lastRemoveButton = removeButtons[removeButtons.length - 1];
                
                lastRemoveButton.addEventListener('click', function() {
                    this.closest('.repeat-item').remove();
                    
                    // إعادة ترقيم التكرارات
                    renumberRepeats();
                });
            });
            
            // دالة لإعادة ترقيم التكرارات
            function renumberRepeats() {
                var repeatItems = document.querySelectorAll('.repeat-item');
                
                repeatItems.forEach(function(item, index) {
                    var count = index + 1;
                    
                    // تحديث العنوان
                    item.querySelector('h6').textContent = 'تكرار #' + count;
                    
                    // تحديث أسماء الحقول
                    var inputs = item.querySelectorAll('input, textarea');
                    inputs.forEach(function(input) {
                        var name = input.getAttribute('name');
                        if (name) {
                            name = name.replace(/repeat_items\[\d+\]/, 'repeat_items[' + count + ']');
                            input.setAttribute('name', name);
                        }
                    });
                });
            }
        }
        
        // إضافة حقول قطع مخصصة جديدة
        var addCustomBtn = document.getElementById('addCustomBtn');
        var customContainer = document.getElementById('customContainer');
        
        if (addCustomBtn && customContainer) {
            addCustomBtn.addEventListener('click', function() {
                var customCount = document.querySelectorAll('.custom-item').length + 1;
                
                var customHtml = `
                <div class="custom-item border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">قطعة مخصصة #${customCount}</h6>
                        <button type="button" class="btn btn-sm btn-danger remove-custom">حذف</button>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-2">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" name="custom_items[${customCount}][description]" placeholder="وصف القطعة المخصصة" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">صورة (اختياري)</label>
                            <input type="file" class="form-control" name="custom_images[]" accept="image/*">
                        </div>
                    </div>
                </div>
                `;
                
                customContainer.insertAdjacentHTML('beforeend', customHtml);
                
                // تعيين سلوك زر الحذف
                var removeButtons = document.querySelectorAll('.remove-custom');
                var lastRemoveButton = removeButtons[removeButtons.length - 1];
                
                lastRemoveButton.addEventListener('click', function() {
                    this.closest('.custom-item').remove();
                    
                    // إعادة ترقيم القطع المخصصة
                    renumberCustomItems();
                });
            });
            
            // دالة لإعادة ترقيم القطع المخصصة
            function renumberCustomItems() {
                var customItems = document.querySelectorAll('.custom-item');
                
                customItems.forEach(function(item, index) {
                    var count = index + 1;
                    
                    // تحديث العنوان
                    item.querySelector('h6').textContent = 'قطعة مخصصة #' + count;
                    
                    // تحديث أسماء الحقول
                    var inputs = item.querySelectorAll('input, textarea');
                    inputs.forEach(function(input) {
                        var name = input.getAttribute('name');
                        if (name) {
                            name = name.replace(/custom_items\[\d+\]/, 'custom_items[' + count + ']');
                            input.setAttribute('name', name);
                        }
                    });
                });
            }
        }
        
        // حساب المجموع الكلي
        var basketPriceInput = document.getElementById('basket_price');
        var deliveryCostInput = document.getElementById('delivery_cost');
        var totalPriceInput = document.getElementById('total_price');
        var provinceSelect = document.getElementById('province');
        
        if (basketPriceInput && deliveryCostInput && totalPriceInput && provinceSelect) {
            // دالة تحديث المجموع الكلي
            function updateTotalPrice() {
                var basketPrice = parseFloat(basketPriceInput.value) || 0;
                var basketPriceIQD = basketPrice * 1300; // تحويل الدولار إلى دينار
                
                var province = provinceSelect.value;
                var deliveryCost = (province === 'بغداد') ? 6000 : 7000;
                
                deliveryCostInput.value = deliveryCost;
                
                var totalPrice = basketPriceIQD + deliveryCost;
                totalPriceInput.value = totalPrice;
            }
            
            // تعيين مستمعي الأحداث
            basketPriceInput.addEventListener('input', updateTotalPrice);
            provinceSelect.addEventListener('change', updateTotalPrice);
            
            // تحديث المجموع الكلي عند تحميل الصفحة
            updateTotalPrice();
        }
        
        // التحقق من صحة النموذج قبل الإرسال
        invoiceForm.addEventListener('submit', function(e) {
            if (!validateInvoiceForm()) {
                e.preventDefault();
            }
        });
    }
}

/**
 * التحقق من صحة بيانات نموذج الفاتورة
 */
function validateInvoiceForm() {
    var customerName = document.getElementById('customer_name').value;
    var phone = document.getElementById('phone').value;
    var province = document.getElementById('province').value;
    var area = document.getElementById('area').value;
    var address = document.getElementById('address').value;
    var pageId = document.getElementById('page_id').value;
    var instagram = document.getElementById('instagram').value;
    var itemCount = document.getElementById('item_count').value;
    
    var errors = [];
    
    if (!customerName) {
        errors.push('الرجاء إدخال اسم الزبون');
    }
    
    if (!phone) {
        errors.push('الرجاء إدخال رقم الهاتف');
    } else if (!validateIraqiPhone(phone)) {
        errors.push('رقم الهاتف يجب أن يكون رقم هاتف عراقي صحيح يبدأ بـ 07 ويتكون من 11 رقم');
    }
    
    var phone2 = document.getElementById('phone2');
    if (phone2 && phone2.value && !validateIraqiPhone(phone2.value)) {
        errors.push('رقم الهاتف الثانوي يجب أن يكون رقم هاتف عراقي صحيح يبدأ بـ 07 ويتكون من 11 رقم');
    }
    
    if (!province) {
        errors.push('الرجاء اختيار المحافظة');
    }
    
    if (!area) {
        errors.push('الرجاء إدخال المنطقة');
    }
    
    if (!address) {
        errors.push('الرجاء إدخال العنوان');
    }
    
    if (!pageId) {
        errors.push('الرجاء اختيار الصفحة');
    }
    
    if (!instagram) {
        errors.push('الرجاء إدخال رابط الإنستغرام');
    } else if (!instagram.startsWith('http')) {
        errors.push('رابط الإنستغرام يجب أن يبدأ بـ http');
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
 * تهيئة ميزة البحث عن الفواتير
 */
function initInvoiceSearch() {
    var searchForm = document.getElementById('searchForm');
    var searchResultsContainer = document.getElementById('searchResults');
    var searchSpinner = document.getElementById('searchSpinner');
    
    if (searchForm && searchResultsContainer) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var keyword = document.getElementById('keyword').value.trim();
            
            if (keyword === '') {
                alert('الرجاء إدخال كلمة البحث');
                return;
            }
            
            // عرض مؤشر التحميل
            if (searchSpinner) {
                searchSpinner.classList.remove('d-none');
            }
            
            // إرسال طلب البحث
            fetch('invoice_search_ajax.php?keyword=' + encodeURIComponent(keyword), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // إخفاء مؤشر التحميل
                if (searchSpinner) {
                    searchSpinner.classList.add('d-none');
                }
                
                // عرض النتائج
                searchResultsContainer.innerHTML = html;
                searchResultsContainer.classList.remove('d-none');
                
                // تهيئة أزرار الحذف
                initDeleteButtons();
            })
            .catch(error => {
                console.error('خطأ في البحث:', error);
                
                // إخفاء مؤشر التحميل
                if (searchSpinner) {
                    searchSpinner.classList.add('d-none');
                }
                
                // عرض رسالة الخطأ
                searchResultsContainer.innerHTML = '<div class="alert alert-danger">حدث خطأ أثناء البحث. الرجاء المحاولة مرة أخرى.</div>';
                searchResultsContainer.classList.remove('d-none');
            });
        });
        
        // دالة تهيئة أزرار الحذف
        function initDeleteButtons() {
            var deleteButtons = document.querySelectorAll('.delete-invoice');
            
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    var invoiceId = this.getAttribute('data-id');
                    var message = 'هل أنت متأكد من حذف الفاتورة رقم ' + invoiceId + '؟';
                    
                    if (confirm(message)) {
                        // إرسال طلب الحذف
                        fetch('invoice_delete.php?id=' + invoiceId, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('تم حذف الفاتورة بنجاح');
                                
                                // إزالة الفاتورة من القائمة
                                var invoiceRow = this.closest('tr');
                                if (invoiceRow) {
                                    invoiceRow.remove();
                                }
                            } else {
                                alert('فشل في حذف الفاتورة: ' + data.error);
                            }
                        })
                        .catch(error => {
                            console.error('خطأ في حذف الفاتورة:', error);
                            alert('حدث خطأ أثناء حذف الفاتورة. الرجاء المحاولة مرة أخرى.');
                        });
                    }
                });
            });
        }
    }
}

/**
 * تهيئة عناصر عرض الفاتورة
 */
function initInvoiceView() {
    // تهيئة عرض الصور
    var imageLinks = document.querySelectorAll('.image-link');
    
    imageLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            var imageUrl = this.getAttribute('href') || this.getAttribute('data-image-url');
            var imageTitle = this.getAttribute('data-image-title') || 'عرض الصورة';
            
            // فتح الصورة في مربع حوار
            var modalHtml = `
            <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imageModalLabel">${imageTitle}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${imageUrl}" class="img-fluid" alt="صورة">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                            <a href="${imageUrl}" class="btn btn-primary" download>تحميل الصورة</a>
                        </div>
                    </div>
                </div>
            </div>
            `;
            
            // إضافة مربع الحوار إلى صفحة الويب إذا لم يكن موجوداً
            var existingModal = document.getElementById('imageModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // فتح مربع الحوار
            var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        });
    });
    
    // تهيئة تغيير حالة الفاتورة
    var statusForm = document.getElementById('statusForm');
    
    if (statusForm) {
        statusForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var invoiceId = this.getAttribute('data-id');
            var status = document.getElementById('status').value;
            
            if (!status) {
                alert('الرجاء اختيار الحالة');
                return;
            }
            
            // إرسال طلب تغيير الحالة
            fetch('invoice_update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + invoiceId + '&status=' + encodeURIComponent(status)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم تحديث حالة الفاتورة بنجاح');
                    
                    // تحديث العرض
                    document.getElementById('currentStatus').textContent = status;
                    
                    // تحديث لون الحالة
                    var statusBadge = document.getElementById('statusBadge');
                    if (statusBadge) {
                        // إزالة جميع فئات الألوان
                        statusBadge.classList.remove('bg-primary', 'bg-info', 'bg-success', 'bg-warning', 'bg-danger', 'bg-secondary', 'bg-dark');
                        
                        // إضافة فئة اللون الجديد
                        var colorClass = 'bg-secondary';
                        switch (status) {
                            case 'جديد':
                                colorClass = 'bg-primary';
                                break;
                            case 'ليث':
                            case 'الوسيط':
                                colorClass = 'bg-info';
                                break;
                            case 'لم يتم الشراء':
                                colorClass = 'bg-danger';
                                break;
                            case 'تم الشراء':
                                colorClass = 'bg-success';
                                break;
                            case 'تم الشحن':
                                colorClass = 'bg-warning';
                                break;
                            case 'مرتجع':
                                colorClass = 'bg-danger';
                                break;
                            case 'مكتمل':
                                colorClass = 'bg-success';
                                break;
                            case 'ملغي':
                                colorClass = 'bg-dark';
                                break;
                        }
                        
                        statusBadge.classList.add(colorClass);
                        statusBadge.textContent = status;
                    }
                } else {
                    alert('فشل في تحديث حالة الفاتورة: ' + data.error);
                }
            })
            .catch(error => {
                console.error('خطأ في تحديث حالة الفاتورة:', error);
                alert('حدث خطأ أثناء تحديث حالة الفاتورة. الرجاء المحاولة مرة أخرى.');
            });
        });
    }
    
    // تهيئة إضافة رقم سلة
    var cartNumberForm = document.getElementById('cartNumberForm');
    
    if (cartNumberForm) {
        cartNumberForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var invoiceId = this.getAttribute('data-id');
            var cartNumber = document.getElementById('cart_number').value;
            
            if (!cartNumber) {
                alert('الرجاء إدخال رقم السلة');
                return;
            }
            
            // إرسال طلب إضافة رقم السلة
            fetch('invoice_add_cart_number.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'id=' + invoiceId + '&cart_number=' + encodeURIComponent(cartNumber)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم إضافة رقم السلة بنجاح');
                    
                    // تحديث العرض
                    document.getElementById('currentCartNumber').textContent = cartNumber;
                    document.getElementById('cart_number').value = '';
                    
                    // إخفاء النموذج وإظهار زر التحرير
                    document.getElementById('cartNumberFormContainer').classList.add('d-none');
                    document.getElementById('editCartNumberBtn').classList.remove('d-none');
                } else {
                    alert('فشل في إضافة رقم السلة: ' + data.error);
                }
            })
            .catch(error => {
                console.error('خطأ في إضافة رقم السلة:', error);
                alert('حدث خطأ أثناء إضافة رقم السلة. الرجاء المحاولة مرة أخرى.');
            });
        });
    }
    
    // تهيئة زر تحرير رقم السلة
    var editCartNumberBtn = document.getElementById('editCartNumberBtn');
    
    if (editCartNumberBtn) {
        editCartNumberBtn.addEventListener('click', function() {
            document.getElementById('cartNumberFormContainer').classList.remove('d-none');
            this.classList.add('d-none');
        });
    }
}

/**
 * تهيئة طباعة الفاتورة
 */
function initInvoicePrint() {
    var printInvoiceBtn = document.getElementById('printInvoiceBtn');
    
    if (printInvoiceBtn) {
        printInvoiceBtn.addEventListener('click', function() {
            window.print();
        });
    }
}
