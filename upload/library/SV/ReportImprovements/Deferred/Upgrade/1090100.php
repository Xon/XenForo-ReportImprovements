<?php

class SV_ReportImprovements_Deferred_Upgrade_1090100 extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $increment = 250;
        $minCommentId = isset($data['comment_id']) ? $data['comment_id'] : -1;

        $db = XenForo_Application::getDb();

        $commentQuery = $db->query("
            SELECT MAX(report_comment_id) AS max_report_comment_id
            FROM xf_report_comment 
            WHERE message LIKE '%@[%:%]%'
            AND report_comment_id > ?
        ", [$minCommentId]);
        $commentRows = $commentQuery->fetchAll();

        if (empty($commentRows) || empty($commentRows[0]) || empty($commentRows[0]['max_report_comment_id']))
        {
            return false;
        }

        $commentQuery = $db->query("
            SELECT report_comment_id, message
            FROM xf_report_comment 
            WHERE message LIKE '%@[%:%]%'
            AND report_comment_id > ?
            ORDER BY report_comment_id
            LIMIT ?
        ",[$minCommentId, $increment]);

        $lastCommentId = false;
        $comments = $commentQuery->fetchAll();

        if (!empty($comments))
        {
            foreach ($comments AS $comment)
            {
                $output = preg_replace("/\@\[([^:]+):([^\]]+)\]/Uu", "[USER=$1]$2[/USER]", $comment['message']);
                if ($output !== null && $output != $comment['message'])
                {
                    $db->query('
                        UPDATE xf_report_comment
                        SET message = ?
                        WHERE report_comment_id = ?
                    ', [$output, $comment['report_comment_id']]);
                }
                $lastCommentId = $comment['report_comment_id'];
            }
        }

        XenForo_Db::commit($db);

        if (empty($lastCommentId))
        {
            return false;
        }

        return ['comment_id' => $lastCommentId];
    }
}