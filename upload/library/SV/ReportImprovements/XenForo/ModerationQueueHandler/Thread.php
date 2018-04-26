<?php

class SV_ReportImprovements_XenForo_ModerationQueueHandler_Thread extends XFCP_SV_ReportImprovements_XenForo_ModerationQueueHandler_Thread
{
    public function getVisibleModerationQueueEntriesForUser(array $contentIds, array $viewingUser)
    {
        $output = parent::getVisibleModerationQueueEntriesForUser($contentIds, $viewingUser);

        if ($output)
        {
            $db = XenForo_Application::getDb();
            $threadIds = array_keys($output);
            $threadFirstPosts = $db->fetchPairs('select thread_id, first_post_id from xf_thread where thread_id in (' . $db->quote($threadIds) . ')');
            foreach ($threadFirstPosts as $threadId => $first_post_id)
            {
                $output[$threadId]['first_post_id'] = $first_post_id;
            }
        }

        return $output;
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ModerationQueueHandler_Thread extends XenForo_ModerationQueueHandler_Thread {}
}