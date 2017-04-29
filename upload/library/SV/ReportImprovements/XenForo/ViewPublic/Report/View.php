<?php

class SV_ReportImprovements_XenForo_ViewPublic_Report_View extends XFCP_SV_ReportImprovements_XenForo_ViewPublic_Report_View
{
    public function renderHtml()
    {
        $ret = parent::renderHtml();
        $report =& $this->_params['report'];
        if (isset($report['extraContentTemplate']) && 
            isset($this->_params['canSearchReports']) && 
            is_callable(array($report['extraContentTemplate'], 'setParam')))
        {
            $report['extraContentTemplate']->setParam('canSearchReports', $this->_params['canSearchReports']);
        }
        return $ret;
    }
}