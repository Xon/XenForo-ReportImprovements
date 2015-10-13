<?php

class SV_ReportImprovements_XenForo_DataWriter_ReportComment extends XFCP_SV_ReportImprovements_XenForo_DataWriter_ReportComment
{
    const OPTION_MAX_TAGGED_USERS = 'maxTaggedUsers';
    const OPTION_INDEX_FOR_SEARCH = 'indexForSearch';

    protected $_taggedUsers = array();

    protected function _getDefaultOptions()
    {
        $defaultOptions = parent::_getDefaultOptions();
        $defaultOptions[self::OPTION_MAX_TAGGED_USERS] = SV_ReportImprovements_Globals::$Report_MaxAlertCount;
        $defaultOptions[self::OPTION_INDEX_FOR_SEARCH] = true;
        return $defaultOptions;
    }

    protected function _getFields()
    {
        $fields = parent::_getFields();
        $fields['xf_report_comment']['warning_log_id'] = array('type' => self::TYPE_UINT,    'default' => 0);
        $fields['xf_report_comment']['likes'] = array('type' => self::TYPE_UINT_FORCED, 'default' => 0);
        $fields['xf_report_comment']['like_users'] = array('type' => self::TYPE_SERIALIZED);
        $fields['xf_report_comment']['alertSent'] = array('type' => self::TYPE_UINT, 'default' => 0);
        $fields['xf_report_comment']['alertComment'] = array('type' => self::TYPE_STRING);
        return $fields;
    }

    protected function _preSave()
    {
        if (SV_ReportImprovements_Globals::$UserReportAlertComment)
        {
            $this->set('alertSent', true);
            if (SV_ReportImprovements_Globals::$UserReportAlertComment !== true)
            {
                $this->set('alertComment', SV_ReportImprovements_Globals::$UserReportAlertComment);
            }
        }

        if (!$this->get('state_change') && !$this->get('message'))
        {
            if ($this->get('warning_log_id'))
            {
                return;
            }
        }

        $taggingModel = $this->getModelFromCache('XenForo_Model_UserTagging');

        $this->_taggedUsers = $taggingModel->getTaggedUsersInMessage(
            $this->get('message'), $newMessage, 'text'
        );
        $this->set('message', $newMessage);

        return parent::_preSave();
    }

    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();

        if ($this->getOption(self::OPTION_INDEX_FOR_SEARCH))
        {
            $this->_insertIntoSearchIndex();
        }

        if (!$this->isInsert())
        {
            return;
        }

        SV_ReportImprovements_Globals::$reportId = $this->get('report_id');

        $reportModel = $this->_getReportModel();
        $report = $reportModel->getReportById($this->get('report_id'));
        if (empty($report))
        {
            return;
        }
        $handler = $reportModel->getReportHandler($report['content_type']);
        if (empty($handler))
        {
            return;
        }

        $reportComment = $this->getMergedData();

        $alertedUserIds = array();

        // alert users who are tagged
        $maxTagged = $this->getOption(self::OPTION_MAX_TAGGED_USERS);
        if ($maxTagged && $this->_taggedUsers)
        {
            if ($maxTagged > 0)
            {
                $alertTagged = array_slice($this->_taggedUsers, 0, $maxTagged, true);
            }
            else
            {
                $alertTagged = $this->_taggedUsers;
            }

            $alertedUserIds = array_merge($alertedUserIds, $this->_getReportModel()->alertTaggedMembers($report, $reportComment, $alertTagged, $alertedUserIds, array(
                    'user_id' => $this->get('user_id'),
                    'username' => $this->get('username')
                )
            ));
        }

        // alert users interacting with this report
        $otherCommenterIds = $reportModel->getReportCommentUserIds(
            $this->get('report_id')
        );

        $otherCommenters = $this->_getUserModel()->getUsersByIds($otherCommenterIds, array(
            'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
        ));

        $db = XenForo_Application::getDb();

        $_alertedUserIds = array_fill_keys($alertedUserIds, true);

        foreach ($otherCommenters AS $otherCommenter)
        {
            if (isset($_alertedUserIds[$otherCommenter['user_id']]))
            {
                continue;
            }

            if ($otherCommenter['user_id'] == $this->get('user_id'))
            {
                continue;
            }

            if ($otherCommenter['is_moderator'])
            {
                $hasUnviewedReport = $db->fetchRow("
                    SELECT alert.alert_id
                    FROM xf_user_alert AS alert
                    JOIN xf_report_comment AS report_comment on report_comment_id = alert.content_id
                    WHERE alert.alerted_user_id = ?
                          and alert.view_date = 0
                          and alert.content_type = ?
                          and alert.action = ?
                          and report_comment.report_id = ?
                    LIMIT 1
                ", array($otherCommenter['user_id'], 'report_comment', 'insert', $this->get('report_id')));

                if (!empty($hasUnviewedReport))
                {
                    continue;
                }

                $otherCommenter['permissions'] = XenForo_Permission::unserializePermissions($otherCommenter['global_permission_cache']);

                $reports = $handler->getVisibleReportsForUser(array($this->get('report_id') => $report), $otherCommenter);

                if (!empty($reports))
                {
                    XenForo_Model_Alert::alert(
                        $otherCommenter['user_id'],
                        $this->get('user_id'),
                        $this->get('username'),
                        'report_comment',
                        $this->get('report_comment_id'),
                        'insert');
                }
            }
        }
    }

    public function delete()
    {
        parent::delete();
        // update search index outside the transaction
        $this->_deleteFromSearchIndex();
    }

    protected function _insertIntoSearchIndex()
    {
        $dataHandler = $this->_getSearchDataHandler();
        if (!$dataHandler)
        {
            return;
        }

        $viewingUser = XenForo_Visitor::getInstance()->toArray();
        $indexer = new XenForo_Search_Indexer();
        $dataHandler->insertIntoIndex($indexer, $this->getMergedData(), array());
    }

    protected function _deleteFromSearchIndex()
    {
        $dataHandler = $this->_getSearchDataHandler();
        if (!$dataHandler)
        {
            return;
        }

        $indexer = new XenForo_Search_Indexer();
        $dataHandler->deleteFromIndex($indexer, $this->getMergedData());
    }

    public function _getSearchDataHandler()
    {
        return XenForo_Search_DataHandler_Abstract::create('SV_ReportImprovements_Search_DataHandler_ReportComment');
    }
}