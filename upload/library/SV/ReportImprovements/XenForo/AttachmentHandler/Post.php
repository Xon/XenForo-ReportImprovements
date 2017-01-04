<?php

class SV_ReportImprovements_XenForo_AttachmentHandler_Post extends XFCP_SV_ReportImprovements_XenForo_AttachmentHandler_Post
{
    protected function _canViewAttachment(array $attachment, array $viewingUser)
    {
        return parent::_canViewAttachment($attachment, $viewingUser);
    }
}