<?php

namespace hardMOB\Afiliados\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Audit Log Entity for tracking all system operations
 * 
 * @property int $log_id
 * @property string $event_type
 * @property string $entity_type
 * @property int $entity_id
 * @property int $user_id
 * @property string $ip_address
 * @property string $user_agent
 * @property array $old_data
 * @property array $new_data
 * @property string $description
 * @property int $created_date
 * @property \XF\Entity\User $User
 */
class AuditLog extends Entity
{
    const EVENT_CREATE = 'create';
    const EVENT_UPDATE = 'update';
    const EVENT_DELETE = 'delete';
    const EVENT_ACCESS = 'access';
    const EVENT_SECURITY = 'security';
    const EVENT_LOGIN = 'login';
    const EVENT_LOGOUT = 'logout';

    /**
     * Log an event
     */
    public static function logEvent($eventType, $entityType = null, $entityId = null, $description = '', $oldData = [], $newData = [])
    {
        $app = \XF::app();
        $request = $app->request();
        $visitor = \XF::visitor();

        $log = $app->em()->create('hardMOB\Afiliados:AuditLog');
        $log->event_type = $eventType;
        $log->entity_type = $entityType;
        $log->entity_id = $entityId;
        $log->user_id = $visitor->user_id;
        $log->ip_address = $request->getIp(true);
        $log->user_agent = substr($request->getServer('HTTP_USER_AGENT', ''), 0, 500);
        $log->description = $description;
        $log->old_data = $oldData;
        $log->new_data = $newData;
        $log->created_date = \XF::$time;

        try {
            $log->save();
        } catch (\Exception $e) {
            // Log to error log if audit log fails
            \XF::logError('Failed to save audit log: ' . $e->getMessage());
        }

        return $log;
    }

    protected function _postSave()
    {
        // Clean up old logs periodically
        if (rand(1, 100) === 1) { // 1% chance
            $this->cleanupOldLogs();
        }
    }

    protected function cleanupOldLogs()
    {
        $options = \XF::app()->options();
        $retentionDays = $options->hardmob_afiliados_log_retention ?? 90;
        $cutoffDate = \XF::$time - ($retentionDays * 86400);

        $this->db()->delete('xf_hardmob_affiliate_audit_logs', 'created_date < ?', $cutoffDate);
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_hardmob_affiliate_audit_logs';
        $structure->shortName = 'hardMOB\Afiliados:AuditLog';
        $structure->primaryKey = 'log_id';
        
        $structure->columns = [
            'log_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'event_type' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'entity_type' => ['type' => self::STR, 'maxLength' => 100],
            'entity_id' => ['type' => self::UINT, 'default' => 0],
            'user_id' => ['type' => self::UINT, 'default' => 0],
            'ip_address' => ['type' => self::BINARY, 'maxLength' => 16],
            'user_agent' => ['type' => self::STR, 'maxLength' => 500],
            'old_data' => ['type' => self::JSON_ARRAY, 'default' => []],
            'new_data' => ['type' => self::JSON_ARRAY, 'default' => []],
            'description' => ['type' => self::STR, 'maxLength' => 500],
            'created_date' => ['type' => self::UINT, 'required' => true]
        ];

        $structure->getters = [];
        
        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ]
        ];

        return $structure;
    }
}