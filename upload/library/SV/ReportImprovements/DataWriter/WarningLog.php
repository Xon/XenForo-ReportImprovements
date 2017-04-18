<?php

/**
* Data writer for warning logs.
*/
class SV_ReportImprovements_DataWriter_WarningLog extends XenForo_DataWriter
{
    /**
     * Gets the fields that are defined for the table. See parent for explanation.
     *
     * @return array
     */
    protected function _getFields()
    {
        return array(
            'xf_sv_warning_log' => array(
                'warning_log_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
                'operation_type' => array('type' => self::TYPE_STRING, 'default' => 'new',
                                          'allowedValues' => array('new', 'edit', 'delete', 'expire', 'acknowledge')
                ),

                'warning_edit_date' => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
                'warning_id' => array('type' => self::TYPE_UINT, 'default' => 0),
                'content_type' => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 25),
                'content_id' => array('type' => self::TYPE_UINT, 'required' => true),
                'content_title' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 255),
                'user_id' => array('type' => self::TYPE_UINT, 'required' => true),
                'warning_date' => array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
                'warning_user_id' => array('type' => self::TYPE_UINT, 'required' => true),
                'warning_definition_id' => array('type' => self::TYPE_UINT, 'default' => 0),
                'title' => array('type' => self::TYPE_STRING, 'default' => '', 'required' => SV_ReportImprovements_Globals::$RequireWarningLogTitle,
                                 'requiredError' => 'please_enter_valid_title', 'maxLength' => 255
                ),
                'notes' => array('type' => self::TYPE_STRING, 'default' => ''),
                'points' => array('type' => self::TYPE_UINT, 'required' => true, 'max' => 65535),
                'expiry_date' => array('type' => self::TYPE_UINT, 'default' => 0),
                'is_expired' => array('type' => self::TYPE_BOOLEAN, 'default' => 0),
                'extra_user_group_ids' => array('type' => self::TYPE_UNKNOWN, 'default' => '',
                                                'verification' => array('XenForo_DataWriter_Helper_User', 'verifyExtraUserGroupIds')
                ),
                'reply_ban_thread_id' => array('type' => self::TYPE_UINT, 'default' => 0),
            )
        );
    }

    /**
     * Gets the actual existing data out of data that was passed in. See parent for explanation.
     *
     * @param mixed
     *
     * @return array|false
     */
    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data))
        {
            return false;
        }

        return array('sv_warning_log' => $this->_getWarningLogModel()->getWarningLogById($id));
    }

    /**
     * Gets SQL condition to update the existing record.
     *
     * @return string
     */
    protected function _getUpdateCondition($tableName)
    {
        return 'warning_log_id = ' . $this->_db->quote($this->getExisting('warning_log_id'));
    }

    protected function _preSave()
    {
    }

    protected function _postSave()
    {
    }

    protected function _postDelete()
    {
    }

    /**
     * @return SV_ReportImprovements_Model_WarningLog
     */
    protected function _getWarningLogModel()
    {
        return $this->getModelFromCache('SV_ReportImprovements_Model_WarningLog');
    }
}