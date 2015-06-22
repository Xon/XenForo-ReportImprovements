<?php

class SV_ReportImprovements_Deferred_WarningLogMigration extends XenForo_Deferred_Abstract
{
    public function execute(array $deferred, array $data, $targetRunTime, &$status)
    {
        $db->query("
            insert into xf_sv_warning_log (warning_edit_date,operation_type,warning_id,content_type,content_id,content_title,user_id,warning_date,warning_user_id,warning_definition_id,title,notes,points,expiry_date,is_expired,extra_user_group_ids)
            SELECT xf_warning.warning_date,'new',warning_id,content_type,content_id,content_title,user_id,warning_date,warning_user_id,warning_definition_id,title,notes,points,expiry_date,is_expired,extra_user_group_ids
            FROM xf_warning
            where warning_id not in (select warning_id from xf_sv_warning_log)
        ");


        $db->query("
            insert into xf_report_comment (report_id,comment_date,user_id,username,message,state_change,is_report,warning_log_id)
            select (select report_id from xf_report where xf_report.content_type = xf_sv_warning_log.content_type and xf_report.content_id = xf_sv_warning_log.content_id) as report_id,
                xf_sv_warning_log. warning_date,xf_user.user_id, xf_user.username,'','',0,warning_log_id
            from xf_sv_warning_log
            join xf_user on xf_user.user_id = xf_sv_warning_log.warning_user_id
            where not exists(select * from xf_report where xf_report.content_type = xf_sv_warning_log.content_type and xf_report.content_id = xf_sv_warning_log.content_id)
                and not exists(select * from xf_report_comment where xf_report_comment.warning_log_id = xf_sv_warning_log.warning_log_id)
        ");
        return false;
    }
}
