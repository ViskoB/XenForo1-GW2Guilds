<?php

class Moturdrn_GW2Guilds_Installer
{
    protected static $table = array(
        'xf_moturdrn_gw2guilds_guild' => array(
            'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_moturdrn_gw2guilds_guild` (
                  `guild_id` int(11) NOT NULL AUTO_INCREMENT,
                  `guild_name` varchar(50) NOT NULL,
                  `guild_tag` varchar(4) NOT NULL,
                  `founded` int(11) DEFAULT NULL,
                  `guild_website` varchar(500) NOT NULL,
                  `members` varchar(20) NOT NULL,
                  `guildleader_userid` int(11) NOT NULL,
                  `guildofficer_userids` char(250) NOT NULL,
                  `guild_recruitment` mediumtext NOT NULL,
                  `WvW` varchar(1) NOT NULL DEFAULT \'N\',
                  `PvE` varchar(1) NOT NULL DEFAULT \'N\',
                  `PvP` varchar(1) NOT NULL DEFAULT \'N\',
                  `Casual` varchar(1) NOT NULL DEFAULT \'N\',
                  `status` varchar(20) NOT NULL,
                  `last_modified` int(11) DEFAULT 0,
                  PRIMARY KEY (`guild_id`),
                  UNIQUE KEY `guild_name` (`guild_name`)
                ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
            'dropQuery' => 'DROP TABLE IF EXISTS `xf_moturdrn_gw2guilds_guild`;'
        ),
        'xf_moturdrn_gw2guilds_member' => array(
            'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_moturdrn_gw2guilds_member` (
                  `guild_id` int(11) NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `state` varbinary(25) NOT NULL,
                  `join_date` int(11) NOT NULL,
                  PRIMARY KEY (`guild_id`, `user_id`)
                ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
            'dropQuery' => 'DROP TABLE IF EXISTS `xf_moturdrn_gw2guilds_member`;'
        ),
        'xf_moturdrn_gw2guilds_pending' => array(
            'createQuery' => 'CREATE TABLE IF NOT EXISTS `xf_moturdrn_gw2guilds_pending` (
                  `pending_id` int(11) NOT NULL AUTO_INCREMENT,
                  `guild_id` int(11) NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `pending_type` varchar(50) NOT NULL,
                  PRIMARY KEY (`pending_id`)
                ) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;',
            'dropQuery' => 'DROP TABLE IF EXISTS `xf_moturdrn_gw2guilds_pending`;'
        ),
    );

    public static function install()
    {
        $db = XenForo_Application::get('db');
        $db->query(self::$table['xf_moturdrn_gw2guilds_guild']['createQuery']);
        $db->query(self::$table['xf_moturdrn_gw2guilds_member']['createQuery']);
        $db->query(self::$table['xf_moturdrn_gw2guilds_pending']['createQuery']);

        self::installCustomized();
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');
        $db->query(self::$table['xf_moturdrn_gw2guilds_guild']['dropQuery']);
        $db->query(self::$table['xf_moturdrn_gw2guilds_member']['dropQuery']);
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
				('moturdrn_gw2guilds', 					    'Moturdrn_GW2Guilds')
		");
        $db->query("
			INSERT IGNORE INTO xf_content_type_field
				(content_type, field_name, field_value)
			VALUES
				('moturdrn_gw2guilds', 		'alert_handler_class', 	  'Moturdrn_GW2Guilds_AlertHandler_Pending'),
				('guild',         'sitemap_handler_class',          'Moturdrn_GW2Guilds_SitemapHandler_Guild')
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