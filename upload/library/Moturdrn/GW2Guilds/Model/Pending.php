<?php

class Moturdrn_GW2Guilds_Model_Pending extends XenForo_Model
{
    public function getPendingRequestByUserGuild($guildId, $userId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE guild_id = ? and user_id = ? and pending_type = \'JoinReq\'', array($guildId, $userId));
    }

    public function getPendingRequestById($pendingId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE pending_id = ?', $pendingId);
    }

    public function getPendingRequestActivateByGuildId($guildId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE guild_id = ? and pending_type in (\'NewGuild\',\'ChangeGuild\')', $guildId);
    }

    public function getPendingRequestsByIds(array $pendingIds)
    {
        if (empty($pendingIds))
        {
            return array();
        }

        return $this->fetchAllKeyed('
			SELECT pending.pending_id, pending.user_id, guild.*
			FROM xf_moturdrn_gw2guilds_pending pending
			JOIN xf_moturdrn_gw2guilds_guild guild ON guild.guild_id = pending.guild_id
			WHERE pending_id IN (' . $this->_getDb()->quote($pendingIds) . ')
		', 'pending_id');
    }

    public function getPendingRequestsByGuildId($guildId)
    {
        return $this->fetchAllKeyed($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE pending_type != \'JoinReq\' and guild_id = ' . $this->_getDb()->quote($guildId),0),'pending_id');
    }

    public function getPendingJoinRequestsByGuildId($guildId)
    {
        return $this->fetchAllKeyed($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE pending_type = \'JoinReq\' and guild_id = ' . $this->_getDb()->quote($guildId),0),'pending_id');
    }
	
	public function getPendingRequestsByUser($userId)
	{
		return $this->fetchAllKeyed($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE user_id = ' . $this->_getDb()->quote($userId),0),'pending_id');
	}
	
	public function scrubPendingRequests($userId)
	{
		$requests = $this->getPendingRequestsByUser($userId);
		
		XenForo_Db::beginTransaction();
		
		foreach ($requests as $request)
		{
			$dw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Pending');
			$dw->setExistingData($request, true);
			if($request['pending_type'] == 'JoinReq')
			{
				$dw->delete();
			}else{
				$dw->set('userid', 2502);
				$dw->save();
			}
		}
		
		XenForo_Db::commit();
		
		return count($requests);
	}
}