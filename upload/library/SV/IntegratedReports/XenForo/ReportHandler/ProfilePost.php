<?php

class SV_IntegratedReports_XenForo_ReportHandler_ProfilePost extends XFCP_SV_IntegratedReports_XenForo_ReportHandler_ProfilePost
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
                !$userProfileModel->canManageReportedMessage($users[$userId], $errorPhraseKey, $viewingUser))
            {
				foreach ($userReports AS $reportId)
				{
					unset($reports[$reportId]);
				}
			}
		}

		return $reports;
	}

    var $_userModel = null;
    protected function _getUserModel()
    {
        if (empty($this->_userModel))
        {
            $this->_userModel = XenForo_Model::create('XenForo_Model_User');
        }

        return $this->_userModel;
    }

    var $_ProfilePostModel = null;
    protected function _getProfilePostModel()
    {
        if (empty($this->_ProfilePostModel))
        {
            $this->_ProfilePostModel = XenForo_Model::create('XenForo_Model_ProfilePost');
        }

        return $this->_ProfilePostModel;
    }
}