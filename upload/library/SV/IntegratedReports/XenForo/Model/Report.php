<?php

class SV_IntegratedReports_XenForo_Model_Report extends XFCP_SV_IntegratedReports_XenForo_Model_Report
{
	public function getReportComments($reportId)
	{
		return $this->fetchAllKeyed('
			SELECT report_comment.*,
				user.*
                ,warning.warning_id
                ,warningLog.warning_definition_id
                ,warningLog.title
                ,warningLog.points
                ,warningLog.notes
                ,warningLog.expiry_date
                ,warningLog.operation_type
                ,warning.is_expired
			FROM xf_report_comment AS report_comment
            LEFT JOIN xf_sv_warning_log warningLog on warningLog.warning_log_id = report_comment.warning_log_id
            LEFT JOIN xf_warning warning on warningLog.warning_id = warning.warning_id
			LEFT JOIN xf_user AS user ON (user.user_id = report_comment.user_id)
			WHERE report_comment.report_id = ?
			ORDER BY report_comment.comment_date
		', 'report_comment_id', $reportId);
	} 

    public function getReportCommentById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_report_comment
			WHERE report_comment_id = ?
		', $id);
	}
}