<?php
/**
 * دوال مساعدة إضافية للنظام
 */

/**
 * تنسيق الأرقام بالآلاف
 * مثال: 1000 -> 1K, 1000000 -> 1M
 *
 * @param float $number الرقم المراد تنسيقه
 * @param int $decimals عدد الخانات العشرية
 * @return string الرقم بعد التنسيق
 */
function formatNumberShort($number, $decimals = 1) {
    if ($number >= 1000000000) {
        return number_format($number / 1000000000, $decimals) . 'B';
    } elseif ($number >= 1000000) {
        return number_format($number / 1000000, $decimals) . 'M';
    } elseif ($number >= 1000) {
        return number_format($number / 1000, $decimals) . 'K';
    }
    
    return number_format($number, $decimals);
}

/**
 * تنسيق التاريخ إلى الصيغة العربية
 *
 * @param string $date التاريخ بصيغة Y-m-d أو timestamp
 * @param bool $withTime إضافة الوقت
 * @return string التاريخ بالصيغة العربية
 */
function formatArabicDate($date, $withTime = false) {
    if (is_numeric($date)) {
        $timestamp = $date;
    } else {
        $timestamp = strtotime($date);
    }
    
    $format = 'j F Y';
    if ($withTime) {
        $format .= ' - g:i A';
    }
    
    $englishMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    
    $englishDay = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $arabicDay = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
    
    $formattedDate = date($format, $timestamp);
    $formattedDate = str_replace($englishMonths, $arabicMonths, $formattedDate);
    
    if (strpos($format, 'l') !== false) {
        $formattedDate = str_replace($englishDay, $arabicDay, $formattedDate);
    }
    
    if ($withTime) {
        $formattedDate = str_replace('AM', 'صباحًا', $formattedDate);
        $formattedDate = str_replace('PM', 'مساءً', $formattedDate);
    }
    
    return $formattedDate;
}

/**
 * الحصول على الوقت المنقضي منذ تاريخ معين (بالعربية)
 *
 * @param string|int $datetime التاريخ
 * @return string الوقت المنقضي
 */
function timeAgo($datetime) {
    if (is_numeric($datetime)) {
        $timestamp = $datetime;
    } else {
        $timestamp = strtotime($datetime);
    }
    
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'منذ ' . $diff . ' ثانية';
    } elseif ($diff < 3600) {
        return 'منذ ' . floor($diff / 60) . ' دقيقة';
    } elseif ($diff < 86400) {
        return 'منذ ' . floor($diff / 3600) . ' ساعة';
    } elseif ($diff < 604800) {
        return 'منذ ' . floor($diff / 86400) . ' يوم';
    } elseif ($diff < 2592000) {
        return 'منذ ' . floor($diff / 604800) . ' أسبوع';
    } elseif ($diff < 31536000) {
        return 'منذ ' . floor($diff / 2592000) . ' شهر';
    } else {
        return 'منذ ' . floor($diff / 31536000) . ' سنة';
    }
}

/**
 * الحصول على اسم المحافظة من الرمز
 *
 * @param string $code رمز المحافظة
 * @return string اسم المحافظة
 */
function getGovernorateName($code) {
    $governorates = include(ROOT_PATH . '/includes/governorates.php');
    
    if (isset($governorates[$code])) {
        return $governorates[$code];
    }
    
    return $code;
}

/**
 * تنظيف النص من الأكواد الضارة
 *
 * @param string $text النص المراد تنظيفه
 * @return string النص بعد التنظيف
 */
function sanitizeText($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    return $text;
}

/**
 * تنظيف النص مع السماح ببعض وسوم HTML
 *
 * @param string $text النص المراد تنظيفه
 * @return string النص بعد التنظيف
 */
function sanitizeHtml($text) {
    $allowed_tags = '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
    
    return strip_tags($text, $allowed_tags);
}

/**
 * تحويل نص عادي إلى URL آمن
 *
 * @param string $string النص المراد تحويله
 * @return string النص بعد التحويل
 */
