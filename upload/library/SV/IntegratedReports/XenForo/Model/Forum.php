<?php

class SV_IntegratedReports_XenForo_Model_Forum extends XFCP_SV_IntegratedReports_XenForo_Model_Forum
{

	public function canManageReportedMessage(array $forum, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

        if ($viewingUser['is_moderator'] &&
            ($this->canViewForum($forum, $errorPhraseKey, $forum['permissions'], $viewingUser)) &&
            (XenForo_Permission::hasContentPermission($forum['permissions'], 'editAnyPost') ||
             XenForo_Permission::hasContentPermission($forum['permissions'], 'deleteAnyPost') ||
             XenForo_Permission::hasContentPermission($forum['permissions'], 'viewReportPost')
            )
           )
		{
			return true;
		}

		$errorPhraseKey = 'you_may_not_manage_this_reported_content';
		return false;
	}
}