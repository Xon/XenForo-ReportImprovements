<?php

class SV_ReportImprovements_XenForo_AttachmentHandler_Post extends XFCP_SV_ReportImprovements_XenForo_AttachmentHandler_Post
{
    protected function _canViewAttachment(array $attachment, array $viewingUser)
    {
        $canView = parent::_canViewAttachment($attachment, $viewingUser);

        if (!$canView && SV_ReportImprovements_Globals::$attachmentReportKey)
        {
            $reportModel = XenForo_Model::create('XenForo_Model_Report');

            $key = SV_ReportImprovements_Globals::$attachmentReportKey;
            SV_ReportImprovements_Globals::$attachmentReportKey = null;

            if ($key === $reportModel->getAttachmentReportKey($attachment, $viewingUser))
            {
                return true;
            }
        }

        return $canView;
    }
}