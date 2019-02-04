<?php
/**
 *
 */
class Settings
{
    private $config;

    function __construct()
    {
        //$this->config = json_encode('../settings/config.json');
    }

    public static function getConfig()
    {
        $configFile = file_get_contents('http://'.$_SERVER['HTTP_HOST'].'/SGM_menu/settings/config.json');
        $config = json_decode($configFile);
        return $config;
    }

    public static function getWorkMode()
    {
        return Settings::getConfig()->workMode;
    }

    public static function getModeData($modeName)
    {
        return Settings::getConfig()->$modeName;
    }

    public static function getAnswerMode()
    {
        return Settings::getConfig()->answerMode;
    }
}
