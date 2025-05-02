<?php
/**
 * فئة التوجيه
 * تدير توجيه الطلبات إلى المتحكمات والإجراءات المناسبة
 */

class Router {
    private $routes = [];
    private $notFoundCallback;
    private static $instance = null;
    
    /**
     * منع إنشاء كائن مباشرة (نمط Singleton)
     */
    private function __construct() {}
    
    /**
     * منع نسخ الكائن
     */
    private function __clone() {}
    
    /**
     * الحصول على كائن وحيد من الموجه
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * إضافة مسار GET
     */
    public function get($pattern, $callback) {
        $this->addRoute('GET', $pattern, $callback);
        return $this;
    }
    
    /**
     * إضافة مسار POST
     */
    public function post($pattern, $callback) {
        $this->addRoute('POST', $pattern, $callback);
        return $this;
    }
    
    /**
     * إضافة مسار PUT
     */
    public function put($pattern, $callback) {
        $this->addRoute('PUT', $pattern, $callback);
        return $this;
    }
    
    /**
     * إضافة مسار DELETE
     */
    public function delete($pattern, $callback) {
        $this->addRoute('DELETE', $pattern, $callback);
        return $this;
    }
    
    /**
     * إضافة مسار لعدة طرق طلب
     */
    public function map($methods, $pattern, $callback) {
        foreach ($methods as $method) {
            $this->addRoute($method, $pattern, $callback);
        }
        return $this;
    }
    
    /**
     * إضافة مسار لأي طريقة طلب
     */
    public function any($pattern, $callback) {
        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        $this->map($methods, $pattern, $callback);
        return $this;
    }
    
    /**
     * تعيين مسار للطلبات غير الموجودة
     */
    public function notFound($callback) {
        $this->notFoundCallback = $callback;
        return $this;
    }
    
    /**
     * إضافة مسار إلى مصفوفة المسارات
     */
    private function addRoute($method, $pattern, $callback) {
        // تنظيف النمط
        $pattern = rtrim($pattern, '/');
        if ($pattern === '') {
            $pattern = '/';
        }
        
        // تحويل المعلمات إلى تعبير منتظم
        $patternAsRegex = $this->convertPatternToRegex($pattern);
        
        // إضافة المسار إلى المصفوفة
        $this->routes[$method][$pattern] = [
            'pattern' => $pattern,
            'regex' => $patternAsRegex,
            'callback' => $callback
        ];
    }
    
    /**
     * تحويل نمط المسار إلى تعبير منتظم
     */
    private function convertPatternToRegex($pattern) {
        if ($pattern === '/') {
            return '@^/$@';
        }
        
        // استبدال معلمات المسار بتعبيرات منتظمة
        $patternAsRegex = preg_replace('/\/{([^\/]+)}/', '/([^/]+)', $pattern);
        
        // إضافة حدود البداية والنهاية
        $patternAsRegex = '@^' . $patternAsRegex . '$@';
        
        return $patternAsRegex;
    }
    
    /**
     * استخراج معلمات المسار من النمط
     */
    private function extractParamNames($pattern) {
        preg_match_all('/\/{([^\/]+)}/', $pattern, $matches);
        return $matches[1];
    }
    
    /**
     * الحصول على معلمات المسار من عنوان URL
     */
    private function extractParamValues($pattern, $uri) {
        $regex = $this->convertPatternToRegex($pattern);
        preg_match($regex, $uri, $matches);
        
        // إزالة التطابق الكامل
        array_shift($matches);
        
        return $matches;
    }
    
    /**
     * بناء مصفوفة المعلمات
     */
    private function buildParams($paramNames, $paramValues) {
        $params = [];
        foreach ($paramNames as $index => $name) {
            $params[$name] = $paramValues[$index];
        }
        return $params;
    }
    
    /**
     * البحث عن المسار المطابق
     */
    private function findRoute($method, $uri) {
        // التحقق من وجود المسارات لطريقة الطلب
        if (!isset($this->routes[$method])) {
            return null;
        }
        
        // تنظيف عنوان URI
        $uri = rtrim($uri, '/');
        if ($uri === '') {
            $uri = '/';
        }
        
        // البحث عن مسار مطابق
        foreach ($this->routes[$method] as $pattern => $route) {
            if (preg_match($route['regex'], $uri)) {
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * تنفيذ التوجيه
     */
    public function dispatch() {
        // تحديد طريقة الطلب وعنوان URI
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // تعديل طريقة الطلب إذا كانت PUT أو DELETE عبر النموذج
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        // البحث عن المسار المطابق
        $route = $this->findRoute($method, $uri);
        
        if ($route) {
            // استخراج معلمات المسار
            $paramNames = $this->extractParamNames($route['pattern']);
            $paramValues = $this->extractParamValues($route['pattern'], $uri);
            $params = $this->buildParams($paramNames, $paramValues);
            
            // تنفيذ مستدعى المسار
            $callback = $route['callback'];
            
            if (is_callable($callback)) {
                // استدعاء دالة مباشرة
                call_user_func_array($callback, $params);
            } elseif (is_string($callback) && strpos($callback, '@') !== false) {
                // استدعاء متحكم@إجراء
                list($controller, $action) = explode('@', $callback);
                
                // التحقق من وجود المتحكم
                if (!class_exists($controller)) {
                    $controllerFile = ROOT_PATH . '/controllers/' . $controller . '.php';
                    
                    if (file_exists($controllerFile)) {
                        require_once $controllerFile;
                    } else {
                        throw new Exception("المتحكم '{$controller}' غير موجود.");
                    }
                }
                
                // إنشاء كائن المتحكم وتنفيذ الإجراء
                $controllerInstance = new $controller();
                
                if (method_exists($controllerInstance, $action)) {
                    call_user_func_array([$controllerInstance, $action], $params);
                } else {
                    throw new Exception("الإجراء '{$action}' غير موجود في المتحكم '{$controller}'.");
                }
            } else {
                throw new Exception("مستدعى المسار غير صالح.");
            }
        } else {
            // تنفيذ مستدعى المسار غير الموجود
            if ($this->notFoundCallback) {
                call_user_func($this->notFoundCallback);
            } else {
                header("HTTP/1.0 404 Not Found");
                echo "404 - الصفحة غير موجودة";
            }
        }
    }
    
    /**
     * إنشاء عنوان URL
     */
    public function url($pattern, $params = []) {
        $url = $pattern;
        
        // استبدال المعلمات في النمط
        foreach ($params as $key => $value) {
            $url = str_replace("{{$key}}", $value, $url);
        }
        
        return SITE_URL . $url;
    }
    
    /**
     * إعادة توجيه إلى مسار
     */
    public function redirect($pattern, $params = []) {
        $url = $this->url($pattern, $params);
        header("Location: {$url}");
        exit;
    }
}