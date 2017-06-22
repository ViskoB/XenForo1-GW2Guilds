<?php

class Moturdrn_GW2Guilds_Installer
{
    protected static $table = array(
        'xf_moturdrn_gw2guilds_guilds' => array(
            'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_moturdrn_gw2guilds_guilds` (
                  `guildid` int(11) NOT NULL AUTO_INCREMENT,
                  `guildname` varchar(50) NOT NULL,
                  `guildtag` varchar(4) NOT NULL,
                  `founded` int(11) DEFAULT NULL,
                  `guildwebsite` varchar(500) NOT NULL,
                  `members` varchar(20) NOT NULL,
                  `guildleader_userid` int(11) NOT NULL,
                  `guildofficer_userids` char(250) NOT NULL,
                  `guildrecruitment` mediumtext NOT NULL,
                  `WvW` varchar(1) NOT NULL DEFAULT \'N\',
                  `PvE` varchar(1) NOT NULL DEFAULT \'N\',
                  `PvP` varchar(1) NOT NULL DEFAULT \'N\',
                  `Casual` varchar(1) NOT NULL DEFAULT \'N\',
                  `status` varchar(20) NOT NULL,
                  `last_modified` int(11) DEFAULT 0,
                  PRIMARY KEY (`guildid`),
                  UNIQUE KEY `guildname` (`guildname`)
                ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
            'dropQuery' => 'DROP TABLE IF EXISTS `xf_moturdrn_gw2guilds_guilds`;'
        ),
        'xf_moturdrn_gw2guilds_members' => array(
            'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_moturdrn_gw2guilds_members` (
                  `guildid` int(11) NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `username` varchar(50) NOT NULL,
                  `state` varbinary(25) NOT NULL,
                  `join_date` int(11) NOT NULL,
                  PRIMARY KEY (`guildid`, `user_id`)
                ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
            'dropQuery' => 'DROP TABLE IF EXISTS `xf_moturdrn_gw2guilds_members`;'
        ),
        'xf_moturdrn_gw2guilds_pending' => array(
            'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_moturdrn_gw2guilds_pending` (
                  `pendingid` int(11) NOT NULL AUTO_INCREMENT,
                  `guildid` int(11) NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `pendingtype` varchar(50) NOT NULL,
                  PRIMARY KEY (`pendingid`)
                ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
            'transferQuery' => 'INSERT IGNORE INTO `xf_moturdrn_gw2guilds_members` (guildid, user_id, username, state, join_date)
                  SELECT p.guildid, p.user_id, u.username, \'pending\', UNIX_TIMESTAMP()
                    FROM `xf_moturdrn_gw2guilds_pending` as p
                    JOIN `xf_user` as u ON p.user_id = u.user_id
                    WHERE p.pendingtype = \'JoinReq\';',
            'dropQuery' => 'DROP TABLE IF EXISTS `xf_moturdrn_gw2guilds_pending`;'
        ),
    );

    public static function install()
    {
        $db = XenForo_Application::get('db');
        $db->query(self::$table['xf_moturdrn_gw2guilds_guilds']['createQuery']);
        $db->query(self::$table['xf_moturdrn_gw2guilds_members']['createQuery']);
        $db->query(self::$table['xf_moturdrn_gw2guilds_pending']['createQuery']);
        $db->query(self::$table['xf_moturdrn_gw2guilds_pending']['transferQuery']);

        self::installCustomized();
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');
        $db->query(self::$table['xf_moturdrn_gw2guilds_guilds']['dropQuery']);
        $db->query(self::$table['xf_moturdrn_gw2guilds_members']['dropQuery']);
        $db->query(self::$table['xf_moturdrn_gw2guilds_pending']['dropQuery']);

        self::uninstallCustomized();
    }

    private static function installCustomized()
    {
        $db = XenForo_Application::get('db');
        $db->query("
			INSERT IGNORE INTO xf_content_type
				(content_type, addon_id)
			VALUES
				('moturdrn_gw2guilds', 					    'Moturdrn_GW2Guilds'),
				('moturdrn_gw2guilds_member', 					    'Moturdrn_GW2Guilds')
		");
        $db->query("
			INSERT IGNORE INTO xf_content_type_field
				(content_type, field_name, field_value)
			VALUES
				('moturdrn_gw2guilds', 		'alert_handler_class', 	  'Moturdrn_GW2Guilds_AlertHandler_Pending'),
				('moturdrn_gw2guilds_member',     'alert_handler_class',    'Moturdrn_GW2Guilds_AlertHandler_Member'),
				('guild',         'sitemap_handler_class',          'Moturdrn_GW2Guilds_SitemapHandler_Guild')
		");
        $db->query("
            INSERT IGNORE INTO xf_moturdrn_gw2guilds_members (guildid, user_id, username, state, join_date)
              SELECT g.guildid, u.user_id, u.username, 'accepted', UNIX_TIMESTAMP()
				FROM xf_user_group_relation as ugr
				JOIN xf_user u ON ugr.user_id = u.user_id
				JOIN xf_moturdrn_gw2guilds_guilds as g on g.user_group_id = ugr.user_group_id;
        ");
    }

    private static function uninstallCustomized()
    {
        $db = XenForo_Application::get('db');

        $contentTypes = array('moturdrn_gw2guilds');
        $contentTypesQuoted = $db->quote($contentTypes);

        XenForo_Db::beginTransaction($db);

        $contentTypeTables = array(
            'xf_attachment',
            'xf_content_type',
            'xf_content_type_field',
            'xf_deletion_log',
            'xf_liked_content',
            'xf_moderation_queue',
            'xf_moderator_log',
            'xf_news_feed',
            'xf_report',
            'xf_user_alert'
        );

        foreach ($contentTypeTables as $table)
        {
            $db->delete($table, 'content_type IN (' . $contentTypesQuoted . ')');
        }

        XenForo_Db::commit($db);

        //XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
        // this will be rebuilt later, but workaround a < 1.2.3 bug where the current cache isn't updated
        XenForo_Application::set('contentTypes',
            XenForo_Model::create('XenForo_Model_ContentType')->getContentTypesForCache()
        );
    }
}