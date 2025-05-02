</div>
    <!-- نهاية محتوى الصفحة -->
    
    <!-- تذييل الصفحة -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - جميع الحقوق محفوظة</span>
                <span class="text-muted">الإصدار 1.0.0</span>
            </div>
        </div>
    </footer>
    
    <!-- نوافذ الحوار المشتركة -->
    
    <!-- نافذة حوار التأكيد -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalLabel">تأكيد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    هل أنت متأكد من أنك تريد القيام بهذا الإجراء؟
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <a href="#" class="btn btn-primary" id="confirmAction">تأكيد</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- نافذة حوار عرض الصورة -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">عرض الصورة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" id="modalImage" class="img-fluid" alt="صورة">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- سكريبت التنبيهات -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // إخفاء التنبيهات تلقائياً بعد 5 ثوان
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // تهيئة مربعات الحوار
            const confirmModal = document.getElementById('confirmModal');
            if (confirmModal) {
                confirmModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const confirmAction = document.getElementById('confirmAction');
                    
                    // تحديث رسالة التأكيد إذا تم تحديدها
                    const message = button.getAttribute('data-confirm-message');
                    if (message) {
                        confirmModal.querySelector('.modal-body').textContent = message;
                    }
                    
                    // تحديث عنوان التأكيد إذا تم تحديده
                    const title = button.getAttribute('data-confirm-title');
                    if (title) {
                        confirmModal.querySelector('.modal-title').textContent = title;
                    }
                    
                    // تحديث نص زر التأكيد إذا تم تحديده
                    const buttonText = button.getAttribute('data-confirm-button');
                    if (buttonText) {
                        confirmAction.textContent = buttonText;
                    }
                    
                    // تحديث لون زر التأكيد إذا تم تحديده
                    const buttonClass = button.getAttribute('data-confirm-button-class');
                    if (buttonClass) {
                        confirmAction.className = 'btn ' + buttonClass;
                    }
                    
                    // تحديث الرابط
                    const href = button.getAttribute('data-confirm-href');
                    if (href) {
                        confirmAction.setAttribute('href', href);
                    }
                    
                    // دعم النماذج
                    const formId = button.getAttribute('data-confirm-form');
                    if (formId) {
                        const form = document.getElementById(formId);
                        confirmAction.addEventListener('click', function(e) {
                            e.preventDefault();
                            form.submit();
                        }, { once: true });
                    }
                });
            }
            
            // تهيئة عرض الصور
            const imageModal = document.getElementById('imageModal');
            if (imageModal) {
                imageModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const imageUrl = button.getAttribute('data-image-url');
                    const imageTitle = button.getAttribute('data-image-title') || 'عرض الصورة';
                    
                    document.getElementById('modalImage').src = imageUrl;
                    document.getElementById('imageModalLabel').textContent = imageTitle;
                });
            }
        });
        
        // دالة لتأكيد الإجراءات
        function confirmAction(button, callback) {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            
            // تحديث رسالة التأكيد إذا تم تحديدها
            const message = button.getAttribute('data-confirm-message');
            if (message) {
                document.querySelector('#confirmModal .modal-body').textContent = message;
            }
            
            // تحديث عنوان التأكيد إذا تم تحديده
            const title = button.getAttribute('data-confirm-title');
            if (title) {
                document.querySelector('#confirmModal .modal-title').textContent = title;
            }
            
            // تحديث نص زر التأكيد إذا تم تحديده
            const buttonText = button.getAttribute('data-confirm-button');
            if (buttonText) {
                document.getElementById('confirmAction').textContent = buttonText;
            }
            
            // تحديث لون زر التأكيد إذا تم تحديده
            const buttonClass = button.getAttribute('data-confirm-button-class');
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
    </script>
    
    <?php if (isset($pageScripts)): ?>
    <!-- سكريبتات خاصة بالصفحة -->
    <?php foreach ($pageScripts as $script): ?>
    <script src="<?php echo base_url($script); ?>" defer></script>
    <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>