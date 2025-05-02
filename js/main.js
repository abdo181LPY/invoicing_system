/**
 * الملف الرئيسي لـ JavaScript
 * يحتوي على الدوال المشتركة المستخدمة في جميع صفحات التطبيق
 */

document.addEventListener('DOMContentLoaded', function() {
    // تهيئة التلميحات
    initTooltips();
    
    // تهيئة مربعات الحوار
    initModals();
    
    // تهيئة عناصر التنبيهات
    initAlerts();
    
    // تهيئة عناصر النسخ
    initClipboard();
    
    // تهيئة عناصر عرض الصور
    initImageViewer();
    
    // تهيئة الإشعارات
    initNotifications();
    
    // تهيئة القائمة الجانبية
    initSidebar();
});

/**
 * تهيئة التلميحات (Tooltips)
 */
function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * تهيئة مربعات الحوار (Modals)
 */
function initModals() {
    // مربع حوار التأكيد
    var confirmModal = document.getElementById('confirmModal');
    if (confirmModal) {
        confirmModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var confirmAction = document.getElementById('confirmAction');
            
            // تحديث رسالة التأكيد إذا تم تحديدها
            var message = button.getAttribute('data-confirm-message');
            if (message) {
                confirmModal.querySelector('.modal-body').textContent = message;
            }
            
            // تحديث عنوان التأكيد إذا تم تحديده
            var title = button.getAttribute('data-confirm-title');
            if (title) {
                confirmModal.querySelector('.modal-title').textContent = title;
            }
            
            // تحديث نص زر التأكيد إذا تم تحديده
            var buttonText = button.getAttribute('data-confirm-button');
            if (buttonText) {
                confirmAction.textContent = buttonText;
            }
            
            // تحديث لون زر التأكيد إذا تم تحديده
            var buttonClass = button.getAttribute('data-confirm-button-class');
            if (buttonClass) {
                confirmAction.className = 'btn ' + buttonClass;
            }
            
            // تحديث الرابط
            var href = button.getAttribute('data-confirm-href');
            if (href) {
                confirmAction.setAttribute('href', href);
            }
            
            // دعم النماذج
            var formId = button.getAttribute('data-confirm-form');
            if (formId) {
                var form = document.getElementById(formId);
                confirmAction.addEventListener('click', function(e) {
                    e.preventDefault();
                    form.submit();
                }, { once: true });
            }
        });
    }
    
    // مربع حوار عرض الصورة
    var imageModal = document.getElementById('imageModal');
    if (imageModal) {
        imageModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var imageUrl = button.getAttribute('data-image-url');
            var imageTitle = button.getAttribute('data-image-title') || 'عرض الصورة';
            
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('imageModalLabel').textContent = imageTitle;
        });
    }
}

/**
 * تهيئة عناصر التنبيهات (Alerts)
 */
function initAlerts() {
    // إخفاء التنبيهات تلقائياً بعد 5 ثوان
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
}

/**
 * تهيئة عناصر النسخ (Clipboard)
 */
function initClipboard() {
    var clipboardButtons = document.querySelectorAll('.copy-btn');
    
    clipboardButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var textToCopy = this.getAttribute('data-clipboard-text');
            var originalText = this.innerHTML;
            
            if (textToCopy) {
                // نسخ النص إلى الحافظة
                navigator.clipboard.writeText(textToCopy).then(function() {
                    // تغيير نص الزر بعد النسخ
                    button.innerHTML = '<i class="fas fa-check me-2"></i>تم النسخ';
                    
                    // إعادة النص الأصلي بعد 2 ثانية
                    setTimeout(function() {
                        button.innerHTML = originalText;
                    }, 2000);
                }).catch(function() {
                    // حدث خطأ أثناء النسخ
                    button.innerHTML = '<i class="fas fa-times me-2"></i>فشل النسخ';
                    
                    // إعادة النص الأصلي بعد 2 ثانية
                    setTimeout(function() {
                        button.innerHTML = originalText;
                    }, 2000);
                });
            }
        });
    });
}

/**
 * تهيئة عرض الصور
 */
