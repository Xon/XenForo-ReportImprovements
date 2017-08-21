<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Warning extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Warning
{
    public function actionIndex()
    {
        $response = parent::actionIndex();
        $this->_getReportHelper()->injectReportInfo($response, 'warning_info', function($response) {
            return array($response->params['warning']['content_type'], $response->params['warning']['content_id']);
        }, null, false);
        return $response;
    }

    /**
     * @return SV_ReportImprovements_ControllerHelper_Reports
     */
    protected function _getReportHelper()
    {
        return $this->getHelper('SV_ReportImprovements_ControllerHelper_Reports');
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