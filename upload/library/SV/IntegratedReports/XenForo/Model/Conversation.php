<?php

class SV_IntegratedReports_XenForo_Model_Conversation extends XFCP_SV_IntegratedReports_XenForo_Model_Conversation
{

	public function canManageReportedMessage(array $message, array $conversation, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if ($viewingUser['is_moderator'] &&
            (
             XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'warn') ||
             XenForo_Permission::hasPermission($viewingUser['permissions'], 'conversation', 'viewReportConversation')
             )
           )
             
		{
			return true;
		}

		$errorPhraseKey = 'you_may_not_manage_this_reported_content';
		return false;
	}
}