<?php

class SV_ReportImprovements_XenForo_Model_Forum extends XFCP_SV_ReportImprovements_XenForo_Model_Forum
{
    var $sv_moderators_respect_view_node = null;
    public function canManageReportedMessage(array $forum, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);
        
        if ($this->sv_moderators_respect_view_node === null)
        {
            $this->sv_moderators_respect_view_node = XenForo_Application::getOptions()->sv_moderators_respect_view_node;
        }

        if ($this->_getReportModel()->canViewReports($viewingUser) &&
            ((empty($this->sv_moderators_respect_view_node) && $viewingUser['is_moderator'])  || $this->canViewForum($forum, $errorPhraseKey, $forum['permissions'], $viewingUser)) &&
            XenForo_Permission::hasContentPermission($forum['permissions'], 'viewReportPost')
           )
        {
            return true;
        }

        $errorPhraseKey = 'you_may_not_manage_this_reported_content';
        return false;
    }

    protected function _getReportModel()
    {
        return $this->getModelFromCache('XenForo_Model_Report');
    }
}