function slugify($string) {
    // تبديل الأحرف العربية إلى أحرف إنجليزية مناسبة
    $replacements = [
        'أ' => 'a', 'إ' => 'a', 'آ' => 'a', 'ا' => 'a',
        'ب' => 'b', 'ت' => 't', 'ث' => 'th',
        'ج' => 'j', 'ح' => 'h', 'خ' => 'kh',
        'د' => 'd', 'ذ' => 'th',
        'ر' => 'r', 'ز' => 'z',
        'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd',
        'ط' => 't', 'ظ' => 'z',
        'ع' => 'a', 'غ' => 'gh',
        'ف' => 'f', 'ق' => 'q', 'ك' => 'k',
        'ل' => 'l', 'م' => 'm', 'ن' => 'n',
        'ه' => 'h', 'و' => 'w', 'ي' => 'y', 'ى' => 'a',
        'ة' => 'a', 'ء' => '', 'ؤ' => 'o', 'ئ' => 'e'
    ];
    
    $string = str_replace(array_keys($replacements), array_values($replacements), $string);
    
    // إزالة جميع الأحرف غير الألفبائية والأرقام والمسافات
    $string = preg_replace('~[^\p{L}\p{N}\s]+~u', '', $string);
    
    // تبديل المسافات إلى شرطات
    $string = preg_replace('~[\s]+~', '-', $string);
    
    // تحويل إلى أحرف صغيرة وإزالة الشرطات الزائدة
    $string = strtolower(trim($string, '-'));
    
    return $string;
}

/**
 * اختصار النص إلى طول محدد
 *
 * @param string $text النص المراد اختصاره
 * @param int $length الطول المطلوب
 * @param string $append النص المضاف في نهاية النص المختصر
 * @return string النص بعد الاختصار
 */
function truncateText($text, $length = 100, $append = '...') {
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    $text = mb_substr($text, 0, $length, 'UTF-8');
    $text = mb_substr($text, 0, mb_strrpos($text, ' ', 0, 'UTF-8'), 'UTF-8');
    
    return $text . $append;
}

/**
 * تحويل النص العادي إلى نص قابل للعرض في HTML
 *
 * @param string $text النص المراد تحويله
 * @return string النص بعد التحويل
 */
function nl2p($text) {
    $paragraphs = preg_split('/\n+/', $text);
    $paragraphs = array_map(function($paragraph) {
        return '<p>' . trim($paragraph) . '</p>';
    }, $paragraphs);
    
    return implode('', $paragraphs);
}

/**
 * إنشاء كود تأكيد عشوائي
 *
 * @param int $length طول الكود
 * @param bool $numbersOnly استخدام أرقام فقط
 * @return string الكود العشوائي
 */
function generateVerificationCode($length = 6, $numbersOnly = true) {
    if ($numbersOnly) {
        $characters = '0123456789';
    } else {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    
    $code = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[mt_rand(0, $max)];
    }
    
    return $code;
}

/**
 * التحقق من رقم الهاتف العراقي
 *
 * @param string $phoneNumber رقم الهاتف
 * @return bool صحة رقم الهاتف
 */
function validateIraqiPhoneNumber($phoneNumber) {
    // حذف المسافات والأقواس والشرطات
    $phoneNumber = preg_replace('/[\s\(\)\-]/', '', $phoneNumber);
    
    // التحقق من أن الرقم يبدأ بـ 07 وطوله 11 رقم
    if (preg_match('/^07[0-9]{9}$/', $phoneNumber)) {
        return true;
    }
    
    // التحقق من أن الرقم يبدأ بـ +964 وطوله 13 رقم
    if (preg_match('/^\+964[0-9]{10}$/', $phoneNumber)) {
        return true;
    }
    
    // التحقق من أن الرقم يبدأ بـ 00964 وطوله 14 رقم
    if (preg_match('/^00964[0-9]{10}$/', $phoneNumber)) {
        return true;
    }
    
    return false;
}

/**
 * تنسيق رقم الهاتف العراقي
 *
 * @param string $phoneNumber رقم الهاتف
 * @param string $format صيغة الرقم (local, international, full)
 * @return string الرقم بعد التنسيق
 */
