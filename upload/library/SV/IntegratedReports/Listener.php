<?php

class SV_IntegratedReports_Listener
{
    public static function load_class($class, array &$extend)
    {
        switch ($class)
        {
            case 'XenForo_Model_Conversation':
                $extend[] = 'SV_IntegratedReports_XenForo_Model_Conversation';
                break;
            case 'XenForo_Model_Forum':
                $extend[] = 'SV_IntegratedReports_XenForo_Model_Forum';
                break;
            case 'XenForo_Model_User':
                $extend[] = 'SV_IntegratedReports_XenForo_Model_User';
                break;
            case 'XenForo_ReportHandler_ProfilePost':
                $extend[] = 'SV_IntegratedReports_XenForo_ReportHandler_ProfilePost';
                break;
            case 'XenForo_ReportHandler_Post':
                $extend[] = 'SV_IntegratedReports_XenForo_ReportHandler_Post';
                break;
        }
    }


}