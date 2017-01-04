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

        $post = $postModel->getPostById($report['content_id']);
        $posts = $postModel->getAndMergeAttachmentsIntoPosts(array($post['post_id'] => $post));
        $post = reset($posts);

        if (!empty($post['attachments']))
        {
            $contentInfo['attachments'] = $post['attachments'];
            $contentInfo['attachments_count'] = count($post['attachments']);
        }

        $template = parent::viewCallback($view, $report, $contentInfo);

        if (!empty($post['attachments']))
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