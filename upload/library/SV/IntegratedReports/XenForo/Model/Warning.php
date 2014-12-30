<?php

class SV_IntegratedReports_XenForo_Model_Warning extends XFCP_SV_IntegratedReports_XenForo_Model_Warning
{
    public function processExpiredWarnings()
    {
        SV_IntegratedReports_Model_WarningLog::$UseSystemUsernameForComments = true;
        parent::processExpiredWarnings();
    }
}