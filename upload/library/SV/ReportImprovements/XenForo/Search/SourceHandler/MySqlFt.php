<?php

class SV_ReportImprovements_XenForo_Search_SourceHandler_MySqlFt extends XFCP_SV_ReportImprovements_XenForo_Search_SourceHandler_MySqlFt
{
    protected $_allowWildcard = false;
    public function executeSearch($searchQuery, $titleOnly, array $processedConstraints, array $orderParts, $groupByDiscussionType, $maxResults, XenForo_Search_DataHandler_Abstract $typeHandler = null)
    {
        $this->_allowWildcard = $typeHandler instanceof SV_ReportImprovements_Search_DataHandler_ReportComment;
        
        return parent::executeSearch($searchQuery, $titleOnly, $processedConstraints, $orderParts, $groupByDiscussionType, $maxResults, $typeHandler);
    }

    public function tokenizeQuery($query)
    {
        if ($this->_allowWildcard && $query == '*')
        {
            return array();
        }

        return parent::tokenizeQuery($query);
    }
}