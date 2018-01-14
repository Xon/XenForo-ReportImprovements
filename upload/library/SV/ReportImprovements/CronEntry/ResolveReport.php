<?php

class SV_ReportImprovements_CronEntry_ResolveReport
{
    public static function run()
    {
        $xenOptions = XenForo_Application::getOptions();

        if (!empty($xenOptions->sv_ri_expiry_days) && !empty($xenOptions->sv_ri_expiry_action))
        {
            /** @var SV_ReportImprovements_XenForo_Model_Report $reportModel */
            $reportModel = XenForo_Model::create('XenForo_Model_Report');
            $reportsToResolve = $reportModel->getReportsToResolve($xenOptions->sv_ri_expiry_days);
            /** @var string $action */
            $action = $xenOptions->sv_ri_expiry_action;

            /** @var XenForo_Model_User $userModel */
            $userModel = XenForo_Model::create('XenForo_Model_User');
            $user = $userModel->getUserById($xenOptions->sv_ri_user_id);

            if (!empty($reportsToResolve) && $user)
            {
                foreach ($reportsToResolve AS $report)
                {
                    /** @var XenForo_DataWriter_Report $reportDw */
                    $reportDw = XenForo_DataWriter::create('XenForo_DataWriter_Report');
                    $reportDw->setExistingData($report);
                    $reportDw->set('report_state', 'resolved');
                    $reportDw->save();

                    $dw_comment = XenForo_DataWriter::create('XenForo_DataWriter_ReportComment');
                    $dw_comment->bulkSet(
                        [
                            'report_id'    => $report['report_id'],
                            'user_id'      => $user['user_id'],
                            'username'     => $user['username'],
                            'state_change' => $action,
                        ]
                    );
                    $dw_comment->save();
                }
            }
        }

        return true;
    }
}
