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

    public function canCreateReportFor($contentType)
    {
        $WarningHandler = $this->_getWarningModel()->getWarningHandler($contentType);
        $ReportHandler = $this->_getReportModel()->getReportHandler($contentType);
        return !empty($WarningHandler) && !empty($ReportHandler);
    }

    public function getContentForReportFromWarning(array $warning)
    {
        $contentType = $warning['content_type'];
        $contentId = $warning['content_id'];
        $handler = $this->_getWarningModel()->getWarningHandler($contentType);
        if (empty($handler))
        {
            return false;
        }
        return $handler->getContent($contentId);
    }

    /**
     * Gets the specified Warning Log if it exists.
     *
     * @param string $operationType
     * @param array $warning
     *
     * @return int|false
     */
    public function LogOperation($operationType, $warning, $ImporterMode = false)
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
        $reportModel = $this->_getReportModel();
        $reportUser = XenForo_Visitor::getInstance()->toArray();
        if (!empty(SV_ReportImprovements_Globals::$OverrideReportUserId) || $reportUser['user_id'] == 0 || $reportUser['username'] == '')
        {
            $reportUser = $this->_getUserModel()->getUserById(SV_ReportImprovements_Globals::$OverrideReportUserId);
            if (empty($reportUser))
            {
                return $warningLogId;
            }
        }

        if (!empty(SV_ReportImprovements_Globals::$OverrideReportUsername))
        {
            $reportUser['username'] = SV_ReportImprovements_Globals::$OverrideReportUsername;
        }

        $commentToUpdate = null;

        $report = $reportModel->getReportByContent($warning['content_type'], $warning['content_id']);
        if (empty($report))
        {
            if($options->sv_report_new_warnings)
            {
                // create a report for tracking purposes.
                $content = $this->getContentForReportFromWarning($warning);
                if (!empty($content))
                {
                    $reportId = $reportModel->reportContent($warning['content_type'], $content, '.', $reportUser);
                    if($reportId)
                    {
                        $report = $reportModel->getReportById($reportId);
                        $reportComments = $reportModel->getReportComments($reportId);
                        $commentToUpdate = reset($reportComments);
                        if ($ImporterMode)
                        {
                            $reportDw = XenForo_DataWriter::create('XenForo_DataWriter_Report');
                            $reportDw->setExistingData($report['report_id']);
                            $reportDw->set('first_report_date', $warning['warning_date']);
                            $reportDw->set('last_modified_date', $warning['warning_date']);
                            $reportDw->setImportMode(true);
                            $reportDw->save();
                            $report['first_report_date'] = $report['last_modified_date'] = $warning['warning_date'];
                        }
                    }
                }
            }
        }

        if (empty($report))
        {
            return $warningLogId;
        }

        $newReportState = '';
        $assigned_user_id = 0;
        if(SV_ReportImprovements_Globals::$ResolveReport)
        {
            $newReportState = 'resolved';
        }
        if(SV_ReportImprovements_Globals::$AssignReport)
        {
            $assigned_user_id = $reportUser['user_id'];
        }

        // don't re-open the report when a warning expires naturally.
        if ($operationType != SV_ReportImprovements_Model_WarningLog::Operation_ExpireWarning)
        {
            if ($newReportState == '' && ($report['report_state'] == 'resolved' || $report['report_state'] == 'rejected'))
            {
                // re-open an existing report
                $newReportState = empty($report['assigned_user_id']) ? 'open' : 'assigned';
            }
        }
        // do not change the report state to something it already is
        if ($newReportState != '' && $report['report_state'] == $newReportState)
        {
            $newReportState = '';
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
                'report_id' => $report['report_id'],
                'user_id' => $reportUser['user_id'],
                'username' => $reportUser['username'],
                'is_report' => 0,
            ));
        }
        $commentDw->bulkSet(array(
            'message' => $this->_BuildWarningLogMessage(),
            'state_change' => $newReportState,
            'warning_log_id' => $warningLogId,
        ));
        if ($ImporterMode)
        {
            $commentDw->set('comment_date', $warning['warning_date']);
            $commentDw->setImportMode(true);
        }
        $commentDw->save();

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

    protected function _getWarningModel()
    {
        return $this->getModelFromCache('XenForo_Model_Warning');
    }
}