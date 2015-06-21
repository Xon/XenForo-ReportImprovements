<?php

class SV_ReportImprovements_XenForo_Model_Warning extends XFCP_SV_ReportImprovements_XenForo_Model_Warning
{
    public function processExpiredWarnings()
    {
        SV_ReportImprovements_Globals::$UseSystemUsernameForComments = true;
        $options = XenForo_Application::getOptions();
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
            SV_ReportImprovements_Globals::$UseSystemUsernameForComments = false;
            SV_ReportImprovements_Globals::$SupressLoggingWarningToReport = false;
        }
    }
}