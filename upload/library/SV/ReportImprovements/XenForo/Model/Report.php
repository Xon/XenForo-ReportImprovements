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

    public function getReportComments($reportId, $orderDirection = 'ASC')
    {
        $db = $this->_getDb();
        return $this->fetchAllKeyed("
            SELECT report_comment.*,
                user.*
                , IF(user.username IS NULL, report_comment.username, user.username) AS username
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
            SELECT report_comment.*, user.*, IF(user.username IS NULL, report_comment.username, user.username) AS username
                ,warning.warning_id
                ,warningLog.warning_definition_id
                ,warningLog.title
                ,warningLog.points
                ,warningLog.notes
                ,warningLog.expiry_date
                ,warningLog.operation_type
                ,warning.is_expired
            FROM xf_report_comment AS report_comment
            LEFT JOIN xf_user AS user ON (user.user_id = report_comment.user_id)
            LEFT JOIN xf_sv_warning_log warningLog on warningLog.warning_log_id = report_comment.warning_log_id
            LEFT JOIN xf_warning warning on warningLog.warning_id = warning.warning_id
            WHERE report_comment_id = ?
        ', $id);
    }

    public function getReportCommentsByIds($ids)
    {
        return $this->fetchAllKeyed('
            SELECT report_comment.*, user.*, IF(user.username IS NULL, report_comment.username, user.username) AS username
                ,warning.warning_id
                ,warningLog.warning_definition_id
                ,warningLog.title
                ,warningLog.points
                ,warningLog.notes
                ,warningLog.expiry_date
                ,warningLog.operation_type
                ,warning.is_expired
            FROM xf_report_comment AS report_comment
            LEFT JOIN xf_user AS user ON (user.user_id = report_comment.user_id)
            LEFT JOIN xf_sv_warning_log warningLog on warningLog.warning_log_id = report_comment.warning_log_id
            LEFT JOIN xf_warning warning on warningLog.warning_id = warning.warning_id
            WHERE report_comment_id IN (' . $this->_getDb()->quote($ids) . ')
        ', 'report_comment_id');
    }

    var $_handlerCache = array();

    public function getReportHandlerCached($content_type)
    {
        if (isset($this->_handlerCache[$content_type]))
        {
            $handler = $this->_handlerCache[$content_type];
        }
        else
        {
            $handler = $this->_handlerCache[$content_type] = $this->getReportHandler($content_type);
        }
        return $handler;
    }

    public function getReportCommentsByIdsForUser(array $contentIds, array $viewingUser)
    {
        if (!$this->canViewReports($viewingUser))
        {
            return array();
        }

        $comments = $this->getReportCommentsByIds($contentIds);
        $reportIds = array_unique(XenForo_Application::arrayColumn($comments, 'report_id'));
        $reports = $this->getReportsByIds($reportIds);

        foreach($reports as $reportId => &$report)
        {
            $handler = $this->getReportHandlerCached($report['content_type']);
            $visibleReport = $handler->getVisibleReportsForUser(array($report), $viewingUser);
            if (empty($visibleReport))
            {
                unset($reports[$reportId]);
                continue;
            }
        }

        foreach($comments as $commentId => &$comment)
        {
            $reportId = $comment['report_id'];
            if (!isset($reports[$reportId]))
            {
                unset($comments[$commentId]);
                continue;
            }

            $comment['report'] = $reports[$reportId];
        }

        return $comments;
    }

    public function getReportCommentsIdsInRange($start, $limit)
    {
        $db = $this->_getDb();

        return $db->fetchCol($db->limit('
            SELECT report_comment_id
            FROM xf_report_comment
            WHERE report_comment_id > ?
            ORDER BY report_comment_id
        ', $limit), $start);
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
                if (isset($alertedUserIds[$user['user_id']]) || $user['user_id'] == $taggingUser['user_id'] || !$this->canViewReports($user))
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

    protected static $sv_moderators_bypass_report_permissions = null;

    protected function bypassPermissionCheck(array $viewingUser)
    {
        if (self::$sv_moderators_bypass_report_permissions == null)
        {
            self::$sv_moderators_bypass_report_permissions = XenForo_Application::getOptions()->sv_moderators_bypass_report_permissions;
        }

        return self::$sv_moderators_bypass_report_permissions || $viewingUser['is_moderator'];
    }

    public function canViewReports(array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        return $viewingUser['user_id'] &&
               ($this->bypassPermissionCheck($viewingUser) || XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'viewReports'));
    }

    public function canUpdateReport(array $report, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        return $viewingUser['user_id'] &&
               parent::canUpdateReport($report, $viewingUser) &&
               ($this->bypassPermissionCheck($viewingUser)  || XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'updateReport'));
    }

    public function canAssignReport(array $report, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        return $viewingUser['user_id'] &&
               parent::canAssignReport($report, $viewingUser) &&
               ($this->bypassPermissionCheck($viewingUser)  || XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'assignReport'));
    }

    public function canViewReporterUsername(array $report = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        return $viewingUser['user_id'] &&
              ($this->bypassPermissionCheck($viewingUser) || XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'viewReporterUsername'));
    }

    public function canLikeReportComment(array $comment, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if ($comment['user_id'] == XenForo_Visitor::getUserId())
        {
            return false;
        }

        return $viewingUser['user_id'] &&
               XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'reportLike');
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