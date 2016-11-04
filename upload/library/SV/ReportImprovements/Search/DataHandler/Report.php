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
        $threadData = $handler->getContentForThread($data, $contentInfo );
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
        if (!($this->enabled)) return;
        $indexer->updateIndex('report', $data['report_id'], $fieldUpdates);
    }

    /**
     * Deletes one or more records from the index.
     *
     * @see XenForo_Search_DataHandler_Abstract::_deleteFromIndex()
     */
    protected function _deleteFromIndex(XenForo_Search_Indexer $indexer, array $dataList)
    {
        if (!($this->enabled)) return;
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
        if (!($this->enabled)) return false;
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
        if (!($this->enabled)) return false;
        $reportModel = $this->_getReportModel();
        $reports = $reportModel->getReportsByIds($contentIds);
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
        if (!($this->enabled)) return array();
        $reportModel = $this->_getReportModel();
        $reports = $reportModel->getReportsByIds($ids);
        return $reportModel->getVisibleReportsForUser($reports, $viewingUser);
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
        if (!($this->enabled)) return null;
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
     *
     * @return boolean
     */
    public function allowInSearch(array $viewingUser)
    {
        if (!($this->enabled)) return false;
        return $this->_getReportModel()->canViewReports($viewingUser);
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
