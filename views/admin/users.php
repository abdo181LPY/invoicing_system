<?php 
/**
 * صفحة إدارة المستخدمين
 * تتيح للمدراء عرض وإضافة وتعديل وحذف المستخدمين
 */
$pageTitle = 'إدارة المستخدمين';
include 'views/layout/header.php';
include 'views/layout/sidebar.php';
?>

<!-- عنوان الصفحة -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users me-2"></i>إدارة المستخدمين</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="user_create.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i>إضافة مستخدم جديد
        </a>
    </div>
</div>

<!-- بطاقات الإحصائيات -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-primary h-100 text-center">
            <div class="card-body">
                <div class="display-4 text-primary mb-2">
                    <i class="fas fa-users"></i>
                </div>
                <h5 class="card-title">إجمالي المستخدمين</h5>
                <p class="card-text display-6"><?php echo count($users); ?></p>
            </div>
        </div>
    </div>
    
    <?php
    // حساب عدد المستخدمين النشطين وغير النشطين
    $activeUsers = array_filter($users, function($user) { return $user['is_active'] == 1; });
    $inactiveUsers = array_filter($users, function($user) { return $user['is_active'] == 0; });
    
    // حساب عدد المستخدمين حسب مستوى الصلاحية
    $adminUsers = array_filter($users, function($user) { return $user['permission_level'] >= 3; });
    $managerUsers = array_filter($users, function($user) { return $user['permission_level'] == 2; });
    $normalUsers = array_filter($users, function($user) { return $user['permission_level'] == 1; });
    ?>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-success h-100 text-center">
            <div class="card-body">
                <div class="display-4 text-success mb-2">
                    <i class="fas fa-user-check"></i>
                </div>
                <h5 class="card-title">المستخدمون النشطون</h5>
                <p class="card-text display-6"><?php echo count($activeUsers); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-warning h-100 text-center">
            <div class="card-body">
                <div class="display-4 text-warning mb-2">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h5 class="card-title">المدراء</h5>
                <p class="card-text display-6"><?php echo count($adminUsers); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 col-sm-6 mb-3">
        <div class="card border-danger h-100 text-center">
            <div class="card-body">
                <div class="display-4 text-danger mb-2">
                    <i class="fas fa-user-lock"></i>
                </div>
                <h5 class="card-title">الحسابات المعطلة</h5>
                <p class="card-text display-6"><?php echo count($inactiveUsers); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- طلبات التسجيل المعلقة -->
<?php
// الحصول على طلبات التسجيل المعلقة
$user = new User();
$pendingRequests = $user->getRegistrationRequests('pending');