function formatIraqiPhoneNumber($phoneNumber, $format = 'local') {
    // حذف المسافات والأقواس والشرطات
    $phoneNumber = preg_replace('/[\s\(\)\-]/', '', $phoneNumber);
    
    // تحويل الرقم إلى الصيغة المحلية (يبدأ بـ 07)
    if (preg_match('/^\+964(\d{10})$/', $phoneNumber, $matches)) {
        $phoneNumber = '0' . $matches[1];
    } elseif (preg_match('/^00964(\d{10})$/', $phoneNumber, $matches)) {
        $phoneNumber = '0' . $matches[1];
    }
    
    // التحقق من صحة الرقم
    if (!preg_match('/^07[0-9]{9}$/', $phoneNumber)) {
        return $phoneNumber; // إرجاع الرقم كما هو إذا كان غير صالح
    }
    
    // تنسيق الرقم حسب الصيغة المطلوبة
    switch ($format) {
        case 'international':
            return '+964' . substr($phoneNumber, 1);
            
        case 'full':
            return '+964 ' . substr($phoneNumber, 1, 3) . ' ' . substr($phoneNumber, 4, 3) . ' ' . substr($phoneNumber, 7);
            
        case 'local':
        default:
            return substr($phoneNumber, 0, 4) . ' ' . substr($phoneNumber, 4, 3) . ' ' . substr($phoneNumber, 7);
    }
}

/**
 * تحويل السعر من الدولار إلى الدينار العراقي
 *
 * @param float $usdPrice السعر بالدولار
 * @param float $exchangeRate سعر الصرف
 * @return float السعر بالدينار
 */
function convertUsdToIqd($usdPrice, $exchangeRate = 1300) {
    return $usdPrice * $exchangeRate;
}

/**
 * تحويل السعر من الدينار العراقي إلى الدولار
 *
 * @param float $iqdPrice السعر بالدينار
 * @param float $exchangeRate سعر الصرف
 * @return float السعر بالدولار
 */
function convertIqdToUsd($iqdPrice, $exchangeRate = 1300) {
    return $iqdPrice / $exchangeRate;
}

/**
 * تقريب السعر إلى أقرب قيمة
 *
 * @param float $price السعر
 * @param int $nearest أقرب قيمة للتقريب (مثل 250، 500، 1000)
 * @param string $direction اتجاه التقريب (up, down, nearest)
 * @return float السعر بعد التقريب
 */
function roundPriceToNearest($price, $nearest = 1000, $direction = 'nearest') {
    if ($direction == 'up') {
        return ceil($price / $nearest) * $nearest;
    } elseif ($direction == 'down') {
        return floor($price / $nearest) * $nearest;
    } else {
        return round($price / $nearest) * $nearest;
    }
}

/**
 * حساب قيمة العربون
 *
 * @param float $totalPrice إجمالي السعر
 * @param float $percentage نسبة العربون (بين 0 و 1)
 * @param int $nearest أقرب قيمة للتقريب
 * @return float قيمة العربون
 */
function calculateDeposit($totalPrice, $percentage = 0.3, $nearest = 5000) {
    $deposit = $totalPrice * $percentage;
    return roundPriceToNearest($deposit, $nearest, 'up');
}

/**
 * الحصول على عنوان IP الحقيقي للمستخدم
 *
 * @return string عنوان IP
 */
function getRealIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * التحقق من تطابق عنوان IP مع المعطى
 *
 * @param string $userIp عنوان IP المستخدم
 * @param string $ipRange نطاق IP للتحقق
 * @return bool نتيجة التحقق
 */
function checkIpMatch($userIp, $ipRange) {
    // إذا كان IP محدد بدقة
    if (strpos($ipRange, '/') === false) {
        return $userIp === $ipRange;
    }
    
    // إذا كان نطاق IP
    list($subnet, $bits) = explode('/', $ipRange);
    $ip2long = ip2long($userIp);
    $subnet2long = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    $subnet2long &= $mask;
    
    return ($ip2long & $mask) == $subnet2long;
}

/**
 * إعادة توجيه المستخدم إلى صفحة معينة
 *
 * @param string $url عنوان URL للتوجيه إليه
 * @param int $statusCode كود الحالة HTTP
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * التحقق من نوع الطلب HTTP
 *
 * @param string $method نوع الطلب (GET, POST, PUT, DELETE, etc.)
 * @return bool نتيجة التحقق
 */
function isRequestMethod($method) {
    return $_SERVER['REQUEST_METHOD'] === strtoupper($method);
}

