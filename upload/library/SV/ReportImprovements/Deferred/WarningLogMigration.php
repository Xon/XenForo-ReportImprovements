<?php

class SV_ReportImprovements_Deferred_WarningLogMigration extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $increment = 100;
        $min_warning_id = isset($data['warning_id']) ? $data['warning_id'] : -1;

        $db = XenForo_Application::getDb();

        $warningQuery = $db->query("
            select max(warning_id) as max_warning_id
            from xf_warning
            where warning_id > ? and warning_id < (? + ?)
        ", array($min_warning_id,$min_warning_id, $increment));
        $warningRows = $warningQuery->fetchAll();

        if (empty($warningRows) || empty($warningRows[0]) || empty($warningRows[0]['max_warning_id']))
        {
           return false;
        }

        $max_warning_id = $warningRows[0]['max_warning_id'];
        $actionPhrase = new XenForo_Phrase('sv_ri_migrating');

        $status = sprintf('%s... %s', $actionPhrase, str_repeat(' . ', $max_warning_id / 10));

        $warningLogModel = XenForo_Model::create("SV_ReportImprovements_Model_WarningLog");

        // Except for the sub-select, this list must match SV_ReportImprovements_Model_WarningLog::_getLogData()
        $warningQuery = $db->query("
            SELECT
                warning_id,
                content_type,
                content_id,
                content_title,
                user_id,
                warning_date,
                warning_user_id,
                warning_definition_id,
                title,
                notes,
                points,
                expiry_date,
                is_expired,
                extra_user_group_ids,

                (select username from xf_user where xf_user.user_id = xf_warning.warning_user_id) as warning_username
            FROM xf_warning
            where
                warning_id not in (select warning_id from xf_sv_warning_log)
                and warning_id >= ? and warning_id <= ?
        ", array($min_warning_id, $max_warning_id));

        $warningRows = $warningQuery->fetchAll();
        if (!empty($warningRows))
        {
            SV_ReportImprovements_Globals::$UseSystemUsernameForComments = true;
            foreach($warningRows as $warning)
            {
                SV_ReportImprovements_Globals::$SystemUserId = $warning['warning_user_id'];
                SV_ReportImprovements_Globals::$SystemUsername = $warning['warning_username'];
                SV_ReportImprovements_Globals::$resolve_report = true;
                SV_ReportImprovements_Globals::$UseWarningTimeStamp = true;
                unset($warning['warning_username']);

                $warningLogModel->LogOperation(SV_ReportImprovements_Model_WarningLog::Operation_NewWarning, $warning);
            }
        }
        XenForo_Db::commit($db);

        return array('warning_id' => $max_warning_id);
    }
}
