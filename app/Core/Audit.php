<?php
namespace App\Core;

defined('APP') or exit;

/** Logs de auditoria. */
class Audit {
    public static function log($userId, $action, $entity = null, $entityId = null, $details = null) {
        try {
            Database::insert('audit_logs', [
                'user_id' => $userId,
                'action' => mb_substr((string) $action, 0, 80),
                'entity' => $entity ? mb_substr((string) $entity, 0, 60) : null,
                'entity_id' => $entityId,
                'details' => $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                'ip' => client_ip(),
                'created_at' => now(),
            ]);
        } catch (\Exception $e) { /* nunca quebrar o fluxo por causa de log */ }
    }
}
