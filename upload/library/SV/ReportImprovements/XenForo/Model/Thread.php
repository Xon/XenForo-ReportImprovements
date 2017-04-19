<?php

class SV_ReportImprovements_XenForo_Model_Thread extends XFCP_SV_ReportImprovements_XenForo_Model_Thread
{
    public function _getReplyBanLogData(array $thread, array $replyBan, $expired = false)
    {
        $link = XenForo_Link::buildPublicLink('full:threads', array_merge($thread, array('title' => '')));

        return array(
            'content_type' => 'user',
            'content_id' => $replyBan['user_id'],
            'content_title' => $replyBan['username'],
            'user_id' => $replyBan['user_id'],
            'warning_date' => $replyBan['ban_date'],
            'warning_user_id' => $replyBan['ban_user_id'],
            'title' => (string)new XenForo_Phrase('SV_Reply_Banned_From_Thread_title', array('thread' => $thread['title'])),
            'reply_ban_thread_id' => $thread['thread_id'],
            'notes' => (string)new XenForo_Phrase('SV_Reply_Banned_notes', array('link' => $link, 'reason' => $replyBan['reason'])),
            'points' => 0,
            'expiry_date' => $replyBan['expiry_date'],
            'is_expired' => $expired,
            'extra_user_group_ids' => '',
        );
    }

    public function insertThreadReplyBan(array $thread, array $user, $expiryDate = null, $reason = '', $sendAlert = false, $banUserId = null)
    {
        $result = parent::insertThreadReplyBan($thread, $user, $expiryDate, $reason, $sendAlert, $banUserId);

        if ($result)
        {
            $replyBan = $this->_getDb()->fetchRow("
                SELECT * 
                FROM xf_thread_reply_ban
                WHERE thread_id = ?
                    AND user_id = ?
            ", array($thread['thread_id'], $user['user_id']));
            $replyBan['username'] = $user['username'];
            // added/updated a reply ban
            if ($replyBan['ban_date'] == XenForo_Application::$time)
            {
                $operationType = SV_ReportImprovements_Model_WarningLog::Operation_NewWarning;
            }
            else
            {
                $operationType = SV_ReportImprovements_Model_WarningLog::Operation_EditWarning;
            }

            try
            {
                return $this->_getWarningLogModel()->LogOperation($operationType, $this->_getReplyBanLogData($thread, $replyBan));
            }
            catch (XenForo_Exception $e)
            {
                XenForo_Error::logException($e);
                throw $e;
            }
        }

        return $result;
    }

    public function deleteThreadReplyBan(array $thread, array $user)
    {
        $replyBan = $this->_getDb()->fetchRow("
			SELECT * 
			FROM xf_thread_reply_ban
			WHERE thread_id = ?
				AND user_id = ?
		", array($thread['thread_id'], $user['user_id']));

        $result = parent::deleteThreadReplyBan($thread, $user);

        if ($result && $replyBan)
        {
            $replyBan['username'] = $user['username'];
            // deleted a reply ban
            $operationType = SV_ReportImprovements_Model_WarningLog::Operation_DeleteWarning;
            try
            {
                return $this->_getWarningLogModel()->LogOperation($operationType, $this->_getReplyBanLogData($thread, $replyBan));
            }
            catch (XenForo_Exception $e)
            {
                XenForo_Error::logException($e);
                throw $e;
            }
        }

        return $result;
    }

    public function cleanUpExpiredThreadReplyBans($expiryDate = null)
    {
        if ($expiryDate === null)
        {
            $expiryDate = XenForo_Application::$time;
        }

        $db = $this->_getDb();
        $replyBans = $db->fetchAll("
			SELECT xf_thread_reply_ban.*, xf_user.username 
			FROM xf_thread_reply_ban
			LEFT JOIN xf_user ON xf_user.user_id = xf_thread_reply_ban.user_id
			WHERE expiry_date <= ?
		", $expiryDate);
        $operationType = SV_ReportImprovements_Model_WarningLog::Operation_ExpireWarning;
        $threadIds = XenForo_Application::arrayColumn($replyBans, 'thread_id');
        $threads = $this->fetchAllKeyed("SELECT * FROM xf_thread WHERE thread_id IN (" . $db->quote($threadIds) . ")");

        foreach ($replyBans as $replyBan)
        {
            XenForo_Db::beginTransaction();
            if (!empty($threads[$replyBan['thread_id']]))
            {
                $thread = $threads[$replyBan['thread_id']];
                $this->_getWarningLogModel()->LogOperation($operationType, $this->_getReplyBanLogData($thread, $replyBan, true));
            }
            $db->query("DELETE FROM xf_thread_reply_ban WHERE thread_reply_ban_id = ?", $replyBan['thread_reply_ban_id']);
            XenForo_Db::commit();
        }
    }

    /**
     * @return SV_ReportImprovements_Model_WarningLog
     */
    protected function _getWarningLogModel()
    {
        return $this->getModelFromCache('SV_ReportImprovements_Model_WarningLog');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_Model_Thread extends XenForo_Model_Thread {}
}