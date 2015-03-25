<?php

class SV_IntegratedReports_XenForo_ControllerPublic_Member extends XFCP_SV_IntegratedReports_XenForo_ControllerPublic_Member
{
    public function actionWarn()
    {
		if ($this->_request->isPost())
		{
            SV_IntegratedReports_Globals::$resolve_report = $this->_input->filterSingle('resolve_linked_report', XenForo_Input::BOOLEAN);
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
            }
        }
        return $response;
    }

	protected function _getReportModel()
	{
		return $this->getModelFromCache('XenForo_Model_Report');
	}
}