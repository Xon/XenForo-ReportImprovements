<?php

// This class is used to encapsulate global state between layers without using $GLOBAL[] or
// relying on the consumer being loaded correctly by the dynamic class autoloader
class SV_ReportImprovements_Globals
{
    public static $Report_MaxAlertCount = 0;

    public static $SupressLoggingWarningToReport = false;
    public static $OverrideReportUserId = null;
    public static $OverrideReportUsername = null;
    public static $ResolveReport = false;
    public static $AssignReport = false;
    public static $UserReportAlertComment = null;
    public static $RequireWarningLogTitle = true;

    public static $attachmentReportKey = null;

    private function __construct() {}
}
