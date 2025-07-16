<?php

namespace FrugalPhpPlugin\Orm;

use FrugalPhpPlugin\Orm\Commands\Database\ResetDatabase;

class PluginConfig
{
    public const PLUGIN_NAME = "Orm plugin";
    public static function pluginRouteFiles() : array
    {
        return [
            'dynamic' => '',
            'static' => ''
        ];
    }

    public static function pluginRouteCommands() : array
    {
        return ['resetDatabase' => ResetDatabase::class];
    }
}