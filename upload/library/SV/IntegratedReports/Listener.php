<?php

class SV_IntegratedReports_Listener
{
	public static function install($installedAddon, array $addonData, SimpleXMLElement $xml)
	{
    
		$db = XenForo_Application::getDb();

		XenForo_Db::beginTransaction($db);

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

		XenForo_Db::commit($db); 

        XenForo_Application::defer('Permission', array(), 'Permission', true);
	}

	public static function uninstall()
	{
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
    
    public static function load_class($class, array &$extend)
    {
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
        }
    }


}