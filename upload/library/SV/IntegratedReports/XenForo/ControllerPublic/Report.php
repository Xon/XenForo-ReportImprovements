<?php

class SV_IntegratedReports_XenForo_ControllerPublic_Report extends XFCP_SV_IntegratedReports_XenForo_ControllerPublic_Report
{
    public function actionComment()
    {
        $visitor = XenForo_Visitor::getInstance();
        SV_IntegratedReports_Globals::$Report_MaxAlertCount = $visitor->hasPermission('general', 'maxTaggedUsers');
        try
        {
            return parent::actionComment();
        }
        finally
        {
            SV_IntegratedReports_Globals::$Report_MaxAlertCount = 0;
        }
    }

    public function actionUpdate()
    {
        $visitor = XenForo_Visitor::getInstance();
        SV_IntegratedReports_Globals::$Report_MaxAlertCount = $visitor->hasPermission('general', 'maxTaggedUsers');
        try
        {
            return parent::actionUpdate();
        }
        finally
        {
            SV_IntegratedReports_Globals::$Report_MaxAlertCount = 0;
        }
    }
}