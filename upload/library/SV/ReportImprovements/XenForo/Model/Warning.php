<?php

class SV_ReportImprovements_XenForo_Model_Warning extends XFCP_SV_ReportImprovements_XenForo_Model_Warning
{
    public function processExpiredWarnings()
    {
        $options = XenForo_Application::getOptions();
        SV_ReportImprovements_Globals::$OverrideReportUserId = $options->sv_ri_user_id;
        SV_ReportImprovements_Globals::$OverrideReportUsername = null;
        SV_ReportImprovements_Globals::$ResolveReport = true;
        SV_ReportImprovements_Globals::$AssignReport = false;
        if (!$options->sv_ri_log_to_report_natural_warning_expire)
        {
            SV_ReportImprovements_Globals::$SupressLoggingWarningToReport = true;
        }
        try
        {
            parent::processExpiredWarnings();
        }
        finally
        {
            SV_ReportImprovements_Globals::$OverrideReportUserId = null;
            SV_ReportImprovements_Globals::$SupressLoggingWarningToReport = false;
        }
    }
}