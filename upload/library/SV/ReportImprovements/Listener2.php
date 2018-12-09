<?php

class SV_ReportImprovements_Listener2
{
    public static function load_class_patch2($class, array &$extend)
    {
        $extend[] = 'SV_ReportImprovements_' . $class . 'Patch2';
    }
}