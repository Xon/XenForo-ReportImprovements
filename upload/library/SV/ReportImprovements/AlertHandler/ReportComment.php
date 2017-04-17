<?php

class SV_ReportImprovements_AlertHandler_ReportComment extends XenForo_AlertHandler_Abstract
{
    var $_reportModel  = null;
    var $_handlerCache = array();

    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        return $this->_getReportModel()->getReportCommentsByIdsForUser($contentIds, $viewingUser);
    }

    public function prepareAlert(array $item, array $viewingUser)
    {
        $item = parent::prepareAlert($item, $viewingUser);

        if (!empty($item['content']['report']['content_info']))
        {
            $handler = $this->_getReportHandler($item['content']['report']['content_type']);
            if (!empty($handler))
            {
                $item['content']['report'] = $handler->prepareReport($item['content']['report']);
            }
        }

        return $item;
    }

    protected function _getReportHandler($content_type)
    {
        if (isset($this->_handlerCache[$content_type]))
        {
            $handler = $this->_handlerCache[$content_type];
        }
        else
        {
            $handler = $this->_handlerCache[$content_type] = $this->_getReportModel()->getReportHandler($content_type);
        }

        return $handler;
    }

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected function _getReportModel()
    {
        if (empty($this->_reportModel))
        {
            $this->_reportModel = XenForo_Model::create('XenForo_Model_Report');
        }

        return $this->_reportModel;
    }
}
