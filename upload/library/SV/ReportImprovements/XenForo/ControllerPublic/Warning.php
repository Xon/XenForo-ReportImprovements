<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Warning extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Warning
{
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
                        $response->params['report'] = reset($reports);
                    }
                }
            }
        }
        return $response;
    }

    public function actionExpire()
    {
        if ($this->isConfirmedPost())
        {
            SV_ReportImprovements_Globals::$ResolveReport = $this->_input->filterSingle('resolve_linked_report', XenForo_Input::BOOLEAN);
            SV_ReportImprovements_Globals::$AssignReport = SV_ReportImprovements_Globals::$ResolveReport;
        }

        return parent::actionExpire();
    }

    public function actionDelete()
    {
        if ($this->isConfirmedPost())
        {
            SV_ReportImprovements_Globals::$ResolveReport = $this->_input->filterSingle('resolve_linked_report', XenForo_Input::BOOLEAN);
            SV_ReportImprovements_Globals::$AssignReport = SV_ReportImprovements_Globals::$ResolveReport;
        }

        return parent::actionDelete();
    }

    protected function _getReportModel()
    {
        return $this->getModelFromCache('XenForo_Model_Report');
    }
}