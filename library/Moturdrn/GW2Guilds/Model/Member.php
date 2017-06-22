<?php

class Moturdrn_GW2Guilds_Model_Member extends XenForo_Model
{
    public function getPendingRequestByUserGuild($guildId, $userId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_members WHERE guildid = ? and user_id = ? and state = \'pending\'', array($guildId, $userId));
    }

    public function getPendingRequestActivateByGuildId($guildId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_pending WHERE guildid = ? and pendingtype in (\'NewGuild\',\'ChangeGuild\')', $guildId);
    }

    public function getPendingRequestsByGuildId($guildId)
    {
        return $this->_getDb()->fetchAll($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_members WHERE state = \'pending\' and guildid = ' . $this->_getDb()->quote($guildId),0));
    }

    public function getPendingRequestsByUser($userId)
    {
        return $this->_getDb()->fetchAll($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_members WHERE state = \'pending\' AND user_id = ' . $this->_getDb()->quote($userId),0));
    }

    public function getGuildMember($guildId, $userId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_members WHERE guildid = ' . $this->_getDb()->quote($guildId) . ' AND user_id = ' . $this->_getDb()->quote($userId));
    }

    public function getActiveGuildMember($guildId, $userId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_members WHERE state = \'accepted\' AND guildid = ' . $this->_getDb()->quote($guildId) . ' AND user_id = ' . $this->_getDb()->quote($userId));
    }

    public function getGuildMembers($guildId)
    {
        return $this->_getDb()->fetchAll($this->limitQueryResults(
            'SELECT * FROM xf_moturdrn_gw2guilds_members WHERE state = \'accepted\' AND guildid = ' . $this->_getDb()->quote($guildId),0));
    }

    public function getMemberState($userId, $guildId)
    {
        return $this->_getDb()->fetchRow('SELECT * FROM xf_moturdrn_gw2guilds_members WHERE user_id = ' . $this->_getDb()->quote($userId) . ' AND guildid = ' . $this->_getDb()->quote($guildId));
    }

    public function scrubPendingRequests($userId)
    {
        $requests = $this->getPendingRequestsByUser($userId);

        XenForo_Db::beginTransaction();

        foreach ($requests as $request)
        {
            $dw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
            $dw->setExistingData($request, true);
            $dw->delete();
        }

        XenForo_Db::commit();

        return count($requests);
    }
}