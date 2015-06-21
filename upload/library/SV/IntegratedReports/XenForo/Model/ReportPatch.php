<?php

class SV_IntegratedReports_XenForo_Model_ReportPatch extends XFCP_SV_IntegratedReports_XenForo_Model_ReportPatch
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