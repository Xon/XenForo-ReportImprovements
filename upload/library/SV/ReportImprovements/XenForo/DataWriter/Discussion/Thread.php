<?php

class SV_ReportImprovements_XenForo_DataWriter_Discussion_Thread extends XFCP_SV_ReportImprovements_XenForo_DataWriter_Discussion_Thread
{
    protected function _deleteDiscussionMessages()
    {
        $this->logContentDeleteToReport();
        // this actually deletes posts without calling the post data writer
        parent::_deleteDiscussionMessages();
    }

    protected function _discussionPostSave()
    {
        parent::_discussionPostSave();
        if ($this->isChanged('discussion_state') && $this->get('discussion_state') == 'deleted')
        {
            $this->logContentDeleteToReport();
        }
    }

    protected function logContentDeleteToReport()
    {
        $deleteContentOptions = SV_ReportImprovements_Globals::$deleteContentOptions;
        $reportModel = $this->_getReportModel();
        $posts = $this->_db->fetchAll("
            select xf_post.post_id, message_state
            from xf_post
            join xf_report on xf_report.content_type = 'post' and content_id = xf_post.post_id
            where xf_post.thread_id = ?
        ", $this->get('thread_id'));

        foreach ($posts as $post)
        {
            $reportModel->logContentDeleteToReport('post', $post['post_id'], $deleteContentOptions, $post['message_state'] == 'visible');
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
    class XFCP_SV_ReportImprovements_XenForo_DataWriter_DiscussionMessage_Thread extends XenForo_XenForo_DataWriter_DiscussionMessage_Thread {}
}