<?php

class SV_ReportImprovements_LikeHandler_ReportComment extends XenForo_LikeHandler_Abstract
{
    protected $_reportModel = null;

    public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
    {
        $dw = XenForo_DataWriter::create('XenForo_DataWriter_ReportComment');
        if ($dw->setExistingData($contentId))
        {
            $dw->set('likes', $dw->get('likes') + $adjustAmount);
            $dw->set('like_users', $latestLikes);
            $dw->save();
        }
    }

    public function getContentData(array $contentIds, array $viewingUser)
    {
        return $this->_getReportModel()->getReportCommentsByIdsForUser($contentIds, $viewingUser);
    }

    public function batchUpdateContentUser($oldUserId, $newUserId, $oldUsername, $newUsername)
    {
        $this->_getReportModel()->batchUpdateLikeUser($oldUserId, $newUserId, $oldUsername, $newUsername);
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        if (empty($this->_reportModel))
        {
            $this->_reportModel = XenForo_Model::create('XenForo_Model_Report');
        }

        return $this->_reportModel;
    }

    public function getListTemplateName()
    {
        return 'news_feed_item_report_comment_like';
    }
}
