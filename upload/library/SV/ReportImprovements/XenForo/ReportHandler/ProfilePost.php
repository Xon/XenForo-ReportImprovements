<?php

class SV_ReportImprovements_XenForo_ReportHandler_ProfilePost extends XFCP_SV_ReportImprovements_XenForo_ReportHandler_ProfilePost
{
    public function getVisibleReportsForUser(array $reports, array $viewingUser)
    {
        $reportsByUser = array();
        foreach ($reports AS $reportId => $report)
        {
            $info = unserialize($report['content_info']);
            $reportsByUser[$info['profile_user_id']][] = $reportId;
        }

        $users = $this->_getUserModel()->getUsersByIds(array_keys($reportsByUser), array(
            'join' => XenForo_Model_User::FETCH_USER_PRIVACY,
            'followingUserId' => $viewingUser['user_id']
        ));

        $userProfileModel = $this->_getProfilePostModel();

        foreach ($reportsByUser AS $userId => $userReports)
        {
            if (!isset($users[$userId]) ||
                !$userProfileModel->canManageReportedMessage($users[$userId], $errorPhraseKey, $viewingUser)
            )
            {
                foreach ($userReports AS $reportId)
                {
                    unset($reports[$reportId]);
                }
            }
        }

        return $reports;
    }

    public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
    {
        $userProfileModel = $this->_getProfilePostModel();

        $message = $userProfileModel->getProfilePostById($report['content_id']);

        if (isset($message['post_date']))
        {
            $contentInfo['content_date'] = $message['post_date'];
        }

        $template = parent::viewCallback($view, $report, $contentInfo);

        return $template;
    }

    protected $_userModel = null;

    /**
     * @return SV_ReportImprovements_XenForo_Model_User
     */
    protected function _getUserModel()
    {
        if (empty($this->_userModel))
        {
            $this->_userModel = XenForo_Model::create('XenForo_Model_User');
        }

        return $this->_userModel;
    }

    protected $_ProfilePostModel = null;

    /**
     * @return SV_ReportImprovements_XenForo_Model_ProfilePost
     */
    protected function _getProfilePostModel()
    {
        if (empty($this->_ProfilePostModel))
        {
            $this->_ProfilePostModel = XenForo_Model::create('XenForo_Model_ProfilePost');
        }

        return $this->_ProfilePostModel;
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ReportHandler_ProfilePost extends XenForo_ReportHandler_ProfilePost {}
}