<?php

class SV_IntegratedReports_AlertHandler_Report extends XenForo_AlertHandler_Abstract
{
    const ContentType = 'report';
    var $_reportModel = null;

    public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
    {
        if (empty($this->_reportModel))
        {
            $this->_reportModel = $model->getModelFromCache('XenForo_Model_Report');
        }
        return $this->_reportModel->getReportsByIds($contentIds);
    }

    public function canViewAlert(array $alert, $content, array $viewingUser)
    {
        return ($viewingUser['is_moderator'] || $viewingUser['is_admin']);
    }
    
    public function prepareAlert(array $item, array $viewingUser)
    {
    	parent::prepareAlert($item, $viewingUser);

        if (!empty($item['content']['content_info']))
        {
            if (empty($this->_reportModel))
            {
                $this->_reportModel = XenForo_Model::create("XenForo_Model_Report");
            }
            $handler = $this->_reportModel->getReportHandler($item['content']['content_type']);
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
}
