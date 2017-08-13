<?php

class SV_ReportImprovements_ControllerHelper_Reports extends XenForo_ControllerHelper_Abstract
{
    public function setupOnPost($getContentTypeId = null)
    {
        // this function just setups up the options
        $reportModel = $this->_getReportModel();
        if ($reportModel->canViewReports() && $this->_controller->getRequest()->isPost())
        {
            SV_ReportImprovements_Globals::$ResolveReport = $this->_controller->getInput()->filterSingle('resolve_linked_report', XenForo_Input::BOOLEAN);
            SV_ReportImprovements_Globals::$AssignReport = SV_ReportImprovements_Globals::$ResolveReport;
        }
    }

    public function injectReportInfoOrResolveReport($response, $templateName, $getContentTypeId = null, $reportCommentFunc = null, $forceDefaultResolve = null, $allowReportCreate = true)
    {
        if ($this->_controller->getRequest()->isPost())
        {
            $resolve = SV_ReportImprovements_Globals::$ResolveReport;
            if ($getContentTypeId && $response instanceof XenForo_ControllerResponse_Redirect && $resolve)
            {
                list($contentType, $contentId) = $getContentTypeId($response);
                $reportModel = $this->_getReportModel();
                $report = $reportModel->getReportForContent('post', $this->get('post_id'));
                if ($report)
                {
                    if ($resolve && $reportModel->canUpdateReport($report))
                    {
                        $reportModel->logReportForContent($report, $resolve);
                    }
                }
            }
        }
        else
        {
            $this->injectReportInfo($response, $templateName, $getContentTypeId, $forceDefaultResolve, $allowReportCreate);
        }
    }

    public function injectReportInfo($response, $templateName, $getContentTypeId = null, $forceDefaultResolve = null, $allowReportCreate = true)
    {
        if ($response instanceof XenForo_ControllerResponse_View && $response->templateName == $templateName)
        {
           $reportModel = $this->_getReportModel();
            if ($reportModel->canViewReports())
            {
                $response->params['canViewReporterUsername'] = $reportModel->canViewReporterUsername();
                if ($getContentTypeId)
                {
                    list($contentType, $contentId) = $getContentTypeId($response);
                }
                else
                {
                    $contentType = $response->params['contentType'];
                    $contentId = $response->params['contentId'];
                }

                $report = $reportModel->getReportByContent($contentType, $contentId);
                $response->params['canResolveReport'] = false;
                $response->params['canCreateReport'] = $this->_getWarningLogModel()->canCreateReportFor($contentType);
                $response->params['ContentType'] = $contentType;
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
                else if (!$allowReportCreate)
                {
                    $response->params['canResolveReport'] = false;
                }

                if ($forceDefaultResolve !== null)
                {
                    $response->params['defaultResolveReports'] = $forceDefaultResolve;
                }
                else
                {
                    $response->params['defaultResolveReports'] = XenForo_Application::getOptions()->sv_default_resolve_report_on_warning;
                }
            }
        }
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        return $this->_controller->getModelFromCache('XenForo_Model_Report');
    }

    /**
     * @return SV_ReportImprovements_Model_WarningLog
     */
    protected function _getWarningLogModel()
    {
        return $this->_controller->getModelFromCache('SV_ReportImprovements_Model_WarningLog');
    }
}