if (!empty($pendingRequests)):
?>
<div class="alert alert-warning shadow-sm mb-4">
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-3">
            <i class="fas fa-user-clock fa-2x"></i>
        </div>
        <div class="flex-grow-1">
            <h5 class="alert-heading mb-1">طلبات تسجيل معلقة</h5>
            <p class="mb-0">يوجد <?php echo count($pendingRequests); ?> طلب تسجيل بحاجة إلى مراجعة</p>
        </div>
        <div>
            <a href="registration_requests.php" class="btn btn-warning">
                عرض الطلبات <i class="fas fa-arrow-left me-1"></i>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- قائمة المستخدمين -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-user-friends me-2"></i>قائمة المستخدمين</h5>
            
            <!-- بحث عن مستخدم -->
            <div class="input-group input-group-sm w-auto">
                <input type="text" id="searchInput" class="form-control" placeholder="بحث عن مستخدم...">
                <button class="btn btn-light" type="button" id="searchButton">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>المستخدم</th>
                        <th>معلومات الاتصال</th>
                        <th>الصلاحيات</th>
                        <th>الحالة</th>
                        <th>النشاط</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">لا يوجد مستخدمين لعرضهم</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <div class="avatar bg-light text-primary rounded-circle">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h6>
                                        <?php if ($user['id'] == $auth->getCurrentUserId()): ?>
                                        <span class="badge bg-info">أنت</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <i class="fas fa-envelope me-1 text-muted"></i>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </div>
                                <div>
                                    <i class="fas fa-phone me-1 text-muted"></i>
                                    <span dir="ltr"><?php echo htmlspecialchars($user['phone']); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php
                                $levelName = '';
                                $levelClass = '';
                                $levelIcon = '';
                                
                                switch ($user['permission_level']) {
                                    case 1:
                                        $levelName = 'مستخدم عادي';
                                        $levelClass = 'bg-secondary';
                                        $levelIcon = 'fa-user';
                                        break;
                                    case 2:
                                        $levelName = 'مشرف';
                                        $levelClass = 'bg-info';
                                        $levelIcon = 'fa-user-cog';
                                        break;
                                    case 3:
                                        $levelName = 'مدير';
                                        $levelClass = 'bg-warning';
                                        $levelIcon = 'fa-user-shield';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $levelClass; ?>">
                                    <i class="fas <?php echo $levelIcon; ?> me-1"></i>
                                    <?php echo $levelName; ?>
                                </span>
                                
                                <!-- قائمة منسدلة لتغيير الصلاحيات -->
                                <?php if ($user['id'] != $auth->getCurrentUserId()): ?>
                                <div class="dropdown d-inline-block ms-1">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="permissionDropdown<?php echo $user['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        تغيير
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="permissionDropdown<?php echo $user['id']; ?>">
                                        <li>
                                            <form action="update_user_permission.php" method="post" id="permissionForm<?php echo $user['id']; ?>_1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="permission_level" value="1">
                                                <button type="submit" class="dropdown-item <?php echo ($user['permission_level'] == 1) ? 'active' : ''; ?>">
                                                    <i class="fas fa-user me-1"></i> مستخدم عادي
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="update_user_permission.php" method="post" id="permissionForm<?php echo $user['id']; ?>_2">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="permission_level" value="2">
                                                <button type="submit" class="dropdown-item <?php echo ($user['permission_level'] == 2) ? 'active' : ''; ?>">
                                                    <i class="fas fa-user-cog me-1"></i> مشرف
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="update_user_permission.php" method="post" id="permissionForm<?php echo $user['id']; ?>_3">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="permission_level" value="3">
                                                <button type="submit" class="dropdown-item <?php echo ($user['permission_level'] == 3) ? 'active' : ''; ?>">
                                                    <i class="fas fa-user-shield me-1"></i> مدير
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input toggle-status" type="checkbox" role="switch" id="statusSwitch<?php echo $user['id']; ?>" 
                                           <?php echo $user['is_active'] ? 'checked' : ''; ?> 
                                           <?php echo ($user['id'] == $auth->getCurrentUserId()) ? 'disabled' : ''; ?>
                                           data-user-id="<?php echo $user['id']; ?>"
                                           data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                    <label class="form-check-label" for="statusSwitch<?php echo $user['id']; ?>">
                                        <?php echo $user['is_active'] ? 'نشط' : 'معطل'; ?>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <div>
                                        <i class="fas fa-calendar-plus me-1 text-muted"></i>
                                        <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-sign-in-alt me-1 text-muted"></i>
                                        <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'لم يسجل الدخول بعد'; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <!-- زر عرض الملف الشخصي -->
                                    <a href="user_profile.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="عرض الملف الشخصي">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- زر تعديل المستخدم -->
                                    <a href="user_edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" title="تعديل المستخدم">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- زر عرض النشاط -->
                                    <a href="user_activity.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary" title="عرض النشاط">
                                        <i class="fas fa-history"></i>
                                    </a>
                                    
                                    <!-- زر حذف المستخدم (باستثناء المستخدم الحالي) -->
                                    <?php if ($user['id'] != $auth->getCurrentUserId()): ?>
                                    <button type="button" class="btn btn-sm btn-danger delete-user" title="حذف المستخدم"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteUserModal" 
                                        data-user-id="<?php echo $user['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted">إجمالي المستخدمين: <?php echo count($users); ?></span>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="refreshUsersBtn">
                    <i class="fas fa-sync-alt me-1"></i> تحديث
                </button>
                
                <?php if ($auth->hasPermission(3)): ?>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#quickAddUserModal">
                    <i class="fas fa-user-plus me-1"></i> إضافة سريعة
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- نموذج حذف المستخدم -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel">تأكيد حذف المستخدم</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من رغبتك في حذف المستخدم <span id="deleteUserName" class="fw-bold"></span>؟</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    تحذير: هذا الإجراء لا يمكن التراجع عنه!
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form action="user_delete.php" method="post" id="deleteUserForm">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- نموذج تغيير حالة المستخدم -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleStatusModalLabel">تأكيد تغيير حالة المستخدم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من رغبتك في <span id="statusAction" class="fw-bold"></span> حساب المستخدم <span id="toggleStatusUserName" class="fw-bold"></span>؟</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form action="update_user_status.php" method="post" id="toggleStatusForm">
                    <input type="hidden" name="user_id" id="toggleStatusUserId">
                    <input type="hidden" name="is_active" id="toggleStatusValue">
                    <button type="submit" class="btn" id="toggleStatusBtn">تأكيد</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- نموذج إضافة مستخدم سريع -->
<div class="modal fade" id="quickAddUserModal" tabindex="-1" aria-labelledby="quickAddUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="quickAddUserModalLabel"><i class="fas fa-user-plus me-2"></i>إضافة مستخدم سريعة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <form action="user_create_quick.php" method="post" id="quickUserForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="username" class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="form-text">يجب أن يكون من 3 إلى 50 حرفًا</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="form-text">يجب أن يكون رقم هاتف عراقي صحيح يبدأ بـ 07</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="permission_level" class="form-label">مستوى الصلاحية <span class="text-danger">*</span></label>
                            <select class="form-select" id="permission_level" name="permission_level" required>
                                <option value="1">مستخدم عادي</option>
                                <option value="2">مشرف</option>
                                <option value="3">مدير</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="password" class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="إظهار/إخفاء كلمة المرور">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">يجب أن تكون على الأقل 6 أحرف</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="password_confirm" class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">تفعيل الحساب</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success" id="submitQuickUserForm">
                    <i class="fas fa-user-plus me-1"></i>إضافة المستخدم
                </button>
            </div>
        </div>
    </div>
</div>

<!-- سكريبت الصفحة -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // زر إظهار/إخفاء كلمة المرور
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    
    // البحث في جدول المستخدمين
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const usersTable = document.getElementById('usersTable');
    
    if (searchInput && searchButton && usersTable) {
        const searchUsers = function() {
            const filter = searchInput.value.toLowerCase();
            const rows = usersTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const username = rows[i].cells[1] ? rows[i].cells[1].textContent.toLowerCase() : '';
                const email = rows[i].cells[2] ? rows[i].cells[2].textContent.toLowerCase() : '';
                const phone = rows[i].cells[2] ? rows[i].cells[2].textContent.toLowerCase() : '';
                
                if (username.indexOf(filter) > -1 || email.indexOf(filter) > -1 || phone.indexOf(filter) > -1) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        };
        
        searchButton.addEventListener('click', searchUsers);
        searchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                searchUsers();
            }
        });
    }
    
    // تحديث حالة المستخدم
    const toggleStatusSwitches = document.querySelectorAll('.toggle-status');
    const toggleStatusModal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
    const toggleStatusForm = document.getElementById('toggleStatusForm');
    const toggleStatusUserId = document.getElementById('toggleStatusUserId');
    const toggleStatusValue = document.getElementById('toggleStatusValue');
    const toggleStatusUserName = document.getElementById('toggleStatusUserName');
    const statusAction = document.getElementById('statusAction');
    const toggleStatusBtn = document.getElementById('toggleStatusBtn');
    
    toggleStatusSwitches.forEach(function(statusSwitch) {
        statusSwitch.addEventListener('change', function(event) {
            // منع التبديل التلقائي
            event.preventDefault();
            
            // إعادة الحالة للقيمة السابقة
            this.checked = !this.checked;
            
            // إعداد بيانات النموذج
            const userId = this.dataset.userId;
            const username = this.dataset.username;
            const newStatus = this.checked ? 0 : 1;
            
            toggleStatusUserId.value = userId;
            toggleStatusValue.value = newStatus;
            toggleStatusUserName.textContent = username;
            
            if (newStatus === 1) {
                statusAction.textContent = 'تفعيل';
                toggleStatusBtn.className = 'btn btn-success';
            } else {
                statusAction.textContent = 'تعطيل';
                toggleStatusBtn.className = 'btn btn-warning';
            }
            
            // عرض نافذة التأكيد
            toggleStatusModal.show();
        });
    });
    
    // حذف المستخدم
    const deleteUserButtons = document.querySelectorAll('.delete-user');
    const deleteUserForm = document.getElementById('deleteUserForm');
    const deleteUserId = document.getElementById('deleteUserId');
    const deleteUserName = document.getElementById('deleteUserName');
    
    deleteUserButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const username = this.dataset.username;
            
            deleteUserId.value = userId;
            deleteUserName.textContent = username;
        });
    });
    
    // إرسال نموذج إضافة المستخدم السريع
    const quickUserForm = document.getElementById('quickUserForm');
    const submitQuickUserForm = document.getElementById('submitQuickUserForm');
    
    if (submitQuickUserForm && quickUserForm) {
        submitQuickUserForm.addEventListener('click', function() {
            // التحقق من كلمة المرور
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                alert('كلمة المرور وتأكيدها غير متطابقين');
                return;
            }
            
            // إرسال النموذج
            quickUserForm.submit();
        });
    }
    
    // تحديث قائمة المستخدمين
    const refreshUsersBtn = document.getElementById('refreshUsersBtn');
    
    if (refreshUsersBtn) {
        refreshUsersBtn.addEventListener('click', function() {
            location.reload();
        });
    }
});
</script>

<?php include 'views/layout/footer.php'; ?>
