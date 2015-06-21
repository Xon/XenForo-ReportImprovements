<?php

class SV_ReportImprovements_XenForo_Model_User extends XFCP_SV_ReportImprovements_XenForo_Model_User
{

    public function canManageReportedMessage(array $user, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if ($viewingUser['is_moderator'] &&
            (
             XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'viewReportUser')
            )
           )
        {
            return true;
        }

        $errorPhraseKey = 'you_may_not_manage_this_reported_content';
        return false;
    }
}