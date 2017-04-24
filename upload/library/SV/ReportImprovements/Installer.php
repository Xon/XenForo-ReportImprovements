<?php

class SV_ReportImprovements_Installer
{
    const AddonNameSpace = 'SV_ReportImprovements';

    public static function install($installedAddon, array $addonData, SimpleXMLElement $xml)
    {
        $version = isset($installedAddon['version_id']) ? $installedAddon['version_id'] : 0;

        $db = XenForo_Application::getDb();

        if (!$db->fetchRow("show tables like 'xf_sv_warning_log'"))
        {
            $db->query("
                CREATE TABLE IF NOT EXISTS `xf_sv_warning_log` (
                  `warning_log_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `warning_edit_date` INT(10) UNSIGNED NOT NULL,
                  `operation_type` ENUM('new','edit','expire','delete','acknowledge') NOT NULL,
                  `warning_id` INT(10) UNSIGNED NOT NULL,
                  `content_type` VARBINARY(25) NOT NULL,
                  `content_id` INT(10) UNSIGNED NOT NULL,
                  `content_title` VARCHAR(255) NOT NULL,
                  `user_id` INT(10) UNSIGNED NOT NULL,
                  `warning_date` INT(10) UNSIGNED NOT NULL,
                  `warning_user_id` INT(10) UNSIGNED NOT NULL,
                  `warning_definition_id` INT(10) UNSIGNED NOT NULL,
                  `title` VARCHAR(255) NOT NULL,
                  `notes` TEXT NOT NULL,
                  `points` SMALLINT(5) UNSIGNED NOT NULL,
                  `expiry_date` INT(10) UNSIGNED NOT NULL,
                  `is_expired` TINYINT(3) UNSIGNED NOT NULL,
                  `extra_user_group_ids` VARBINARY(255) NOT NULL,
                  `reply_ban_thread_id` INT(10) UNSIGNED DEFAULT 0,
                  PRIMARY KEY (`warning_log_id`),
                  KEY (`warning_id`),
                  KEY `content_type_id` (`content_type`,`content_id`),
                  KEY `user_id_date` (`user_id`,`warning_date`),
                  KEY `expiry` (`expiry_date`),
                  KEY `operation_type` (`operation_type`),
                  KEY `warning_edit_date` (`warning_edit_date`)
                ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
            ");
        }

        SV_Utils_Install::addColumn('xf_sv_warning_log', 'reply_ban_thread_id', 'INT(10) UNSIGNED DEFAULT 0');
        SV_Utils_Install::addColumn('xf_report_comment', 'warning_log_id', 'int unsigned default 0');

        SV_Utils_Install::addColumn('xf_report_comment', 'likes', 'INT UNSIGNED NOT NULL DEFAULT 0');
        SV_Utils_Install::addColumn('xf_report_comment', 'like_users', 'BLOB');
        SV_Utils_Install::addColumn('xf_report_comment', 'alertSent', 'tinyint(3) unsigned NOT NULL DEFAULT 0');
        SV_Utils_Install::addColumn('xf_report_comment', 'alertComment', 'MEDIUMTEXT');

        XenForo_Db::beginTransaction($db);

        if ($version == 0)
        {
            $db->query("INSERT IGNORE INTO xf_permission_entry_content (content_type, content_id, user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                SELECT DISTINCT content_type, content_id, user_group_id, user_id, convert(permission_group_id USING utf8), 'viewReportPost', permission_value, permission_value_int
                FROM xf_permission_entry_content
                WHERE permission_group_id = 'forum' AND permission_id IN ('warn','editAnyPost','deleteAnyPost')
            ");

            $db->query("INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                SELECT DISTINCT user_group_id, user_id, convert(permission_group_id USING utf8), 'viewReportPost', permission_value, permission_value_int
                FROM xf_permission_entry
                WHERE permission_group_id = 'forum' AND permission_id IN ('warn','editAnyPost','deleteAnyPost')
            ");
            $db->query("INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                SELECT DISTINCT user_group_id, user_id, convert(permission_group_id USING utf8), 'viewReportConversation', permission_value, permission_value_int
                FROM xf_permission_entry
                WHERE permission_group_id = 'conversation' AND permission_id IN ('alwaysInvite','editAnyPost','viewAny')
            ");
            $db->query("INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                SELECT DISTINCT user_group_id, user_id, convert(permission_group_id USING utf8), 'viewReportProfilePost', permission_value, permission_value_int
                FROM xf_permission_entry
                WHERE permission_group_id = 'profilePost' AND permission_id IN ('warn','editAny','deleteAny')
            ");
            $db->query("INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                SELECT DISTINCT user_group_id, user_id, convert(permission_group_id USING utf8), 'viewReportUser', permission_value, permission_value_int
                FROM xf_permission_entry
                WHERE permission_group_id = 'general' AND  permission_id IN ('warn','editBasicProfile')
            ");
        }

        if ($version < 1010000)
        {
            if ($version != 0)
            {
                $db->query("
                    DELETE FROM xf_content_type_field
                    WHERE xf_content_type_field.field_value LIKE '" . self::AddonNameSpace . "%'
                ");

                $db->query("
                    DELETE FROM xf_content_type
                    WHERE xf_content_type.addon_id = '" . self::AddonNameSpace . "'
                ");

                XenForo_Application::defer(self::AddonNameSpace . '_Deferred_AlertMigration', array('alert_id' => -1));
            }

            $db->query("INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                SELECT DISTINCT user_group_id, user_id, convert(permission_group_id USING utf8), 'viewReportUser', permission_value, permission_value_int
                FROM xf_permission_entry
                WHERE permission_group_id = 'general' AND  permission_id IN ('reportLike')
            ");
        }

        $db->query("
            INSERT IGNORE INTO xf_content_type
                (content_type, addon_id, fields)
            VALUES
                ('report', '" . self::AddonNameSpace . "', '')
        ");
        $db->query("
            INSERT IGNORE INTO xf_content_type
                (content_type, addon_id, fields)
            VALUES
                ('report_comment', '" . self::AddonNameSpace . "', '')
        ");

        $db->query("
            INSERT IGNORE INTO xf_content_type_field
                (content_type, field_name, field_value)
            VALUES
                ('report', 'search_handler_class', '" . self::AddonNameSpace . "_Search_DataHandler_Report'),
                ('report_comment', 'like_handler_class', '" . self::AddonNameSpace . "_LikeHandler_ReportComment'),
                ('report_comment', 'alert_handler_class', '" . self::AddonNameSpace . "_AlertHandler_ReportComment'),
                ('report_comment', 'search_handler_class', '" . self::AddonNameSpace . "_Search_DataHandler_ReportComment')
        ");

        XenForo_Db::commit($db);

        $requireIndexing = array();

        if ($version < 1010000)
        {
            XenForo_Application::defer('Permission', array(), 'Permission');
        }

        if ($version < 1010001)
        {
            XenForo_Application::defer(self::AddonNameSpace . '_Deferred_AlertMigration', array('alert_id' => -1));
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
            $globalReportPerms = array('assignReport', 'replyReport', 'replyReportClosed', 'updateReport', 'viewReporterUsername', 'viewReports', 'reportLike');
            foreach ($globalReportPerms as $perm)
            {
                $db->query("INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                    SELECT DISTINCT user_group_id, user_id, convert(permission_group_id USING utf8), ?, permission_value, permission_value_int
                    FROM xf_permission_entry
                    WHERE permission_group_id = 'general' AND permission_id IN ('warn','editBasicProfile')
                ", $perm);
            }
        }

        if ($version < 1030400)
        {
            SV_Utils_Install::modifyColumn('xf_sv_warning_log', 'operation_type', false, "enum('new','edit','expire','delete','acknowledge') NOT NULL");
        }

        if (XenForo_Application::$versionId <= 1040370)
        {
            // enable the patch for older versions of XF
            $xmlListeners = XenForo_Helper_DevelopmentXml::fixPhpBug50670($xml->code_event_listeners->listener);
            foreach ($xmlListeners AS $event)
            {
                if ((string)$event['callback_method'] == 'load_class_patch')
                {
                    $event['active'] = 1;
                }
            }
        }

        if ($version <= 1040002)
        {
            $moderatorModel = XenForo_Model::create('XenForo_Model_Moderator');
            $contentModerators = $moderatorModel->getContentModerators();
            foreach ($contentModerators as $contentModerator)
            {
                $permissions = @unserialize($contentModerator['moderator_permissions']);
                if (empty($permissions))
                {
                    continue;
                }
                $changes = false;
                if (!isset($permissions['forum']['viewReportPost']) &&
                    (!empty($permissions['forum']['editAnyPost']) || !empty($permissions['forum']['deleteAnyPost']) || !empty($permissions['forum']['warn']))
                )
                {
                    $permissions['forum']['viewReportPost'] = "1";
                    $changes = true;
                }
                if ($changes)
                {
                    $moderatorModel->insertOrUpdateContentModerator($contentModerator['user_id'], $contentModerator['content_type'], $contentModerator['content_id'], $permissions);
                }
            }
            $moderators = $moderatorModel->getAllGeneralModerators();
            $globalReportPerms = array(
                'assignReport' => array('general' => array('warn', 'editBasicProfile')),
                'replyReport' => array('general' => array('warn', 'editBasicProfile')),
                'replyReportClosed' => array('general' => array('warn', 'editBasicProfile')),
                'updateReport' => array('general' => array('warn', 'editBasicProfile')),
                'viewReporterUsername' => array('general' => array('warn', 'editBasicProfile')),
                'viewReports' => array('general' => array('warn', 'editBasicProfile')),
                'reportLike' => array('general' => array('warn', 'editBasicProfile')),
                'viewReportPost' => array('forum' => array('warn', 'editAnyPost', 'deleteAnyPost')),
                'viewReportConversation' => array('conversation' => array('alwaysInvite', 'editAnyPost', 'viewAny')),
                'viewReportProfilePost' => array('profilePost' => array('warn', 'editAnyPost', 'viewAny')),
                'viewReportUser' => array('general' => array('warn', 'editBasicProfile')),
            );

            foreach ($moderators as $moderator)
            {
                $userPerms = $db->fetchAll('
                    SELECT *
                    FROM xf_permission_entry
                    WHERE user_id = ?
                ', array($moderator['user_id']));
                if (empty($userPerms))
                {
                    continue;
                }

                $userPermsGrouped = array();
                foreach ($userPerms as $userPerm)
                {
                    if ($userPerm['permission_value'] == 'allow')
                    {
                        $userPermsGrouped[$userPerm['permission_group_id']][$userPerm['permission_id']] = "1";
                    }
                }
                $permissions = @unserialize($moderator['moderator_permissions']);
                $changes = false;
                foreach ($globalReportPerms as $perm => $data)
                {
                    $keys = array_keys($data);
                    $category = reset($keys);
                    if (!isset($permissions[$category][$perm]) && !empty($data[$category]))
                    {
                        if (!empty($userPermsGrouped[$category][$perm]))
                        {
                            $permissions[$category][$perm] = "1";
                            $changes = true;
                            continue;
                        }
                        foreach ($data[$category] as $permToTest)
                        {
                            if (!empty($permissions[$category][$permToTest]) ||
                                !empty($userPermsGrouped[$category][$permToTest])
                            )
                            {
                                $permissions[$category][$perm] = "1";
                                $changes = true;
                                break;
                            }
                        }
                    }
                }
                if ($changes)
                {
                    $dw = XenForo_DataWriter::create('XenForo_DataWriter_Moderator');
                    $dw->setExistingData($moderator, true);
                    $dw->setGeneralPermissions($permissions);
                    $dw->save();
                }
            }
        }

        if ($version < 1060600)
        {
            $requireIndexing['report_comment'] = true;
            $requireIndexing['report'] = true;
        }

        // if Elastic Search is installed, determine if we need to push optimized mappings for the search types
        // requires overriding XenES_Model_Elasticsearch
        SV_Utils_Deferred_Search::SchemaUpdates($requireIndexing);

        XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
        XenForo_Application::defer('Permission', array(), 'Permission', true);
        // migration code which gets to run each install
        XenForo_Application::defer(self::AddonNameSpace . '_Deferred_WarningLogMigration', array('warning_id' => -1));
    }

    public static function uninstall()
    {
        $db = XenForo_Application::getDb();

        XenForo_Db::beginTransaction($db);

        $db->query("
            DELETE FROM xf_content_type_field
            WHERE xf_content_type_field.field_value LIKE '" . self::AddonNameSpace . "%'
        ");

        $db->query("
            DELETE FROM xf_content_type
            WHERE xf_content_type.addon_id = '" . self::AddonNameSpace . "'
        ");

        $db->query("DELETE FROM xf_report_comment WHERE warning_log_id IS NOT NULL AND warning_log_id <> 0");


        $db->query("
            DELETE FROM xf_permission_entry
            WHERE permission_id IN (
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
            DELETE FROM xf_permission_entry_content
            WHERE permission_id IN (
                'viewReportPost'
        )");

        XenForo_Db::commit($db);

        SV_Utils_Install::dropColumn('xf_report_comment', 'warning_log_id');
        $db->query("DROP TABLE IF EXISTS xf_sv_warning_log");

        XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
        XenForo_Application::defer('Permission', array(), 'Permission');
    }
}