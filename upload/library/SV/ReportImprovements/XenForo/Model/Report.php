<?php

class SV_ReportImprovements_XenForo_Model_Report extends XFCP_SV_ReportImprovements_XenForo_Model_Report
{
    protected $userReportCountCache = array();

    public function countReportsByUser($userId, $days, $state = '')
    {
        if (isset($this->userReportCountCache[$userId][$days]))
        {
            if (isset($this->userReportCountCache[$userId][$days][$state]))
            {
                return $this->userReportCountCache[$userId][$days][$state];
            }
            return 0;
        }

        $args = array($userId);
        $whereSQL = '';
        if ($days)
        {
            $args[] = XenForo_Application::$time - 86400 * $days;
            $whereSQL .= ' and report_comment.comment_date > ?';
        }

        $db = $this->_getDb();
        $reportStats = $db->fetchAll('
            select report.report_state, count(*) as count
            from xf_report_comment as report_comment
            join xf_report as report on report_comment.report_id = report.report_id
            where report_comment.is_report = 1 and report_comment.user_id = ? '.$whereSQL.'
            group by report.report_state
        ', $args);

        // built the totals & cache array
        $stats = array();
        $total = 0;
        foreach($reportStats as $reportStat)
        {
            $total += $reportStat['count'];
            $stats[$reportStat['report_state']] = $reportStat['count'];
        }
        $stats[''] = $total;
        $this->userReportCountCache[$userId][$days] = $stats;

        if (isset($this->userReportCountCache[$userId][$days][$state]))
        {
            return $this->userReportCountCache[$userId][$days][$state];
        }
        return 0;
    }

    public function getAttachmentReportKey(array $attachment, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (!$viewingUser['user_id'] || !XenForo_Application::isRegistered('session'))
        {
            return null;
        }

        $session = XenForo_Application::get('session');

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

    public function getReplyBansForReportComments(array $report, array $comments)
    {
        $threadIds = array_filter(XenForo_Application::arrayColumn($comments, 'reply_ban_thread_id'));
        if ($threadIds)
        {
            $visitor = XenForo_Visitor::getInstance()->toArray();
            $threadModel = $this->_getThreadModel();
            $threads = $threadModel->getThreadsByIds($threadIds, array(
                'join' =>
                    XenForo_Model_Thread::FETCH_FORUM,
                'permissionCombinationId' => $visitor['permission_combination_id'],
                'replyBanUserId' => $visitor['user_id'],
            ));
            foreach ($threads AS $threadId => &$thread)
            {
                $thread['permissions'] = XenForo_Permission::unserializePermissions($thread['node_permission_cache']);
                if (!$threadModel->canViewThreadAndContainer($thread, $thread, $null, $thread['permissions']))
                {
                    unset($threads[$threadId]);
                    continue;
                }

                $thread = $threadModel->prepareThread($thread, $thread, $thread['permissions']);

                $thread['forum'] = array(
                    'node_id' => $thread['node_id'],
                    'node_name' => $thread['node_name'],
                    'title' => $thread['node_title']
                );
            }

            $threadIds = array_filter(XenForo_Application::arrayColumn($threads, 'thread_id'));
            if ($threadIds)
            {
                $db = $this->_getDb();
                $replyBans = $this->fetchAllKeyed("
                  SELECT *
                  FROM xf_thread_reply_ban
                  WHERE thread_id IN (" . $db->quote($threadIds) . ") AND user_id = ?
                ", 'thread_id', array($report['content_user_id']));
            }

            foreach ($comments as &$comment)
            {
                if (empty($comment['reply_ban_thread_id']))
                {
                    continue;
                }

                if (isset($threads[$comment['reply_ban_thread_id']]))
                {
                    $comment['reply_ban_thread'] = $threads[$comment['reply_ban_thread_id']];
                }

                if (isset($replyBans[$comment['reply_ban_thread_id']]))
                {
                    $comment['reply_ban'] = $replyBans[$comment['reply_ban_thread_id']];
                }
            }
        }

        return $comments;
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
                            AND liked_content.like_user_id = " . $db->quote(XenForo_Visitor::getUserId()) . ")
            WHERE report_comment.report_id = ?
            ORDER BY report_comment.comment_date $orderDirection
        ", 'report_comment_id', $reportId);
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function getReportCommentById($id)
    {
        return $this->_getDb()->fetchRow('
            SELECT report_comment.*,
                warning.warning_id, warningLog.*, warning.is_expired,
                user.*, IF(user.username IS NULL, report_comment.username, user.username) AS username
            FROM xf_report_comment AS report_comment
            LEFT JOIN xf_user AS user ON (user.user_id = report_comment.user_id)
            LEFT JOIN xf_sv_warning_log warningLog ON warningLog.warning_log_id = report_comment.warning_log_id
            LEFT JOIN xf_warning warning ON warningLog.warning_id = warning.warning_id
            WHERE report_comment_id = ?
        ', $id);
    }

    /**
     * @param array $ids
     * @return array|null
     */
    public function getReportCommentsByIds(array $ids)
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
            LEFT JOIN xf_sv_warning_log warningLog ON warningLog.warning_log_id = report_comment.warning_log_id
            LEFT JOIN xf_warning warning ON warningLog.warning_id = warning.warning_id
            WHERE report_comment_id IN (' . $this->_getDb()->quote($ids) . ')
        ', 'report_comment_id');
    }

    var $_handlerCache = array();

    /**
     * @param string $content_type
     * @return XenForo_ReportHandler_Abstract|false
     */
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

    /**
     * @param array $contentIds
     * @param array $viewingUser
     * @return array
     */
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

        foreach ($comments as $commentId => &$comment)
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

    /**
     * @param int $start
     * @param int $limit
     * @return array|null
     */
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

    /**
     * @param int $start
     * @param int $limit
     * @return array|null
     */
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

    /**
     * @param array $reportIds
     * @return array|null
     */
    public function sv_getReportsByIds(array $reportIds)
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

    /**
     * @param array $report
     * @return array
     */
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
        if (count($watcherUserIds) <= 1 && $alert_mode != 'watchers')
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

    /**
     * @param array $report
     * @param array $reportComment
     * @param array $tagged
     * @param array $alreadyAlerted
     * @param array $taggingUser
     * @return array
     */
    public function alertTaggedMembers(array $report, array $reportComment, array $tagged, array $alreadyAlerted, array $taggingUser)
    {
        $userIds = XenForo_Application::arrayColumn($tagged, 'user_id');
        $userIds = array_diff($userIds, $alreadyAlerted);
        $alertedUserIds = array();

        $report = $this->getReportById($report['report_id']);
        if (empty($report))
        {
            return array();
        }
        $handler = $this->getReportHandler($report['content_type']);
        if (empty($handler))
        {
            return array();
        }

        if ($userIds)
        {
            $userModel = $this->_getUserModel();
            $users = $userModel->getUsersByIds($userIds, array(
                'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
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

    /**
     * @param array $comment
     * @param array|null $viewingUser
     * @return array
     */
    public function prepareReportComment(array $comment, array $viewingUser = null)
    {
        $comment = parent::prepareReportComment($comment);

        $this->standardizeViewingUserReference($viewingUser);

        $comment['canLike'] = $this->canLikeReportComment($comment, $null, $viewingUser);
        if (!empty($comment['likes']))
        {
            $comment['likeUsers'] = @unserialize($comment['like_users']);
        }
        $comment['canViewReporterUsername'] = $this->canViewReporterUsername($comment, $viewingUser);
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

    /**
     * @param array|null $viewingUser
     * @return bool
     */
    public function canResolveReplyBanReports(array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'viewReports') &&
               XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'updateReport');
    }

    /**
     * @param array|null $viewingUser
     * @return bool
     */
    public function canViewReports(array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'viewReports');
    }

    /**
     * @param array $report
     * @param array|null $viewingUser
     * @return bool
     */
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

    /**
     * @param array $report
     * @param array|null $viewingUser
     * @return bool
     */
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

    /**
     * @param array $report
     * @param array|null $viewingUser
     * @return bool
     */
    public function canCommentReport(array $report, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        if (empty($viewingUser['user_id']))
        {
            return false;
        }

        if (($report['report_state'] == 'resolved' || $report['report_state'] == 'rejected') &&
            !XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'replyReportClosed')
        )
        {
            return false;
        }

        return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'replyReport');
    }

    /**
     * @param array|null $comment
     * @param array|null $viewingUser
     * @return bool
     */
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

    /**
     * @param array $comment
     * @param string $errorPhraseKey
     * @param array|null $viewingUser
     * @return bool
     */
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

    /**
     * @param int $oldUserId
     * @param int $newUserId
     * @param string $oldUsername
     * @param string $newUsername
     */
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

    public function getReportForContent($contentType, $contentId, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);
        if (!$this->canViewReports($viewingUser))
        {
            return null;
        }

        $report = $this->getReportByContent($contentType, $contentId);
        if (!$report)
        {
            return null;
        }

        $reports = $this->getVisibleReportsForUser(array($report['report_id'] => $report));
        if (empty($reports))
        {
            return null;
        }

        $report = reset($reports);
        if (empty($report))
        {
            return null;
        }

        return $report;
    }

    public function logReportForContent(array $report, $resolve = true, $reportCommentFunc, array $viewingUser = null)
    {
        $this->standardizeViewingUserReference($viewingUser);

        XenForo_Db::beginTransaction();

        $reportDw = XenForo_DataWriter::create('XenForo_DataWriter_Report');
        $reportDw->setExistingData($report, true);

        $commentDw = XenForo_DataWriter::create('XenForo_DataWriter_ReportComment');
        $commentDw->setOption(SV_ReportImprovements_XenForo_DataWriter_ReportComment::OPTION_WARNINGLOG_REPORT, $report);
        $commentDw->bulkSet(array(
                       'report_id' => $report['report_id'],
                       'user_id' => $viewingUser['user_id'],
                       'username' => $viewingUser['username'],
                   ));
                   
        if ($resolve)
        {
            $reportDw->set('report_state', 'resolved');
            $commentDw->set('state_change', 'resolved');
        }

        $save = true;
        if ($reportCommentFunc)
        {
            $save = call_user_func($reportCommentFunc, $reportDw, $commentDw, $viewingUser);
        }
        if ($save)
        {
            $commentDw->save();
            if ($reportDw->hasChanges())
            {
                $reportDw->save();
            }
        }

        XenForo_Db::commit();
    }

    /**
     * @param $reportState
     * @return int|null
     */
    public function mapReportState($reportState)
    {
        switch ($reportState)
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

    /**
     * @return SV_ReportImprovements_XenForo_Model_User
     */
    protected function _getUserModel()
    {
        return $this->getModelFromCache('XenForo_Model_User');
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Thread
     */
    protected function _getThreadModel()
    {
        return $this->getModelFromCache('XenForo_Model_Thread');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_Model_Report extends XenForo_Model_Report {}
}