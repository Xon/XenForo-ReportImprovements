<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Member extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Member
{
    public function actionWarn()
    {
        $reportHelper = $this->_getReportHelper();
        $reportHelper->setupOnPost();

        $response = parent::actionWarn();

        $reportHelper->injectReportInfo($response, 'member_warn');

        return $response;
    }

    /**
     * @return SV_ReportImprovements_ControllerHelper_Reports
     */
    protected function _getReportHelper()
    {
        return $this->getHelper('SV_ReportImprovements_ControllerHelper_Reports');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Member extends XenForo_ControllerPublic_Member {}
}