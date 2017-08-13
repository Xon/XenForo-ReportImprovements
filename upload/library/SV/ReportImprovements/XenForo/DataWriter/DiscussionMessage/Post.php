<?php

class SV_ReportImprovements_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_SV_ReportImprovements_XenForo_DataWriter_DiscussionMessage_Post
{
    protected function _messagePostDelete()
    {
        parent::_messagePostDelete();
        if (SV_ReportImprovements_Globals::$deletePostOptions !== null)
        {
            $this->_getReportModel()->resolveReportForContent('post', $this->get('post_id'), array($this, 'resolveReportForContent'));
        }
    }

    protected function _messagePostSave()
    {
        parent::_messagePostSave();
        if (SV_ReportImprovements_Globals::$deletePostOptions !== null && $this->isChanged('message_state') && $this->get('message_state') == 'deleted')
        {
            $this->_getReportModel()->resolveReportForContent('post', $this->get('post_id'), array($this, 'resolveReportForContent'));
        }
    }

    public function resolveReportForContent(XenForo_DataWriter_Report $reportDw, XenForo_DataWriter_ReportComment $commentDw, array $viewingUser)
    {
        $options = SV_ReportImprovements_Globals::$deletePostOptions;
        $commentDw->set('message', $options['reason']);
        $commentDw->set('alertSent', ($this->getExisting('message_state') == 'visible') && $options['authorAlert']);
        $commentDw->set('alertComment', $options['authorAlertReason']);
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        return $this->_controller->getModelFromCache('XenForo_Model_Report');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_DataWriter_DiscussionMessage_Post extends XenForo_XenForo_DataWriter_DiscussionMessage_Post {}
}