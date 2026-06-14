<?php

 /* Log admin activity for audit trail
 * Used in: admin/api/add_product.php, admin/products/add_product.php, admin/products/delete_product.php
 */
function logAdminActivity($action, $type, $entityId, $details = '') {
    $logFile = 'admin/logs/admin_activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $adminId = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'unknown';
    
    $logEntry = "[$timestamp] [Admin: $adminId] Action: $action, Type: $type, ID: $entityId";
    if ($details) {
        $logEntry .= ", Details: $details";
    }
    $logEntry .= "\n";
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>