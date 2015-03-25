<?php

// This class is used to encapsulate global state between layers without using $GLOBAL[] or
// relying on the consumer being loaded correctly by the dynamic class autoloader
class SV_IntegratedReports_Globals
{
    public static $SupressLoggingWarningToReport = false;
    public static $UseSystemUsernameForComments = false;
    public static $SystemUserId = null;
    public static $SystemUsername = null;

    private function__construct() {}
}