<?php

class SV_ReportImprovements_Listener
{
    const AddonNameSpace = 'SV_ReportImprovements_';

    public static function load_class($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace.$class;
    }

    public static function load_class_patch($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace.$class.'Patch';
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
        try
        {
            if (!$reportModel->canViewReports())
            {
                return;
            }
        }
        catch(Exception $e)
        {
            XenForo_Error::logException($e);
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