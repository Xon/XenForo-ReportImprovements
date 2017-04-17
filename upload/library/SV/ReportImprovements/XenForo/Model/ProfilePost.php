<?php

class SV_ReportImprovements_XenForo_Model_ProfilePost extends XFCP_SV_ReportImprovements_XenForo_Model_ProfilePost
{

    public function canManageReportedMessage(array $user, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if ($this->_getReportModel()->canViewReports($viewingUser) && isset($user) &&
            (
            XenForo_Permission::hasPermission($viewingUser['permissions'], 'profilePost', 'viewReportProfilePost')
            )
        )
        {
            return true;
        }

        $errorPhraseKey = 'you_may_not_manage_this_reported_content';

        return false;
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
    class XFCP_SV_ReportImprovements_XenForo_Model_ProfilePost extends XenForo_Model_ProfilePost {}
}