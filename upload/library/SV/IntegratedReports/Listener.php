<?php

class SV_IntegratedReports_Listener
{
	public static function install($installedAddon, array $addonData, SimpleXMLElement $xml)
	{
    
		$db = XenForo_Application::getDb();
     

// migration code.     
/*        
        XenForo_Db::beginTransaction($db);
        $db->query("alter table xf_report_comment add column warning_log_id int unsigned default 0");
        
        $db->query("
CREATE TABLE `xf_sv_warning_log` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
     
        $db->query("     
insert into xf_sv_warning_log (warning_edit_date,operation_type,warning_id,content_type,content_id,content_title,user_id,warning_date,warning_user_id,warning_definition_id,title,notes,points,expiry_date,is_expired,extra_user_group_ids)
SELECT xf_warning.warning_date,'new',xf_warning.*
FROM xf_warning");

        $db->query("
insert into xf_report_comment (report_id,comment_date,user_id,username,message,state_change,is_report,warning_log_id)
select (select report_id from xf_report where xf_report.content_type = xf_sv_warning_log.content_type and xf_report.content_id = xf_sv_warning_log.content_id) as report_id,
    xf_sv_warning_log. warning_date,xf_user.user_id, xf_user.username,'','',0,warning_log_id
from xf_sv_warning_log
join xf_user on xf_user.user_id = xf_sv_warning_log.warning_user_id
where exists(select * from xf_report where xf_report.content_type = xf_sv_warning_log.content_type and xf_report.content_id = xf_sv_warning_log.content_id)
");
*/

/*
		

		$db->query("insert into xf_permission_entry_content (content_type, content_id, user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int) 
select distinct content_type, content_id, user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportPost', permission_value, permission_value_int 
from xf_permission_entry_content 
where permission_group_id = 'forum' and permission_id in ('warn','editAnyPost','deleteAnyPost')
");

		$db->query("insert into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int) 
select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportPost', permission_value, permission_value_int 
from xf_permission_entry
where permission_group_id = 'forum' and permission_id in ('warn','editAnyPost','deleteAnyPost')
");
        $db->query("insert into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportConversation', permission_value, permission_value_int 
from xf_permission_entry
where permission_group_id = 'conversation' and permission_id in ('alwaysInvite','editAnyPost','viewAny')        
");
        $db->query("insert into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportProfilePost', permission_value, permission_value_int 
from xf_permission_entry
where permission_group_id = 'profilePost' and permission_id in ('warn','editAny','deleteAny')        
");
        $db->query("insert into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'viewReportUser', permission_value, permission_value_int 
from xf_permission_entry
where permission_group_id = 'general' and  permission_id in ('warn','editBasicProfile')        
");
        */
        
        XenForo_Db::commit($db); 
        
        //XenForo_Application::defer('Permission', array(), 'Permission', true);
	}

	public static function uninstall()
	{
        $db->query("alter table xf_report_comment drop column warning_log_id;");
        $db->query("drop table xf_sv_warning_log;");
        /*
		$db = XenForo_Application::getDb();

		XenForo_Db::beginTransaction($db);

		$db->delete('xf_permission_entry', "permission_id = 'viewReportConversation'");
		$db->delete('xf_permission_entry', "permission_id = 'viewReportPost'");
		$db->delete('xf_permission_entry', "permission_id = 'viewReportProfilePost'");
		$db->delete('xf_permission_entry', "permission_id = 'viewReportUser'");

		$db->delete('xf_permission_entry_content', "permission_id = 'viewReportConversation'");
        $db->delete('xf_permission_entry_content', "permission_id = 'viewReportPost'");		
		$db->delete('xf_permission_entry_content', "permission_id = 'viewReportProfilePost'");
		$db->delete('xf_permission_entry_content', "permission_id = 'viewReportUser'");

		XenForo_Db::commit($db);
        
        XenForo_Application::defer('Permission', array(), 'Permission', true);
        */
	}
    
    static $init = false;
    
    public static function load_class($class, array &$extend)
    {
        if (!self::$init)
        {
            self::$init = true;
            
        }

        switch ($class)
        {
            case 'XenForo_Model_Conversation':
                $extend[] = 'SV_IntegratedReports_XenForo_Model_Conversation';
                break;
            case 'XenForo_Model_Forum':
                $extend[] = 'SV_IntegratedReports_XenForo_Model_Forum';
                break;
            case 'XenForo_Model_ProfilePost':
                $extend[] = 'SV_IntegratedReports_XenForo_Model_ProfilePost';
                break;                
            case 'XenForo_Model_User':
                $extend[] = 'SV_IntegratedReports_XenForo_Model_User';
                break;
            case 'XenForo_Model_Report':
                if (XenForo_Application::$versionId <= 1040370)
                    $extend[] = 'SV_IntegratedReports_XenForo_Model_ReportPatch';
                $extend[] = 'SV_IntegratedReports_XenForo_Model_Report';
                break;
            case 'XenForo_Model_Warning':
                $extend[] = 'SV_IntegratedReports_XenForo_Model_Warning';
                break;
            case 'XenForo_ReportHandler_ProfilePost':
                $extend[] = 'SV_IntegratedReports_XenForo_ReportHandler_ProfilePost';
                break;
            case 'XenForo_ReportHandler_Post':
                $extend[] = 'SV_IntegratedReports_XenForo_ReportHandler_Post';
                break;
            case 'XenForo_ReportHandler_User':
                $extend[] = 'SV_IntegratedReports_XenForo_ReportHandler_User';
                break;
            case 'XenForo_DataWriter_Warning':
                $extend[] = 'SV_IntegratedReports_XenForo_DataWriter_Warning';
                break;
            case 'XenForo_DataWriter_ReportComment':
                $extend[] = 'SV_IntegratedReports_XenForo_DataWriter_ReportComment';
                break;

        }
    }


}