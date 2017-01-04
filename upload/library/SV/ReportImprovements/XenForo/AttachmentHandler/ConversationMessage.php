<?php

class SV_ReportImprovements_XenForo_AttachmentHandler_ConversationMessage extends XFCP_SV_ReportImprovements_XenForo_AttachmentHandler_ConversationMessage
{
    protected function _canViewAttachment(array $attachment, array $viewingUser)
    {
        return parent::_canViewAttachment($attachment, $viewingUser);
    }
}