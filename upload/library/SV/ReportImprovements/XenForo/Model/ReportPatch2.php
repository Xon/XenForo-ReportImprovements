<?php

class SV_ReportImprovements_XenForo_Model_ReportPatch2 extends XFCP_SV_ReportImprovements_XenForo_Model_ReportPatch2
{
    public function reportContent($contentType, array $content, $message, array $viewingUser = null)
    {
        $options = XenForo_Application::getOptions();
        /** @noinspection PhpUndefinedFieldInspection */
        $svLogToReportCentreAndForum = $options->svLogToReportCentreAndForum;
        //$reportIntoForumId = $options->reportIntoForumId;
        if ($svLogToReportCentreAndForum)
        {
            $options->set('reportIntoForumId', $svLogToReportCentreAndForum);
            parent::reportContent($contentType, $content, $message, $viewingUser);
            $options->set('reportIntoForumId', 0);
        }

        return parent::reportContent($contentType, $content, $message, $viewingUser);
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_Model_ReportPatch2 extends XenForo_Model_Report {}
}