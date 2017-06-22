<?php

class Moturdrn_GW2Guilds_Listener
{
    public static function init(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        $callbacks = array();
        $callbacks += array(
            'guildemblem' 		=> array('Moturdrn_GW2Guilds_Helpers', 'helperGuildEmblem'),
        );
        if ($dependencies instanceof XenForo_Dependencies_Public)
        {
            $GLOBALS['XenForoHelperUserBanner'] = XenForo_Template_Helper_Core::$helperCallbacks['userbanner'];
            if ($GLOBALS['XenForoHelperUserBanner'][0] === 'self')
            {
                $GLOBALS['XenForoHelperUserBanner'][0] = 'XenForo_Template_Helper_Core';
            }

            $callbacks['userbanner'] = array('Moturdrn_GW2Guilds_Helpers', 'helperUserBanner');
        }

        foreach($callbacks as $helperName => $callback)
        {
            XenForo_Template_Helper_Core::$helperCallbacks[$helperName] = $callback;
        }
    }

    public static function WidgetFrameworkReady(&$renderers){
        $renderers[]= "Moturdrn_GW2Guilds_WidgetRenderer_RandomGuild";
    }

    public static function navigation_tabs(array &$extraTabs, $selectedTabId)
    {
        $extraTabs['guilds'] = array(
            'href' => XenForo_Link::buildPublicLink("canonical:" . 'guilds'),
            'title' => 'Guilds',
            'position' => "middle",
            'selected' => ($selectedTabId == 'guilds'),
            'linksTemplate' => ''
        );
    }
	
	public static function extendDataWriter($class, array &$extend)
	{
		if ($class == 'XenForo_DataWriter_User')
			$extend[] = 'Moturdrn_GW2Guilds_Extend_XenForo_DataWriter_User';
	}
}