function initImageViewer() {
    var imageLinks = document.querySelectorAll('.image-view');
    
    imageLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            var imageUrl = this.getAttribute('href') || this.getAttribute('data-image-url');
            var imageTitle = this.getAttribute('data-image-title') || 'عرض الصورة';
            
            // إنشاء مربع حوار ديناميكي إذا لم يكن موجوداً
            var imageModal = document.getElementById('imageModal');
            
            if (!imageModal) {
                // إنشاء مربع حوار جديد
                var modalHtml = `
                <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="imageModalLabel">${imageTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img src="${imageUrl}" id="modalImage" class="img-fluid" alt="صورة">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                            </div>
                        </div>
                    </div>
                </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                imageModal = document.getElementById('imageModal');
            } else {
                // تحديث الصورة والعنوان
                document.getElementById('modalImage').src = imageUrl;
                document.getElementById('imageModalLabel').textContent = imageTitle;
            }
            
            // فتح مربع الحوار
            var modal = new bootstrap.Modal(imageModal);
            modal.show();
        });
    });
}

/**
 * تهيئة الإشعارات
 */
function initNotifications() {
    var notificationsDropdown = document.querySelector('.notifications-dropdown');
    
    if (notificationsDropdown) {
        // تعيين جميع الإشعارات كمقروءة
        var markAllReadBtn = notificationsDropdown.querySelector('.mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function() {
                fetch('notifications_mark_all_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        var unreadBadges = document.querySelectorAll('.notifications-dropdown .badge');
                        unreadBadges.forEach(function(badge) {
                            badge.style.display = 'none';
                        });
                        
                        var unreadItems = document.querySelectorAll('.notifications-dropdown .notification-item.unread');
                        unreadItems.forEach(function(item) {
                            item.classList.remove('unread');
                            item.classList.add('read');
                        });
                        
                        markAllReadBtn.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('خطأ في تعيين الإشعارات كمقروءة:', error);
                });
            });
        }
        
        // تعيين إشعار معين كمقروء
        var markAsReadBtns = notificationsDropdown.querySelectorAll('.mark-as-read');
        markAsReadBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var notificationId = this.getAttribute('data-id');
                var notificationItem = this.closest('.notification-item');
                
                fetch('notifications_mark_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ id: notificationId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        notificationItem.classList.remove('unread');
                        notificationItem.classList.add('read');
                        this.style.display = 'none';
                        
                        // تحديث عدد الإشعارات غير المقروءة
                        var unreadCount = parseInt(data.unread_count);
                        var unreadBadge = document.querySelector('.notifications-dropdown .badge');
                        
                        if (unreadBadge) {
                            if (unreadCount > 0) {
                                unreadBadge.textContent = unreadCount;
                            } else {
                                unreadBadge.style.display = 'none';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('خطأ في تعيين الإشعار كمقروء:', error);
                });
            });
        });
    }
}

/**
 * تهيئة القائمة الجانبية
 */
function initSidebar() {
    var sidebarToggle = document.getElementById('sidebarToggle');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
            
            // حفظ حالة القائمة الجانبية في التخزين المحلي
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
        });
        
        // استرجاع حالة القائمة الجانبية من التخزين المحلي
        var sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
        if (sidebarCollapsed === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
    }
}

/**
 * دالة لتأكيد الإجراءات
 */
function confirmAction(button, callback) {
    var confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    
    // تحديث رسالة التأكيد إذا تم تحديدها
    var message = button.getAttribute('data-confirm-message');
    if (message) {
        document.querySelector('#confirmModal .modal-body').textContent = message;
    }
    
    // تحديث عنوان التأكيد إذا تم تحديده
    var title = button.getAttribute('data-confirm-title');
    if (title) {
        document.querySelector('#confirmModal .modal-title').textContent = title;
    }
    
    // تحديث نص زر التأكيد إذا تم تحديده
    var buttonText = button.getAttribute('data-confirm-button');
    if (buttonText) {
        document.getElementById('confirmAction').textContent = buttonText;
    }
    
    // تحديث لون زر التأكيد إذا تم تحديده
    var buttonClass = button.getAttribute('data-confirm-button-class');
    if (buttonClass) {
        document.getElementById('confirmAction').className = 'btn ' + buttonClass;
    }
    
    // تعيين دالة الاستدعاء
    document.getElementById('confirmAction').onclick = function() {
        confirmModal.hide();
        callback();
        return false;
    };
    
    // عرض مربع الحوار
    confirmModal.show();
}

/**
 * دالة للتحقق من صحة رقم الهاتف العراقي
 */
function validateIraqiPhone(phone) {
    var pattern = /^07[0-9]{9}$/;
    return pattern.test(phone);
}

/**
 * دالة لإظهار وإخفاء كلمة المرور
 */
function togglePasswordVisibility(inputId, buttonId) {
    var passwordInput = document.getElementById(inputId);
    var toggleButton = document.getElementById(buttonId);
    
    if (passwordInput && toggleButton) {
        toggleButton.addEventListener('click', function() {
            var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            var icon = toggleButton.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
}

/**
 * دالة للتحقق من تطابق كلمتي المرور
 */
function validatePasswordsMatch(password1Id, password2Id, formId) {
    var form = document.getElementById(formId);
    var password1 = document.getElementById(password1Id);
    var password2 = document.getElementById(password2Id);
    
    if (form && password1 && password2) {
        form.addEventListener('submit', function(e) {
            if (password1.value !== password2.value) {
                e.preventDefault();
                alert('كلمة المرور وتأكيدها غير متطابقين');
                password2.focus();
            }
        });
    }
}

/**
 * دالة لإعادة توجيه
 */
function redirect(url) {
    window.location.href = url;
}

/**
 * دالة للتحقق من وجود رقم هاتف في القائمة السوداء
 */
function checkBlacklist(phone, callback) {
    fetch('blacklist_check.php?phone=' + encodeURIComponent(phone), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        callback(data);
    })
    .catch(error => {
        console.error('خطأ في التحقق من القائمة السوداء:', error);
        callback({ error: 'حدث خطأ أثناء التحقق' });
    });
}
