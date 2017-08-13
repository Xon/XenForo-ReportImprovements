<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Post extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Post
{
    public function actionDelete()
    {
        $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);
        $reportHelper = $this->_getReportHelper();
        $reportHelper->setupOnPost();

        $response = parent::actionDelete();

        $reportHelper->injectReportInfoOrResolveReport($response, 'post_delete', function($response) use ($postId) {
            return array('post', $postId );
        }, null, false);

        return $response;
    }

    public function actionReport()
    {
        $postId = $this->_input->filterSingle('post_id', XenForo_Input::UINT);
        $reportHelper = $this->_getReportHelper();
        $reportHelper->setupOnPost();

        $response = parent::actionReport();

        $reportHelper->injectReportInfoOrResolveReport($response, 'post_report', function($response) use ($postId) {
            return array('post', $postId );
        }, false);

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
    class XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Warning extends XenForo_ControllerPublic_Warning {}
}