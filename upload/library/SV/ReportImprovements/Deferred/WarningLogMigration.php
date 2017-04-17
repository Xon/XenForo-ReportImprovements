<?php

class SV_ReportImprovements_Deferred_WarningLogMigration extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $increment = 100;
        $min_warning_id = isset($data['warning_id']) ? $data['warning_id'] : -1;

        $db = XenForo_Application::getDb();

        $warningQuery = $db->query("
            SELECT max(warning_id) AS max_warning_id
            FROM xf_warning
            WHERE warning_id > ? AND warning_id < (? + ?)
        ", array($min_warning_id, $min_warning_id, $increment));
        $warningRows = $warningQuery->fetchAll();

        if (empty($warningRows) || empty($warningRows[0]) || empty($warningRows[0]['max_warning_id']))
        {
            return false;
        }

        $max_warning_id = $warningRows[0]['max_warning_id'];
        $actionPhrase = new XenForo_Phrase('sv_ri_migrating');

        $status = sprintf('%s... %s', $actionPhrase, str_repeat(' . ', $max_warning_id / 10));

        /** @var SV_ReportImprovements_Model_WarningLog $warningLogModel */
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

                (SELECT username FROM xf_user WHERE xf_user.user_id = xf_warning.warning_user_id) AS warning_username
            FROM xf_warning
            WHERE
                warning_id NOT IN (SELECT warning_id FROM xf_sv_warning_log)
                AND warning_id >= ? AND warning_id <= ?
        ", array($min_warning_id, $max_warning_id));

        $warningRows = $warningQuery->fetchAll();
        if (!empty($warningRows))
        {
            // make sure the add-on is enabled
            XenForo_DataWriter::create('XenForo_DataWriter_ReportComment');
            if (!class_exists('XFCP_SV_ReportImprovements_XenForo_DataWriter_ReportComment', false))
            {
                throw new Exception('Please enable the Report Improvements add-on and install/upgrade it again to migrate warnings');
            }

            XenForo_Db::beginTransaction($db);
            foreach ($warningRows as $warning)
            {
                SV_ReportImprovements_Globals::$OverrideReportUserId = $warning['warning_user_id'];
                SV_ReportImprovements_Globals::$OverrideReportUsername = $warning['warning_username'];
                SV_ReportImprovements_Globals::$ResolveReport = true;
                SV_ReportImprovements_Globals::$AssignReport = true;
                unset($warning['warning_username']);

                $warningLogModel->LogOperation(SV_ReportImprovements_Model_WarningLog::Operation_NewWarning, $warning, true);
            }
            XenForo_Db::commit($db);
        }

        return array('warning_id' => $max_warning_id);
    }
}
