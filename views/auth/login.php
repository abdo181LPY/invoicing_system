<?php 
/**
 * صفحة تسجيل الدخول
 * تتيح للمستخدمين تسجيل الدخول إلى النظام
 */
$pageTitle = 'تسجيل الدخول';
include 'views/layout/header.php';

// استرجاع البيانات القديمة إن وجدت
$old = $session->getFlash('old') ?: [];
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm mt-5">
            <div class="card-header bg-primary text-white text-center">
                <h4 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول</h4>
            </div>
            <div class="card-body p-4">
                <form action="login_process.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">اسم المستخدم</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="أدخل اسم المستخدم" required autofocus value="<?php echo isset($old['username']) ? htmlspecialchars($old['username']) : ''; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">كلمة المرور</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="أدخل كلمة المرور" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="إظهار/إخفاء كلمة المرور">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                        <label class="form-check-label" for="remember">تذكرني</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>تسجيل الدخول
                        </button>
                    </div>
                </form>
            </div>
            <div class="card-footer bg-light text-center">
                <p class="mb-0">ليس لديك حساب؟ <a href="register.php">تسجيل حساب جديد</a></p>
            </div>
        </div>
        
        <?php if (isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
        <div class="alert alert-warning mt-3">
            <i class="fas fa-clock me-2"></i>
            انتهت مدة جلستك. يرجى تسجيل الدخول مرة أخرى.
        </div>
        <?php endif; ?>
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
    });
</script>

<?php include 'views/layout/footer.php'; ?>
