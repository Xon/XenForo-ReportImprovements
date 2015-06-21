<?php

class SV_ReportImprovements_XenForo_ReportHandler_Post extends XFCP_SV_ReportImprovements_XenForo_ReportHandler_Post
{
    public function getVisibleReportsForUser(array $reports, array $viewingUser)
    {
        $reportsByForum = array();
        foreach ($reports AS $reportId => $report)
        {
            $info = unserialize($report['content_info']);
            $reportsByForum[$info['node_id']][] = $reportId;
        }

        $forumModel = $this->_getForumModel();
        $forums = $forumModel->getForumsByIds(array_keys($reportsByForum), array(
            'permissionCombinationId' => $viewingUser['permission_combination_id']
        ));
        $forums = $forumModel->unserializePermissionsInList($forums, 'node_permission_cache');

        foreach ($reportsByForum AS $forumId => $forumReports)
        {
            if (!isset($forums[$forumId]) ||
                !$forumModel->canManageReportedMessage($forums[$forumId], $errorPhraseKey, $viewingUser))
            {
                foreach ($forumReports AS $reportId)
                {
                    unset($reports[$reportId]);
                }
            }
        }

        return $reports;
    }

    var $_forumModel = null;
    protected function _getForumModel()
    {
        if (empty($this->_forumModel))
        {
            $this->_forumModel = XenForo_Model::create('XenForo_Model_Forum');
        }

        return $this->_forumModel;
    }
}