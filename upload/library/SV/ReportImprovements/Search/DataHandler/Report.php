<?php

class SV_ReportImprovements_Search_DataHandler_Report extends XenForo_Search_DataHandler_Abstract
{
    var $enabled = false;

    public function __construct()
    {
        // use the proxy class existence as a cheap check for if this add-on is enabled.
        $this->_getReportModel();
        $this->enabled = class_exists('XFCP_SV_ReportImprovements_XenForo_Model_Report', false);
    }

    public function getCustomMapping(array $mapping = array())
    {
        $mapping['properties']['report'] = array("type" => "long");
        $mapping['properties']['report_state'] = array("type" => "long");

        return $mapping;
    }

    protected $_reportModel = null;

    /**
     * Inserts into (or replaces a record) in the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_insertIntoIndex()
     */
    protected function _insertIntoIndex(XenForo_Search_Indexer $indexer, array $data, array $parentData = null)
    {
        if (!($this->enabled))
        {
            return;
        }

        $metadata = array();
        $metadata['report'] = $data['report_id'];

        $reportModel = $this->_getReportModel();
        if (!isset($data['handler']))
        {
            $handler = $reportModel->getReportHandlerCached($data['content_type']);
        }
        else
        {
            $handler = $data['handler'];
        }
        if (empty($handler))
        {
            return;
        }

        $contentInfo = @unserialize($data['content_info']);
        $title = $handler->getContentTitle($data, $contentInfo);
        $threadData = $handler->getContentForThread($data, $contentInfo);
        $text = $threadData['message'];
        if ($title instanceof XenForo_Phrase)
        {
            $title->setInsertParamsEscaped(false);
        }
        $title = (string)$title;

        $metadata['report_state'] = $reportModel->mapReportState($data['report_state']);
        if ($metadata['report_state'] === null)
        {
            unset($metadata['report_state']);
        }
        $metadata['assigned_user_id'] = $data['assigned_user_id'];

        $indexer->insertIntoIndex(
            'report', $data['report_id'],
            $title, $text,
            $data['first_report_date'], $data['content_user_id'], $data['report_id'], $metadata
        );
    }

    /**
     * Updates a record in the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_updateIndex()
     */
    protected function _updateIndex(XenForo_Search_Indexer $indexer, array $data, array $fieldUpdates)
    {
        if (!($this->enabled))
        {
            return;
        }
        $indexer->updateIndex('report', $data['report_id'], $fieldUpdates);
    }

    /**
     * Deletes one or more records from the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_deleteFromIndex()
     */
    protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
    {
        if (!($this->enabled))
        {
            return;
        }
        $reportIds = array();
        foreach ($dataList AS $data)
        {
            $reportIds[] = is_array($data) ? $data['report_id'] : $data;
        }

        $indexer->deleteFromIndex('report', $reportIds);
    }

    /**
     * Rebuilds the index for a batch.
     *
     * @see XenForo_Search_DataHandler_Abstract::rebuildIndex()
     */
    public function rebuildIndex(XenForo_Search_Indexer $indexer, $lastId, $batchSize)
    {
        if (!($this->enabled))
        {
            return false;
        }
        $reportIds = $this->_getReportModel()->getReportIdsInRange($lastId, $batchSize);
        if (!$reportIds)
        {
            return false;
        }

        $this->quickIndex($indexer, $reportIds);

        return max($reportIds);
    }

