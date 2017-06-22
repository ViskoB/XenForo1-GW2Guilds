<?php

class Moturdrn_GW2Guilds_Extend_XenForo_DataWriter_User extends XFCP_Moturdrn_GW2Guilds_Extend_XenForo_DataWriter_User
{
	protected function _postDelete()
	{
		parent::_postDelete();
		
		$guildModel = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild');
		$pendingModel = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Pending');
		$gmemberModel = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Member');
		
		$pendingModel->scrubPendingRequests($this->get('user_id'));
		$gmemberModel->scrubPendingRequests($this->get('user_id'));
		$guildModel->scrubGuildLeaderOrOfficer($this->get('user_id'));
	}
}