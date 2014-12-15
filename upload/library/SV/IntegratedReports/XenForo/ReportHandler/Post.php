<?php

class SV_IntegratedReports_XenForo_ReportHandler_Post extends XFCP_SV_IntegratedReports_XenForo_ReportHandler_Post
{
	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		$reportsByForum = array();
		foreach ($reports AS $reportId => $report)
		{
			$info = unserialize($report['content_info']);
			$reportsByForum[$info['node_id']][] = $reportId;
		}

		/* @var $forumModel XenForo_Model_Forum */
		$forumModel = XenForo_Model::create('XenForo_Model_Forum');
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
}