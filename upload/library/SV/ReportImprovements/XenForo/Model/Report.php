<?php

class SV_ReportImprovements_XenForo_Model_Report extends XFCP_SV_ReportImprovements_XenForo_Model_Report
{
    public function getAttachmentReportKey(array $attachment, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (!$viewingUser['user_id'] || !XenForo_Application::isRegistered('session'))
        {
            return null;
        }

        $session =  XenForo_Application::get('session');

        if ($session->get('robotId'))
        {
            return null;
        }

        if (!$this->canViewReports($viewingUser))
        {
            return null;
        }

        // TODO - store this generated key into cache with an expiry, on viewing the attachment if the key doesn't exist fail to allow them to view the attachment

        // links are only valid for upto an 30 minutes
        $time = XenForo_Application::$time;
        $time = $time - ($time % 1800);

        // this must be deterministic, and unique per atttachment-viewer pair, and use information the user should not have access to
        return sha1($attachment['attachment_id'] . $attachment['data_id'] . $attachment['file_hash'] . $viewingUser['user_id'] . $viewingUser['password_date'] . $time);
    }

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
                warning.warning_id, warningLog.*, warning.is_expired,
                liked_content.like_date,
                user.*, IF(user.username IS NULL, report_comment.username, user.username) AS username
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
            SELECT report_comment.*,
                warning.warning_id, warningLog.*, warning.is_expired,
                user.*, IF(user.username IS NULL, report_comment.username, user.username) AS username
            FROM xf_report_comment AS report_comment
            LEFT JOIN xf_user AS user ON (user.user_id = report_comment.user_id)
            LEFT JOIN xf_sv_warning_log warningLog on warningLog.warning_log_id = report_comment.warning_log_id
            LEFT JOIN xf_warning warning on warningLog.warning_id = warning.warning_id
            WHERE report_comment_id = ?
        ', $id);
    }

    public function getReportCommentsByIds($ids)
    {
        if (empty($ids))
        {
            return array();
        }

        return $this->fetchAllKeyed('
            SELECT report_comment.*,
                warning.warning_id, warningLog.*, warning.is_expired,
                user.*, IF(user.username IS NULL, report_comment.username, user.username) AS username
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
        $reports = $this->sv_getReportsByIds($reportIds);

        // group reports to make getVisibleReportsForUser more efficient
        $reportsGrouped = array();
        foreach ($reports AS $reportId => $report)
        {
            $reportsGrouped[$report['content_type']][$reportId] = $report;
        }

        if (!$reportsGrouped)
        {
            return array();
        }

        $reportHandlers = $this->getReportHandlers();

        $userReports = array();
        foreach ($reportsGrouped AS $contentType => $typeReports)
        {
            if (!empty($reportHandlers[$contentType]))
            {
                $handler = $reportHandlers[$contentType];

                $typeReports = $handler->getVisibleReportsForUser($typeReports, $viewingUser);
                $userReports += $handler->prepareReports($typeReports);
            }
        }
        $reports = $userReports;

        foreach($comments as $commentId => &$comment)
        {
            $reportId = $comment['report_id'];
            if (!isset($reports[$reportId]))
            {
                unset($comments[$commentId]);
                continue;
            }

            $comment = $this->prepareReportComment($comment, $viewingUser);
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

    public function getReportIdsInRange($start, $limit)
    {
        $db = $this->_getDb();

        return $db->fetchCol($db->limit('
            SELECT report_id
            FROM xf_report
            WHERE report_id > ?
            ORDER BY report_id
        ', $limit), $start);
    }

    public function sv_getReportsByIds($reportIds)
    {
        if (empty($reportIds))
        {
            return array();
        }

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

    public function getUsersForReportCommentAlerts(array $report)
    {
        $alert_mode = XenForo_Application::getOptions()->sv_report_alert_mode;
        $users = array();
        $watcherUserIds = array();
        if ($alert_mode != 'always_alert')
        {
            $watcherUserIds = $this->_getDb()->fetchCol('
                SELECT DISTINCT user_id
                FROM xf_report_comment
                WHERE report_id = ?
            ', $report['report_id']);
        }
        // if a single user id is returned, then this is a new report
        if (count($watcherUserIds) <= 1 && $alert_mode != 'watchers' )
        {
           $users = $this->getUsersWhoCanViewReport($report);
        }

        $userIds = XenForo_Application::arrayColumn($users, 'user_id');
        $userIds = array_diff($watcherUserIds, $userIds);

        if ($userIds)
        {
            // otherwise build a list of users to notify
            $users = $users + $this->_getUserModel()->getUsersByIds($userIds, array(
                'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
            ));
        }
        return $users;
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

                if (!$this->canViewReports($user))
                {
                    continue;
                }
                if (XenForo_Model_Alert::userReceivesAlert($user, 'report_comment', 'tag'))
                {
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
        }

        return array_keys($alertedUserIds);
    }

    public function prepareReportComment(array $comment, array $viewingUser = null)
    {
        $comment = parent::prepareReportComment($comment);

        $this->standardizeViewingUserReference($viewingUser);

        $comment['canLike'] = $this->canLikeReportComment($comment, $null, $viewingUser);
        if (!empty($comment['likes']))
        {
            $comment['likeUsers'] = @unserialize($comment['like_users']);
        }
        $comment['canViewReporterUsername'] = $this->canViewReporterUsername($comment, $null, $viewingUser);
        if (empty($comment['canViewReporterUsername']))
        {
            $comment['username'] = new XenForo_Phrase('guest');
            $comment['user_id'] = 0;
            $this->_getUserModel();
            $comment['permission_combination_id'] = XenForo_Model_User::$defaultGuestGroupId;
            $comment['email'] = '';
            $comment['gender'] = '';
        }
        return $comment;
    }

    public function canViewReports(array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'viewReports');
    }

    public function canUpdateReport(array $report, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        return parent::canUpdateReport($report, $viewingUser) &&
               XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'updateReport');
    }

    public function canAssignReport(array $report, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        return parent::canAssignReport($report, $viewingUser) &&
               XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'assignReport');
    }

    public function canCommentReport(array $report, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        if (($report['report_state'] == 'resolved' || $report['report_state'] == 'rejected') &&
            !XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'replyReportClosed'))
        {
            return false;
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'replyReport');
    }

    public function canViewReporterUsername(array $comment = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        if ($comment)
        {
            if ($comment['user_id'] == $viewingUser['user_id'])
            {
                return true;
            }

            if (empty($comment['is_report']) || !empty($comment['warning_log_id']))
            {
                return true;
            }
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'viewReporterUsername');
    }

    public function canLikeReportComment(array $comment, &$errorPhraseKey = '', array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        if ($comment['user_id'] == $viewingUser['user_id'])
        {
            return false;
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'reportLike');
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

    public function mapReportState($reportState)
    {
        switch($reportState)
        {
            case '':
                return 0;
            case 'open':
                return 1;
            case 'assigned':
                return 2;
            case 'resolved':
                return 3;
            case 'rejected':
                return 4;
            default:
                return null;
        }
    }

    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }
}