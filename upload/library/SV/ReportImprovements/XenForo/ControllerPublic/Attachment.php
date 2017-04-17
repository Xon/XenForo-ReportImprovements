<?php

class SV_ReportImprovements_XenForo_ControllerPublic_Attachment extends XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Attachment
{
    public function actionIndex()
    {
        SV_ReportImprovements_Globals::$attachmentReportKey = $this->_input->filterSingle('k', XenForo_Input::STRING);

        return parent::actionIndex();
    }
}

// ******************** FOR IDE AUTO COMPLETE ********************
if (false)
{
    class XFCP_SV_ReportImprovements_XenForo_ControllerPublic_Attachment extends XenForo_ControllerPublic_Attachment {}
}