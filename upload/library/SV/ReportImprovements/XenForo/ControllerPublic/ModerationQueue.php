<?php

class SV_ReportImprovements_XenForo_ControllerPublic_ModerationQueue extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_ModerationQueue
{
    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['queue']))
        {
            /** @var SV_ReportImprovements_XenForo_Model_Report $reportModel */
            $reportModel = $this->getModelFromCache('XenForo_Model_Report');
            $queue = &$response->params['queue'];
            if (is_array($queue) && $reportModel->canViewReports())
            {
                $grouped = [];
                foreach ($queue AS &$entry)
                {
                    list($contentType, $contentId) = $this->remapContentTypeToReport($entry);
                    if ($contentType && $contentId)
                    {
                        $grouped[$contentType][$contentId] = &$entry;
                    }
                }

                $reports = $reportModel->getReportsForGroupedContent($grouped);
                if ($reports)
                {
                    $reports = $reportModel->getVisibleReportsForUser($reports);

                    $groupedReports = [];
                    foreach ($reports AS $report)
                    {
                        $groupedReports[$report['content_type']][$report['content_id']] = $report;
                    }

                    foreach ($queue AS &$entry)
                    {
                        list($contentType, $contentId) = $this->remapContentTypeToReport($entry);
                        if ($contentType && $contentId && isset($groupedReports[$contentType][$contentId]))
                        {
                            $grouped[$contentType][$contentId]['report'] = $groupedReports[$contentType][$contentId];
                        }
                    }
                }
            }
        }

        return $response;
    }

    /**
     * @param array $entry
     * @return array
     */
    protected function remapContentTypeToReport($entry)
    {
        $contentType = $entry['content_type'];
        $contentId = $entry['content_id'];
        switch($contentType)
        {
            case 'thread':
                if (isset($entry['content']['first_post_id']))
                {
                    return ['post', $entry['content']['first_post_id']];
                }
                break;
            case 'unc':
                if (empty($entry['content']['user']['user_id']))
                {
                    break;
                }
                return ['user', $entry['content']['user']['user_id']];
            case 'avf_cm_message':
                return ['conversation_message', $contentId];
        }
        return [$contentType, $contentId];
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ControllerPublic_ModerationQueue extends XenForo_ControllerPublic_ModerationQueue {}
}