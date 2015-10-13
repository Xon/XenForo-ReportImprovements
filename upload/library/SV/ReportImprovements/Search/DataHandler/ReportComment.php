<?php

class SV_ReportImprovements_Search_DataHandler_ReportComment extends XenForo_Search_DataHandler_Abstract
{
    var $enabled = false;

    public function __construct()
    {
        // use the proxy class existence as a cheap check for if this add-on is enabled.
        $this->_getReportModel();
        $this->enabled = class_exists('XFCP_SV_ReportImprovements_XenForo_Model_Report', false);
    }

    protected $_reportModel = null;

    /**
     * Inserts into (or replaces a record) in the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_insertIntoIndex()
     */
    protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
    {
        if (!($this->enabled)) return;

        $metadata = array();
        $metadata['report'] = $data['report_id'];
        if (!empty($data['prefix_id']))
        {
            $metadata['prefix'] = $data['prefix_id'];
        }

        $metadata['state_change'] = $data['state_change'];
        $metadata['is_report'] = $data['is_report'];

        $title = '';
        $text = $data['message'];
        if (!empty($data['state_change']))
        {
            $text = $data['state_change'] . ' ' . $text;
        }
        if (!empty($data['warning_log_id']) && !empty($data['title']))
        {
            if (empty($data['title']))
                throw new Exception(var_export($data, true));
            $text = $data['title'] . ' '. $data['notes']. ' '. $text;

            $title = $data['title'];
            $metadata['points'] = $data['points'];
            $metadata['expiry_date'] = $data['expiry_date'];
        }

        if (!empty($data['alertComment']))
        {
            $text = $data['alertComment'] . ' ' . $text;
        }

        $indexer->insertIntoIndex(
            'report_comment', $data['report_comment_id'],
            $title, $text,
            $data['comment_date'], $data['user_id'], $data['report_comment_id'], $metadata
        );
    }

    /**
     * Updates a record in the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_updateIndex()
     */
    protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
    {
        if (!($this->enabled)) return;
        $indexer->updateIndex('report_comment', $data['report_comment_id'], $fieldUpdates);
    }

    /**
     * Deletes one or more records from the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_deleteFromIndex()
     */
    protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
    {
        if (!($this->enabled)) return;
        $reportCommentIds = array();
        foreach ($dataList AS $data)
        {
            $reportCommentIds[] = is_array($data) ? $data['report_comment_id'] : $data;
        }

        $indexer->deleteFromIndex('report_comment', $reportCommentIds);
    }

    /**
     * Rebuilds the index for a batch.
     *
     * @see XenForo_Search_DataHandler_Abstract::rebuildIndex()
     */
    public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
    {
        if (!($this->enabled)) return false;
        $reportCommentIds = $this->_getReportModel()->getReportCommentsIdsInRange($lastId, $batchSize);
        if (!$reportCommentIds)
        {
            return false;
        }

        $this->quickIndex($indexer, $reportCommentIds);

        return max($reportCommentIds);
    }

    /**
     * Rebuilds the index for the specified content.

     * @see XenForo_Search_DataHandler_Abstract::quickIndex()
     */
    public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
    {
        if (!($this->enabled)) return false;
        $reportModel = $this->_getReportModel();
        $reportComments = $reportModel->getReportCommentsByIds($contentIds);

        foreach ($reportComments AS $reportCommentId => &$reportComment)
        {
            $this->insertIntoIndex($indexer, $reportComment);
        }

        return true;
    }

    public function getInlineModConfiguration()
    {
        return array();
    }

    /**
     * Gets the type-specific data for a collection of results of this content type.
     *
     * @see XenForo_Search_DataHandler_Abstract::getDataForResults()
     */
    public function getDataForResults(array $ids, array $viewingUser, array $resultsGrouped)
    {
        if (!($this->enabled)) return array();
        return $this->_getReportModel()->getReportCommentsByIdsForUser($ids, $viewingUser);
    }

    /**
     * Determines if this result is viewable.
     *
     * @see XenForo_Search_DataHandler_Abstract::canViewResult()
     */
    public function canViewResult(array $result, array $viewingUser)
    {
        if (!($this->enabled)) return false;
        return true;
    }

    /**
     * Prepares a result for display.
     *
     * @see XenForo_Search_DataHandler_Abstract::prepareResult()
     */
    public function prepareResult(array $result, array $viewingUser)
    {
        if (!($this->enabled)) return $result;
        $reportModel = $this->_getReportModel();

        $handler = $reportModel->getReportHandlerCached($result['report']['content_type']);
        if ($handler)
        {
            $result['report'] = $handler->prepareReport($result['report']);
        }

        return $reportModel->prepareReportComment($result);
    }

    public function addInlineModOption(array &$result)
    {
        return array();
    }

