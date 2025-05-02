<?php
/**
 * نموذج الفاتورة
 * يدير عمليات الفواتير والطلبات
 */
class Invoice {
    private $db;
    
    /**
     * إنشاء كائن نموذج الفاتورة
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * إنشاء فاتورة جديدة
     */
    public function create($invoiceData) {
        // التحقق من وجود رقم الهاتف في البلاك ليست
        if (isset($invoiceData['phone']) && $this->isPhoneBlacklisted($invoiceData['phone'])) {
            $invoiceData['requires_full_payment'] = 1;
        }
        
        // إضافة حقول إضافية
        $invoiceData['created_at'] = date('Y-m-d H:i:s');
        $invoiceData['created_by'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $invoiceData['status'] = isset($invoiceData['status']) ? $invoiceData['status'] : 'جديد';
        
        // بدء المعاملة
        $this->db->beginTransaction();
        
        try {
            // إدراج الفاتورة
            $invoiceId = $this->db->insert('invoices', $invoiceData);
            
            // تسجيل صور التكرارات إذا وجدت
            if (isset($invoiceData['repeat_images']) && is_array($invoiceData['repeat_images'])) {
                foreach ($invoiceData['repeat_images'] as $image) {
                    $this->db->insert('invoice_images', [
                        'invoice_id' => $invoiceId,
                        'image_path' => $image,
                        'type' => 'repeat'
                    ]);
                }
            }
            
            // تسجيل صور القطع المخصصة إذا وجدت
            if (isset($invoiceData['custom_images']) && is_array($invoiceData['custom_images'])) {
                foreach ($invoiceData['custom_images'] as $image) {
                    $this->db->insert('invoice_images', [
                        'invoice_id' => $invoiceId,
                        'image_path' => $image,
                        'type' => 'custom'
                    ]);
                }
            }
            
            // تسجيل صورة تحويل العربون إذا وجدت
            if (isset($invoiceData['deposit_image']) && !empty($invoiceData['deposit_image'])) {
                $this->db->insert('invoice_images', [
                    'invoice_id' => $invoiceId,
                    'image_path' => $invoiceData['deposit_image'],
                    'type' => 'deposit'
                ]);
            }
            
            // تنفيذ المعاملة
            $this->db->commit();
            
            // إنشاء إشعار
            $notification = Notification::getInstance();
            $notification->notifyNewInvoice($invoiceId, $invoiceData);
            
            return ['success' => true, 'invoice_id' => $invoiceId];
        } catch (Exception $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $this->db->rollback();
            return ['error' => 'فشل في إنشاء الفاتورة: ' . $e->getMessage()];
        }
    }
    
    /**
     * تحديث معلومات الفاتورة
     */
    public function update($id, $invoiceData) {
        // التحقق من وجود الفاتورة
        if (!$this->exists(['id' => $id])) {
            return ['error' => 'الفاتورة غير موجودة'];
        }
        
        // إضافة حقل التحديث
        $invoiceData['updated_at'] = date('Y-m-d H:i:s');
        $invoiceData['updated_by'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        
        // بدء المعاملة
        $this->db->beginTransaction();
        
        try {
            // تحديث الفاتورة
            $this->db->update('invoices', $invoiceData, 'id = :id', ['id' => $id]);
            
            // تحديث الصور إذا وجدت
            if (isset($invoiceData['repeat_images']) && is_array($invoiceData['repeat_images'])) {
                // حذف الصور القديمة
                $this->db->delete('invoice_images', 'invoice_id = :invoice_id AND type = :type', [
                    'invoice_id' => $id,
                    'type' => 'repeat'
                ]);
                
                // إدراج الصور الجديدة
                foreach ($invoiceData['repeat_images'] as $image) {
                    $this->db->insert('invoice_images', [
                        'invoice_id' => $id,
                        'image_path' => $image,
                        'type' => 'repeat'
                    ]);
                }
            }
            
            if (isset($invoiceData['custom_images']) && is_array($invoiceData['custom_images'])) {
                // حذف الصور القديمة
                $this->db->delete('invoice_images', 'invoice_id = :invoice_id AND type = :type', [
                    'invoice_id' => $id,
                    'type' => 'custom'
                ]);
                
                // إدراج الصور الجديدة
                foreach ($invoiceData['custom_images'] as $image) {
                    $this->db->insert('invoice_images', [
                        'invoice_id' => $id,
                        'image_path' => $image,
                        'type' => 'custom'
                    ]);
                }
            }
            
            if (isset($invoiceData['deposit_image']) && !empty($invoiceData['deposit_image'])) {
                // حذف الصور القديمة
                $this->db->delete('invoice_images', 'invoice_id = :invoice_id AND type = :type', [
                    'invoice_id' => $id,
                    'type' => 'deposit'
                ]);
                
                // إدراج الصورة الجديدة
                $this->db->insert('invoice_images', [
                    'invoice_id' => $id,
                    'image_path' => $invoiceData['deposit_image'],
                    'type' => 'deposit'
                ]);
            }
            
            // تنفيذ المعاملة
            $this->db->commit();
            
            return ['success' => true];
        } catch (Exception $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $this->db->rollback();
            return ['error' => 'فشل في تحديث الفاتورة: ' . $e->getMessage()];
        }
    }
    
    /**
     * حذف فاتورة
     */
    public function delete($id) {
        // التحقق من وجود الفاتورة
        $invoice = $this->getById($id);
        if (!$invoice) {
            return ['error' => 'الفاتورة غير موجودة'];
        }
        
        // التحقق من صلاحية الحذف
        $auth = Auth::getInstance();
        if (!$auth->canDeleteInvoice($id)) {
            return ['error' => 'ليس لديك صلاحية لحذف هذه الفاتورة'];
        }
        
        // بدء المعاملة
        $this->db->beginTransaction();
        
        try {
            // نقل الفاتورة إلى جدول الفواتير المحذوفة
            $invoice['deleted_at'] = date('Y-m-d H:i:s');
            $invoice['deleted_by'] = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            
            // إدراج في جدول الفواتير المحذوفة
            $this->db->insert('deleted_invoices', $invoice);
            
            // نقل صور الفاتورة إلى جدول صور الفواتير المحذوفة
            $images = $this->getInvoiceImages($id);
            
            foreach ($images as $image) {
                $image['deleted_at'] = date('Y-m-d H:i:s');
                $this->db->insert('deleted_invoice_images', $image);
            }
            
            // حذف صور الفاتورة من الجدول الأصلي
            $this->db->delete('invoice_images', 'invoice_id = :id', ['id' => $id]);
            
            // حذف الفاتورة من الجدول الأصلي
            $this->db->delete('invoices', 'id = :id', ['id' => $id]);
            
            // تنفيذ المعاملة
            $this->db->commit();
            
            // إنشاء إشعار
            $notification = Notification::getInstance();
            $notification->notifyDeletedInvoice($id, $invoice);
            
            return ['success' => true];
        } catch (Exception $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $this->db->rollback();
            return ['error' => 'فشل في حذف الفاتورة: ' . $e->getMessage()];
        }
    }
    
    /**
     * استعادة فاتورة محذوفة
     */
    public function restore($id) {
        // التحقق من وجود الفاتورة المحذوفة
        $sql = "SELECT * FROM deleted_invoices WHERE id = :id LIMIT 1";
        $invoice = $this->db->fetch($sql, ['id' => $id]);
        
        if (!$invoice) {
            return ['error' => 'الفاتورة المحذوفة غير موجودة'];
        }
        
        // بدء المعاملة
        $this->db->beginTransaction();
        
        try {
            // حذف حقول الحذف
            unset($invoice['deleted_at']);
            unset($invoice['deleted_by']);
            
            // إدراج الفاتورة في الجدول الأصلي
            $this->db->insert('invoices', $invoice);
            
            // استعادة صور الفاتورة
            $sql = "SELECT * FROM deleted_invoice_images WHERE invoice_id = :id";
            $images = $this->db->fetchAll($sql, ['id' => $id]);
            
            foreach ($images as $image) {
                // حذف حقل الحذف
                unset($image['deleted_at']);
                
                // إدراج الصورة في الجدول الأصلي
                $this->db->insert('invoice_images', $image);
            }
            
            // حذف الفاتورة وصورها من جداول المحذوفات
            $this->db->delete('deleted_invoice_images', 'invoice_id = :id', ['id' => $id]);
            $this->db->delete('deleted_invoices', 'id = :id', ['id' => $id]);
            
            // تنفيذ المعاملة
            $this->db->commit();
            
            return ['success' => true];
        } catch (Exception $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $this->db->rollback();
            return ['error' => 'فشل في استعادة الفاتورة: ' . $e->getMessage()];
        }
    }
    
    /**
     * التحقق من وجود فاتورة
     */
    public function exists($conditions) {
        $where = [];
        $params = [];
        
        foreach ($conditions as $field => $value) {
            $where[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        return $this->db->exists('invoices', implode(' AND ', $where), $params);
    }
    
    /**
     * الحصول على فاتورة بواسطة المعرف
     */
    public function getById($id) {
        $sql = "SELECT i.*, p.name as page_name
                FROM invoices i
                LEFT JOIN pages p ON i.page_id = p.id
                WHERE i.id = :id LIMIT 1";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    /**
     * الحصول على صور الفاتورة
     */
    public function getInvoiceImages($invoiceId) {
        $sql = "SELECT * FROM invoice_images WHERE invoice_id = :invoice_id";
        return $this->db->fetchAll($sql, ['invoice_id' => $invoiceId]);
    }
    
    /**
     * الحصول على قائمة الفواتير
     */
    public function getAll($limit = null, $offset = 0, $orderBy = 'id', $orderDir = 'DESC') {
        $sql = "SELECT i.*, u.username as created_by_username, p.name as page_name
                FROM invoices i
                LEFT JOIN users u ON i.created_by = u.id
                LEFT JOIN pages p ON i.page_id = p.id
                ORDER BY i.{$orderBy} {$orderDir}";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * عدد الفواتير
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) FROM invoices";
        
        if (!empty($conditions)) {
            $where = [];
            $params = [];
            
            foreach ($conditions as $field => $value) {
                $where[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            
            $sql .= " WHERE " . implode(' AND ', $where);
            
            return $this->db->fetchColumn($sql, $params);
        }
        
        return $this->db->fetchColumn($sql);
    }
    
    /**
     * البحث عن الفواتير
     */
    public function search($keyword, $limit = null, $offset = 0) {
        $keyword = '%' . $keyword . '%';
        
        $sql = "SELECT i.*, u.username as created_by_username, p.name as page_name
                FROM invoices i
                LEFT JOIN users u ON i.created_by = u.id
                LEFT JOIN pages p ON i.page_id = p.id
                WHERE i.id LIKE :keyword 
                OR i.customer_name LIKE :keyword 
                OR i.phone LIKE :keyword 
                OR i.province LIKE :keyword 
                OR i.cart_number LIKE :keyword 
                ORDER BY i.id DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql, ['keyword' => $keyword]);
    }
    
    /**
     * الحصول على فواتير المستخدم
     */
    public function getUserInvoices($userId, $limit = null, $offset = 0) {
        $sql = "SELECT i.*, p.name as page_name
                FROM invoices i
                LEFT JOIN pages p ON i.page_id = p.id
                WHERE i.created_by = :user_id
                ORDER BY i.id DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }
    
    /**
     * الحصول على الفواتير حسب الحالة
     */
    public function getByStatus($status, $limit = null, $offset = 0) {
        $sql = "SELECT i.*, u.username as created_by_username, p.name as page_name
                FROM invoices i
                LEFT JOIN users u ON i.created_by = u.id
                LEFT JOIN pages p ON i.page_id = p.id
                WHERE i.status = :status
                ORDER BY i.id DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql, ['status' => $status]);
    }
    
    /**
     * الحصول على الفواتير المحذوفة
     */
    public function getDeleted($limit = null, $offset = 0) {
        $sql = "SELECT d.*, u.username as deleted_by_username, c.username as created_by_username
                FROM deleted_invoices d
                LEFT JOIN users u ON d.deleted_by = u.id
                LEFT JOIN users c ON d.created_by = c.id
                ORDER BY d.deleted_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * تحديث حالة الفاتورة
     */
    public function updateStatus($id, $status) {
        // الحصول على الفاتورة الحالية
        $invoice = $this->getById($id);
        if (!$invoice) {
            return ['error' => 'الفاتورة غير موجودة'];
        }
        
        $oldStatus = $invoice['status'];
        
        // تحديث الحالة
        $updated = $this->db->update('invoices', 
            ['status' => $status, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $_SESSION['user_id']], 
            'id = :id', 
            ['id' => $id]
        );
        
        if ($updated) {
            // إنشاء إشعار بتغيير الحالة
            $notification = Notification::getInstance();
            $notification->notifyStatusChange($id, $invoice, $oldStatus, $status);
            
            return ['success' => true];
        }
        
        return ['error' => 'فشل في تحديث حالة الفاتورة'];
    }
    
    /**
     * إضافة رقم سلة للفاتورة
     */
    public function addCartNumber($id, $cartNumber) {
        return $this->db->update('invoices', 
            ['cart_number' => $cartNumber, 'updated_at' => date('Y-m-d H:i:s'), 'updated_by' => $_SESSION['user_id']], 
            'id = :id', 
            ['id' => $id]
        );
    }
    
    /**
     * إضافة أرقام سلات لمجموعة من الفواتير
     */
    public function addCartNumbers($startId, $endId, $startCartNumber) {
        $success = 0;
        $failed = 0;
        
        for ($id = $startId; $id <= $endId; $id++) {
            $cartNumber = $startCartNumber + ($id - $startId);
            
            if ($this->exists(['id' => $id])) {
                $result = $this->addCartNumber($id, $cartNumber);
                
                if ($result) {
                    $success++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }
        
        return [
            'success' => $success,
            'failed' => $failed,
            'total' => ($endId - $startId + 1)
        ];
    }
    
    /**
     * حساب تكلفة الفاتورة
     */
    public function calculateCost($basketPrice, $itemCount, $province) {
        // تحويل أسعار السلة إلى رقم
        $basketPrice = floatval($basketPrice);
        $itemCount = intval($itemCount);
        
        // تكلفة التوصيل
        $deliveryCost = ($province === 'بغداد') ? BAGHDAD_DELIVERY_COST : PROVINCES_DELIVERY_COST;
        
        // حساب التكلفة حسب القواعد
        if ($basketPrice > 400) {
            // أكثر من 400 دولار: سعر السلة × 1300
            $costIQD = $basketPrice * EXCHANGE_RATE;
        } elseif ($basketPrice >= 200) {
            // بين 200 و400 دولار: (سعر السلة + ربع عدد القطع) × 1300
            $costIQD = ($basketPrice + ($itemCount * 0.25)) * EXCHANGE_RATE;
        } else {
            // أقل من 200 دولار: (سعر السلة + نصف عدد القطع) × 1300
            $costIQD = ($basketPrice + ($itemCount * 0.5)) * EXCHANGE_RATE;
        }
        
        // إضافة تكلفة التوصيل
        $totalCostIQD = $costIQD + $deliveryCost;
        
        // حساب قيمة العربون
        $depositIQD = 0;
        if ($totalCostIQD > 70000) {
            if ($basketPrice > 400) {
                // للطلبات فوق 400 دولار: نصف المبلغ
                $depositIQD = $totalCostIQD * 0.5;
            } else {
                // للطلبات العادية: بين ربع وثلث المبلغ
                $depositIQD = $totalCostIQD * 0.3; // استخدام 30% كمتوسط
            }
            
            // تقريب العربون لأقرب 5000 دينار للأعلى
            $depositIQD = round_up_to_nearest_5000($depositIQD);
        }
        
        return [
            'basket_price_usd' => $basketPrice,
            'basket_price_iqd' => $basketPrice * EXCHANGE_RATE,
            'delivery_cost' => $deliveryCost,
            'total_cost_iqd' => $totalCostIQD,
            'deposit_iqd' => $depositIQD,
            'remaining_iqd' => $totalCostIQD - $depositIQD,
            'exchange_rate' => EXCHANGE_RATE
        ];
    }
    
    /**
     * التحقق من وجود رقم هاتف في البلاك ليست
     */
    public function isPhoneBlacklisted($phone) {
        return $this->db->exists('blacklist', 'phone = :phone', ['phone' => $phone]);
    }
    
    /**
     * إنشاء رسالة مُنسقة للزبون
     */
    public function createCustomerMessage($calculationData, $pageData) {
        $message = "✅ *تم حساب سعر طلبك بنجاح* ✅\n\n";
        $message .= "🛍️ *تفاصيل الطلب:*\n";
        $message .= "💲 سعر السلة: " . number_format($calculationData['basket_price_usd'], 2) . " دولار\n";
        $message .= "💵 سعر الصرف: " . number_format(EXCHANGE_RATE) . " دينار\n";
        $message .= "🚚 سعر التوصيل: " . number_format($calculationData['delivery_cost']) . " دينار\n\n";
        
        $message .= "💰 *التكلفة الإجمالية:* " . number_format($calculationData['total_cost_iqd']) . " دينار\n\n";
        
        if ($calculationData['deposit_iqd'] > 0) {
            $message .= "⚠️ *العربون المطلوب:* " . number_format($calculationData['deposit_iqd']) . " دينار\n";
            $message .= "⏳ *المبلغ المتبقي عند الاستلام:* " . number_format($calculationData['remaining_iqd']) . " دينار\n\n";
        }
        
        $message .= "📞 *للطلب والاستفسار:*\n";
        $message .= "📱 " . $pageData['phone'] . "\n";
        $message .= "📱 " . $pageData['alternate_phone'] . "\n";
        $message .= "📷 " . $pageData['instagram'] . "\n";
        
        return $message;
    }
    
    /**
     * الحصول على إحصائيات الفواتير
     */
    public function getStatistics() {
        $stats = [
            'total' => $this->count(),
            'by_status' => [],
            'total_sales' => 0,
            'today_sales' => 0,
            'today_count' => 0,
            'average_price' => 0
        ];
        
        // عدد الفواتير حسب الحالة
        $sql = "SELECT status, COUNT(*) as count FROM invoices GROUP BY status";
        $result = $this->db->fetchAll($sql);
        
        foreach ($result as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        // إجمالي المبيعات
        $sql = "SELECT SUM(total_price) as total FROM invoices";
        $stats['total_sales'] = $this->db->fetchColumn($sql) ?: 0;
        
        // مبيعات اليوم
        $today = date('Y-m-d');
        $sql = "SELECT COUNT(*) as count, SUM(total_price) as total FROM invoices WHERE DATE(created_at) = :today";
        $todayStats = $this->db->fetch($sql, ['today' => $today]);
        
        $stats['today_sales'] = $todayStats['total'] ?: 0;
        $stats['today_count'] = $todayStats['count'] ?: 0;
        
        // متوسط سعر الفاتورة
        if ($stats['total'] > 0) {
            $stats['average_price'] = $stats['total_sales'] / $stats['total'];
        }
        
        return $stats;
    }
}