/**
 * التحقق من أن الطلب هو طلب AJAX
 *
 * @return bool نتيجة التحقق
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * تحويل السعر إلى نص عربي
 *
 * @param float $amount المبلغ
 * @param string $currency العملة (دينار، دولار)
 * @return string المبلغ بالنص العربي
 */
function numberToArabicWords($amount, $currency = 'دينار') {
    $fraction = round($amount - floor($amount), 2) * 100;
    $amount = floor($amount);
    
    $units = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة', 'عشرة', 'أحد عشر', 'اثنا عشر'];
    $tens = ['', 'عشرة', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
    $hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];
    $thousands = ['', 'ألف', 'ألفان', 'آلاف', 'ألف'];
    $millions = ['', 'مليون', 'مليونان', 'ملايين', 'مليون'];
    $billions = ['', 'مليار', 'ملياران', 'مليارات', 'مليار'];
    
    $words = '';
    
    if ($amount == 0) {
        $words = 'صفر';
    } else {
        // أقل من 1000
        if ($amount < 1000) {
            $hundreds_value = floor($amount / 100);
            $tens_value = $amount % 100;
            
            if ($hundreds_value > 0) {
                $words .= $hundreds[$hundreds_value] . ' ';
            }
            
            if ($tens_value > 0) {
                if ($tens_value < 13) {
                    $words .= $units[$tens_value] . ' ';
                } else {
                    $unit_value = $tens_value % 10;
                    $ten_value = floor($tens_value / 10);
                    
                    if ($unit_value > 0) {
                        $words .= $units[$unit_value] . ' و';
                    }
                    
                    $words .= $tens[$ten_value] . ' ';
                }
            }
        }
        // أقل من مليون
        else if ($amount < 1000000) {
            $thousands_value = floor($amount / 1000);
            $remainder = $amount % 1000;
            
            if ($thousands_value == 1) {
                $words .= $thousands[1] . ' ';
            } elseif ($thousands_value == 2) {
                $words .= $thousands[2] . ' ';
            } elseif ($thousands_value >= 3 && $thousands_value <= 10) {
                $words .= $units[$thousands_value] . ' ' . $thousands[3] . ' ';
            } else {
                $words .= numberToArabicWords($thousands_value, '') . ' ' . $thousands[4] . ' ';
            }
            
            if ($remainder > 0) {
                $words .= 'و' . numberToArabicWords($remainder, '') . ' ';
            }
        }
        // أقل من مليار
        else if ($amount < 1000000000) {
            $millions_value = floor($amount / 1000000);
            $remainder = $amount % 1000000;
            
            if ($millions_value == 1) {
                $words .= $millions[1] . ' ';
            } elseif ($millions_value == 2) {
                $words .= $millions[2] . ' ';
            } elseif ($millions_value >= 3 && $millions_value <= 10) {
                $words .= $units[$millions_value] . ' ' . $millions[3] . ' ';
            } else {
                $words .= numberToArabicWords($millions_value, '') . ' ' . $millions[4] . ' ';
            }
            
            if ($remainder > 0) {
                $words .= 'و' . numberToArabicWords($remainder, '') . ' ';
            }
        }
        // أكبر من مليار
        else {
            $billions_value = floor($amount / 1000000000);
            $remainder = $amount % 1000000000;
            
            if ($billions_value == 1) {
                $words .= $billions[1] . ' ';
            } elseif ($billions_value == 2) {
                $words .= $billions[2] . ' ';
            } elseif ($billions_value >= 3 && $billions_value <= 10) {
                $words .= $units[$billions_value] . ' ' . $billions[3] . ' ';
            } else {
                $words .= numberToArabicWords($billions_value, '') . ' ' . $billions[4] . ' ';
            }
            
            if ($remainder > 0) {
                $words .= 'و' . numberToArabicWords($remainder, '') . ' ';
            }
        }
    }
    
    // إضافة العملة
    if ($currency) {
        $words .= $currency;
    }
    
    // إضافة الكسور
    if ($fraction > 0) {
        $words .= ' و' . numberToArabicWords($fraction, $currency == 'دينار' ? 'فلس' : 'سنت');
    }
    
    return $words;
}
