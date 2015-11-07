<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Report extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Report
{
    protected function _preDispatch($action)
    {
        if (!$this->_getReportModel()->canViewReports())
        {
            throw $this->getNoPermissionResponseException();
        }
    }

    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View)
        {
            $response->params['canViewReporterUsername'] = $this->_getReportModel()->canViewReporterUsername();
        }
        return $response;
    }

    public function actionClosed()
    {
        $response = parent::actionClosed();

        if ($response instanceof XenForo_ControllerResponse_View)
        {
            $response->params['canViewReporterUsername'] = $this->_getReportModel()->canViewReporterUsername();
        }
        return $response;
    }

    public function actionSearch()
    {
        $response = parent::actionSearch();

        if ($response instanceof XenForo_ControllerResponse_View)
        {
            $response->params['canViewReporterUsername'] = $this->_getReportModel()->canViewReporterUsername();
        }
        return $response;
    }

    public function actionView()
    {
        $response = parent::actionView();

        if ($response instanceof XenForo_ControllerResponse_View && isset($response->params['report']))
        {
            $response->params['canCommentReport'] = $this->_getReportModel()->canCommentReport($response->params['report']);
        }
        return $response;
    }

    public function actionLike()
    {
        $commentId = $this->_input->filterSingle('report_comment_id', XenForo_Input::UINT);
        $reportId = $this->_input->filterSingle('report_id', XenForo_Input::UINT);

        list($report, $comment) = $this->_getReportCommentOrError($reportId, $commentId);

        if (!$this->_getReportModel()->canLikeReportComment($comment, $errorPhraseKey))
        {
            throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
        }

        if (!isset($comment['likes']))
        {
            throw $this->getErrorOrNoPermissionResponseException();
        }

        $likeModel = $this->getModelFromCache('XenForo_Model_Like');

        $existingLike = $likeModel->getContentLikeByLikeUser('report_comment', $commentId, XenForo_Visitor::getUserId());

        if ($this->_request->isPost())
        {
            if ($existingLike)
            {
                $latestUsers = $likeModel->unlikeContent($existingLike);
            }
            else
            {
                $latestUsers = $likeModel->likeContent('report_comment', $commentId, $comment['user_id']);
            }

            $liked = ($existingLike ? false : true);

            if ($this->_noRedirect() && $latestUsers !== false)
            {
                $comment['likeUsers'] = $latestUsers;
                $comment['likes'] += ($liked ? 1 : -1);
                $comment['like_date'] = ($liked ? XenForo_Application::$time : 0);

                $viewParams = array(
                    'report' => $report,
                    'comment' => $comment,
                    'liked' => $liked,
                );

                return $this->responseView('SV_ReportImprovements_ViewPublic_Report_Comment_LikeConfirmed', '', $viewParams);
            }
            else
            {
                return $this->responseRedirect(
                        XenForo_ControllerResponse_Redirect::SUCCESS,
                        XenForo_Link::buildPublicLink('reports', $report, array('report_comment_id' => $comment['report_comment_id']))
                );
            }
        }
        else
        {
            $viewParams = array(
                'report' => $report,
                'comment' => $comment,
                'like' => $existingLike
            );

            return $this->responseView('SV_ReportImprovements_ViewPublic_Report_Comment_Like', 'sv_report_comment_like', $viewParams);
        }
    }

    public function actionLikes()
    {
        $commentId = $this->_input->filterSingle('report_comment_id', XenForo_Input::UINT);
        $reportId = $this->_input->filterSingle('report_id', XenForo_Input::UINT);

        list($report, $comment) = $this->_getReportCommentOrError($reportId, $commentId);

        $page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
        $perPage = 100;

        /** @var XenForo_Model_Like $likeModel */
        $likeModel = $this->getModelFromCache('XenForo_Model_Like');

        $total = $likeModel->countContentLikes('report_comment', $commentId);
        if (!$total)
        {
            return $this->responseError(new XenForo_Phrase('sv_no_one_has_liked_this_report_comment_yet'));
        }

        $likes = $likeModel->getContentLikes('report_comment', $commentId, array(
            'page' => $page,
            'perPage' => $perPage
        ));

        $viewParams = array(
            'report' => $report,
            'comment' => $comment,

            'likes' => $likes,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'hasMore' => ($page * $perPage) < $total
        );

        return $this->responseView('SV_ReportImprovements_ViewPublic_Report_Comment_Likes', 'sv_report_comment_likes', $viewParams);
    }

    public function actionComment()
    {
        $visitor = XenForo_Visitor::getInstance();
        SV_ReportImprovements_Globals::$Report_MaxAlertCount = $visitor->hasPermission('general', 'maxTaggedUsers');
        return parent::actionComment();
    }

    public function actionUpdate()
    {
        $visitor = XenForo_Visitor::getInstance();
        SV_ReportImprovements_Globals::$Report_MaxAlertCount = $visitor->hasPermission('general', 'maxTaggedUsers');
        $input = $this->_input->filter(array(
            'send_alert' => XenForo_Input::UINT,
            'alert_comment' => XenForo_Input::STRING,
        ));
        if ($input['send_alert'])
        {
            SV_ReportImprovements_Globals::$UserReportAlertComment = $input['alert_comment'];
        }
        return parent::actionUpdate();
    }

    public function _getReportCommentOrError($reportId, $commentId)
    {
        $report = $this->_getVisibleReportOrError($reportId);

        $comment = $this->_getReportModel()->getReportCommentById($commentId);
        if (!$comment || $comment['report_id'] != $reportId)
        {
            throw $this->responseException($this->responseError(new XenForo_Phrase('requested_report_not_found'), 404));
        }

        return array($report, $comment);
    }
}