<?php

class Moturdrn_GW2Guilds_Extend_XenForo_Model_User extends XFCP_Moturdrn_GW2Guilds_Extend_XenForo_Model_User
{
    public function prepareUserFetchOptions(array $fetchOptions)
    {
        $response = parent::prepareUserFetchOptions($fetchOptions);
        extract($response);

        $selectFields .= ',guild_members.guild_ids';
        $joinTables .= '
                    LEFT JOIN (select group_concat(guild_id) as guild_ids, user_id from `xf_moturdrn_gw2guilds_member` group by user_id) AS guild_members ON (guild_members.user_id = user.user_id)';

        return compact('selectFields', 'joinTables');
    }

    public function getFollowingIdsForUser($userId, $limit = 50, $random = true)
    {
        $orderClause = $random ? 'RAND()' : 'user_id';
        $db = $this->_getDb();

        return $db->fetchCol($db->limit('
			SELECT follow_user_id
			FROM xf_user_follow
			WHERE user_id = ?
			ORDER BY ' . $orderClause . '
		', $limit), array($userId));
    }

}
