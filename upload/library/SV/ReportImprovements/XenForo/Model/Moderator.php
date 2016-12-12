<?php

class SV_ReportImprovements_XenForo_Model_Moderator extends XFCP_SV_ReportImprovements_XenForo_Model_Moderator
{
    public function getGeneralModeratorInterfaceGroupIds()
    {
        $ids = parent::getGeneralModeratorInterfaceGroupIds();
        $ids[] = 'ri_ReportCenter';
        return $ids;
    }
}