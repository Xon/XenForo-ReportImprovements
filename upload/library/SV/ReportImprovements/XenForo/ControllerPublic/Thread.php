<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Thread extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Thread
{
    public function actionReplyBans()
    {
        $canViewReports = $this->_getReportModel()->canResolveReplyBanReports();
        if ($canViewReports && $this->isConfirmedPost())
        {
            SV_ReportImprovements_Globals::$ResolveReport = $this->_input->filterSingle('resolve_linked_report', XenForo_Input::BOOLEAN);
            SV_ReportImprovements_Globals::$AssignReport = SV_ReportImprovements_Globals::$ResolveReport;
        }

        $response = parent::actionReplyBans();

        if ($response instanceof XenForo_ControllerResponse_View)
        {
            $response->params['CanCreateReport'] = true;
            $response->params['showResolveOptions'] = $canViewReports;
        }
        return $response;
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        return $this->getModelFromCache('XenForo_Model_Report');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Thread extends XenForo_ControllerPublic_Thread {}
}