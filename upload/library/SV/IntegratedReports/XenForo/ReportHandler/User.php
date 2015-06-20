<?php

class SV_IntegratedReports_XenForo_ReportHandler_User extends XFCP_SV_IntegratedReports_XenForo_ReportHandler_User
{
	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		$userModel = $this->_getUserModel();

		foreach ($reports AS $reportId => $report)
		{
			$info = unserialize($report['content_info']);

			if (!$info
				|| empty($info['user'])
				|| !$userModel->canManageReportedMessage($info['user'], $errorPhraseKey, $viewingUser))
			{
				unset($reports[$reportId]);
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
}