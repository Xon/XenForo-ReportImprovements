<?php

class SV_ReportImprovements_Listener
{
    const AddonNameSpace = 'SV_ReportImprovements_';

    public static function load_class($class, array &$extend)
    {
        switch ($class)
        {
            case 'XenForo_Model_Report':
                if (XenForo_Application::$versionId <= 1040370)
                    $extend[] = self::AddonNameSpace.$class.'Patch';
                break;

        }
        $extend[] = self::AddonNameSpace.$class;
    }
}