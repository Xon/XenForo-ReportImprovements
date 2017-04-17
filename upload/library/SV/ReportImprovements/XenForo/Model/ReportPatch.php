<?php

class SV_ReportImprovements_XenForo_Model_ReportPatch extends XFCP_SV_ReportImprovements_XenForo_Model_ReportPatch
{
    public function getReportHandlers()
    {
        $classes = $this->getContentTypesWithField('report_handler_class');
        $handlers = array();
        foreach ($classes AS $contentType => $class)
        {
            if (!class_exists($class))
            {
                continue;
            }

            $class = XenForo_Application::resolveDynamicClass($class);
            $handlers[$contentType] = new $class();
        }
        return $handlers;
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_Model_ReportPatch extends XenForo_Model_Report {}
}