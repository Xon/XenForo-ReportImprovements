<?php

class SV_ReportImprovements_XenForo_ControllerPublic_InlineMod_Thread extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_InlineMod_Thread
{
    public function actionDelete()
    {
        $reportHelper = $this->_getReportHelper();
        $canSee = $reportHelper->setupOnPost();

        $response = parent::actionDelete();

        $reportHelper->injectReportInfoForBulk($canSee, $response);

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
    class XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Thread extends XenForo_ControllerPublic_Thread {}
}