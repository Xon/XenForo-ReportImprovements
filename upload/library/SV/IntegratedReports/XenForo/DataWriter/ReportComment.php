<?php

class SV_IntegratedReports_XenForo_DataWriter_ReportComment extends XFCP_SV_IntegratedReports_XenForo_DataWriter_ReportComment
{
    const OPTION_MAX_TAGGED_USERS = 'maxTaggedUsers';

    protected $_taggedUsers = array();

    protected function _getDefaultOptions()
    {
        return parent::_getDefaultOptions() + array(
            self::OPTION_MAX_TAGGED_USERS => SV_IntegratedReports_Globals::$Report_MaxAlertCount
        );
    }

	protected function _getFields()
	{
        $fields = parent::_getFields();
        $fields['xf_report_comment']['warning_log_id'] = array('type' => self::TYPE_UINT,    'default' => 0);
        return $fields;
	}

	protected function _preSave()
	{
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

    protected function _postSave()
    {
        parent::_postSave();

        if (!$this->isInsert())
        {
            return;
        }

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

            $this->_getReportModel()->alertTaggedMembers($report, $reportComment, $alertTagged, $alertedUserIds, array(
                    'user_id' => $this->get('user_id'),
                    'username' => $this->get('username')
                )
            );

            $alertedUserIds = $alertedUserIds + $alertTagged;
        }

        // alert users interacting with this report
        $otherCommenterIds = $reportModel->getReportCommentUserIds(
            $this->get('report_id')
        );

        $otherCommenters = $this->_getUserModel()->getUsersByIds($otherCommenterIds, array(
            'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
        ));

        $db = XenForo_Application::getDb();

        $_alertedUserIds = array_keys($alertedUserIds);

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
                $hasUnviewedReport = $db->fetchRow("select alert_id from xf_user_alert
                    where alerted_user_id = ? and content_type = ? and content_id = ? and view_date = 0 and action = ? ",
                    array($otherCommenter['user_id'], SV_IntegratedReports_Globals::$Report_ContentType, $this->get('report_id'), SV_IntegratedReports_Globals::$Report_Comment)
                );

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
                        SV_IntegratedReports_Globals::$Report_ContentType,
                        $this->get('report_id'),
                        SV_IntegratedReports_Globals::$Report_Comment,
                        array('report_comment_id' => $this->get('report_comment_id'))
                    );
                }
            }
        }
    }
}