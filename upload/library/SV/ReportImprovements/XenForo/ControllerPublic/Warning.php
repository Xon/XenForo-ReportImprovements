<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Warning extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Warning
{
    public function actionIndex()
    {
        $response = parent::actionIndex();
        $this->_getReportHelper()->injectReportInfo($response, 'member_warn');
        return $response;
    }

    /**
     * @return SV_ReportImprovements_ControllerHelper_Reports
     */
    protected function _getReportHelper()
    {
        return $this->getHelper('SV_ReportImprovements_ControllerHelper_Reports');
    }
    
    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View && $response->templateName == 'warning_info')
        {
            $reportModel = $this->_getReportModel();
            if ($reportModel->canViewReports() && isset($response->params['warning']['content_type']) && isset($response->params['warning']['content_id']))
            {
                $response->params['canViewReporterUsername'] = $reportModel->canViewReporterUsername();
                $content_type = $response->params['warning']['content_type'];
                $content_id = $response->params['warning']['content_id'];

                $report = $reportModel->getReportByContent($content_type, $content_id);
                if ($report)
                {
                    $reports = $reportModel->getVisibleReportsForUser(array($report['report_id'] => $report));
                    if (!empty($reports))
                    {
                        $report = reset($reports);
                        $response->params['report'] = $report;
                        $response->params['canResolveReport'] = $reportModel->canUpdateReport($report);
                    }
                }
                $response->params['canCreateReport'] = $this->_getWarningLogModel()->canCreateReportFor($content_type);
                $response->params['ContentType'] = $content_type;
            }
        }
        return $response;
    }

    public function actionExpire()
    {
        $this->_getReportHelper()->setupOnPost();
        return parent::actionExpire();
    }

    public function actionDelete()
    {
        $this->_getReportHelper()->setupOnPost();
        return parent::actionDelete();
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        return $this->getModelFromCache('XenForo_Model_Report');
    }

    /**
     * @return SV_ReportImprovements_Model_WarningLog
     */
    protected function _getWarningLogModel()
    {
        return $this->getModelFromCache('SV_ReportImprovements_Model_WarningLog');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Warning extends XenForo_ControllerPublic_Warning {}
}