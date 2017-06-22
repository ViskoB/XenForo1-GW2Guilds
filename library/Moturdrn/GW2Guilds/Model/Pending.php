<?php

class Moturdrn_GW2Guilds_Model_Pending extends XenForo_Model
{
    public function getPendingRequestByUserGuild($guildId, $userId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE guildid = ? and user_id = ? and pendingtype = \'JoinReq\'', array($guildId, $userId));
    }

    public function getPendingRequestById($pendingId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE pendingid = ?', $pendingId);
    }

    public function getPendingRequestActivateByGuildId($guildId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE guildid = ? and pendingtype in (\'NewGuild\',\'ChangeGuild\')', $guildId);
    }

    public function getPendingRequestsByIds(array $pendingIds)
    {
        if (empty($pendingIds))
        {
            return array();
        }

        return $this->fetchAllKeyed('
			SELECT pending.pendingid, pending.user_id, guild.*
			FROM xf_moturdrn_gw2guilds_pending pending
			JOIN xf_moturdrn_gw2guilds_guilds guild ON guild.guildid = pending.guildid
			WHERE pendingid IN (' . $this->_getDb()->quote($pendingIds) . ')
		', 'pendingid');
    }

    public function getPendingRequestsByGuildId($guildId)
    {
        return $this->fetchAllKeyed($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE pendingtype = \'JoinReq\' and guildid = ' . $this->_getDb()->quote($guildId),0),'pendingid');
    }
	
	public function getPendingRequestsByUser($userId)
	{
		return $this->fetchAllKeyed($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE user_id = ' . $this->_getDb()->quote($userId),0),'pendingid');
	}
	
	public function scrubPendingRequests($userId)
	{
		$requests = $this->getPendingRequestsByUser($userId);
		
		XenForo_Db::beginTransaction();
		
		foreach ($requests as $request)
		{
			$dw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Pending');
			$dw->setExistingData($request, true);
			if($request['pendingtype'] == 'JoinReq')
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