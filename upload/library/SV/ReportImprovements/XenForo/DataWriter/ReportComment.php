<?php

/**
 * Class SV_ReportImprovements_XenForo_DataWriter_ReportComment
 */
class SV_ReportImprovements_XenForo_DataWriter_ReportComment extends XFCP_SV_ReportImprovements_XenForo_DataWriter_ReportComment
{
    const OPTION_MAX_TAGGED_USERS   = 'maxTaggedUsers';
    const OPTION_INDEX_FOR_SEARCH   = 'indexForSearch';
    const OPTION_WARNINGLOG_WARNING = 'warningLog_warning';
    const OPTION_WARNINGLOG_REPORT  = 'warningLog_report';
    const OPTION_SEND_ALERTS        = 'sendAlerts';

    protected $_taggedUsers = array();

    protected function _getDefaultOptions()
    {
        $defaultOptions = parent::_getDefaultOptions();
        $defaultOptions[self::OPTION_MAX_TAGGED_USERS] = SV_ReportImprovements_Globals::$Report_MaxAlertCount;
        $defaultOptions[self::OPTION_INDEX_FOR_SEARCH] = true;
        $defaultOptions[self::OPTION_WARNINGLOG_WARNING] = false;
        $defaultOptions[self::OPTION_WARNINGLOG_REPORT] = false;
        $defaultOptions[self::OPTION_SEND_ALERTS] = true;

        return $defaultOptions;
    }

    protected function _getFields()
    {
        $fields = parent::_getFields();
        $fields['xf_report_comment']['warning_log_id'] = array('type' => self::TYPE_UINT, 'default' => 0);
        $fields['xf_report_comment']['likes'] = array('type' => self::TYPE_UINT_FORCED, 'default' => 0);
        $fields['xf_report_comment']['like_users'] = array('type' => self::TYPE_SERIALIZED);
        $fields['xf_report_comment']['alertSent'] = array('type' => self::TYPE_UINT, 'default' => 0);
        $fields['xf_report_comment']['alertComment'] = array('type' => self::TYPE_STRING);

        return $fields;
    }

    protected function _getReport()
    {
        $report = $this->getOption(self::OPTION_WARNINGLOG_REPORT);
        if (empty($report))
        {
            $report = $this->_getReportModel()->getReportById($this->get('report_id'));
            $this->getOption(self::OPTION_WARNINGLOG_REPORT, $report);
        }

        return $report;
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

        $warning = $this->getOption(self::OPTION_WARNINGLOG_WARNING);
        $report = $this->_getReport();
        if ($warning && $report)
        {
            $this->set('warning_log_id', $warning['warning_log_id']);
            $this->sv_linkWarning($warning, $report);
        }

        if (!$this->get('state_change') && !$this->get('message'))
        {
            if ($this->get('warning_log_id'))
            {
                return;
            }
        }

        /** @var XenForo_Model_UserTagging $taggingModel */
        $taggingModel = $this->getModelFromCache('XenForo_Model_UserTagging');

        $this->_taggedUsers = $taggingModel->getTaggedUsersInMessage(
            $this->get('message'), $newMessage, 'text'
        );
        $this->set('message', $newMessage);

        parent::_preSave();
    }

    protected function sv_getNewReportState($assigned_user_id, array $warning, array $report)
    {
        $newReportState = '';
        if (SV_ReportImprovements_Globals::$ResolveReport)
        {
            $newReportState = 'resolved';
        }

        // don't re-open the report when a warning expires naturally.
        if (
            $warning['operation_type'] != SV_ReportImprovements_Model_WarningLog::Operation_ExpireWarning &&
            $warning['operation_type'] != SV_ReportImprovements_Model_WarningLog::Operation_AcknowledgeWarning
        )
        {
            if ($newReportState == '' && ($report['report_state'] == 'resolved' || $report['report_state'] == 'rejected'))
            {
                // re-open an existing report
                $newReportState = empty($report['assigned_user_id']) && empty($assigned_user_id)
                    ? 'open'
                    : 'assigned';
            }
        }
        // do not change the report state to something it already is
        if ($newReportState != '' && $report['report_state'] == $newReportState)
        {
            $newReportState = '';
        }

        return array($newReportState, $assigned_user_id);
    }

    protected function sv_linkWarning(array $warning, array $report)
    {
        $assigned_user_id = 0;
        if (SV_ReportImprovements_Globals::$AssignReport)
        {
            $assigned_user_id = $this->get('user_id');
        }
        list($newReportState, $assigned_user_id) = $this->sv_getNewReportState($assigned_user_id, $warning, $report);

        if ($this->get('message') == '.')
        {
            $this->set('message', '');
        }
        $this->set('state_change', $newReportState);
        $this->set('warning_log_id', $warning['warning_log_id']);

        if (!empty($newReportState) || !empty($assigned_user_id))
        {
            $reportDw = XenForo_DataWriter::create('XenForo_DataWriter_Report');
            $reportDw->setExistingData($report['report_id']);
            if (!empty($newReportState))
            {
                $reportDw->set('report_state', $newReportState);
            }
            if (!empty($assigned_user_id))
            {
                $reportDw->set('assigned_user_id', $assigned_user_id);
            }
            $reportDw->save();
        }
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

        $report = $this->_getReport();
        if (empty($report))
        {
            return;
        }
        $reportModel = $this->_getReportModel();
        $handler = $reportModel->getReportHandler($report['content_type']);
        if (empty($handler))
        {
            return;
        }

        $reportComment = $this->getMergedData();

        $alertedUserIds = array();

        if ($this->getOption(self::OPTION_SEND_ALERTS))
        {
            // alert users who are tagged
            $alertedUserIds = $this->_tagUsers($alertedUserIds, $report, $reportComment);
            $alertedUserIds = $this->_alertWatchers($handler, $alertedUserIds, $report, $reportComment);
        }
    }

