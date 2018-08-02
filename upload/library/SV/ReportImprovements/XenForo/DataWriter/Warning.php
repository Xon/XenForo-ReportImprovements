<?php

class SV_ReportImprovements_XenForo_DataWriter_Warning extends XFCP_SV_ReportImprovements_XenForo_DataWriter_Warning
{
    protected $replyBanUser = null;
    protected $replyBanThread = null;
    protected $replyBanOptions = null;

    protected function _preSave()
    {
        // cap expiry time due to expiry_date calc bug
        if ($this->isInsert() && $this->get('expiry_date') >= 4294967295)
        {
             $this->set('expiry_date', 0);
        }

        parent::_preSave();

        if (SV_ReportImprovements_Globals::$replyBanOptions && $this->get('content_type') == 'post')
        {
            $user = $this->_getUserModel()->getUserById($this->get('user_id'));

            $postId = $this->get('content_id');
            $post = $this->_getPostModel()->getPostById($postId, array(
                'join' => XenForo_Model_Post::FETCH_THREAD | XenForo_Model_Post::FETCH_FORUM,
                'skip_wordcount' => true,
            ));

            if (!empty($post['thread_id']) && $user && $this->_getThreadModel()->canReplyBanUserFromThread($user, $post, $post, $errorPhraseKey))
            {
                $this->replyBanUser = $user;
                $this->replyBanThread = $post;
                $this->replyBanOptions = SV_ReportImprovements_Globals::$replyBanOptions;
            }
            else
            {
                $this->error($errorPhraseKey, 'ban_length');
            }
        }
    }

    protected function _postDelete()
    {
        $operationType = SV_ReportImprovements_Model_WarningLog::Operation_DeleteWarning;
        $this->_logOperation($operationType);
        parent::_postDelete();
    }

    protected function _postSave()
    {
        $operationType = $this->getOperationType();
        $this->_logOperation($operationType);

        if ($this->replyBanOptions)
        {
            if ($this->replyBanOptions['ban_length'] == 'permanent')
            {
                $expiryDate = null;
            }
            else
            {
                $expiryDate = min(
                    pow(2,32) - 1,
                    strtotime("+{$this->replyBanOptions['ban_length_value']} {$this->replyBanOptions['ban_length_unit']}")
                );
            }

            if (empty($this->replyBanOptions['reason_reply_ban']))
            {
                $this->replyBanOptions['reason_reply_ban'] = $this->get('title');
            }

            $this->_getThreadModel()->insertThreadReplyBan($this->replyBanThread, $this->replyBanUser, $expiryDate, $this->replyBanOptions['reason_reply_ban'], $this->replyBanOptions['send_alert_reply_ban']);
        }

        parent::_postSave();
    }

    protected function _getLogData()
    {
        return array(
            'warning_id'            => $this->get('warning_id'),
            'content_type'          => $this->get('content_type'),
            'content_id'            => $this->get('content_id'),
            'content_title'         => $this->get('content_title'),
            'user_id'               => $this->get('user_id'),
            'warning_date'          => $this->get('warning_date'),
            'warning_user_id'       => $this->get('warning_user_id'),
            'warning_definition_id' => $this->get('warning_definition_id'),
            'title'                 => $this->get('title'),
            'notes'                 => $this->get('notes'),
            'points'                => $this->get('points'),
            'expiry_date'           => $this->get('expiry_date'),
            'is_expired'            => $this->get('is_expired'),
            'extra_user_group_ids'  => $this->get('extra_user_group_ids'),
        );
    }

    protected function getOperationType()
    {
        $operationType = '';
        if ($this->isInsert())
        {
            $operationType = SV_ReportImprovements_Model_WarningLog::Operation_NewWarning;
        }
        else if ($this->isUpdate())
        {
            $operationType = SV_ReportImprovements_Model_WarningLog::Operation_EditWarning;

            if ($this->isChanged('sv_acknowledgement') && $this->get('sv_acknowledgement') == 'completed') {
                $operationType = SV_ReportImprovements_Model_WarningLog::Operation_AcknowledgeWarning;
            }

            if (!$this->isChanged('expiry_date') && ($this->get('is_expired') == 1 && $this->getExisting('is_expired') == 0))
            {
                $operationType = SV_ReportImprovements_Model_WarningLog::Operation_ExpireWarning;
            }
        }
        return $operationType;
    }

    protected function _logOperation($operationType)
    {
        try
        {
            return $this->_getWarningLogModel()->LogOperation($operationType, $this->_getLogData());
        }
        catch(XenForo_Exception $e)
        {
            XenForo_Error::logException($e);
            throw $e;
        }
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Post|XenForo_Model
     */
    protected function _getPostModel()
    {
        return $this->getModelFromCache('XenForo_Model_Post');
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Thread|XenForo_Model
     */
    protected function _getThreadModel()
    {
        return $this->getModelFromCache('XenForo_Model_Thread');
    }

    /**
     * @return SV_ReportImprovements_Model_WarningLog|XenForo_Model
     */
    protected function _getWarningLogModel()
    {
        return $this->getModelFromCache('SV_ReportImprovements_Model_WarningLog');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_DataWriter_Warning extends XenForo_DataWriter_Warning {}
}
