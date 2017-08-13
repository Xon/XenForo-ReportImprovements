<?php

class SV_ReportImprovements_Criteria
{
    /** @var SV_ReportImprovements_XenForo_Model_Report $reportModel */
    protected static $reportModel =  null;

    /**
     * @return SV_ReportImprovements_XenForo_Model_Report
     */
    protected static function getReportModel()
    {
        if (self::$reportModel === null)
        {
            self::$reportModel = XenForo_Model::create('XenForo_Model_Report');
        }
        return self::$reportModel;
    }

    public static function criteriaUser($rule, array $data, array $user, &$returnValue)
    {
        switch ($rule)
        {
            case 'sv_reports_minimum':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days);
                if ($reportCount >= $data['reports'])
                {
                    $returnValue = true;
                }
                break;

            case 'sv_reports_maximum':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days);
                if ($reportCount <= $data['reports'])
                {
                    $returnValue = true;
                }
                break;

            case 'sv_reports_minimum_open':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days, 'open');
                if ($reportCount >= $data['reports'])
                {
                    $returnValue = true;
                }
                break;

            case 'sv_reports_maximum_open':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days, 'open');
                if ($reportCount <= $data['reports'])
                {
                    $returnValue = true;
                }
                break;

            case 'sv_reports_minimum_assigned':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days, 'assigned');
                if ($reportCount >= $data['reports'])
                {
                    $returnValue = true;
                }
                break;

            case 'sv_reports_maximum_assigned':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days, 'assigned');
                if ($reportCount <= $data['reports'])
                {
                    $returnValue = true;
                }
                break;

            case 'sv_reports_minimum_resolved':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days, 'resolved');
                if ($reportCount >= $data['reports'])
                {
                    $returnValue = true;
                }
                break;

            case 'sv_reports_maximum_resolved':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days, 'resolved');
                if ($reportCount <= $data['reports'])
                {
                    $returnValue = true;
                }
                break;

            case 'sv_reports_minimum_rejected':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days, 'rejected');
                if ($reportCount >= $data['reports'])
                {
                    $returnValue = true;
                }
                break;

            case 'sv_reports_maximum_rejected':
                $days = empty($data['days']) ? 0 : intval($data['days']);
                $reportCount = self::getReportModel()->countReportsByUser($user['user_id'], $days, 'rejected');
                if ($reportCount <= $data['reports'])
                {
                    $returnValue = true;
                }
                break;
        }
    }
}