<?php
/**
 * توجيهات واجهة برمجة التطبيقات (API)
 * يحدد مسارات API والمتحكمات المرتبطة بها
 */

/**
 * تعريف المسارات
 * 
 * تنسيق المسار:
 * [
 *      'path' => '/example/{param}',     // مسار API مع معلمات اختيارية محاطة بأقواس مجعدة
 *      'method' => 'GET',                // طريقة الطلب (GET, POST, PUT, DELETE)
 *      'controller' => 'ExampleController', // اسم فئة المتحكم
 *      'action' => 'exampleAction',      // اسم دالة الإجراء في المتحكم
 *      'auth' => true                    // هل المصادقة مطلوبة؟
 * ]
 */
$routes = [
    // مسارات المصادقة
    [
        'path' => '/auth/login',
        'method' => 'POST',
        'controller' => 'UserController',
        'action' => 'apiLogin',
        'auth' => false
    ],
    [
        'path' => '/auth/register',
        'method' => 'POST',
        'controller' => 'UserController',
        'action' => 'apiRegister',
        'auth' => false
    ],
    [
        'path' => '/auth/logout',
        'method' => 'POST',
        'controller' => 'UserController',
        'action' => 'apiLogout',
        'auth' => true
    ],
    
    // مسارات الفواتير
    [
        'path' => '/invoices',
        'method' => 'GET',
        'controller' => 'InvoiceController',
        'action' => 'apiGetAll',
        'auth' => true
    ],
    [
        'path' => '/invoices/{id}',
        'method' => 'GET',
        'controller' => 'InvoiceController',
        'action' => 'apiGetById',
        'auth' => true
    ],
    [
        'path' => '/invoices',
        'method' => 'POST',
        'controller' => 'InvoiceController',
        'action' => 'apiCreate',
        'auth' => true
    ],
    [
        'path' => '/invoices/{id}',
        'method' => 'PUT',
        'controller' => 'InvoiceController',
        'action' => 'apiUpdate',
        'auth' => true
    ],
    [
        'path' => '/invoices/{id}',
        'method' => 'DELETE',
        'controller' => 'InvoiceController',
        'action' => 'apiDelete',
        'auth' => true
    ],
    [
        'path' => '/invoices/{id}/status',
        'method' => 'PUT',
        'controller' => 'InvoiceController',
        'action' => 'apiUpdateStatus',
        'auth' => true
    ],
    [
        'path' => '/invoices/{id}/cart-number',
        'method' => 'PUT',
        'controller' => 'InvoiceController',
        'action' => 'apiAddCartNumber',
        'auth' => true
    ],
    [
        'path' => '/invoices/search',
        'method' => 'GET',
        'controller' => 'InvoiceController',
        'action' => 'apiSearch',
        'auth' => true
    ],
    [
        'path' => '/invoices/deleted',
        'method' => 'GET',
        'controller' => 'InvoiceController',
        'action' => 'apiGetDeleted',
        'auth' => true
    ],
    [
        'path' => '/invoices/deleted/{id}/restore',
        'method' => 'POST',
        'controller' => 'InvoiceController',
        'action' => 'apiRestore',
        'auth' => true
    ],
    [
        'path' => '/invoices/status/{status}',
        'method' => 'GET',
        'controller' => 'InvoiceController',
        'action' => 'apiGetByStatus',
        'auth' => true
    ],
    
    // مسارات الصفحات
    [
        'path' => '/pages',
        'method' => 'GET',
        'controller' => 'PageController',
        'action' => 'apiGetAll',
        'auth' => true
    ],
    [
        'path' => '/pages/{id}',
        'method' => 'GET',
        'controller' => 'PageController',
        'action' => 'apiGetById',
        'auth' => true
    ],
    [
        'path' => '/pages',
        'method' => 'POST',
        'controller' => 'PageController',
        'action' => 'apiCreate',
        'auth' => true
    ],
    [
        'path' => '/pages/{id}',
        'method' => 'PUT',
        'controller' => 'PageController',
        'action' => 'apiUpdate',
        'auth' => true
    ],
    [
        'path' => '/pages/{id}',
        'method' => 'DELETE',
        'controller' => 'PageController',
        'action' => 'apiDelete',
        'auth' => true
    ],
    
    // مسارات القائمة السوداء
    [
        'path' => '/blacklist',
        'method' => 'GET',
        'controller' => 'BlacklistController',
        'action' => 'apiGetAll',
        'auth' => true
    ],
    [
        'path' => '/blacklist/{id}',
        'method' => 'GET',
        'controller' => 'BlacklistController',
        'action' => 'apiGetById',
        'auth' => true
    ],
    [
        'path' => '/blacklist/check/{phone}',
        'method' => 'GET',
        'controller' => 'BlacklistController',
        'action' => 'apiCheck',
        'auth' => true
    ],
    [
        'path' => '/blacklist',
        'method' => 'POST',
        'controller' => 'BlacklistController',
        'action' => 'apiAdd',
        'auth' => true
    ],
    [
        'path' => '/blacklist/{id}',
        'method' => 'DELETE',
        'controller' => 'BlacklistController',
        'action' => 'apiRemove',
        'auth' => true
    ],
    
    // مسارات المستخدمين
    [
        'path' => '/users',
        'method' => 'GET',
        'controller' => 'UserController',
        'action' => 'apiGetAll',
        'auth' => true
    ],
    [
        'path' => '/users/{id}',
        'method' => 'GET',
        'controller' => 'UserController',
        'action' => 'apiGetById',
        'auth' => true
    ],
    [
        'path' => '/users',
        'method' => 'POST',
        'controller' => 'UserController',
        'action' => 'apiCreate',
        'auth' => true
    ],
    [
        'path' => '/users/{id}',
        'method' => 'PUT',
        'controller' => 'UserController',
        'action' => 'apiUpdate',
        'auth' => true
    ],
    [
        'path' => '/users/{id}',
        'method' => 'DELETE',
        'controller' => 'UserController',
        'action' => 'apiDelete',
        'auth' => true
    ],
    [
        'path' => '/users/{id}/status',
        'method' => 'PUT',
        'controller' => 'UserController',
        'action' => 'apiUpdateStatus',
        'auth' => true
    ],
    [
        'path' => '/users/{id}/permission',
        'method' => 'PUT',
        'controller' => 'UserController',
        'action' => 'apiUpdatePermission',
        'auth' => true
    ],
    [
        'path' => '/users/{id}/password',
        'method' => 'PUT',
        'controller' => 'UserController',
        'action' => 'apiChangePassword',
        'auth' => true
    ],
    [
        'path' => '/users/requests',
        'method' => 'GET',
        'controller' => 'UserController',
        'action' => 'apiGetRegistrationRequests',
        'auth' => true
    ],
    [
        'path' => '/users/requests/{id}/approve',
        'method' => 'POST',
        'controller' => 'UserController',
        'action' => 'apiApproveRegistration',
        'auth' => true
    ],
    [
        'path' => '/users/requests/{id}/reject',
        'method' => 'POST',
        'controller' => 'UserController',
        'action' => 'apiRejectRegistration',
        'auth' => true
    ],
    
    // مسارات الحاسبة
    [
        'path' => '/calculator',
        'method' => 'POST',
        'controller' => 'CalculationController',
        'action' => 'apiCalculate',
        'auth' => true
    ],
    
    // مسارات الإشعارات
    [
        'path' => '/notifications',
        'method' => 'GET',
        'controller' => 'NotificationController',
        'action' => 'apiGetAll',
        'auth' => true
    ],
    [
        'path' => '/notifications/unread',
        'method' => 'GET',
        'controller' => 'NotificationController',
        'action' => 'apiGetUnread',
        'auth' => true
    ],
    [
        'path' => '/notifications/{id}/read',
        'method' => 'PUT',
        'controller' => 'NotificationController',
        'action' => 'apiMarkAsRead',
        'auth' => true
    ],
    [
        'path' => '/notifications/read-all',
        'method' => 'PUT',
        'controller' => 'NotificationController',
        'action' => 'apiMarkAllAsRead',
        'auth' => true
    ],
    
    // مسارات الإحصائيات
    [
        'path' => '/statistics',
        'method' => 'GET',
        'controller' => 'DashboardController',
        'action' => 'apiGetStatistics',
        'auth' => true
    ],
    [
        'path' => '/statistics/invoices',
        'method' => 'GET',
        'controller' => 'DashboardController',
        'action' => 'apiGetInvoiceStatistics',
        'auth' => true
    ],
    [
        'path' => '/statistics/users',
        'method' => 'GET',
        'controller' => 'DashboardController',
        'action' => 'apiGetUserStatistics',
        'auth' => true
    ],
    [
        'path' => '/statistics/pages',
        'method' => 'GET',
        'controller' => 'DashboardController',
        'action' => 'apiGetPageStatistics',
        'auth' => true
    ]
];
