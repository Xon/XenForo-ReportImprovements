<?php

class SV_ReportImprovements_Listener
{
    const AddonNameSpace = 'SV_ReportImprovements';

    public static function install($installedAddon, array $addonData, SimpleXMLElement $xml)
    {
        $version = isset($installedAddon['version_id']) ? $installedAddon['version_id'] : 0;

        $db = XenForo_Application::getDb();

        XenForo_Db::beginTransaction($db);

        if ($version == 0)
        {
            $db->query("
                CREATE TABLE IF NOT EXISTS `xf_sv_warning_log` (
                  `warning_log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `warning_edit_date` int(10) unsigned NOT NULL,
                  `operation_type` enum('new','edit','expire','delete') NOT NULL,
                  `warning_id` int(10) unsigned NOT NULL,
                  `content_type` varbinary(25) NOT NULL,
                  `content_id` int(10) unsigned NOT NULL,
                  `content_title` varchar(255) NOT NULL,
                  `user_id` int(10) unsigned NOT NULL,
                  `warning_date` int(10) unsigned NOT NULL,
                  `warning_user_id` int(10) unsigned NOT NULL,
                  `warning_definition_id` int(10) unsigned NOT NULL,
                  `title` varchar(255) NOT NULL,
                  `notes` text NOT NULL,
                  `points` smallint(5) unsigned NOT NULL,
                  `expiry_date` int(10) unsigned NOT NULL,
                  `is_expired` tinyint(3) unsigned NOT NULL,
                  `extra_user_group_ids` varbinary(255) NOT NULL,
                  PRIMARY KEY (`warning_log_id`),
                  KEY (`warning_id`),
                  KEY `content_type_id` (`content_type`,`content_id`),
                  KEY `user_id_date` (`user_id`,`warning_date`),
                  KEY `expiry` (`expiry_date`),
                  KEY `operation_type` (`operation_type`),
                  KEY `warning_edit_date` (`warning_edit_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8
            ");

            SV_ReportImprovements_Install::addColumn('xf_report_comment', 'warning_log_id', 'int unsigned default 0');

            $db->query("insert ignore into xf_permission_entry_content (content_type, content_id, user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                select distinct content_type, content_id, user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportPost', permission_value, permission_value_int
                from xf_permission_entry_content
                where permission_group_id = 'forum' and permission_id in ('warn','editAnyPost','deleteAnyPost')
            ");

            $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportPost', permission_value, permission_value_int
                from xf_permission_entry
                where permission_group_id = 'forum' and permission_id in ('warn','editAnyPost','deleteAnyPost')
            ");
            $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportConversation', permission_value, permission_value_int
                from xf_permission_entry
                where permission_group_id = 'conversation' and permission_id in ('alwaysInvite','editAnyPost','viewAny')
            ");
            $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportProfilePost', permission_value, permission_value_int
                from xf_permission_entry
                where permission_group_id = 'profilePost' and permission_id in ('warn','editAny','deleteAny')
            ");
            $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportUser', permission_value, permission_value_int
                from xf_permission_entry
                where permission_group_id = 'general' and  permission_id in ('warn','editBasicProfile')
            ");
        }

        $db->query("
            INSERT IGNORE INTO xf_content_type
                (content_type, addon_id, fields)
            VALUES
                ('".SV_ReportImprovements_Globals::$Report_ContentType."', '".self::AddonNameSpace."', '')
        ");

        $db->query("
            INSERT IGNORE INTO xf_content_type_field
                (content_type, field_name, field_value)
            VALUES
                ('".SV_ReportImprovements_Globals::$Report_ContentType."', 'alert_handler_class', 'SV_ReportImprovements_AlertHandler_Report')
        ");

        XenForo_Db::commit($db);

        if ($version == 0)
        {
            XenForo_Application::defer('Permission', array(), 'Permission');
            XenForo_Application::defer('SV_ReportImprovements_Deferred_WarningLogMigration', array('warning_id' => -1));
        }

        XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
    }

    public static function uninstall()
    {
        $db = XenForo_Application::getDb();

        XenForo_Db::beginTransaction($db);

        $db->query("
            DELETE FROM xf_content_type_field
            WHERE xf_content_type_field.field_value = 'SV_ReportImprovements_AlertHandler_Report'
        ");

        $db->query("
            DELETE FROM xf_content_type
            WHERE xf_content_type.addon_id = '".self::AddonNameSpace."'
        ");

        $db->query("delete from xf_report_comment where warning_log_id is not null and warning_log_id <> 0");
        SV_ReportImprovements_Install::dropColumn('xf_report_comment', 'warning_log_id');
        $db->query("drop table xf_sv_warning_log");

        $db->delete('xf_permission_entry', "permission_id = 'viewReportConversation'");
        $db->delete('xf_permission_entry', "permission_id = 'viewReportPost'");
        $db->delete('xf_permission_entry', "permission_id = 'viewReportProfilePost'");
        $db->delete('xf_permission_entry', "permission_id = 'viewReportUser'");
        $db->delete('xf_permission_entry_content', "permission_id = 'viewReportConversation'");
        $db->delete('xf_permission_entry_content', "permission_id = 'viewReportPost'");
        $db->delete('xf_permission_entry_content', "permission_id = 'viewReportProfilePost'");
        $db->delete('xf_permission_entry_content', "permission_id = 'viewReportUser'");

        XenForo_Db::commit($db);

        XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
        XenForo_Application::defer('Permission', array(), 'Permission');
    }

    public static function load_class($class, array &$extend)
    {
        switch ($class)
        {
            case 'XenForo_Model_Report':
                if (XenForo_Application::$versionId <= 1040370)
                    $extend[] = self::AddonNameSpace.'_'.$class.'Patch';
                break;

        }
        $extend[] = self::AddonNameSpace.'_'.$class;
    }
}