<?php

class SV_IntegratedReports_XenForo_ControllerPublic_Member extends XFCP_SV_IntegratedReports_XenForo_ControllerPublic_Member
{
    public function actionWarn()
    {
		if ($this->_request->isPost())
		{
            SV_IntegratedReports_Model_WarningLog::$resolve_report = $this->_input->filterSingle('resolve_linked_report', XenForo_Input::BOOLEAN);
        }

        $response = parent::actionWarn();

        if ($response instanceof XenForo_ControllerResponse_View && $response->templateName == 'member_warn')
        {
            $content_type = $response->params['contentType'];
            $reportModel = $this->_getReportModel();
            $handler = $reportModel->getReportHandler($content_type);
            if ($handler)
            {
                $report = $reportModel->getReportByContent($content_type, $response->params['contentId']);
                if ($report)
                {
                    $response->params['report'] = $handler->prepareReport($report);
                    $response->params['report']['isVisible'] = true;
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