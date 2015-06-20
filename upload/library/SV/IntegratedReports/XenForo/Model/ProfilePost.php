<?php

class SV_IntegratedReports_XenForo_Model_ProfilePost extends XFCP_SV_IntegratedReports_XenForo_Model_ProfilePost
{

	public function canManageReportedMessage(array $user, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if ($viewingUser['is_moderator'] && isset($user) &&
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
}