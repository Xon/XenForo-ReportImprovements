<?php

class SV_IntegratedReports_XenForo_DataWriter_ReportComment extends XFCP_SV_IntegratedReports_XenForo_DataWriter_ReportComment
{
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
        return parent::_preSave();
	}

    protected function _postSave()
    {
        parent::_postSave();

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

        $otherCommenterIds = $reportModel->getReportCommentUserIds(
            $this->get('report_id')
        );

        $otherCommenters = $this->_getUserModel()->getUsersByIds($otherCommenterIds, array(
            'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
        ));

        $db = XenForo_Application::getDb();

        foreach ($otherCommenters AS $otherCommenter)
        {
            if ($otherCommenter['user_id'] == $this->get('user_id'))
            {
                continue;
            }

            if ($otherCommenter['is_moderator'])
            {
                $hasUnviewedReport = $db->fetchRow("select alert_id from xf_user_alert
                    where alerted_user_id = ? and content_type = ? and content_id = ? and view_date = 0 and action = ? ",
                    array($otherCommenter['user_id'], SV_IntegratedReports_AlertHandler_Report::ContentType, $this->get('report_id'), 'comment')
                );

                if (!empty($hasUnviewedReport))
                {
                    continue;
                }

                $otherCommenter['permissions'] = unserialize($otherCommenter['global_permission_cache']);

                $reports = $handler->getVisibleReportsForUser(array($this->get('report_id') => $report), $otherCommenter);

                if (!empty($reports))
                {
                    XenForo_Model_Alert::alert(
                        $otherCommenter['user_id'],
                        $this->get('user_id'),
                        $this->get('username'),
                        SV_IntegratedReports_AlertHandler_Report::ContentType,
                        $this->get('report_id'),
                        'comment',
                        array('report_comment_id' => $this->get('report_comment_id'))
                    );
                }
            }
        }
    }
}