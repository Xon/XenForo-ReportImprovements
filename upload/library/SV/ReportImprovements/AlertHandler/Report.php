<?php

class SV_ReportImprovements_AlertHandler_Report extends XenForo_AlertHandler_Abstract
{
    var $_reportModel = null;
    var $_handlerCache = array();

    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        return $this->_getReportModel()->getReportsByIds($contentIds);
    }

    public function canViewAlert(array $alert, $content, array $viewingUser)
    {
        return true;
        $handler = $this->_getReportHandler($item['content']['content_type']);
        if (empty($handler))
        {
            return false;
        }
        $reports = $handler->getVisibleReportsForUser(array($item['content']), $viewingUser);
        return !empty($reports);
    }

    public function prepareAlert(array $item, array $viewingUser)
    {
        $item = parent::prepareAlert($item, $viewingUser);

        if (!empty($item['content']['content_info']))
        {
            $handler = $this->_getReportHandler($item['content']['content_type']);
            if (!empty($handler))
            {
                $item['content'] = $handler->prepareReport($item['content']);
            }
        }
        if (!empty($item['extra_data']))
        {
            $item['content']['extra'] = unserialize($item['extra_data']);
            unset($item['extra_data']);
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

    protected function _getReportModel()
    {
        if (empty($this->_reportModel))
        {
            $this->_reportModel = XenForo_Model::create('XenForo_Model_Report');
        }

        return $this->_reportModel;
    }
}
