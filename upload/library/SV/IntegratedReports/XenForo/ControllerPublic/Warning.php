<?php

class SV_IntegratedReports_XenForo_ControllerPublic_Warning extends XFCP_SV_IntegratedReports_XenForo_ControllerPublic_Warning
{
    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View && $response->templateName == 'warning_info')
        {
            $visitor = XenForo_Visitor::getInstance()->toArray();
            if ($visitor['is_moderator'] && isset($response->params['warning']['content_type']) && isset($response->params['warning']['content_id']))
            {
                $content_type = $response->params['warning']['content_type'];
                $content_id = $response->params['warning']['content_id'];
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