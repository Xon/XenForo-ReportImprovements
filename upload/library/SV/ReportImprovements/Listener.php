<?php

class SV_ReportImprovements_Listener
{
    const AddonNameSpace = 'SV_ReportImprovements_';

    public static function load_class($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace . $class;
    }

    public static function load_class_patch($class, array &$extend)
    {
        $extend[] = self::AddonNameSpace . $class . 'Patch';
    }

    public static function controller_pre_dispatch(XenForo_Controller $controller, $action, $controllerName)
    {
        $visitor = XenForo_Visitor::getInstance();
        if (!XenForo_Application::isRegistered('session') ||
            SV_ReportImprovements_Globals::$disablePreDispatch ||
            isset($visitor['canViewReports']))
        {
            return;
        }

        if (!empty($visitor['is_moderator']))
        {
            $visitor['canViewReports'] = true;
            $visitor['canSearchReports'] = $visitor->canSearch();
            return;
        }

        /** @var SV_ReportImprovements_XenForo_Model_Report $reportModel */
        $reportModel = $controller->getModelFromCache('XenForo_Model_Report');
        try
        {
            if (!is_callable(array($reportModel,'canViewReports')) || !$reportModel->canViewReports())
            {
                $visitor['canViewReports'] = $visitor['canSearchReports'] = false;
                return;
            }
        }
        catch (Exception $e)
        {
            XenForo_Error::logException($e);

            $visitor['canViewReports'] = $visitor['canSearchReports'] = false;
            return;
        }

        $visitor['canViewReports'] = true;
        $visitor['canSearchReports'] = $visitor->canSearch();

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