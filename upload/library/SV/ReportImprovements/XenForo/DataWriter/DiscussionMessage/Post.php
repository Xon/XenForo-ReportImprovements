<?php

class SV_ReportImprovements_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_SV_ReportImprovements_XenForo_DataWriter_DiscussionMessage_Post
{
    protected function _messagePostDelete()
    {
        parent::_messagePostDelete();
        if (SV_ReportImprovements_Globals::$deletePostOptions !== null)
        {
            $options = SV_ReportImprovements_Globals::$deletePostOptions;
            $resolve = $options['resolve'];
            $reportModel = $this->_getReportModel();
            $report = $reportModel->getReportForContent('post', $this->get('post_id'));
            if ($report)
            {
                if ($resolve && !$reportModel->canUpdateReport($report))
                {
                    $resolve = false;
                }
                $reportModel->logReportForContent($report, $resolve, array($this, 'resolveReportForContent'));
            }
        }
    }

    protected function _messagePostSave()
    {
        parent::_messagePostSave();
        if (SV_ReportImprovements_Globals::$deletePostOptions !== null && $this->isChanged('message_state') && $this->get('message_state') == 'deleted')
        {
            $options = SV_ReportImprovements_Globals::$deletePostOptions;
            $resolve = $options['resolve'];
            $reportModel = $this->_getReportModel();
            $report = $reportModel->getReportForContent('post', $this->get('post_id'));
            if ($report)
            {
                if ($resolve && !$reportModel->canUpdateReport($report))
                {
                    $resolve = false;
                }
                $reportModel->logReportForContent($report, $resolve, array($this, 'resolveReportForContent'));
            }
        }
    }

    public function resolveReportForContent(XenForo_DataWriter_Report $reportDw, XenForo_DataWriter_ReportComment $commentDw, array $viewingUser)
    {
        $options = SV_ReportImprovements_Globals::$deletePostOptions;
        if ($options['reason'])
        {
            $commentDw->set('message', (string)new XenForo_Phrase('sv_report_delete_post', array('reason' => $options['reason'])));
        }
        $commentDw->set('alertSent', ($this->getExisting('message_state') == 'visible') && $options['authorAlert']);
        if ($options['authorAlertReason'])
        {
            $commentDw->set('alertComment', $options['authorAlertReason']);
        }

        return ($commentDw->get('message') || $commentDw->get('alertComment'));
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        return $this->getModelFromCache('XenForo_Model_Report');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_DataWriter_DiscussionMessage_Post extends XenForo_XenForo_DataWriter_DiscussionMessage_Post {}
}