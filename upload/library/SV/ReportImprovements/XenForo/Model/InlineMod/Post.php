<?php

class SV_ReportImprovements_XenForo_Model_InlineMod_Post extends XFCP_SV_ReportImprovements_XenForo_Model_InlineMod_Post
{
    public function deletePosts(array $postIds, array $options = array(), &$errorKey = '', array $viewingUser = null)
    {
        SV_ReportImprovements_Globals::$deleteContentOptions = $options;
        SV_ReportImprovements_Globals::$deleteContentOptions['resolve'] = SV_ReportImprovements_Globals::$ResolveReport;

        $ret = parent::deletePosts($postIds, $options, $errorKey, $viewingUser);
        SV_ReportImprovements_Globals::$deleteContentOptions = array();
        return $ret;
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_Model_InlineMod_Post extends XenForo_Model_InlineMod_Post {}
}