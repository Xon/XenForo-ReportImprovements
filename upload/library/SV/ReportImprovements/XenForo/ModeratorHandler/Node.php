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

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ModeratorHandler_Node extends XenForo_ModeratorHandler_Node {}
}