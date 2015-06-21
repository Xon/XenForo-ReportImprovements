<?php

class SV_ReportImprovements_Model_WarningLog extends XenForo_Model
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

    public function getContentForReportFromWarning(array $warning)
    {
        $contentType = $warning['content_type'];
        $contentId = $warning['content_id'];
        switch($contentType)
        {
            case 'post':
                return $this->getModelFromCache('XenForo_Model_Post')->getPostById($contentId);
            case 'profile_post':
                return $this->getModelFromCache('XenForo_Model_ProfilePost')->getProfilePostById($contentId);
            case 'user':
                return $this->getModelFromCache('XenForo_Model_User')->getUserById($contentId);
            default:
                XenForo_Error::debug("Can't create report for unknown content type: ".$contentType);
                break;
        }
        return null;
    }

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

        $warningLogDw = XenForo_DataWriter::create('SV_ReportImprovements_DataWriter_WarningLog');
        $warningLogDw->bulkSet($warning);
        $warningLogDw->save();
        $warningLogId = $warningLogDw->get('warning_log_id');

        if (SV_ReportImprovements_Globals::$SupressLoggingWarningToReport)
        {
            return $warningLogId;
        }

        $options = XenForo_Application::getOptions();
        $contentType = $warning['content_type'];
        $contentId = $warning['content_id'];
        $reportModel = $this->_getReportModel();

        $reporterId = $options->sv_ri_user_id;
        $message = $this->_BuildWarningLogMessage();
        $reportUser = $viewingUser = XenForo_Visitor::getInstance()->toArray();
        $updating_user_id = $viewingUser['user_id'];
        $updating_username = $viewingUser['username'];
        if (SV_ReportImprovements_Globals::$UseSystemUsernameForComments || $viewingUser['user_id'] == 0 || $viewingUser['username'] == '')
        {
            if (!empty(SV_ReportImprovements_Globals::$SystemUserId) || $SystemUserId != $reporterId )
            {
                $userModel = $this->_getUserModel();
                $reportUser = $userModel->getUserById($reporterId);
                $updating_user_id = SV_ReportImprovements_Globals::$SystemUserId = $reporterId;
                $updating_username = SV_ReportImprovements_Globals::$SystemUsername = $reportUser['username'];
            }
        }

        $commentToUpdate = null;
        $newReportState = '';
        $assigned_user_id = 0;
        if(SV_ReportImprovements_Globals::$resolve_report && ($reportUser['user_id'] == $viewingUser['user_id']))
        {
            $newReportState = 'resolved';
            $assigned_user_id = $reportUser['user_id'];
        }

        $report = $reportModel->getReportByContent($contentType, $contentId);
        if (empty($report))
        {
            if($options->sv_report_new_warnings)
            {
                // create a report for tracking purposes.
                $content = $this->getContentForReportFromWarning($warning);
                if ($content)
                {
                    $reportId = $reportModel->reportContent($contentType, $content, '.', $reportUser);
                    if($reportId)
                    {
                        $report = $reportModel->getReportById($reportId);
                        $reportComments = $reportModel->getReportComments($reportId);
                        $commentToUpdate = reset($reportComments);
                    }
                }
            }
        }

        if ($report)
        {
            $reportId = $report['report_id'];
            // don't re-open the report when a warning expires naturally.
            if ($operationType != SV_ReportImprovements_Model_WarningLog::Operation_ExpireWarning)
            {
                if ($newReportState == '' && ($report['report_state'] == 'resolved' || $report['report_state'] == 'rejected'))
                {
                    // re-open an existing report
                    $newReportState = 'open';
                }
            }

            if (!empty($newReportState) || !empty($assigned_user_id))
            {
                $reportDw = XenForo_DataWriter::create('XenForo_DataWriter_Report');
                $reportDw->setExistingData($report, true);
                if(!empty($newReportState))
                {
                    $reportDw->set('report_state',  $newReportState);
                }
                if(!empty($assigned_user_id))
                {
                    $reportDw->set('assigned_user_id',  $assigned_user_id);
                }
                $reportDw->save();
            }

            $commentDw = XenForo_DataWriter::create('XenForo_DataWriter_ReportComment');
            if (!empty($commentToUpdate))
            {
                $commentDw->setExistingData($commentToUpdate);
            }
            else
            {
                $commentDw->bulkSet(array(
                    'report_id' => $reportId,
                    'user_id' => $updating_user_id,
                    'username' => $updating_username,
                    'is_report' => 0,
                ));
            }
            $commentDw->bulkSet(array(
                'message' => $message,
                'state_change' => $newReportState,
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