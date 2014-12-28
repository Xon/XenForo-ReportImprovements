<?php

class SV_IntegratedReports_XenForo_DataWriter_ReportComment extends XFCP_SV_IntegratedReports_XenForo_DataWriter_ReportComment
{       
	protected function _getFields()
	{
        $fields = parent::_getFields();
        $fields['xf_report_comment']['warning_log_id'] = array('type' => self::TYPE_UINT,    'default' => 0);
        return $fields;
	}
    
	protected function _preSave()
	{
		if (!$this->get('state_change') && !$this->get('message'))
		{
            if ($this->get('warning_log_id'))
            {
                return;
            }
		}
        return parent::_preSave();
	}    
}