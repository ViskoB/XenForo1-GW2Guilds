<?php

class Moturdrn_GW2Guilds_Route_Prefix_Guilds implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithStringParam($routePath, $request, 'guild_name');
		$actions = explode('/', $action);

		if(in_array($actions[0], array('add','ResetPermissions','SetGuildLeaders','save','mine', 'GuildUpgrade')))
		{
			$action = $router->resolveActionWithIntegerParam($routePath, $request, 'guild_id');
		}
		else
		{
			$action = $router->resolveActionWithIntegerOrStringParam($routePath, $request, 'guild_id', 'guild_name');
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
	    $action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

		if ( ! is_array($data)) {
			$action = XenForo_Link::getPageNumberAsAction($action, $extraParams);

			return XenForo_Link::buildBasicLink($outputPrefix, $action);
		}

		if (isset($data['guild_id'])) {
			return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'guild_id', 'guild_name');
		} else {
			return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, $strParams);
		}

	}
}