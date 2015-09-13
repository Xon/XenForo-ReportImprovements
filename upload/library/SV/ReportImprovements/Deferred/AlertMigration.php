<?php

class SV_ReportImprovements_Deferred_AlertMigration extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $increment = 200;
        $min_alert_id = isset($data['alert_id']) ? $data['alert_id'] : -1;

        $db = XenForo_Application::getDb();

        $alertQuery = $db->query("
            select max(alert_id) as max_alert_id
            from xf_user_alert
            where content_type = 'report' and alert_id > ?
        ", array($min_alert_id));
        $alertRows = $alertQuery->fetchAll();

        if (empty($alertRows) || empty($alertRows[0]) || empty($alertRows[0]['max_alert_id']))
        {
           return false;
        }

        $alertQuery = $db->query("
            SELECT *
            FROM xf_user_alert
            where content_type = 'report' and alert_id >= ?
            LIMIT ".$increment."
        ", array($min_alert_id));

        $last_alert_id = false;
        $alertRows = $alertQuery->fetchAll();
        if (!empty($alertRows))
        {
            foreach($alertRows as &$alert)
            {
                if ($alert['action'] != 'comment' && $alert['action'] != 'tag')
                {
                    continue;
                }

                $extra_data = @unserialize($alert['extra_data']);

                if (empty($extra_data['report_comment_id']))
                {
                    $db->query("
                        DELET FROM xf_user_alert
                        WHERE alert_id = ?
                    ", array($alert['alert_id']));
                }
                else
                {
                    $db->query("
                        UPDATE xf_user_alert
                        SET content_type = 'report_comment', action = 'insert', extra_data = '', content_id = ?
                        WHERE alert_id = ?
                    ", array($extra_data['report_comment_id'], $alert['alert_id']));
                }

                $last_alert_id = $alert['alert_id'];
            }
        }
        XenForo_Db::commit($db);

        if (empty($last_alert_id))
        {
            return false;
        }

        return array('alert_id' => $last_alert_id);
    }
}
