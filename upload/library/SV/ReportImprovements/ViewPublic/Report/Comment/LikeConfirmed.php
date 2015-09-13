<?php

class SV_ReportImprovements_ViewPublic_Report_Comment_LikeConfirmed extends XenForo_ViewPublic_Base
{
    public function renderJson()
    {
        $report = $this->_params['report'];
        $comment = $this->_params['comment'];

        if (!empty($comment['likes']))
        {
            $params = array(
                'message' => $comment,
                'likesUrl' => XenForo_Link::buildPublicLink('reports/likes', $report, array('report_comment_id' => $comment['report_comment_id']))
            );

            $output = $this->_renderer->getDefaultOutputArray(get_class($this), $params, 'likes_summary');
        }
        else
        {
            $output = array('templateHtml' => '', 'js' => '', 'css' => '');
        }

        $output += XenForo_ViewPublic_Helper_Like::getLikeViewParams($this->_params['liked']);

        return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
    }
}