    /**
     * Gets the date of the result (from the result's content).
     *
     * @see XenForo_Search_DataHandler_Abstract::getResultDate()
     */
    public function getResultDate(array $result)
    {
        return $result['comment_date'];
    }

    /**
     * Renders a result to HTML.
     *
     * @see XenForo_Search_DataHandler_Abstract::renderResult()
     */
    public function renderResult(XenForo_View $view, array $result, array $search)
    {
        if (!($this->enabled)) return null;
        return $view->createTemplateObject('search_result_report_comment', array(
            'report' => $result['report'],
            'comment' => $result,
            'search' => $search,
            'enableInlineMod' => $this->_inlineModEnabled
        ));
    }

    public function getSearchContentTypes()
    {
        return array('report_comment');
    }

    /**
     * Get type-specific constraints from input.
     *
     * @param XenForo_Input $input
     *
     * @return array
     */
    public function getTypeConstraintsFromInput(XenForo_Input $input)
    {
        if (!($this->enabled)) return array();
        $constraints = array();

        $includeUserReports = $input->filterSingle('include_user_reports', XenForo_Input::UINT);
        $includeReportComments = $input->filterSingle('include_report_comments', XenForo_Input::UINT);
        if ($includeUserReports || $includeReportComments)
        {
            if (!$includeUserReports || !$includeReportComments)
            {
                if ($includeUserReports)
                {
                    $constraints['is_report'] = '1';
                }
                else if ($includeReportComments)
                {
                    $constraints['is_report'] = '0';
                }
            }
        }
        else
        {
            throw new XenForo_Exception(new XenForo_Phrase('please_select_a_report_comment_search_type'), true);
        }

        $warningPoints = $input->filterSingle('warning_points', XenForo_Input::ARRAY_SIMPLE);

        if (isset($warningPoints['lower']) && $warningPoints['lower'] !== '')
        {
            $constraints['warning_points'][0] = intval($warningPoints['lower']);
            if ($constraints['warning_points'][0] < 0)
            {
                unset($constraints['warning_points'][0]);
            }
        }

        if (isset($warningPoints['upper']) && $warningPoints['upper'] !== '')
        {
            $constraints['warning_points'][1] = intval($warningPoints['upper']);
            if ($constraints['warning_points'][1] < 0)
            {
                unset($constraints['warning_points'][1]);
            }
        }

        if (empty($constraints['warning_points']))
        {
            unset($constraints['warning_points']);
        }

        return $constraints;
    }

    /**
     * Process a type-specific constraint.
     *
     * @see XenForo_Search_DataHandler_Abstract::processConstraint()
     */
    public function processConstraint(XenForo_Search_SourceHandler_Abstract $sourceHandler, $constraint, $constraintInfo, array $constraints)
    {
        if (!($this->enabled)) return array();
        switch ($constraint)
        {
            case 'is_report':
                if (isset($constraintInfo))
                {
                    return array('metadata' => array('is_report', $constraintInfo));
                }
            case 'warning_points':
                if (isset($constraintInfo))
                {
                    return array(
                        'range_query' => array('points',
                                               isset($constraintInfo[0]) ? array('>=', intval($constraintInfo[0])) : array(),
                                               isset($constraintInfo[1]) ? array('<=', intval($constraintInfo[1])) : array())
                    );
                }
        }

        return false;
    }

    /**
     * Gets the search form controller response for this type.
     *
     * @see XenForo_Search_DataHandler_Abstract::getSearchFormControllerResponse()
     */
    public function getSearchFormControllerResponse(XenForo_ControllerPublic_Abstract $controller, XenForo_Input $input, array $viewParams)
    {
        if (!($this->enabled)) return null;

        $visitor = XenForo_Visitor::getInstance();
        if (!$visitor['is_moderator']) return null;

        $params = $input->filterSingle('c', XenForo_Input::ARRAY_SIMPLE);

        if (!isset($params['is_report']))
        {
            $viewParams['search']['include_user_reports'] = true;
            $viewParams['search']['include_report_comments'] = true;
        }
        else if (!$params['is_report'])
        {

            $viewParams['search']['include_user_reports'] = false;
            $viewParams['search']['include_report_comments'] = true;
        }
        else if ($params['is_report'])
        {
            $viewParams['search']['include_user_reports'] = true;
            $viewParams['search']['include_report_comments'] = false;
        }

        if (isset($params['warning_points'][0]))
        {
            $viewParams['search']['warning_points']['lower'] = $params['warning_points'][0];
        }
        if (isset($params['warning_points'][1]))
        {
            $viewParams['search']['warning_points']['upper'] = $params['warning_points'][1];
        }

        return $controller->responseView('XenForo_ViewPublic_Search_Form_Post', 'search_form_report_comment', $viewParams);
    }

    protected function _getReportModel()
    {
        if (!$this->_reportModel)
        {
            $this->_reportModel = XenForo_Model::create('XenForo_Model_Report');
        }
        return $this->_reportModel;
    }
}