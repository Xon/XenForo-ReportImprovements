<?php

class SV_IntegratedReports_Listener
{
	public static function install($installedAddon, array $addonData, SimpleXMLElement $xml)
	{
/*
$contentTypeInstaller = LiamW_Shared_DatabaseSchema_Abstract2::create('LiamW_AlterEgoDetector_DatabaseSchema_ContentType');
$contentTypeInstaller->install($versionId);
$contentTypeFieldInstaller = LiamW_Shared_DatabaseSchema_Abstract2::create('LiamW_AlterEgoDetector_DatabaseSchema_ContentTypeField');
$contentTypeFieldInstaller->install($versionId);
*/
/*
        // update cache
		$contentTypes = XenForo_Application::get('contentTypes');
		//$contentTypes['alterego']['report_handler_class'] = 'LiamW_AlterEgoDetector_ReportHandler_AlterEgo';
		XenForo_Application::set('contentTypes', $contentTypes);        
*/

/*
('conversation_message', 'report_handler_class', 'XenForo_ReportHandler_ConversationMessage'),
('post', 'report_handler_class', 'XenForo_ReportHandler_Post'),
('user', 'report_handler_class', 'XenForo_ReportHandler_User'),
('profile_post', 'report_handler_class', 'XenForo_ReportHandler_ProfilePost'),
*/  

		if (XenForo_Application::$versionId <= 1040370)
		{
            // enable the XenForo_Model_Report hook
            $codeEventModel = XenForo_Model::create('XenForo_Model_CodeEvent');
            $listeners = $codeEventModel->getAllEventListeners();
            foreach ($listeners as $listener) 
            {
                if ($listener['addon_id'] == $installedAddon['addon_id'] && $listener['event_id'] == 'load_class' && $listener['hint'] == 'XenForo_Model_Report') 
                {

                    break;
                }
            }
		}
	}

	public static function uninstall()
	{
/*    
		$contentTypeInstaller = LiamW_Shared_DatabaseSchema_Abstract2::create('LiamW_AlterEgoDetector_DatabaseSchema_ContentType');
		$contentTypeInstaller->uninstall();
		$contentTypeFieldInstaller = LiamW_Shared_DatabaseSchema_Abstract2::create('LiamW_AlterEgoDetector_DatabaseSchema_ContentTypeField');
		$contentTypeFieldInstaller->uninstall();
*/
/*        
        // update cache
		$contentTypes = XenForo_Application::get('contentTypes');
		unset($contentTypes['alterego']);
		XenForo_Application::set('contentTypes', $contentTypes); 
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
                $extend[] = 'SV_IntegratedReports_XenForo_Model_Report';
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