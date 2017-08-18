<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Member extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Member
{
    public function actionWarn()
    {
        $reportHelper = $this->_getReportHelper();
        $reportHelper->setupOnPost();

        if ($this->_request->isPost())
        {
            $input = $this->_input->filter(array(
                'ban_length' => XenForo_Input::STRING,
                'ban_length_value' => XenForo_Input::UINT,
                'ban_length_unit' => XenForo_Input::STRING,

                'send_alert_reply_ban' => XenForo_Input::UINT,
                'reason_reply_ban' => XenForo_Input::STRING,
            ));
            if (!empty($input['ban_length']) && $input['ban_length'] !== 'none')
            {
                SV_ReportImprovements_Globals::$replyBanOptions = $input;
            }
        }

        $response = parent::actionWarn();

        $reportHelper->injectReportInfo($response, 'member_warn');

        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['contentType'])  && !empty($response->params['user']))
        {
            /* var SV_ReportImprovements_XenForo_Model_Post $postModel */
            $postModel = $this->getModelFromCache('XenForo_Model_Post');
            /* var SV_ReportImprovements_XenForo_Model_Thread $threadModel */
            $threadModel = $this->getModelFromCache('XenForo_Model_Thread');

            $post = null;
            $user = $response->params['user'];
            if ($response->params['contentType'] == 'post')
            {
                $postId = $response->params['contentId'];
                $post = $postModel->getPostById($postId, array(
                    'join' => XenForo_Model_Post::FETCH_THREAD | XenForo_Model_Post::FETCH_FORUM,
                    'skip_wordcount' => true,
                ));
            }

            $response->params['canReplyBan'] = $post ? $threadModel->canReplyBanUserFromThread($user, $post, $post) : false;
        }

        return $response;
    }

    /**
     * @return SV_ReportImprovements_ControllerHelper_Reports
     */
    protected function _getReportHelper()
    {
        return $this->getHelper('SV_ReportImprovements_ControllerHelper_Reports');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Member extends XenForo_ControllerPublic_Member {}
}