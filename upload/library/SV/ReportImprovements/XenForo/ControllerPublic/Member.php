<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Member extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Member
{
    public function actionWarn()
    {
        if ($this->_request->isPost())
        {
            SV_ReportImprovements_Globals::$resolve_report = $this->_input->filterSingle('resolve_linked_report', XenForo_Input::BOOLEAN);
        }

        $response = parent::actionWarn();

        if ($response instanceof XenForo_ControllerResponse_View && $response->templateName == 'member_warn')
        {
            $visitor = XenForo_Visitor::getInstance()->toArray();
            if ($visitor['is_moderator'])
            {
                $content_type = $response->params['contentType'];
                $content_id = $response->params['contentId'];
                $reportModel = $this->_getReportModel();

                $report = $reportModel->getReportByContent($content_type, $content_id);
                if ($report)
                {
                    $reports = $reportModel->getVisibleReportsForUser(array($report['report_id'] => $report));
                    if (!empty($reports))
                    {
                        $response->params['report'] = reset($reports);
                    }
                }
                $response->params['CanCreateReport'] = $this->_getWarningLogModel()->canCreateReportFor($content_type);
                $response->params['ContentType'] = $content_type;
            }
        }
        return $response;
    }

    protected function _getReportModel()
    {
        return $this->getModelFromCache('XenForo_Model_Report');
    }

    protected function _getWarningLogModel()
    {
        return $this->getModelFromCache('SV_ReportImprovements_Model_WarningLog');
    }
}