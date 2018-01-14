<?php

class SV_ReportImprovements_XenForo_ViewPublic_Report_View extends XFCP_SV_ReportImprovements_XenForo_ViewPublic_Report_View
{
    public function renderHtml()
    {
        $bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));

        XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($this->_params['comments'], $bbCodeParser, []);

        $ret = parent::renderHtml();
        $report =& $this->_params['report'];
        if (isset($report['extraContentTemplate']) &&
            isset($this->_params['canSearchReports']) &&
            is_callable(array($report['extraContentTemplate'], 'setParam')))
        {
            /** @var XenForo_Template_Abstract $extraContentTemplate */
            $extraContentTemplate = $report['extraContentTemplate'];
            $extraContentTemplate->setParam('canSearchReports', $this->_params['canSearchReports']);
        }
        return $ret;
    }
}

if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ViewPublic_Report_View extends XenForo_ViewPublic_Report_View {}
}
