<?php

class SV_ReportImprovements_XenForo_Model_Report extends XFCP_SV_ReportImprovements_XenForo_Model_Report
{
    public function reportContent($contentType, array $content, $message, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        $permissions = empty($viewingUser['permissions']) ? array() : $viewingUser['permissions'];
        SV_ReportImprovements_Globals::$Report_MaxAlertCount = XenForo_Permission::hasPermission($permissions, 'general', 'maxTaggedUsers');

        SV_ReportImprovements_Globals::$reportId = false;
        $reportId = parent::reportContent($contentType, $content, $message, $viewingUser);
        if ($reportId === null)
        {
            XenForo_Error::logException(new Exception(class_exists('Waindigo_EmailReport_Extend_XenForo_Model_Report', false)
                                                      ? "Please upgrade the addon 'Email Reports by Waindigo'"
                                                      : "Please upgrade addons related to reports."), false);
            // workaround for a bug in Waindigo_EmailReport_Extend_XenForo_Model_Report (or anyone else implementing reportContent)
            // reportContent will return false (not null) if the report is created.
            $reportId = SV_ReportImprovements_Globals::$reportId;
        }
        return $reportId;
    }

    public function getReportComments($reportId, $orderDirection = 'ASC')
    {
        $db = $this->_getDb();
        return $this->fetchAllKeyed("
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
                ,liked_content.like_date
            FROM xf_report_comment AS report_comment
            LEFT JOIN xf_sv_warning_log warningLog on warningLog.warning_log_id = report_comment.warning_log_id
            LEFT JOIN xf_warning warning on warningLog.warning_id = warning.warning_id
            LEFT JOIN xf_user AS user ON (user.user_id = report_comment.user_id)
            LEFT JOIN xf_liked_content AS liked_content ON (liked_content.content_type = 'report_comment'
                            AND liked_content.content_id = report_comment.report_comment_id
                            AND liked_content.like_user_id = " .$db->quote(XenForo_Visitor::getUserId()) . ")
            WHERE report_comment.report_id = ?
            ORDER BY report_comment.comment_date $orderDirection
        ", 'report_comment_id', $reportId);
    }

    public function getReportCommentById($id)
    {
        return $this->_getDb()->fetchRow('
            SELECT *
            FROM xf_report_comment
            WHERE report_comment_id = ?
        ', $id);
    }

    public function getReportCommentsByIds($ids)
    {
        return $this->fetchAllKeyed('
            SELECT *
            FROM xf_report_comment
            WHERE report_comment_id IN (' . $this->_getDb()->quote($ids) . ')
        ', 'report_comment_id');
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
                if (isset($alertedUserIds[$user['user_id']]) || $user['user_id'] == $taggingUser['user_id'] || !$user['is_moderator'])
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
                        'report_comment', $reportComment['report_comment_id'],
                        'tag'
                    );
                }
            }
        }

        return array_keys($alertedUserIds);
    }

    public function prepareReportComment(array $comment)
    {
        $comment = parent::prepareReportComment($comment);

        $comment['canLike'] = $this->canLikeReportComment($comment);
        if (!empty($comment['likes']))
        {
            $comment['likeUsers'] = @unserialize($comment['like_users']);
        }
        return $comment;
    }

    public function canLikeReportComment(array $comment, &$errorPhraseKey = '')
    {
        if ($comment['user_id'] == XenForo_Visitor::getUserId())
        {
            return false;
        }

        return XenForo_Visitor::getInstance()->hasPermission('general', 'reportLike');
    }

    public function batchUpdateLikeUser($oldUserId, $newUserId, $oldUsername, $newUsername)
    {
        $db = $this->_getDb();

        // note that xf_liked_content should have already been updated with $newUserId
        $db->query('
            UPDATE (
                SELECT content_id FROM xf_liked_content
                WHERE content_type = \'report_comment\'
                AND like_user_id = ?
            ) AS temp
            INNER JOIN xf_report_comment AS report_comment ON (report_comment.report_comment_id = temp.content_id)
            SET like_users = REPLACE(like_users, ' .
            $db->quote('i:' . $oldUserId . ';s:8:"username";s:' . strlen($oldUsername) . ':"' . $oldUsername . '";') . ', ' .
            $db->quote('i:' . $newUserId . ';s:8:"username";s:' . strlen($newUsername) . ':"' . $newUsername . '";') . ')
        ', $newUserId);
    }

    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }
}