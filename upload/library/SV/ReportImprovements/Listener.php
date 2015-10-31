<?php

class SV_ReportImprovements_Listener
{
    const AddonNameSpace = 'SV_ReportImprovements_';

    public static function load_class($class, array &$extend)
    {
        switch ($class)
        {
            case 'XenForo_Model_Report':
                if (XenForo_Application::$versionId <= 1040370)
                    $extend[] = self::AddonNameSpace.$class.'Patch';
                break;

        }
        $extend[] = self::AddonNameSpace.$class;
    }

    public static function controller_pre_dispatch(XenForo_Controller $controller, $action, $controllerName)
    {
        if (!XenForo_Application::isRegistered('session'))
        {
            return;
        }

        $visitor = XenForo_Visitor::getInstance();
        if ($visitor['is_moderator'])
        {
            return;
        }

        $reportModel = $controller->getModelFromCache('XenForo_Model_Report');
        if (!$reportModel->canViewReports())
        {
            return;
        }

        $visitor['canViewReports'] = true;

        if (XenForo_Application::isRegistered('reportCounts'))
        {
            $reportCounts = XenForo_Application::get('reportCounts');
        }
        else
        {
            $reportCounts = $reportModel->rebuildReportCountCache();
        }

        $session = XenForo_Application::get('session');
        $sessionReportCounts = $session->get('reportCounts');

        if (!is_array($sessionReportCounts) || $sessionReportCounts['lastBuildDate'] < $reportCounts['lastModifiedDate'])
        {
            if (!$reportCounts['activeCount'])
            {
                $sessionReportCounts = array(
                    'total' => 0,
                    'assigned' => 0
                );
            }
            else
            {
                $sessionReportCounts = $reportModel->getActiveReportsCountsForUser();
            }

            $sessionReportCounts['lastBuildDate'] = XenForo_Application::$time;
            $session->set('reportCounts', $sessionReportCounts);
        }
    }
}