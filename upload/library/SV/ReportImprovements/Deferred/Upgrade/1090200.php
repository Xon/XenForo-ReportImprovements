<?php

class SV_ReportImprovements_Deferred_Upgrade_1090200 extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $increment = 250;
        $minCommentId = isset($data['comment_id']) ? $data['comment_id'] : -1;

        $db = XenForo_Application::getDb();

        $comments = $db->fetchAll(
            "
            SELECT report_comment_id, message
            FROM xf_report_comment 
            WHERE message LIKE '%http%' and message NOT LIKE '%[URL=%http%'
            AND report_comment_id > ?
            ORDER BY report_comment_id
            LIMIT ?
        ", [$minCommentId, $increment]
        );
        if (empty($comments))
        {
            return false;
        }

        $lastCommentId = false;
        $s = microtime(true);

        foreach ($comments AS $comment)
        {
            $output = XenForo_Helper_String::autoLinkBbCode($comment['message']);
            if ($output !== null && $output != $comment['message'])
            {
                $db->query(
                    '
                    UPDATE xf_report_comment
                    SET message = ?
                    WHERE report_comment_id = ?
                ', [$output, $comment['report_comment_id']]
                );
            }
            $lastCommentId = $comment['report_comment_id'];

            if ($targetRunTime && microtime(true) - $s > $targetRunTime)
            {
                break;
            }
        }

        if (empty($lastCommentId))
        {
            return false;
        }

        return ['comment_id' => $lastCommentId];
    }
}
