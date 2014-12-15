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

		$users = XenForo_Model::create('XenForo_Model_User')->getUsersByIds(array_keys($reportsByUser), array(
			'join' => XenForo_Model_User::FETCH_USER_PRIVACY,
			'followingUserId' => $viewingUser['user_id']
		));

		$userProfileModel = XenForo_Model::create('XenForo_Model_UserProfile');

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
}