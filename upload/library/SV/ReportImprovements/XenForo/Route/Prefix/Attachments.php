<?php

class SV_ReportImprovements_XenForo_Route_Prefix_Attachments extends XFCP_SV_ReportImprovements_XenForo_Route_Prefix_Attachments
{
    public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
    {
        if (isset($data['reportKey']) && $action == '' && !isset($extraParams['k']))
        {
            $extraParams['k'] = $data['reportKey'];
        }

        return parent::buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, $extraParams);
    }
}