<?php

class SV_ReportImprovements_Installer
{
    const AddonNameSpace = 'SV_ReportImprovements';
    public static $extraMappings = array(
            'report_comment' => array(
                "properties" => array(
                    "report" => array("type" => "long"),
                    "state_change" => array("type" => "long"),
                    "is_report" => array("type" => "boolean"),
                    "points" => array("type" => "long"),
                    "expiry_date" => array("type" => "long"),
                )
            ),
        );

    public static function install($installedAddon, array $addonData, SimpleXMLElement $xml)
    {
        $version = isset($installedAddon['version_id']) ? $installedAddon['version_id'] : 0;

        $db = XenForo_Application::getDb();

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
            ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
        ");

        SV_Utils_Install::addColumn('xf_report_comment', 'warning_log_id', 'int unsigned default 0');

        SV_Utils_Install::addColumn('xf_report_comment', 'likes', 'INT UNSIGNED NOT NULL DEFAULT 0');
        SV_Utils_Install::addColumn('xf_report_comment', 'like_users', 'BLOB');
        SV_Utils_Install::addColumn('xf_report_comment', 'alertSent', 'tinyint(3) unsigned NOT NULL DEFAULT 0');
        SV_Utils_Install::addColumn('xf_report_comment', 'alertComment', 'MEDIUMTEXT');

        XenForo_Db::beginTransaction($db);

        if ($version == 0)
        {
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

        if ($version < 1010000)
        {
            if ($version != 0)
            {
                $db->query("
                    DELETE FROM xf_content_type_field
                    WHERE xf_content_type_field.field_value like '".self::AddonNameSpace."%'
                ");

                $db->query("
                    DELETE FROM xf_content_type
                    WHERE xf_content_type.addon_id = '".self::AddonNameSpace."'
                ");

                XenForo_Application::defer(self::AddonNameSpace.'_Deferred_AlertMigration', array('alert_id' => -1));
            }

            $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportUser', permission_value, permission_value_int
                from xf_permission_entry
                where permission_group_id = 'general' and  permission_id in ('reportLike')
            ");
        }

        $db->query("
            INSERT IGNORE INTO xf_content_type
                (content_type, addon_id, fields)
            VALUES
                ('report_comment', '".self::AddonNameSpace."', '')
        ");

        $db->query("
            INSERT IGNORE INTO xf_content_type_field
                (content_type, field_name, field_value)
            VALUES
                ('report_comment', 'like_handler_class', '".self::AddonNameSpace."_LikeHandler_ReportComment'),
                ('report_comment', 'alert_handler_class', '".self::AddonNameSpace."_AlertHandler_ReportComment'),
                ('report_comment', 'search_handler_class', '".self::AddonNameSpace."_Search_DataHandler_ReportComment')
        ");

        XenForo_Db::commit($db);

        $requireIndexing = array();

        if ($version < 1010000)
        {
            XenForo_Application::defer('Permission', array(), 'Permission');
        }

        if ($version < 1010001)
        {
            XenForo_Application::defer(self::AddonNameSpace.'_Deferred_AlertMigration', array('alert_id' => -1));
        }

        if ($version < 1010006)
        {
            $requireIndexing['report_comment'] = true;
            if ($version >= 1010000)
            {
                // need to delete mapping to permit re-indexing to work correctly
                $indexName = XenES_Api::getInstance()->getIndex();
                XenES_Api::deleteMapping($indexName, 'report_comment');
            }
        }

        if ($version < 1020200)
        {
            $globalReportPerms = array('assignReport','replyReport','replyReportClosed','updateReport','viewReporterUsername','viewReports');
            foreach($globalReportPerms as $perm)
            {
                $db->query("insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                    select distinct user_group_id, user_id, convert(permission_group_id using utf8), ?, permission_value, permission_value_int
                    from xf_permission_entry
                    where permission_group_id = 'general' and permission_id in ('warn','editBasicProfile')
                ", $perm);
            }
        }

        // if Elastic Search is installed, determine if we need to push optimized mappings for the search types
        SV_Utils_Install::updateXenEsMapping($requireIndexing, self::$extraMappings);

        XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
        XenForo_Application::defer('Permission', array(), 'Permission', true);
        // migration code which gets to run each install
        XenForo_Application::defer(self::AddonNameSpace.'_Deferred_WarningLogMigration', array('warning_id' => -1));
    }

    public static function uninstall()
    {
        $db = XenForo_Application::getDb();

        XenForo_Db::beginTransaction($db);

        $db->query("
            DELETE FROM xf_content_type_field
            WHERE xf_content_type_field.field_value like '".self::AddonNameSpace."%'
        ");

        $db->query("
            DELETE FROM xf_content_type
            WHERE xf_content_type.addon_id = '".self::AddonNameSpace."'
        ");

        $db->query("delete from xf_report_comment where warning_log_id is not null and warning_log_id <> 0");


        $db->query("
            delete from xf_permission_entry
            where permission_id in (
                'viewReportConversation',
                'viewReportPost',
                'assignReport',
                'replyReport',
                'replyReportClosed',
                'reportLike',
                'updateReport',
                'viewReportUser',
                'viewReporterUsername',
                'viewReports',
                'viewReportProfilePost'
        )");

        $db->query("
            delete from xf_permission_entry_content
            where permission_id in (
                'viewReportPost'
        )");

        XenForo_Db::commit($db);

        SV_Utils_Install::dropColumn('xf_report_comment', 'warning_log_id');
        $db->query("drop table if exists xf_sv_warning_log");

        XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
        XenForo_Application::defer('Permission', array(), 'Permission');
    }
}