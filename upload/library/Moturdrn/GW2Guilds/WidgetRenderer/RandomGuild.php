<?php

class Moturdrn_GW2Guilds_WidgetRenderer_RandomGuild extends WidgetFramework_WidgetRenderer
{
    protected function _getConfiguration(){
        return array(
            'name' => 'Random GW2 Guild',
            'useCache' => false,
            'useWrapper' => true
        );
    }

    protected function _getOptionsTemplate() {
        return false;
    }

    protected function _render(array $widget, $positionCode, array $params, XenForo_Template_Abstract $renderTemplateObject){

        $guildModel = XenForo_Model::create('Moturdrn_GW2Guilds_Model_Guild');

        $guilds = $guildModel->getActiveGuilds();

		if($guilds){
			$guilds = array_values($guilds);

			$guildArrKey = array_rand($guilds, 1);

			$guild = $guilds[$guildArrKey];

			$guild = $guildModel->prepareGuild($guild);
			
			$renderTemplateObject->setParam('guild', $guild);
		}
		
        return $renderTemplateObject->render();
    }

    protected function _getRenderTemplate(array $widget, $positionCode, array $params){
        return 'moturdrn_gw2guilds_widget';
    }

}