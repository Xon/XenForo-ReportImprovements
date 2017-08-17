<?php

class SV_ReportImprovements_XenForo_DataWriter_DiscussionMessage_Post extends XFCP_SV_ReportImprovements_XenForo_DataWriter_DiscussionMessage_Post
{
    protected function _messagePostDelete()
    {
        parent::_messagePostDelete();
        $this->_getReportModel()->logContentDeleteToReport('post', $this->get('post_id'), SV_ReportImprovements_Globals::$deleteContentOptions, $this->getExisting('message_state') == 'visible');
    }

    protected function _messagePostSave()
    {
        parent::_messagePostSave();
        if ($this->isChanged('message_state') && $this->get('message_state') == 'deleted')
        {
            $this->_getReportModel()->logContentDeleteToReport('post', $this->get('post_id'), SV_ReportImprovements_Globals::$deleteContentOptions, $this->getExisting('message_state') == 'visible');
        }
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