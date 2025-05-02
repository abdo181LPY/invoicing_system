<?php
/**
 * متحكم الحساب
 * يدير عمليات حساب التكلفة وعرض المعلومات للزبائن
 */
class CalculationController {
    private $db;
    private $invoice;
    private $page;
    private $validator;
    private $session;
    
    /**
     * إنشاء كائن المتحكم
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->invoice = new Invoice();
        $this->page = new Page();
        $this->validator = Validator::getInstance();
        $this->session = Session::getInstance();
    }
    
    /**
     * عرض صفحة الحساب
     */
    public function index() {
        // الحصول على قائمة الصفحات للقائمة المنسدلة
        $pages = $this->page->getDropdownList();
        $provinces = $GLOBALS['PROVINCES'];
        
        // تحميل القالب
        include 'views/calculator/index.php';
    }
    
    /**
     * حساب التكلفة
     */
    public function calculate() {
        // التحقق من صحة البيانات
        $this->validator->setData($_POST);
        
        $this->validator->required('page_id', 'الصفحة')
                        ->required('instagram', 'رابط الإنستغرام')
                        ->required('province', 'المحافظة')
                        ->required('basket_price', 'سعر السلة')
                        ->required('item_count', 'عدد القطع')
                        ->numeric('basket_price', 'سعر السلة')
                        ->numeric('item_count', 'عدد القطع')
                        ->min('basket_price', 0.01, 'سعر السلة')
                        ->min('item_count', 1, 'عدد القطع')
                        ->integer('item_count', 'عدد القطع')
                        ->inArray('province', $GLOBALS['PROVINCES'], 'المحافظة');
        
        // التحقق من وجود الصفحة
        if (isset($_POST['page_id']) && !$this->page->exists(['id' => $_POST['page_id']])) {
            $this->validator->errors['page_id'] = 'الصفحة غير موجودة';
        }
        
        // التحقق من صحة البيانات
        if (!$this->validator->validate()) {
            // عرض أخطاء التحقق
            $this->session->setFlash('errors', $this->validator->getErrors());
            $this->session->setFlash('old', $_POST);
            header('Location: calculator.php');
            exit;
        }
        
        // حساب التكلفة
        $calculationData = $this->invoice->calculateCost(
            $_POST['basket_price'],
            $_POST['item_count'],
            $_POST['province']
        );
        
        // الحصول على بيانات الصفحة
        $pageData = $this->page->getById($_POST['page_id']);
        
        // إنشاء رسالة للزبون
        $message = $this->invoice->createCustomerMessage($calculationData, $pageData);
        
        // حفظ البيانات في الجلسة للعرض
        $this->session->setFlash('calculation', [
            'data' => $calculationData,
            'page' => $pageData,
            'message' => $message,
            'form_data' => $_POST
        ]);
        
        // إعادة التوجيه إلى صفحة النتائج
        header('Location: calculator.php?result=1');
        exit;
    }
    
    /**
     * عرض نتائج الحساب
     */
    public function result() {
        // التحقق من وجود بيانات الحساب في الجلسة
        $calculation = $this->session->getFlash('calculation');
        
        if (!$calculation) {
            // إعادة التوجيه إلى صفحة الحساب
            $this->session->setFlash('error', 'لا توجد نتائج حساب للعرض');
            header('Location: calculator.php');
            exit;
        }
        
        // استخراج البيانات
        $calculationData = $calculation['data'];
        $pageData = $calculation['page'];
        $message = $calculation['message'];
        $formData = $calculation['form_data'];
        
        // تحميل قالب النتائج
        include 'views/calculator/result.php';
    }
    
    /**
     * تثبيت طلب بعد الحساب
     */
    public function createOrder() {
        // التحقق من وجود بيانات الحساب في الجلسة
        $calculation = $this->session->getFlash('calculation');
        
        if (!$calculation) {
            // إعادة التوجيه إلى صفحة الحساب
            $this->session->setFlash('error', 'لا توجد بيانات حساب لتثبيت الطلب');
            header('Location: calculator.php');
            exit;
        }
        
        // إعادة التوجيه إلى صفحة تثبيت الطلب مع البيانات
        $this->session->setFlash('create_order_data', $calculation);
        header('Location: invoice_create.php');
        exit;
    }
    
    /**
     * تحويل نص الحساب إلى صورة (لمشاركتها)
     */
    public function createImage() {
        // التحقق من وجود بيانات في الطلب
        if (!isset($_POST['message']) || empty($_POST['message'])) {
            $this->session->setFlash('error', 'لا توجد بيانات لإنشاء الصورة');
            header('Location: calculator.php');
            exit;
        }
        
        // الحصول على نص الرسالة
        $message = $_POST['message'];
        
        // إنشاء صورة من النص
        $imagePath = $this->createTextImage($message);
        
        if (!$imagePath) {
            $this->session->setFlash('error', 'فشل في إنشاء الصورة');
            header('Location: calculator.php');
            exit;
        }
        
        // إعادة مسار الصورة
        echo json_encode(['success' => true, 'image_path' => $imagePath]);
        exit;
    }
    
    /**
     * إنشاء صورة من النص
     */
    private function createTextImage($text) {
        // تحديد حجم الصورة والخط
        $fontSize = 20;
        $fontFile = ROOT_PATH . '/assets/fonts/tajawal-medium.ttf';
        
        // حساب حجم النص
        $textBox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = abs($textBox[4] - $textBox[0]) + 40;
        $textHeight = abs($textBox[5] - $textBox[1]) + 40;
        
        // إنشاء صورة بالحجم المناسب
        $image = imagecreatetruecolor($textWidth, $textHeight);
        
        // تحديد الألوان
        $backgroundColor = imagecolorallocate($image, 255, 255, 255);
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $borderColor = imagecolorallocate($image, 230, 230, 230);
        
        // ملء الخلفية
        imagefill($image, 0, 0, $backgroundColor);
        
        // رسم إطار
        imagerectangle($image, 0, 0, $textWidth - 1, $textHeight - 1, $borderColor);
        
        // كتابة النص
        imagettftext($image, $fontSize, 0, 20, $fontSize + 10, $textColor, $fontFile, $text);
        
        // إنشاء مسار الصورة
        $imagePath = UPLOADS_PATH . '/images/calculation_' . time() . '.png';
        
        // حفظ الصورة
        imagepng($image, $imagePath);
        imagedestroy($image);
        
        // إرجاع مسار الصورة
        return str_replace(ROOT_PATH, SITE_URL, $imagePath);
    }
}