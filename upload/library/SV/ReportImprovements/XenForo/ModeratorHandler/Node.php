<?php

class SV_ReportImprovements_XenForo_ModeratorHandler_Node extends XFCP_SV_ReportImprovements_XenForo_ModeratorHandler_Node
{
    public function getModeratorInterfaceGroupIds()
    {
        $ids = parent::getModeratorInterfaceGroupIds();
        $ids[] = "ri_ReportCenter_Forum";
        return $ids;
    }
}