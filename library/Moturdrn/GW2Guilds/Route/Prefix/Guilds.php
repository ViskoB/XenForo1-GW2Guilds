<?php

class Moturdrn_GW2Guilds_Route_Prefix_Guilds implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		//$action = $router->resolveActionWithIntegerOrStringParam($routePath, $request, 'guildid', 'guildname');
		//$action = $router->resolveActionWithStringParam($routePath, $request, 'guildname');
		$action = $router->resolveActionWithStringParam($routePath, $request, 'guildname');
		$actions = explode('/', $action);

		if(in_array($actions[0], array('add','ResetPermissions','SetGuildLeaders','save','mine', 'GuildUpgrade')))
		{
			$action = $router->resolveActionWithIntegerParam($routePath, $request, 'guildid');
		}
		else
		{
			$action = $router->resolveActionWithIntegerOrStringParam($routePath, $request, 'guildid', 'guildname');
		}

		return $router->getRouteMatch('Moturdrn_GW2Guilds_ControllerPublic_Guild', $action, 'guilds');
	}

	/**
	 * Method to build a link to the specified page/action with the provided
	 * data and params.
	 *
	 * @see XenForo_Route_BuilderInterface
	 */
	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		/*$action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

		if(is_array($data) && !empty($data['guildname']))
		{
			$link = XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'guildname');
		}
		else
		{
			if($data && isset($data['guildname']))
			{
				$data['title'] = $data['guildname'];
			}

			$link = XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'guildid', 'title');
		}*/

		//return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'guildname');
		//return $link;

		$action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

		if ( ! is_array($data)) {
			$action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

			return XenForo_Link::buildBasicLink($outputPrefix, $action);
		}

		if (isset($data['guildid'])) {
			return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'guildid', 'guildname');
		} else {
			return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, $strParams);
		}

	}
}