    protected function _tagUsers(array $alertedUserIds, array $report, array $reportComment)
    {
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

            $alertedUserIds = array_merge($alertedUserIds,
                                          $this->_getReportModel()
                                               ->alertTaggedMembers($report, $reportComment, $alertTagged, $alertedUserIds,
                                                                    array(
                                                                        'user_id' => $this->get('user_id'),
                                                                        'username' => $this->get('username')
                                                                    )
                                               ));
        }

        return $alertedUserIds;
    }

    protected function _alertWatchers(XenForo_ReportHandler_Abstract $handler, array $alertedUserIds, array $report, array $reportComment)
    {
        // alert users interacting with this report
        $reportModel = $this->_getReportModel();
        $otherCommenters = $reportModel->getUsersForReportCommentAlerts($report);
        $alertType = 'insert';

        $db = XenForo_Application::getDb();

        $_alertedUserIds = array_fill_keys($alertedUserIds, true);
        $alertedUserIds = array();

        foreach ($otherCommenters AS $otherCommenter)
        {
            if (isset($_alertedUserIds[$otherCommenter['user_id']]))
            {
                continue;
            }

            if ($otherCommenter['user_id'] == $reportComment['user_id'])
            {
                continue;
            }

            if (!isset($otherCommenter['permissions']))
            {
                $otherCommenter['permissions'] = XenForo_Permission::unserializePermissions($otherCommenter['global_permission_cache']);
            }
            if ($reportModel->canViewReports($otherCommenter))
            {
                $hasUnviewedReport = $db->fetchRow("
                    SELECT alert.alert_id
                    FROM xf_user_alert AS alert
                    JOIN xf_report_comment AS report_comment ON report_comment_id = alert.content_id
                    WHERE alert.alerted_user_id = ?
                          AND alert.view_date = 0
                          AND alert.content_type = ?
                          AND alert.action = ?
                          AND report_comment.report_id = ?
                    LIMIT 1
                ", array($otherCommenter['user_id'], 'report_comment', $alertType, $report['report_id']));

                if (!empty($hasUnviewedReport))
                {
                    continue;
                }

                $reports = $handler->getVisibleReportsForUser(array($report['report_id'] => $report), $otherCommenter);

                if (!empty($reports))
                {
                    if ($reportModel->canViewReporterUsername($reportComment, $otherCommenter))
                    {
                        $user_id = $reportComment['user_id'];
                        $username = $reportComment['username'];
                    }
                    else
                    {
                        $user_id = 0;
                        $username = 'Guest';
                    }

                    if (XenForo_Model_Alert::userReceivesAlert($otherCommenter, 'report_comment', $alertType))
                    {
                        XenForo_Model_Alert::alert(
                            $otherCommenter['user_id'],
                            $user_id,
                            $username,
                            'report_comment',
                            $reportComment['report_comment_id'],
                            $alertType);

                        $alertedUserIds[$otherCommenter['user_id']] = true;
                    }
                }
            }
        }

        return array_keys($alertedUserIds);
    }

    public function delete()
    {
        parent::delete();
        // update search index outside the transaction
        $this->_deleteFromSearchIndex();
    }

    protected function _insertIntoSearchIndex()
    {
        $dataHandler = $this->sv_getSearchDataHandler();
        if (!$dataHandler)
        {
            return;
        }

        $report = $this->_getReport();
        $indexer = new XenForo_Search_Indexer();
        $dataHandler->insertIntoIndex($indexer, $this->getMergedData(), $report);
    }

    protected function _deleteFromSearchIndex()
    {
        $dataHandler = $this->sv_getSearchDataHandler();
        if (!$dataHandler)
        {
            return;
        }

        $indexer = new XenForo_Search_Indexer();
        $dataHandler->deleteFromIndex($indexer, $this->getMergedData());
    }

    /**
     * @return XenForo_Search_DataHandler_Abstract|null
     */
    public function sv_getSearchDataHandler()
    {
        /* var $dataHandler XenForo_Search_DataHandler_Abstract */
        $dataHandler = $this->_getSearchModel()->getSearchDataHandler('report_comment');

        return ($dataHandler instanceof SV_ReportImprovements_Search_DataHandler_ReportComment) ? $dataHandler : null;
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        return $this->getModelFromCache('XenForo_Model_Report');
    }

    /**
     * @return XenForo_Model_Search
     */
    protected function _getSearchModel()
    {
        return $this->getModelFromCache('XenForo_Model_Search');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_DataWriter_ReportComment extends XenForo_DataWriter_ReportComment {}
}