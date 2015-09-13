<?php

class SV_ReportImprovements_LikeHandler_ReportComment extends XenForo_LikeHandler_Abstract
{
    var $_reportModel = null;
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
        if (empty($viewingUser['is_moderator']))
        {
            return array();
        }

        $comments = $this->_getReportModel()->getReportCommentsByIds($contentIds);
        $reportIds = array_unique(XenForo_Application::arrayColumn($comments, 'report_id'));
        $reports = $this->_getReportModel()->getReportsByIds($reportIds);

        foreach($reports as $reportId => &$report)
        {
            $handler = $this->_getReportHandler($report['content_type']);
            $visibleReport = $handler->getVisibleReportsForUser(array($report), $viewingUser);
            if (empty($visibleReport))
            {
                unset($reports[$reportId]);
                continue;
            }
        }

        foreach($comments as $commentId => &$comment)
        {
            $reportId = $comment['report_id'];
            if (!isset($reports[$reportId]))
            {
                unset($comments[$commentId]);
                continue;
            }

            $comment['report'] = $reports[$reportId];
        }

        return $comments;
    }

    public function batchUpdateContentUser($oldUserId, $newUserId, $oldUsername, $newUsername)
    {
        $this->_getReportModel()->batchUpdateLikeUser($oldUserId, $newUserId, $oldUsername, $newUsername);
    }

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
