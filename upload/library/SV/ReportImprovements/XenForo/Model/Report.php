<?php

class SV_ReportImprovements_XenForo_Model_Report extends XFCP_SV_ReportImprovements_XenForo_Model_Report
{
	public function reportContent($contentType, array $content, $message, array $viewingUser = null)
	{
        $this->standardizeViewingUserReference($viewingUser);

        $permissions = empty($viewingUser['permissions']) ? array() : $viewingUser['permissions'];
        SV_ReportImprovements_Globals::$Report_MaxAlertCount = XenForo_Permission::hasPermission($permissions, 'general', 'maxTaggedUsers');

        return parent::reportContent($contentType, $content, $message, $viewingUser);
    }

    public function getReportComments($reportId)
    {
        return $this->fetchAllKeyed('
            SELECT report_comment.*,
                user.*
                ,warning.warning_id
                ,warningLog.warning_definition_id
                ,warningLog.title
                ,warningLog.points
                ,warningLog.notes
                ,warningLog.expiry_date
                ,warningLog.operation_type
                ,warning.is_expired
            FROM xf_report_comment AS report_comment
            LEFT JOIN xf_sv_warning_log warningLog on warningLog.warning_log_id = report_comment.warning_log_id
            LEFT JOIN xf_warning warning on warningLog.warning_id = warning.warning_id
            LEFT JOIN xf_user AS user ON (user.user_id = report_comment.user_id)
            WHERE report_comment.report_id = ?
            ORDER BY report_comment.comment_date
        ', 'report_comment_id', $reportId);
    }

    public function getReportCommentById($id)
    {
        return $this->_getDb()->fetchRow('
            SELECT *
            FROM xf_report_comment
            WHERE report_comment_id = ?
        ', $id);
    }

    public function getReportsByIds($reportIds)
    {
        return $this->fetchAllKeyed('
            SELECT report.*,
                user.*,
                assigned.username AS assigned_username
            FROM xf_report AS report
            LEFT JOIN xf_user AS assigned ON (assigned.user_id = report.assigned_user_id)
            LEFT JOIN xf_user AS user ON (user.user_id = report.content_user_id)
            WHERE report.report_id IN (' . $this->_getDb()->quote($reportIds) . ')
        ', 'report_id');
    }

    public function getReportCommentUserIds($reportId)
    {
        return $this->_getDb()->fetchCol('
            SELECT DISTINCT user_id
            FROM xf_report_comment
            WHERE report_id = ?
        ', $reportId);
    }

    public function alertTaggedMembers(array $report, array $reportComment, array $tagged, array $alreadyAlerted, array $taggingUser)
    {
        $userIds = XenForo_Application::arrayColumn($tagged, 'user_id');
        $userIds = array_diff($userIds, $alreadyAlerted);
        $alertedUserIds = array();

        $report = $this->getReportById($report['report_id']);
        if (empty($report))
        {
            return;
        }
        $handler = $this->getReportHandler($report['content_type']);
        if (empty($handler))
        {
            return;
        }

        if ($userIds)
        {
            $userModel = $this->_getUserModel();
            $users = $userModel->getUsersByIds($userIds, array(
                'join' =>  XenForo_Model_User::FETCH_USER_PERMISSIONS
            ));
            foreach ($users AS $user)
            {
                if (isset($alertedUserIds[$user['user_id']]) || $user['user_id'] == $taggingUser['user_id'])
                {
                    continue;
                }

                $user['permissions'] = XenForo_Permission::unserializePermissions($user['global_permission_cache']);

                $reports = $handler->getVisibleReportsForUser(array($report['report_id'] => $report), $user);

                if (!empty($reports))
                {
                    $alertedUserIds[$user['user_id']] = true;

                    XenForo_Model_Alert::alert($user['user_id'],
                        $taggingUser['user_id'], $taggingUser['username'],
                        SV_ReportImprovements_Globals::$Report_ContentType, $report['report_id'],
                        SV_ReportImprovements_Globals::$Report_Tag,
                        array('report_comment_id' => $reportComment['report_comment_id'])
                    );
                }
            }
        }

        return array_keys($alertedUserIds);
    }

    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }
}