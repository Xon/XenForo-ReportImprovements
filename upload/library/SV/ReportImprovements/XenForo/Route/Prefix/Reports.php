<?php

class SV_ReportImprovements_XenForo_Route_Prefix_Reports extends XFCP_SV_ReportImprovements_XenForo_Route_Prefix_Reports
{
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        $hash = '';
        if (isset($extraParams['report_comment_id']) && $action == '')
        {
            $hash = "#reportComment-" . $extraParams['report_comment_id'];
            unset($extraParams['report_comment_id']);
        }

        return parent::buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, $extraParams) . $hash;
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_Route_Prefix_Reports extends XenForo_Route_Prefix_Reports {}
}