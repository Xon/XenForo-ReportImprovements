<?php

/**
 * Class SV_ReportImprovements_XenForo_DataWriter_Report
 */
class SV_ReportImprovements_XenForo_DataWriter_Report extends XFCP_SV_ReportImprovements_XenForo_DataWriter_Report
{
    const OPTION_INDEX_FOR_SEARCH   = 'indexForSearch';

    protected function _getDefaultOptions()
    {
        $defaultOptions = parent::_getDefaultOptions();

        $defaultOptions[self::OPTION_INDEX_FOR_SEARCH] = true;

        return $defaultOptions;
    }

    protected function _postSaveAfterTransaction()
    {
        parent::_postSaveAfterTransaction();

        if ($this->getOption(self::OPTION_INDEX_FOR_SEARCH))
        {
            $this->_insertIntoSearchIndex();
        }
    }

    public function delete()
    {
        parent::delete();
        // update search index outside the transaction
        $this->_deleteFromSearchIndex();
    }

    protected function _insertIntoSearchIndex()
    {
        $dataHandler = $this->sv_getSearchDataHandler();
        if (!$dataHandler)
        {
            return;
        }

        $indexer = new XenForo_Search_Indexer();
        $dataHandler->insertIntoIndex($indexer, $this->getMergedData(), null);
    }

    protected function _deleteFromSearchIndex()
    {
        $dataHandler = $this->sv_getSearchDataHandler();
        if (!$dataHandler)
        {
            return;
        }

        $indexer = new XenForo_Search_Indexer();
        $dataHandler->deleteFromIndex($indexer, $this->getMergedData());
    }

    /**
     * @return XenForo_Search_DataHandler_Abstract|null
     */
    public function sv_getSearchDataHandler()
    {
        /* var $dataHandler XenForo_Search_DataHandler_Abstract */
        $dataHandler = $this->_getSearchModel()->getSearchDataHandler('report');

        return ($dataHandler instanceof SV_ReportImprovements_Search_DataHandler_Report) ? $dataHandler : null;
    }

    /**
     * @return XenForo_Model_Search
     */
    protected function _getSearchModel()
    {
        return $this->getModelFromCache('XenForo_Model_Search');
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_DataWriter_Report extends XenForo_DataWriter_Report {}
}