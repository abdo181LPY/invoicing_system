/**
 * نظام الإشعارات
 */

// كائن نظام الإشعارات
const NotificationSystem = {
    // الإعدادات الافتراضية
    settings: {
        pollInterval: 30000, // الفترة الزمنية للتحقق من الإشعارات الجديدة (30 ثانية)
        notificationSound: '/sounds/notification.mp3', // صوت الإشعار
        desktopNotifications: true, // تفعيل إشعارات سطح المكتب
        maxNotificationsShown: 5, // أقصى عدد من الإشعارات المعروضة
        notificationTimeout: 5000 // مدة ظهور الإشعار (5 ثوان)
    },
    
    // عناصر DOM
    elements: {
        notificationBell: null,
        notificationCount: null,
        notificationDropdown: null,
        notificationList: null
    },
    
    // متغيرات الحالة
    state: {
        isInitialized: false,
        unreadCount: 0,
        notifications: [],
        pollingTimer: null,
        isDropdownVisible: false,
        permissionGranted: false
    },
    
    /**
     * تهيئة نظام الإشعارات
     */
    init: function(options = {}) {
        // تحديث الإعدادات بالخيارات المقدمة
        this.settings = { ...this.settings, ...options };
        
        // تحديد عناصر DOM
        this.elements.notificationBell = document.getElementById('notification-bell');
        this.elements.notificationCount = document.getElementById('notification-count');
        this.elements.notificationDropdown = document.getElementById('notification-dropdown');
        this.elements.notificationList = document.getElementById('notification-list');
        
        // إذا لم يتم العثور على العناصر المطلوبة، توقف التهيئة
        if (!this.elements.notificationBell || !this.elements.notificationCount || 
            !this.elements.notificationDropdown || !this.elements.notificationList) {
            console.error('لم يتم العثور على العناصر المطلوبة لنظام الإشعارات');
            return;
        }
        
        // تهيئة الأحداث
        this.initEvents();
        
        // طلب إذن إشعارات سطح المكتب
        this.requestNotificationPermission();
        
        // بدء مؤقت التحقق من الإشعارات الجديدة
        this.startPolling();
        
        // تحديث العداد
        this.updateNotificationCount();
        
        // تعيين حالة التهيئة
        this.state.isInitialized = true;
        
        console.log('تم تهيئة نظام الإشعارات');
    },
    
    /**
     * تهيئة الأحداث
     */
    initEvents: function() {
        // حدث النقر على أيقونة الجرس
        this.elements.notificationBell.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggleNotificationDropdown();
        });
        
        // إغلاق القائمة المنسدلة عند النقر خارجها
        document.addEventListener('click', (e) => {
            if (this.state.isDropdownVisible && 
                !this.elements.notificationDropdown.contains(e.target) && 
                !this.elements.notificationBell.contains(e.target)) {
                this.hideNotificationDropdown();
            }
        });
        
        // حدث النقر على "تعليم الكل كمقروء"
        const markAllReadBtn = document.getElementById('mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.markAllAsRead();
            });
        }
    },
    
    /**
     * طلب إذن إشعارات سطح المكتب
     */
    requestNotificationPermission: function() {
        if (!this.settings.desktopNotifications) return;
        
        if (!('Notification' in window)) {
            console.log('هذا المتصفح لا يدعم إشعارات سطح المكتب');
            return;
        }
        
        if (Notification.permission === 'granted') {
            this.state.permissionGranted = true;
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    this.state.permissionGranted = true;
                }
            });
        }
    },
    
    /**
     * بدء مؤقت التحقق من الإشعارات الجديدة
     */
    startPolling: function() {
        // إلغاء المؤقت القديم إن وجد
        if (this.state.pollingTimer) {
            clearInterval(this.state.pollingTimer);
        }
        
        // التحقق من الإشعارات الجديدة فورًا
        this.fetchNotifications();
        
        // بدء مؤقت جديد
        this.state.pollingTimer = setInterval(() => {
            this.fetchNotifications();
        }, this.settings.pollInterval);
    },
    
    /**
     * إيقاف مؤقت التحقق من الإشعارات الجديدة
     */
    stopPolling: function() {
        if (this.state.pollingTimer) {
            clearInterval(this.state.pollingTimer);
            this.state.pollingTimer = null;
        }
    },
    
    /**
     * جلب الإشعارات من الخادم
     */
    fetchNotifications: function() {
        fetch('/notifications/getUnread', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const newCount = data.count;
                
                // إذا كان هناك إشعارات جديدة
                if (newCount > this.state.unreadCount) {
                    const newNotifications = data.data.slice(0, newCount - this.state.unreadCount);
                    this.handleNewNotifications(newNotifications);
                }
                
                // تحديث الحالة
                this.state.unreadCount = newCount;
                this.state.notifications = data.data;
                
                // تحديث العداد والقائمة
                this.updateNotificationCount();
                this.renderNotificationList();
            }
        })
        .catch(error => {
            console.error('خطأ في جلب الإشعارات:', error);
        });
    },
    
    /**
     * التعامل مع الإشعارات الجديدة
     */
    handleNewNotifications: function(notifications) {
        if (!notifications || notifications.length === 0) return;
        
        // عرض الإشعارات الجديدة
        notifications.forEach(notification => {
            // إشعار داخل الصفحة
            this.showInPageNotification(notification);
            
            // إشعار سطح المكتب
            if (this.settings.desktopNotifications && this.state.permissionGranted) {
                this.showDesktopNotification(notification);
            }
        });
        
        // تشغيل صوت الإشعار
        this.playNotificationSound();
    },
    
    /**
     * تشغيل صوت الإشعار
     */
    playNotificationSound: function() {
        const audio = new Audio(this.settings.notificationSound);
        audio.play().catch(e => {
            console.log('لم يتمكن من تشغيل صوت الإشعار', e);
        });
    },
    
    /**
     * عرض إشعار داخل الصفحة
     */
    showInPageNotification: function(notification) {
        // إنشاء عنصر الإشعار
        const notificationElement = document.createElement('div');
        notificationElement.className = 'in-page-notification ' + (notification.type || 'info');
        
        // إضافة محتوى الإشعار
        notificationElement.innerHTML = `
            <div class="notification-title">${notification.title}</div>
            <div class="notification-message">${notification.message}</div>
            <button class="notification-close">&times;</button>
        `;
        
        // إضافة الإشعار إلى الصفحة
        document.body.appendChild(notificationElement);
        
        // تأخير قليل قبل إظهار الإشعار (للتأثير المرئي)
        setTimeout(() => {
            notificationElement.classList.add('show');
        }, 100);
        
        // زر الإغلاق
        const closeButton = notificationElement.querySelector('.notification-close');
        closeButton.addEventListener('click', () => {
            notificationElement.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notificationElement);
            }, 300);
        });
        
        // إخفاء الإشعار تلقائيًا بعد فترة
        setTimeout(() => {
            if (document.body.contains(notificationElement)) {
                notificationElement.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(notificationElement)) {
                        document.body.removeChild(notificationElement);
                    }
                }, 300);
            }
        }, this.settings.notificationTimeout);
    },
    
    /**
     * عرض إشعار سطح المكتب
     */
    showDesktopNotification: function(notification) {
        if (!this.state.permissionGranted) return;
        
        const title = notification.title || 'إشعار جديد';
        const options = {
            body: notification.message,
            icon: '/images/logo.png',
            tag: 'notification-' + notification.id
        };
        
        const desktopNotification = new Notification(title, options);
        
        // عند النقر على الإشعار
        desktopNotification.onclick = () => {
            window.focus();
            if (notification.link) {
                window.location.href = notification.link;
            }
            desktopNotification.close();
        };
        
        // إغلاق الإشعار تلقائيًا بعد فترة
        setTimeout(() => {
            desktopNotification.close();
        }, this.settings.notificationTimeout);
    },
    
    /**
     * تحديث عداد الإشعارات غير المقروءة
     */
    updateNotificationCount: function() {
        if (this.state.unreadCount > 0) {
            this.elements.notificationCount.textContent = this.state.unreadCount;
            this.elements.notificationCount.classList.add('has-notifications');
        } else {
            this.elements.notificationCount.textContent = '';
            this.elements.notificationCount.classList.remove('has-notifications');
        }
    },
    
    /**
     * عرض قائمة الإشعارات
     */
    renderNotificationList: function() {
        // حذف جميع الإشعارات الموجودة
        this.elements.notificationList.innerHTML = '';
        
        // إذا لم تكن هناك إشعارات
        if (!this.state.notifications || this.state.notifications.length === 0) {
            const emptyItem = document.createElement('li');
            emptyItem.className = 'empty-notification';
            emptyItem.textContent = 'لا توجد إشعارات';
            this.elements.notificationList.appendChild(emptyItem);
            return;
        }
        
        // إضافة الإشعارات إلى القائمة
        this.state.notifications.slice(0, this.settings.maxNotificationsShown).forEach(notification => {
            const listItem = document.createElement('li');
            listItem.className = 'notification-item';
            
            if (!notification.is_read) {
                listItem.classList.add('unread');
            }
            
            listItem.dataset.id = notification.id;
            
            // تنسيق التاريخ
            const date = new Date(notification.created_at);
            const formattedDate = date.toLocaleDateString('ar-IQ', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // تعيين محتوى الإشعار
            listItem.innerHTML = `
                <div class="notification-header">
                    <span class="notification-title ${notification.type || 'info'}">${notification.title}</span>
                    <span class="notification-time">${formattedDate}</span>
                </div>
                <div class="notification-body">
                    <p>${notification.message}</p>
                </div>
                <div class="notification-actions">
                    ${notification.link ? `<a href="${notification.link}" class="btn-view">عرض</a>` : ''}
                    ${!notification.is_read ? `<button class="btn-mark-read" data-id="${notification.id}">تعليم كمقروء</button>` : ''}
                </div>
            `;
            
            // إضافة العنصر إلى القائمة
            this.elements.notificationList.appendChild(listItem);
        });
        
        // إضافة "عرض الكل" إذا كان هناك المزيد من الإشعارات
        if (this.state.notifications.length > this.settings.maxNotificationsShown) {
            const viewAllItem = document.createElement('li');
            viewAllItem.className = 'view-all-notifications';
            viewAllItem.innerHTML = `<a href="/notifications">عرض جميع الإشعارات (${this.state.notifications.length})</a>`;
            this.elements.notificationList.appendChild(viewAllItem);
        }
        
        // إضافة أحداث الأزرار
        this.addNotificationItemEvents();
    },
    
    /**
     * إضافة أحداث لعناصر الإشعارات
     */
    addNotificationItemEvents: function() {
        // أزرار "تعليم كمقروء"
        const markReadButtons = this.elements.notificationList.querySelectorAll('.btn-mark-read');
        markReadButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const notificationId = button.dataset.id;
                this.markAsRead(notificationId);
            });
        });
        
        // النقر على الإشعار للانتقال إلى الرابط
        const notificationItems = this.elements.notificationList.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', () => {
                const link = item.querySelector('.btn-view');
                if (link) {
                    window.location.href = link.getAttribute('href');
                }
            });
        });
    },
    
    /**
     * تعليم إشعار كمقروء
     */
    markAsRead: function(notificationId) {
        fetch('/notifications/markAsRead', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ notification_id: notificationId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // تحديث الإشعارات في الذاكرة
                this.state.notifications = this.state.notifications.map(notification => {
                    if (notification.id == notificationId) {
                        notification.is_read = 1;
                    }
                    return notification;
                });
                
                // تحديث عدد الإشعارات غير المقروءة
                this.state.unreadCount = Math.max(0, this.state.unreadCount - 1);
                
                // تحديث العرض
                this.updateNotificationCount();
                this.renderNotificationList();
            }
        })
        .catch(error => {
            console.error('خطأ في تعليم الإشعار كمقروء:', error);
        });
    },
    
    /**
     * تعليم جميع الإشعارات كمقروءة
     */
    markAllAsRead: function() {
        fetch('/notifications/markAllAsRead', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // تحديث الإشعارات في الذاكرة
                this.state.notifications = this.state.notifications.map(notification => {
                    notification.is_read = 1;
                    return notification;
                });
                
                // تحديث عدد الإشعارات غير المقروءة
                this.state.unreadCount = 0;
                
                // تحديث العرض
                this.updateNotificationCount();
                this.renderNotificationList();
            }
        })
        .catch(error => {
            console.error('خطأ في تعليم جميع الإشعارات كمقروءة:', error);
        });
    },
    
    /**
     * إظهار/إخفاء قائمة الإشعارات
     */
    toggleNotificationDropdown: function() {
        if (this.state.isDropdownVisible) {
            this.hideNotificationDropdown();
        } else {
            this.showNotificationDropdown();
        }
    },
    
    /**
     * إظهار قائمة الإشعارات
     */
    showNotificationDropdown: function() {
        if (this.state.isDropdownVisible) return;
        
        this.elements.notificationDropdown.classList.add('show');
        this.state.isDropdownVisible = true;
        
        // جلب أحدث الإشعارات عند فتح القائمة
        this.fetchNotifications();
    },
    
    /**
     * إخفاء قائمة الإشعارات
     */
    hideNotificationDropdown: function() {
        if (!this.state.isDropdownVisible) return;
        
        this.elements.notificationDropdown.classList.remove('show');
        this.state.isDropdownVisible = false;
    }
};

// تصدير كائن نظام الإشعارات
window.NotificationSystem = NotificationSystem;

// تهيئة نظام الإشعارات عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    NotificationSystem.init();
});
