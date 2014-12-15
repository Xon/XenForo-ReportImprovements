<?php

class SV_IntegratedReports_XenForo_Model_User extends XFCP_SV_IntegratedReports_XenForo_Model_User
{

	public function canManageReportedMessage(array $user, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
        if (!isset($user) )
            return false;
            
		if ($viewingUser['is_moderator'] && 
            (
                $userProfileModel->canViewFullUserProfile($user, $null, $viewingUser) ||
                XenForo_Permission::hasPermission($viewingUser['permissions'], 'profilePost', 'editAny') ||
                XenForo_Permission::hasPermission($viewingUser['permissions'], 'profilePost', 'deleteAny')
            )
            || XenForo_Permission::hasContentPermission($forum['permissions'], 'viewReportUser')
           )
		{
			return true;
		}   

		$errorPhraseKey = 'you_may_not_manage_this_reported_content';
		return false;
	}
}