<?php

class SV_ReportImprovements_XenForo_ReportHandler_Post extends XFCP_SV_ReportImprovements_XenForo_ReportHandler_Post
{
    public function getVisibleReportsForUser(array $reports, array $viewingUser)
    {
        $reportsByForum = array();
        foreach ($reports AS $reportId => $report)
        {
            $info = unserialize($report['content_info']);
            $reportsByForum[$info['node_id']][] = $reportId;
        }

        $forumModel = $this->_getForumModel();
        $forums = $forumModel->getForumsByIds(array_keys($reportsByForum), array(
            'permissionCombinationId' => $viewingUser['permission_combination_id']
        ));
        $forums = $forumModel->unserializePermissionsInList($forums, 'node_permission_cache');

        foreach ($reportsByForum AS $forumId => $forumReports)
        {
            if (!isset($forums[$forumId]) ||
                !$forumModel->canManageReportedMessage($forums[$forumId], $errorPhraseKey, $viewingUser))
            {
                foreach ($forumReports AS $reportId)
                {
                    unset($reports[$reportId]);
                }
            }
        }

        return $reports;
    }

    public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
    {
        /* @var $conversationModel XenForo_Model_Post */
        $postModel = XenForo_Model::create('XenForo_Model_Post');

        $message = $postModel->getPostById($report['content_id']);
        $posts = $postModel->getAndMergeAttachmentsIntoPosts(array($message['post_id'] => $message));
        $message = reset($posts);

        if (!empty($message['attachments']))
        {
            /* @var $conversationModel XenForo_Model_Conversation */
            $reportModel = XenForo_Model::create('XenForo_Model_Report');
            $reportKey = $reportModel->getAttachmentReportKey();
            foreach($message['attachments'] as &$attachment)
            {
                $attachment['reportKey'] = $reportKey;
            }
            $contentInfo['attachments'] = $message['attachments'];
            $contentInfo['attachments_count'] = count($message['attachments']);
        }

        $template = parent::viewCallback($view, $report, $contentInfo);

        if (!empty($message['attachments']))
        {
            $class = XenForo_Application::resolveDynamicClass('SV_ReportImprovements_AttachmentParser');
            $template->setParam('bbCodeParser', new $class($template->getParam('bbCodeParser'), $report, $contentInfo));
            // trim excess attachments
            $content = $template->getParam('content');
            if (!empty($content['attachments']))
            {
                if (stripos($content['message'], '[/attach]') !== false)
                {
                    if (preg_match_all('#\[attach(=[^\]]*)?\](?P<id>\d+)(\D.*)?\[/attach\]#iU', $content['message'], $matches))
                    {
                        foreach ($matches['id'] AS $attachId)
                        {
                            unset($content['attachments'][$attachId]);
                        }
                    }
                }
            }
            $template->setParam('content', $content);
        }

        return $template;
    }

    var $_forumModel = null;
    protected function _getForumModel()
    {
        if (empty($this->_forumModel))
        {
            $this->_forumModel = XenForo_Model::create('XenForo_Model_Forum');
        }

        return $this->_forumModel;
    }
}