<?php

// This class is used to encapsulate global state between layers without using $GLOBAL[] or
// relying on the consumer being loaded correctly by the dynamic class autoloader
class SV_IntegratedReports_Globals
{
    public static $Report_ContentType = 'report';
    public static $Report_Comment = 'comment';
    public static $Report_Tag = 'tag';
    public static $Report_MaxAlertCount = 0;

    public static $SupressLoggingWarningToReport = false;
    public static $UseSystemUsernameForComments = false;
    public static $SystemUserId = null;
    public static $SystemUsername = null;
    public static $resolve_report = false;

    private function __construct() {}
}
