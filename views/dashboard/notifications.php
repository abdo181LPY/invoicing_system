<?php include_once(ROOT_PATH . '/views/layout/header.php'); ?>

<div class="dashboard-container rtl">
    <?php include_once(ROOT_PATH . '/views/layout/sidebar.php'); ?>

    <div class="content-area">
        <div class="content-header">
            <div class="content-title">
                <h1>الإشعارات</h1>
                <p>إدارة إشعارات النظام</p>
            </div>
            <div class="content-actions">
                <?php if (Auth::hasPermission('admin')): ?>
                <button id="create-notification-btn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> إنشاء إشعار جديد
                </button>
                <?php endif; ?>
                <button id="mark-all-read-btn" class="btn btn-secondary">
                    <i class="fas fa-check-double"></i> تعليم الكل كمقروء
                </button>
            </div>
        </div>

        <div class="content-body">
            <!-- تصفية الإشعارات -->
            <div class="filter-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="notification-type">نوع الإشعار:</label>
                        <select id="notification-type" class="form-control">
                            <option value="">جميع الأنواع</option>
                            <option value="info">معلومات</option>
                            <option value="warning">تحذير</option>
                            <option value="success">نجاح</option>
                            <option value="danger">خطأ</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="notification-read">حالة القراءة:</label>
                        <select id="notification-read" class="form-control">
                            <option value="">الكل</option>
                            <option value="0" selected>غير مقروءة</option>
                            <option value="1">مقروءة</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="notification-date">التاريخ:</label>
                        <select id="notification-date" class="form-control">
                            <option value="">الكل</option>
                            <option value="today">اليوم</option>
                            <option value="week">هذا الأسبوع</option>
                            <option value="month">هذا الشهر</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button id="apply-filter-btn" class="btn btn-primary">تطبيق</button>
                        <button id="reset-filter-btn" class="btn btn-secondary">إعادة تعيين</button>
                    </div>
                </div>
            </div>

            <!-- قائمة الإشعارات -->
            <div class="notifications-container">
                <div class="notifications-loading text-center d-none">
                    <i class="fas fa-spinner fa-pulse fa-3x"></i>
                    <p>جاري تحميل الإشعارات...</p>
                </div>
                
                <div class="notifications-empty text-center d-none">
                    <i class="fas fa-bell-slash fa-3x"></i>
                    <p>لا توجد إشعارات</p>
                </div>
                
                <div class="notifications-list">
                    <!-- سيتم تحميل الإشعارات هنا عن طريق AJAX -->
                </div>
                
                <!-- التنقل بين الصفحات -->
                <div class="pagination-container d-none">
                    <button id="load-more-btn" class="btn btn-outline-primary">تحميل المزيد</button>
                    <span class="pagination-info"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نموذج إنشاء إشعار جديد -->
