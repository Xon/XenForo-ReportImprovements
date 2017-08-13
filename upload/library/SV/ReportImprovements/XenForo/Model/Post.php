<?php

class SV_ReportImprovements_XenForo_Model_Post extends XFCP_SV_ReportImprovements_XenForo_Model_Post
{
    public function deletePost($postId, $deleteType, array $options = array(), array $forum = null)
    {
        SV_ReportImprovements_Globals::$deletePostOptions = $options;
        SV_ReportImprovements_Globals::$deletePostOptions['resolve'] = SV_ReportImprovements_Globals::$ResolveReport;
        $ret = parent::deletePost($postId, $deleteType, $options, $forum);
        SV_ReportImprovements_Globals::$deletePostOptions =  null;
        return $ret;
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_Model_Post extends XenForo_Model_Post {}
}