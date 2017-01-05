<?php

class SV_ReportImprovements_XenForo_ReportHandler_ConversationMessage extends XFCP_SV_ReportImprovements_XenForo_ReportHandler_ConversationMessage
{
    public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
    {
        /* @var $conversationModel XenForo_Model_Conversation */
        $conversationModel = XenForo_Model::create('XenForo_Model_Conversation');

        $message = $conversationModel->getConversationMessageById($report['content_id']);
        $messages = $conversationModel->getAndMergeAttachmentsIntoConversationMessages(array($message['message_id'] => $message));
        $message = reset($messages);

        if (!empty($message['attachments']))
        {
            /* @var $conversationModel XenForo_Model_Conversation */
            $reportModel = XenForo_Model::create('XenForo_Model_Report');
            foreach($message['attachments'] as &$attachment)
            {
                $attachment['reportKey'] = $reportModel->getAttachmentReportKey($attachment);
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
}