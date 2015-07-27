<?php

// This class is used to encapsulate global state between layers without using $GLOBAL[] or
// relying on the consumer being loaded correctly by the dynamic class autoloader
class SV_ReportImprovements_Globals
{
    public static $Report_ContentType = 'report';
    public static $Report_Comment = 'comment';
    public static $Report_Tag = 'tag';
    public static $Report_MaxAlertCount = 0;

    public static $SupressLoggingWarningToReport = false;
    public static $OverrideReportUserId = null;
    public static $OverrideReportUsername = null;
    public static $ResolveReport = false;
    public static $AssignReport = false;

    // workaround for a bug in Waindigo_EmailReport_Extend_XenForo_Model_Report::reportContent
    public static $reportId = false;

    private function __construct() {}
}