<?php if (Auth::hasPermission('admin')): ?>
<div class="modal fade" id="create-notification-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إنشاء إشعار جديد</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="create-notification-form">
                    <div class="form-group">
                        <label for="notification-title">عنوان الإشعار <span class="text-danger">*</span></label>
                        <input type="text" id="notification-title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notification-message">نص الإشعار <span class="text-danger">*</span></label>
                        <textarea id="notification-message" name="message" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notification-type-select">نوع الإشعار</label>
                        <select id="notification-type-select" name="type" class="form-control">
                            <option value="info">معلومات</option>
                            <option value="warning">تحذير</option>
                            <option value="success">نجاح</option>
                            <option value="danger">خطأ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notification-link">رابط (اختياري)</label>
                        <input type="text" id="notification-link" name="link" class="form-control">
                        <small class="form-text text-muted">إذا تم النقر على الإشعار، سيتم توجيه المستخدم إلى هذا الرابط</small>
                    </div>
                    
                    <div class="form-group">
                        <label>المستلمين</label>
                        <div class="recipients-options">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="recipients-all" name="recipients-type" class="custom-control-input" value="all" checked>
                                <label class="custom-control-label" for="recipients-all">جميع المستخدمين</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="recipients-roles" name="recipients-type" class="custom-control-input" value="roles">
                                <label class="custom-control-label" for="recipients-roles">حسب الصلاحيات</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="recipients-users" name="recipients-type" class="custom-control-input" value="users">
                                <label class="custom-control-label" for="recipients-users">مستخدمين محددين</label>
                            </div>
                        </div>
                    </div>
                    
                    <div id="roles-container" class="recipient-container d-none">
                        <div class="form-group">
                            <label>الصلاحيات</label>
                            <div class="role-checkboxes">
                                <?php 
                                $roles = [
                                    'admin' => 'مدير',
                                    'supervisor' => 'مشرف',
                                    'accountant' => 'محاسب',
                                    'employee' => 'موظف'
                                ];
                                
                                foreach ($roles as $role => $label): 
                                ?>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" id="role-<?php echo $role; ?>" name="roles[]" value="<?php echo $role; ?>" class="custom-control-input">
                                    <label class="custom-control-label" for="role-<?php echo $role; ?>"><?php echo $label; ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div id="users-container" class="recipient-container d-none">
                        <div class="form-group">
                            <label>المستخدمين</label>
                            <select id="users-select" name="user_ids[]" class="form-control" multiple>
                                <?php
                                $users = $user->getAllUsers();
                                foreach ($users as $userItem):
                                ?>
                                <option value="<?php echo $userItem['id']; ?>"><?php echo $userItem['username']; ?> (<?php echo $userItem['full_name']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">يمكنك اختيار أكثر من مستخدم</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <button type="button" id="send-notification-btn" class="btn btn-primary">إرسال الإشعار</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- قالب الإشعار -->
<template id="notification-template">
    <div class="notification-item" data-id="{id}">
        <div class="notification-header">
            <div class="notification-badge {type}"></div>
            <div class="notification-title">{title}</div>
            <div class="notification-time">{time}</div>
        </div>
        <div class="notification-body">
            <p>{message}</p>
        </div>
        <div class="notification-footer">
            {actions}
        </div>
    </div>
</template>

<script>
$(document).ready(function() {
    // المتغيرات العامة
    var currentPage = 1;
    var totalPages = 1;
    var currentFilters = {
        type: '',
        is_read: '0',
        date: ''
    };
    
    // تحميل الإشعارات عند تحميل الصفحة
    loadNotifications();
    
    // تطبيق الفلتر
    $('#apply-filter-btn').click(function() {
        currentFilters.type = $('#notification-type').val();
        currentFilters.is_read = $('#notification-read').val();
        currentFilters.date = $('#notification-date').val();
        currentPage = 1;
        loadNotifications();
    });
    
    // إعادة تعيين الفلتر
    $('#reset-filter-btn').click(function() {
        $('#notification-type').val('');
        $('#notification-read').val('0');
        $('#notification-date').val('');
        currentFilters = {
            type: '',
            is_read: '0',
            date: ''
        };
        currentPage = 1;
        loadNotifications();
    });
    
    // تحميل المزيد من الإشعارات
    $('#load-more-btn').click(function() {
        if (currentPage < totalPages) {
            currentPage++;
            loadNotifications(true);
        }
    });
    
    // تعليم جميع الإشعارات كمقروءة
    $('#mark-all-read-btn').click(function() {
        if (confirm('هل أنت متأكد من تعليم جميع الإشعارات كمقروءة؟')) {
            $.ajax({
                url: '/notifications/markAllAsRead',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'تم تعليم جميع الإشعارات كمقروءة بنجاح');
                        loadNotifications();
                    } else {
                        showAlert('danger', response.error || 'حدث خطأ أثناء تعليم الإشعارات كمقروءة');
                    }
                },
                error: function() {
                    showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
                }
            });
        }
    });
    
    // إظهار نموذج إنشاء إشعار جديد
    $('#create-notification-btn').click(function() {
        $('#create-notification-modal').modal('show');
    });
    
    // تبديل عرض خيارات المستلمين
    $('input[name="recipients-type"]').change(function() {
        $('.recipient-container').addClass('d-none');
        
        if ($(this).val() === 'roles') {
            $('#roles-container').removeClass('d-none');
        } else if ($(this).val() === 'users') {
            $('#users-container').removeClass('d-none');
        }
    });
    
    // إرسال الإشعار الجديد
    $('#send-notification-btn').click(function() {
        var form = $('#create-notification-form');
        var formData = {
            title: $('#notification-title').val(),
            message: $('#notification-message').val(),
            type: $('#notification-type-select').val(),
            link: $('#notification-link').val()
        };
        
        // التحقق من الحقول الإلزامية
        if (!formData.title || !formData.message) {
            showAlert('danger', 'يرجى ملء جميع الحقول المطلوبة');
            return;
        }
        
        // إضافة المستلمين حسب النوع المحدد
        var recipientsType = $('input[name="recipients-type"]:checked').val();
        
        if (recipientsType === 'roles') {
            formData.roles = [];
            $('input[name="roles[]"]:checked').each(function() {
                formData.roles.push($(this).val());
            });
            
            if (formData.roles.length === 0) {
                showAlert('danger', 'يرجى اختيار صلاحية واحدة على الأقل');
                return;
            }
        } else if (recipientsType === 'users') {
            formData.user_ids = $('#users-select').val();
            
            if (!formData.user_ids || formData.user_ids.length === 0) {
                showAlert('danger', 'يرجى اختيار مستخدم واحد على الأقل');
                return;
            }
        }
        
        // إرسال الطلب
        $.ajax({
            url: '/notifications/create',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'تم إنشاء الإشعار بنجاح');
                    $('#create-notification-modal').modal('hide');
                    form[0].reset();
                    loadNotifications();
                } else {
                    showAlert('danger', response.error || 'حدث خطأ أثناء إنشاء الإشعار');
                }
            },
            error: function() {
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    });
    
    // تعليم إشعار كمقروء (تفويض الحدث)
    $(document).on('click', '.mark-read-btn', function() {
        var notificationId = $(this).data('id');
        
        $.ajax({
            url: '/notifications/markAsRead',
            type: 'POST',
            data: { notification_id: notificationId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // تحديث واجهة المستخدم
                    var item = $('.notification-item[data-id="' + notificationId + '"]');
                    item.removeClass('unread').addClass('read');
                    item.find('.mark-read-btn').remove();
                    
                    // تحديث عداد الإشعارات في الشريط الجانبي
                    var unreadCount = parseInt($('#sidebar-notification-count').text()) || 0;
                    if (unreadCount > 0) {
                        unreadCount--;
                        $('#sidebar-notification-count').text(unreadCount);
                        if (unreadCount === 0) {
                            $('#sidebar-notification-count').addClass('d-none');
                        }
                    }
                } else {
                    showAlert('danger', response.error || 'حدث خطأ أثناء تعليم الإشعار كمقروء');
                }
            },
            error: function() {
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    });
    
    // تحميل الإشعارات
    function loadNotifications(append) {
        var container = $('.notifications-list');
        var loadingElement = $('.notifications-loading');
        var emptyElement = $('.notifications-empty');
        var paginationContainer = $('.pagination-container');
        
        if (!append) {
            container.empty();
            emptyElement.addClass('d-none');
            paginationContainer.addClass('d-none');
        }
        
        loadingElement.removeClass('d-none');
        
        // بناء عنوان URL مع المعايير
        var url = '/notifications/getAll?page=' + currentPage;
        
        if (currentFilters.type) {
            url += '&type=' + currentFilters.type;
        }
        
        if (currentFilters.is_read !== '') {
            url += '&is_read=' + currentFilters.is_read;
        }
        
        if (currentFilters.date) {
            url += '&date=' + currentFilters.date;
        }
        
        // طلب الإشعارات
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                loadingElement.addClass('d-none');
                
                if (response.success) {
                    var notifications = response.data;
                    totalPages = response.pages || 1;
                    
                    if (notifications.length === 0 && !append) {
                        emptyElement.removeClass('d-none');
                    } else {
                        // إضافة الإشعارات إلى القائمة
                        notifications.forEach(function(notification) {
                            container.append(createNotificationElement(notification));
                        });
                        
                        // تحديث معلومات التنقل بين الصفحات
                        updatePagination(response.page, response.pages, response.total);
                    }
                } else {
                    showAlert('danger', response.error || 'حدث خطأ أثناء تحميل الإشعارات');
                }
            },
            error: function() {
                loadingElement.addClass('d-none');
                showAlert('danger', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    }
    
    // إنشاء عنصر الإشعار
    function createNotificationElement(notification) {
        var template = $('#notification-template').html();
        var readClass = notification.is_read == 1 ? 'read' : 'unread';
        var formattedTime = formatTime(notification.created_at);
        var actions = '';
        
        // إضافة روابط الإجراءات
        if (notification.link) {
            actions += '<a href="' + notification.link + '" class="btn btn-sm btn-outline-primary">عرض</a>';
        }
        
        if (notification.is_read == 0) {
            actions += '<button class="btn btn-sm btn-outline-secondary mark-read-btn" data-id="' + notification.id + '">تعليم كمقروء</button>';
        }
        
        // استبدال القيم في القالب
        var element = template
            .replace('{id}', notification.id)
            .replace('{type}', notification.type || 'info')
            .replace('{title}', notification.title)
            .replace('{message}', notification.message)
            .replace('{time}', formattedTime)
            .replace('{actions}', actions);
        
        return '<div class="notification-item ' + readClass + '" data-id="' + notification.id + '">' + 
               element.substring(element.indexOf('>') + 1);
    }
    
    // تحديث عناصر التنقل بين الصفحات
    function updatePagination(page, pages, total) {
        var paginationContainer = $('.pagination-container');
        var infoElement = $('.pagination-info');
        var loadMoreBtn = $('#load-more-btn');
        
        paginationContainer.removeClass('d-none');
        
        if (page >= pages) {
            loadMoreBtn.addClass('d-none');
        } else {
            loadMoreBtn.removeClass('d-none');
        }
        
        // تحديث معلومات الصفحات
        infoElement.text('عرض ' + page + ' من ' + pages + ' صفحات (إجمالي: ' + total + ' إشعار)');
    }
    
    // تنسيق وقت الإشعار
    function formatTime(dateTime) {
        var date = new Date(dateTime);
        var now = new Date();
        var diffMs = now - date;
        var diffSec = Math.floor(diffMs / 1000);
        var diffMin = Math.floor(diffSec / 60);
        var diffHours = Math.floor(diffMin / 60);
        var diffDays = Math.floor(diffHours / 24);
        
        if (diffSec < 60) {
            return 'منذ ' + diffSec + ' ثانية';
        } else if (diffMin < 60) {
            return 'منذ ' + diffMin + ' دقيقة';
        } else if (diffHours < 24) {
            return 'منذ ' + diffHours + ' ساعة';
        } else if (diffDays < 7) {
            return 'منذ ' + diffDays + ' يوم';
        } else {
            return date.toLocaleDateString('ar-IQ') + ' ' + date.toLocaleTimeString('ar-IQ', {hour: '2-digit', minute:'2-digit'});
        }
    }
    
    // عرض رسالة تنبيه
    function showAlert(type, message) {
        var alertElement = $('<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                            message +
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                            '<span aria-hidden="true">&times;</span>' +
                            '</button>' +
                            '</div>');
        
        $('.content-header').after(alertElement);
        
        // إخفاء التنبيه تلقائيًا بعد 5 ثوانٍ
        setTimeout(function() {
            alertElement.alert('close');
        }, 5000);
    }
});
</script>

<?php include_once(ROOT_PATH . '/views/layout/footer.php'); ?>