    /**
     * Rebuilds the index for the specified content.
     * @see XenForo_Search_DataHandler_Abstract::quickIndex()
     */
    public function quickIndex(XenForo_Search_Indexer $indexer, array $contentIds)
    {
        if (!($this->enabled))
        {
            return false;
        }
        $reportModel = $this->_getReportModel();
        $reports = $reportModel->sv_getReportsByIds($contentIds);
        $handlers = $reportModel->getReportHandlers();

        foreach ($reports AS $reportId => &$report)
        {
            if (!isset($report['hanlder']))
            {
                if (!isset($handlers[$report['content_type']]))
                {
                    continue;
                }
                $report['hanlder'] = $handlers[$report['content_type']];
            }

            $this->insertIntoIndex($indexer, $report);
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
        if (!($this->enabled))
        {
            return array();
        }
        $reportModel = $this->_getReportModel();
        $reports = $reportModel->sv_getReportsByIds($ids);

        return $reportModel->getVisibleReportsForUser($reports, $viewingUser);
    }

    /**
     * Determines if this result is viewable.
     *
     * @see XenForo_Search_DataHandler_Abstract::canViewResult()
     */
    public function canViewResult(array $result, array $viewingUser)
    {
        if (!($this->enabled))
        {
            return false;
        }

        return true;
    }

    /**
     * Prepares a result for display.
     *
     * @see XenForo_Search_DataHandler_Abstract::prepareResult()
     */
    public function prepareResult(array $result, array $viewingUser)
    {
        if (!($this->enabled))
        {
            return $result;
        }
        $reportModel = $this->_getReportModel();

        $handler = $reportModel->getReportHandlerCached($result['content_type']);
        if ($handler)
        {
            $result = $handler->prepareReport($result);
        }

        return $result;
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
        return $result['first_report_date'];
    }

    /**
     * Renders a result to HTML.
     *
     * @see XenForo_Search_DataHandler_Abstract::renderResult()
     */
    public function renderResult(XenForo_View $view, array $result, array $search)
    {
        if (!($this->enabled))
        {
            return null;
        }

        return $view->createTemplateObject('search_result_report', array(
            'report' => $result,
            'search' => $search,
            'enableInlineMod' => $this->_inlineModEnabled
        ));
    }

    public function getSearchContentTypes()
    {
        return array('report');
    }

    /**
     * Allows a content type to opt-out of search based off the viewing user
     *
     * @param array $viewingUser
     * @return boolean
     */
    public function allowInSearch(array $viewingUser)
    {
        if (!($this->enabled))
        {
            return false;
        }

        return $this->_getReportModel()->canViewReports($viewingUser);
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
        if (!($this->enabled))
        {
            return array();
        }
        $constraints = array();

        $includeUserReports = $input->filterSingle('include_user_reports', XenForo_Input::UINT);
        $includeReportComments = $input->filterSingle('include_report_comments', XenForo_Input::UINT);
        if ($includeUserReports || $includeReportComments)
        {
            if (!$includeUserReports || !$includeReportComments)
            {
                if ($includeUserReports)
                {
                    $constraints['is_report'] = true;
                }
                else if ($includeReportComments)
                {
                    $constraints['is_report'] = false;
                }
            }
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

    public function filterConstraints(XenForo_Search_SourceHandler_Abstract $sourceHandler, array $constraints)
    {
        $constraints = parent::filterConstraints($sourceHandler, $constraints);
        if (!$this->_getReportModel()->canViewReporterUsername())
        {
            $constraints['is_report'] = false;
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
        if (!($this->enabled))
        {
            return array();
        }
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
                                               isset($constraintInfo[1]) ? array('<=', intval($constraintInfo[1])) : array()
                        )
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
        if (!($this->enabled))
        {
            return null;
        }

        if (!$this->_getReportModel()->canViewReports())
        {
            return null;
        }

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

        $viewParams['search']['range_query'] = class_exists('XFCP_SV_SearchImprovements_XenES_Search_SourceHandler_ElasticSearch', false);

        if (!empty($params['report_for']))
        {
            $user = $this->_getUserModel()->getUserById($params['report_for']);
            if (isset($user['username']))
            {
                $viewParams['search']['report_for'] = $user['username'];
            }
        }

        return $controller->responseView('XenForo_ViewPublic_Search_Form_Post', 'search_form_report', $viewParams);
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        if (!$this->_reportModel)
        {
            $this->_reportModel = XenForo_Model::create('XenForo_Model_Report');
        }

        return $this->_reportModel;
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_User
     */
    protected function _getUserModel()
    {
        if (!$this->_userModel)
        {
            $this->_userModel = XenForo_Model::create('XenForo_Model_User');
        }

        return $this->_userModel;
    }
}
