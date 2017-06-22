<?php

class Moturdrn_GW2Guilds_Model_Guild extends XenForo_Model
{
	public function getGuildById($guildId)
	{
		return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_guilds WHERE guildid = ?', $guildId);
	}

	public function getGuildsPendingByIds(array $guildIds)
	{
		if (empty($guildIds))
		{
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT guild.*
			FROM xf_moturdrn_gw2guilds_guilds AS guild
			WHERE guild.guildid IN (' . $this->_getDb()->quote($guildIds) . ')
		 AND guild.status LIKE \'Pending%\'', 'guildid');
	}

	public function getGuildsByIds(array $guildIds)
	{
		if (empty($guildIds))
		{
			return array();
		}

		return $this->fetchAllKeyed('
			SELECT guild.*
			FROM xf_moturdrn_gw2guilds_guilds AS guild
			WHERE guild.guildid IN (' . $this->_getDb()->quote($guildIds) . ')
		', 'guildid');
	}
	
	public function getGuildByName($guildName)
	{
		return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_guilds WHERE guildname = ?', $guildName);
	}
	
	public function getGuilds($conditions = null)
	{
		$whereClause = $this->prepareGuildConditions($conditions);
		return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT *
				FROM (SELECT *, 1 AS Rank FROM xf_moturdrn_gw2guilds_guilds WHERE status = \'Active\' OR status LIKE \'Pending%\'
			UNION ALL
			SELECT *, 2 AS Rank
				FROM xf_moturdrn_gw2guilds_guilds WHERE status = \'Inactive\') guilds
			ORDER BY guilds.Rank, guilds.guildname ASC', 0),'guildid');
	}

	public function getActiveGuilds()
	{
		return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT *
			FROM xf_moturdrn_gw2guilds_guilds
			 WHERE status = \'Active\' AND guildid != 115', 0),'guildid');
	}

