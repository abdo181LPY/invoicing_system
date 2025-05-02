<?php 
/**
 * صفحة التسجيل
 * تتيح للمستخدمين تسجيل حساب جديد في النظام
 */
$pageTitle = 'تسجيل حساب جديد';
include 'views/layout/header.php';

// استرجاع البيانات القديمة إن وجدت
$old = $session->getFlash('old') ?: [];
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm mt-4 mb-4">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>تسجيل حساب جديد</h4>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    بعد تسجيل حسابك، سيتم مراجعته من قبل الإدارة قبل تفعيله.
                </div>
                
                <form action="register_process.php" method="post" id="registerForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">اسم المستخدم <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" placeholder="أدخل اسم المستخدم" required value="<?php echo isset($old['username']) ? htmlspecialchars($old['username']) : ''; ?>">
                            </div>
                            <div class="form-text">يجب أن يكون من 3 إلى 50 حرفًا</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="أدخل البريد الإلكتروني" required value="<?php echo isset($old['email']) ? htmlspecialchars($old['email']) : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="أدخل كلمة المرور" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="إظهار/إخفاء كلمة المرور">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">يجب أن تكون على الأقل 6 أحرف</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="أعد إدخال كلمة المرور" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm" title="إظهار/إخفاء كلمة المرور">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" id="phone" name="phone" placeholder="أدخل رقم الهاتف (يبدأ بـ 07)" required value="<?php echo isset($old['phone']) ? htmlspecialchars($old['phone']) : ''; ?>">
                        </div>
                        <div class="form-text">يجب أن يكون رقم هاتف عراقي صحيح يبدأ بـ 07 ويتكون من 11 رقم</div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">أوافق على <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">شروط الاستخدام</a> <span class="text-danger">*</span></label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>تسجيل الحساب
                        </button>
                        <a href="login.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>العودة إلى تسجيل الدخول
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- نافذة شروط الاستخدام -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">شروط الاستخدام</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <h5>شروط استخدام نظام إدارة الفواتير عبر الإنترنت</h5>
                <p>يرجى قراءة هذه الشروط بعناية قبل التسجيل واستخدام النظام:</p>
                
                <h6>1. معلومات الحساب</h6>
                <p>أنت مسؤول عن الحفاظ على سرية معلومات حسابك وكلمة المرور الخاصة بك. أنت مسؤول بالكامل عن جميع الأنشطة التي تتم من خلال حسابك.</p>
                
                <h6>2. الاستخدام المقبول</h6>
                <p>يجب عليك استخدام النظام للأغراض المصرح بها فقط. يُحظر استخدام النظام بطريقة غير قانونية أو قد تؤدي إلى الإضرار بالآخرين.</p>
                
                <h6>3. خصوصية البيانات</h6>
                <p>نحن نحترم خصوصية بياناتك. سيتم استخدام البيانات المقدمة من قبلك لأغراض النظام فقط ولن تتم مشاركتها مع أطراف ثالثة بدون موافقتك.</p>
                
                <h6>4. أمان النظام</h6>
                <p>يجب عليك عدم محاولة اختراق النظام أو تعطيله أو التسبب في أي ضرر له.</p>
                
                <h6>5. تعديل الشروط</h6>
                <p>نحتفظ بالحق في تعديل هذه الشروط في أي وقت. ستتم إخطارك بأي تغييرات عند تسجيل الدخول التالي.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">فهمت وأوافق</button>
            </div>
        </div>
    </div>
</div>

<script>
    // سكريبت لإظهار/إخفاء كلمة المرور
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        
        const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
        const passwordConfirm = document.getElementById('password_confirm');
        
        togglePasswordConfirm.addEventListener('click', function() {
            const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordConfirm.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
        
        // التحقق من تطابق كلمتي المرور
        const registerForm = document.getElementById('registerForm');
        
        registerForm.addEventListener('submit', function(e) {
            if (password.value !== passwordConfirm.value) {
                e.preventDefault();
                alert('كلمة المرور وتأكيدها غير متطابقين');
                passwordConfirm.focus();
            }
        });
    });
</script>

<?php include 'views/layout/footer.php'; ?>
