<?php

class SV_IntegratedReports_Model_WarningLog extends XenForo_Model
{
    const Operation_EditWarning = 'edit';
    const Operation_DeleteWarning = 'delete';
    const Operation_ExpireWarning = 'expire';
    const Operation_NewWarning = 'new';
    
	/**
	 * Gets the specified Warning Log if it exists.
	 *
	 * @param string $warningLogId
	 *
	 * @return array|false
	 */
	public function getWarningLogById($warningLogId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_sv_warning_log
			WHERE warning_log_id = ?
		', $warningLogId);
	}
    
    public static $UseSystemUsernameForComments = false;
    public static $SystemUserId;
    public static $SystemUsername;
    
    
	/**
	 * Gets the specified Warning Log if it exists.
	 *
	 * @param string $operationType
     * @param array $warning
	 *
	 * @return int|false
	 */    
    public function LogOperation($operationType, $warning)
    {
        if (@$operationType == '')
            throw new Exception("Unknown operation type when logging warning");

        unset($warning['warning_log_id']);
        $warning['operation_type'] = $operationType;
        $warning['warning_edit_date'] = XenForo_Application::$time;
        
        $warningLogDw = XenForo_DataWriter::create('SV_IntegratedReports_DataWriter_WarningLog');
        $warningLogDw->bulkSet($warning);
        $warningLogDw->save();
        $warningLogId = $warningLogDw->get('warning_log_id');

        $contentType = $warning['content_type'];
        $contentId = $warning['content_id'];
        $reportModel = $this->_getReportModel();
        

        $report = $reportModel->getReportByContent($contentType, $contentId);
        if ($report)
        {
            $options = XenForo_Application::getOptions();
            $reporterId = $options->sv_ir_user_id;
            $message = $this->_BuildWarningLogMessage();
            $viewingUser = XenForo_Visitor::getInstance()->toArray();
            $updating_user_id = $viewingUser['user_id'];
            $updating_username = $viewingUser['username'];
            if (self::$UseSystemUsernameForComments || $viewingUser['user_id'] == 0 || $viewingUser['username'] == '')
            {
                if (!isset(self::$SystemUserId) || $SystemUserId != $reporterId )
                {
                    $userModel = $this->_getUserModel();
                    $user = $userModel->getUserById($reporterId);
                    $updating_user_id = self::$SystemUserId = $reporterId;
                    $updating_username = self::$SystemUsername = $user['username'];
                }
            }
            
            $reportId = $report['report_id'];
            $newReportState = '';
            // don't re-open the report when a warning expires naturally.
            if ($operationType != SV_IntegratedReports_Model_WarningLog::Operation_ExpireWarning)
            {            
                if ($report['report_state'] == 'resolved' || $report['report_state'] == 'rejected')
                {
                    // re-open an existing report
                    $reportDw = XenForo_DataWriter::create('XenForo_DataWriter_Report');
                    $reportDw->setExistingData($report, true);
                    $reportDw->set('report_state', 'open');
                    $reportDw->save();

                    $newReportState = 'open';
                }
            }

            $commentDw = XenForo_DataWriter::create('XenForo_DataWriter_ReportComment');
            $commentDw->bulkSet(array(
                'report_id' => $reportId,
                'user_id' => $updating_user_id,
                'username' => $updating_username,
                'message' => $message,
                'state_change' => $newReportState,
                'is_report' => 0,
                'warning_log_id' => $warningLogId,
            ));
            $commentDw->save();
        }
        
        return $warningLogId;
    }   

    protected function _BuildWarningLogMessage()
    {
        return '';
    }
    
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
    
	protected function _getReportModel()
	{
		return $this->getModelFromCache('XenForo_Model_Report');
	}
}