	public function getGuildsOfUser($userId){
		return $this->fetchAllKeyed($this->limitQueryResults(
			'SELECT g.*
				FROM xf_moturdrn_gw2guilds_members as m
				JOIN xf_moturdrn_gw2guilds_guilds as g on g.guildid = m.guildid
				WHERE m.user_id = ' . $this->_getDb()->quote($userId),0),'user_id');
	}
	
	public function getActiveGuildsOfUser($userId){
		return $this->fetchAllKeyed($this->limitQueryResults(
			'SELECT g.*
				FROM xf_moturdrn_gw2guilds_members as m
				JOIN xf_moturdrn_gw2guilds_guilds as g on g.guildid = m.guildid
				WHERE g.status = \'Active\' AND m.user_id = ' . $this->_getDb()->quote($userId),0),'user_id');
	}

	public function getGuildsOfUserCount($userId){
		return $this->_getDb()->fetchRow(
			'SELECT count(*) as GuildCount
				FROM xf_moturdrn_gw2guilds_members as m
				JOIN xf_moturdrn_gw2guilds_guilds as g on g.guildid = m.guildid
				WHERE m.user_id = ?',$userId);
	}

	public function getGuildsIdsFromNames(array $names)
	{
		return $this->_getDb()->fetchPairs('
			SELECT guildname, guildid
			FROM xf_moturdrn_gw2guilds_guilds
			WHERE guildname IN (' . $this->_getDb()->quote($names) . ')
		');
	}

	public function getActiveGuildsWhereLeader($userId)
	{
		return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT *
				FROM xf_moturdrn_gw2guilds_guilds
				WHERE status = \'Active\' AND guildleader_userid = ' . $this->_getDb()->quote($userId),0),'guildid');
	}

	public function getPendingGuilds($conditions = null)
	{
		return $this->fetchAllKeyed($this->limitQueryResults(
			'SELECT * FROM xf_moturdrn_gw2guilds_guilds AS guild WHERE status like \'Pending%\' ORDER BY guildname ASC', 0),'guildid');
	}

	public function getPendingGuildsCount(){
		return $this->_getDb()->fetchRow('SELECT count(*) as PendingCount FROM xf_moturdrn_gw2guilds_guilds AS guild WHERE guild.status LIKE \'Pending%\'');
	}

	public function getPendingRequestsCountById($guildId){
		return $this->_getDb()->fetchRow('SELECT count(*) as RequestCount FROM xf_moturdrn_gw2guilds_pending AS pending WHERE pending.guildid=?',$guildId);
	}

	public function getGuildIdsInRange($start, $limit)
	{
		$db = $this->_getDb();

		return $db->fetchCol($db->limit('
			SELECT guildid
			FROM xf_moturdrn_gw2guilds_guilds
			WHERE guildid > ?
			ORDER BY guildid
		', $limit), $start);
	}
	
	public function prepareGuild(array $guild, array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		$guild['canEdit'] = $this->canEditGuild($guild, $null, $viewingUser);
		$guild['canJoin'] = $this->canJoinGuild($guild, $null, $viewingUser);
		$guild['canLeave'] = $this->canLeaveGuild($guild, $null, $viewingUser);
		$guild['canDelete'] = $this->canDeleteGuild($guild, $null, $viewingUser);
		$guild['canTransfer'] = $this->canTransferGuild($guild, $null, $viewingUser);
		$guild['accessLevel'] = $this->guildAccessLevel($guild, $null, $viewingUser);

		$guildMembers = $this->_getMemberModel()->getGuildMembers($guild['guildid']);
		if(count($guildMembers) > 0)
			$guild['member_count'] = count($guildMembers);
		else
			$guild['member_count'] = 0;

		$pendingRequests = $this->getPendingRequestsCountById($guild['guildid']);
		$guild['pending_count'] = $pendingRequests['RequestCount'];

		return $guild;
	}

	public function prepareGuildConditions($conditions = null)
	{
		$sqlConditions = array();
		$db = $this->_getDb();
		$whereStatus = $db->quote('Active');

		if(!empty($conditions))
		{
			$conditionsArr = explode(',',$conditions);
			foreach($conditionsArr as $condition)
			{
				if($condition == 'inactive')
					$whereStatus .= ','.$db->quote('Inactive');
				elseif($condition == 'pending')
					$whereStatus .= ','.$db->quote('Pending');
			}
		}

		$sqlConditions[] = 'guild.status IN ('.$whereStatus.')';

		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function scrubGuildLeaderOrOfficer($userId)
	{
		$guilds = $this->getGuilds('inactive,pending');
		
		XenForo_Db::beginTransaction();
		
		foreach($guilds as $guild)
		{
			$dw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild');
			$isOfficer = 0;
			if($guild['guildleader_userid'] == $userId)
			{
				$dw->setExistingData($guild, true);
				$dw->set('guildleader_userid', 2502);
				$dw->save();
			}
			
			$guildOfficers = explode(',', $guild['guildofficer_userids']);

			foreach($guildOfficers as $key=>$guildOfficer){
				if($guildOfficer == $userId)
				{
					$isOfficer = 1;
					unset($guildOfficers[$key]);
				}
			}
			
			if($isOfficer == 1)
			{
				$guildOfficers = implode(',', $guildOfficers);
				$dw->setExistingData($guild, true);
				$dw->set('guildofficer_userids', $guildOfficers);
				$dw->save();
			}
			
		}
		
		XenForo_Db::commit();
		
		return count($guilds);
	}

	public function prepareGuilds(array $guilds, array $viewingUser = null)
	{
		foreach ($guilds as &$guild)
		{
			$guild = $this->prepareGuild($guild, $viewingUser);
		}

		return $guilds;
	}

	public function canCreateGuild(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($viewingUser['is_banned'])
		{
			return false;
		}
		
		if(XenForo_Permission::hasPermission($viewingUser['permissions'], 'moturdrn_gw2guilds', 'createguild'))
			return true;

		return false;
	}

	public function canEditGuild(array $guild, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($viewingUser['is_banned'])
		{
			return false;
		}
		
		if(!$viewingUser['user_id'])
		{
			return false;
		}

		if($this->isGW2GuildsAdmin($error))
			return true;

		if($guild['guildleader_userid'] == $viewingUser['user_id'])
		{
			return true;
		}

		$guildOfficers = explode(',', $guild['guildofficer_userids']);

		foreach($guildOfficers as $guildOfficer){
			if($guildOfficer == $viewingUser['user_id'])
				return true;
		}

		return false;
	}

	public function canJoinGuild(array $guild, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($viewingUser['is_banned'])
		{
			return false;
		}
		
		if(!$viewingUser['user_id'])
		{
			return false;
		}

		if($this->_getMemberModel()->getGuildMember($guild['guildid'],$viewingUser['user_id']))
			return false;

		if(XenForo_Permission::hasPermission($viewingUser['permissions'], 'moturdrn_gw2guilds', 'joinguild'))
			return true;

		return false;
	}

	public function canLeaveGuild(array $guild, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($viewingUser['is_banned'])
		{
			return false;
		}
		
		if(!$viewingUser['user_id'])
		{
			return false;
		}

		if($guild['guildleader_userid'] == $viewingUser['user_id'])
		{
			return false;
		}

		if($this->_getMemberModel()->getGuildMember($guild['guildid'],$viewingUser['user_id']))
			return true;

		return false;
	}

	public function canDeleteGuild(array $guild, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($viewingUser['is_banned'])
		{
			return false;
		}
		
		if(!$viewingUser['user_id'])
		{
			return false;
		}

		if($this->isGW2GuildsAdmin($error))
			return true;

		if($guild['guildleader_userid'] == $viewingUser['user_id'])
		{
			return true;
		}

		return false;
	}

	public function canTransferGuild(array $guild, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($viewingUser['is_banned'])
		{
			return false;
		}
		
		if(!$viewingUser['user_id'])
		{
			return false;
		}

		if(in_array($guild['status'], array('Inactive','Pending (New)', 'Pending (Change)', 'Pending')))
		{
			return false;
		}

		if($this->isGW2GuildsAdmin($error))
			return true;

		if($guild['guildleader_userid'] == $viewingUser['user_id'])
		{
			return true;
		}

		return false;
	}

	public function guildAccessLevel(array $guild, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($viewingUser['is_banned'])
		{
			return false;
		}
		
		if(!$viewingUser['user_id'])
		{
			return 0;
		}

		if($this->isGW2GuildsAdmin($error))
			return 50;

		if($guild['guildleader_userid'] == $viewingUser['user_id'])
		{
			return 40;
		}

		$guildOfficers = explode(',', $guild['guildofficer_userids']);

		foreach($guildOfficers as $guildOfficer){
			if($guildOfficer == $viewingUser['user_id'])
				return 30;
		}

		if($this->_getMemberModel()->getActiveGuildMember($guild['guildid'],$viewingUser['user_id']))
			return 20;

		if($this->_getMemberModel()->getPendingRequestByUserGuild($guild['guildid'],$viewingUser['user_id']))
			return 10;

		return 0;
	}

	public function isGW2GuildsAdmin(&$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if($viewingUser['is_banned'])
		{
			return false;
		}
		
		if(!$viewingUser['user_id'])
		{
			return false;
		}

		if(XenForo_Permission::hasPermission($viewingUser['permissions'], 'moturdrn_gw2guilds', 'admin'))
			return true;

		return false;
	}

	protected function _getMemberModel()
	{
		return $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Member');
	}

	/**
	 * Gets the user group model.
	 *
	 * @return XenForo_Model_UserGroup
	 */
	protected function _getUserGroupModel()
	{
		return $this->getModelFromCache('XenForo_Model_UserGroup');
	}

	/**
	 * Gets the user model.
	 *
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}