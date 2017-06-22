<?php

class Moturdrn_GW2Guilds_ControllerPublic_Guild extends XenForo_ControllerPublic_Abstract
{

	const SOURCE_GROUP_NO_PERMISSIONS = 2;
	const SOURCE_GROUP_WITH_PERMISSIONS = 306;
	const ACCESS_GROUP_ID = 354;
	const GUILD_LEADER_GROUP_ID = 352;

	public function actionIndex()
	{
		$guildId = $this->_input->filterSingle('guild_id', XenForo_Input::UINT);
		$guildId_Legacy = $this->_input->filterSingle('id', XenForo_Input::UINT);
		$guildName = $this->_input->filterSingle('guild_name', XenForo_Input::STRING);

		$defaultStatus = 'pending';

		$status = $this->_input->filterSingle('status', XenForo_Input::STRING, array('default' => $defaultStatus));

		$guildModel = $this->_getGuildModel();

		if($guildId_Legacy)
		{
			$guild_legacy = $guildModel->getGuildById($guildId_Legacy);
			if($guild_legacy)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds', $guild_legacy),
					''
				);
			}
		}

		if($guildName)
		{
			$guildByName = $guildModel->getGuildByName($guildName);
			if($guildByName)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds', $guildByName),
					''
				);
			}
		}

		if($guildId || $guildName || $guildId_Legacy)
		{
			return $this->actionView();
		}

		$visitor = XenForo_Visitor::getInstance();

		if(!$visitor['user_id'])
		{
			$myGuilds = array('GuildCount' => 0);
		}
		else
		{
			$myGuilds = $this->_getGuildModel()->getGuildsOfUserCount($visitor['user_id']);
		}



		$guilds = $guildModel->getGuilds($status);

		$guilds = $guildModel->prepareGuilds($guilds);

		$canCreate = $guildModel->canCreateGuild($error);

		$pendingGuilds = $guildModel->getPendingGuilds();

		$pendingGuilds = $guildModel->prepareGuilds($pendingGuilds);

		$viewParams = array(
			'guilds'	=> $guilds,
			'canCreate' => $canCreate,
			'pendingGuilds' => $pendingGuilds,
			'isGW2GuildsAdmin' => $guildModel->isGW2GuildsAdmin($error),
			'myGuilds' => $myGuilds['GuildCount'],
		);

		return $this->responseView('Moturdrn_GW2Guilds_ViewPublic_Guild_List', 'moturdrn_gw2guilds_index', $viewParams);
	}

	public function actionMine()
	{
		$guildId = $this->_input->filterSingle('guild_id', XenForo_Input::UINT);
		$guildName = $this->_input->filterSingle('guild_name', XenForo_Input::STRING);

		$defaultStatus = 'pending';

		$status = $this->_input->filterSingle('status', XenForo_Input::STRING, array('default' => $defaultStatus));

		$guildModel = $this->_getGuildModel();

		if($guildName)
		{
			$guildByName = $guildModel->getGuildByName($guildName);
			if($guildByName)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds', $guildByName),
					''
				);
			}
		}

		if($guildId || $guildName)
		{
			return $this->actionView();
		}

		$visitor = XenForo_Visitor::getInstance();

		if(!$visitor['user_id'])
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('guilds', ''),
				'You must be logged in to see this'
			);
		}

		$guilds = $guildModel->getGuildsOfUser($visitor['user_id']);

		$guilds = $guildModel->prepareGuilds($guilds);

		$canCreate = $guildModel->canCreateGuild($error);

		//$pendingGuilds = $guildModel->getPendingGuilds();

		//$pendingGuilds = $guildModel->prepareGuilds($pendingGuilds);

		$viewParams = array(
			'guilds'	=> $guilds,
			'canCreate' => $canCreate,
			'pendingGuilds' => array(),
			'isGW2GuildsAdmin' => $guildModel->isGW2GuildsAdmin($error),
		);

		return $this->responseView('Moturdrn_GW2Guilds_ViewPublic_Guild_List', 'moturdrn_gw2guilds_mine', $viewParams);
	}
	
	public function actionView()
	{
		$guild = $this->_getGuildHelper()->assertGuildValid(null);
		
		$guildId = $guild['guild_id'];
		$visitor = XenForo_Visitor::getInstance();
		
		$guildModel = $this->_getGuildModel();

		$viewParams = array(
			'guild'	=> $guild
		);

		return $this->responseView('Moturdrn_GW2Guilds_ViewPublic_Guild_View', 'moturdrn_gw2guilds_view', $viewParams);
	}

	public function actionViewMini()
	{
		$guildName = $this->_input->filterSingle('guild_name', XenForo_Input::STRING);

		$guildModel = $this->_getGuildModel();

		if($guildName)
		{
			$guildByName = $guildModel->getGuildByName($guildName);
			if($guildByName)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds/ViewMini', $guildByName),
					''
				);
			}
		}

		$guild = $this->_getGuildHelper()->assertGuildValid(null);

		$guildId = $guild['guild_id'];
		$visitor = XenForo_Visitor::getInstance();

		$viewParams = array(
			'guild'	=> $guild
		);

		return $this->responseView('Moturdrn_GW2Guilds_ViewPublic_Guild_ViewMini', 'moturdrn_gw2guilds_view_mini', $viewParams);
	}

	public function actionAdd()
	{
		$guild = array();

		if(!$this->_getGuildModel()->canCreateGuild($error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		return $this->_getGuildAddOrEditResponse($guild);
	}

	public function actionEdit()
	{
		$guildName = $this->_input->filterSingle('guild_name', XenForo_Input::STRING);

		$guildModel = $this->_getGuildModel();

		if($guildName)
		{
			$guildByName = $guildModel->getGuildByName($guildName);
			if($guildByName)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds/Edit', $guildByName),
					''
				);
			}
		}

		$guild = $this->_getGuildHelper()->assertGuildValid(null);

		return $this->_getGuildAddOrEditResponse($guild);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$guildId = $this->_input->filterSingle('guild_id', XenForo_Input::UINT);

		$visitor = XenForo_Visitor::getInstance();

		if ($guildId)
		{
			$guild = $this->_getGuildHelper()->assertGuildValid($guildId);
			$guildId = $guild['guild_id'];

			if (!$this->_getGuildModel()->canEditGuild($guild, $errorPhraseKey))
			{
				// throw error if user try to editing category but don't have permission :-/
				throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
			}
		}
		else {
			$guild = false;
		}

		$guildrecruitment = $this->getHelper('Editor')->getMessageText('guild_recruitment', $this->_input);
		$guildrecruitment = XenForo_Helper_String::autoLinkBbCode($guildrecruitment);

		$members = $this->_input->filterSingle('members', XenForo_Input::STRING);
		if(!in_array($members, array('1-50','51-100','101-150','151-200','201-250','251-300','301-350','351-400','401-450','451-500','500+')))
			throw $this->responseException($this->responseError("You must select a valid number of members from the dropdown list.", 400));

		/**	 @var $writer Moturdrn_GW2Guilds_DataWriter_Guild   **/
		$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild');

		if ($guildId)
		{
			$writer->setExistingData($guildId);
			$oldStatus = $writer->get('status');
			$guildTag = $writer->get('guild_tag');
		}
		else
		{
			$oldStatus = '';
			$guildTag = $this->_input->filterSingle('guild_tag', XenForo_Input::STRING);
		}

		$modified_date = strtotime("now");

		$writer->set('guild_recruitment', $guildrecruitment);
		$writer->set('guild_website', $this->_input->filterSingle('guild_website', XenForo_Input::STRING));
		$writer->set('WvW', $this->_input->filterSingle('WvW', XenForo_Input::STRING));
		$writer->set('PvE', $this->_input->filterSingle('PvE', XenForo_Input::STRING));
		$writer->set('PvP', $this->_input->filterSingle('PvP', XenForo_Input::STRING));
		$writer->set('Casual', $this->_input->filterSingle('Casual', XenForo_Input::STRING));
		$writer->set('members', $this->_input->filterSingle('members', XenForo_Input::STRING));
		$writer->set('last_modified', $modified_date);

		if (!$guildId)
		{
			$writer->set('guild_name', $this->_input->filterSingle('guild_name', XenForo_Input::STRING));
			$writer->set('guild_tag', $this->_input->filterSingle('guild_tag', XenForo_Input::STRING));
			$writer->set('status', "Pending (New)");
			$writer->set('guildleader_userid', $visitor['user_id']);
		}

		$writer->preSave();

		$writer->save();

		$guild = $writer->getMergedData();

		if(!$guildId)
		{
			/**	 @var $writer Moturdrn_GW2Guilds_DataWriter_Guild   **/
			$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');

			$writer->set('guild_id', $guild['guild_id']);
			$writer->set('user_id', $visitor['user_id']);
			$writer->set('state', 'accepted');

			$writer->preSave();

			$writer->save();
		}

		$this->_getMemberModel()->leaderAddOrRemove($guild['guildleader_userid']);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('guilds', ''),
			'New Guild Created'
		);
	}

	public function actionJoin()
	{
		$guild = $this->_getGuildHelper()->assertGuildValid();

		$visitor = XenForo_Visitor::getInstance();

		$user = $this->_getUserModel()->getUserById($visitor['user_id']);

		if(!$guild['canJoin'])
			throw $this->responseException($this->responseError('You have insufficient permissions to join this Guild.', 400));

		/**	 @var $writer Moturdrn_GW2Guilds_DataWriter_Guild   **/
		$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');

		$writer->set('guild_id', $guild['guild_id']);
		$writer->set('user_id', $visitor['user_id']);

		$writer->preSave();

		$writer->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('guilds', $guild)
		);
	}

	public function actionLeave()
	{
		$guild = $this->_getGuildHelper()->assertGuildValid();

		$visitor = XenForo_Visitor::getInstance();

		if(!$guild['canLeave'])
			throw $this->responseException($this->responseError('You cannot leave this Guild.', 400));


		if($existingPending = $this->_getMemberModel()->getGuildMember($guild['guild_id'], $visitor['user_id'])) {
			/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Member   * */
			$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
			$writer->setExistingData($existingPending);
			$writer->delete();
		}

		$guildOfficers = explode(',', $guild['guildofficer_userids']);

		foreach($guildOfficers as $key => $guildOfficer)
		{
			if($guildOfficer == $visitor['user_id'])
				unset($guildOfficers[$key]);
		}

		$guildOfficers = implode(",", $guildOfficers);
		/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Guild   * */
		$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild');

		$writer->setExistingData($guild['guild_id']);

		$writer->set('guildofficer_userids', $guildOfficers);
		$writer->save();
		
		$this->_getMemberModel()->accessAddOrRemove($visitor['user_id']);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('guilds', '')
		);
	}

	public function actionRoster()
	{
		$guildName = $this->_input->filterSingle('guild_name', XenForo_Input::STRING);

		$guildModel = $this->_getGuildModel();

		if($guildName)
		{
			$guildByName = $guildModel->getGuildByName($guildName);
			if($guildByName)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds/Roster', $guildByName),
					''
				);
			}
		}

		$guild = $this->_getGuildHelper()->assertGuildValid();

		$visitor = XenForo_Visitor::getInstance();
		if(!$visitor['user_id']) {
			$showAccNames = false;
		}else{
			$showAccNames = true;
		}

		$membersModel = $this->_getMemberModel();
		$guildMembersList = $membersModel->getGuildMembers($guild['guild_id']);

		$guildOfficers = $guildMembers = array();
		foreach($guildMembersList as $guildMember)
		{
			$guildUser = $this->_getUserModel()->getUserById($guildMember['user_id'], array('join' => 0x01));
			$customFields = unserialize($guildUser['custom_fields']);
			$displayName = $guildUser['username'];
			if($guildUser['user_id'] == $guild['guildleader_userid'])
			{
				$guildLeaderSingle = $guildUser;
				$guildLeaderSingle['GW2AccName'] = $customFields['guild_wars_2_id'];
				$guildLeader[] = $guildLeaderSingle;
			}
			elseif(in_array($guildUser['user_id'],explode(',',$guild['guildofficer_userids'])))
			{
				$guildOfficer = $guildUser;
				$guildOfficer['GW2AccName'] = $customFields['guild_wars_2_id'];
				$guildOfficers[$displayName] = $guildOfficer;
			}
			else
			{
				$guildMember = $guildUser;
				$guildMember['GW2AccName'] = $customFields['guild_wars_2_id'];
				$guildMembers[$displayName] = $guildMember;
			}
		}

		$pendingRequests = $this->_getMemberModel()->getPendingRequestsByGuildId($guild['guild_id']);
		$pendingMembers = array();
		foreach($pendingRequests as $pendingRequest)
		{
			$guildUser = $this->_getUserModel()->getUserById($pendingRequest['user_id'], array('join' => 0x01));
			$customFields = unserialize($guildUser['custom_fields']);
			$displayName = $guildUser['username'];
			$pendingMember = $guildUser;
			$pendingMember['GW2AccName'] = $customFields['guild_wars_2_id'];
			$pendingMembers[$displayName] = $pendingMember;
		}

		$array_lowercase = array_map('strtolower', array_keys($guildOfficers));
		array_multisort(array_keys($array_lowercase), SORT_ASC, SORT_STRING, $guildOfficers);
		$array_lowercase = array_map('strtolower', array_keys($guildMembers));
		array_multisort($array_lowercase, SORT_ASC, SORT_STRING, $guildMembers);
		$array_lowercase = array_map('strtolower', array_keys($pendingMembers));
		array_multisort($array_lowercase, SORT_ASC, SORT_STRING, $pendingMembers);
		$viewParams = array(
			'guild'	=> $guild,
			'leader' => $guildLeader,
			'officers' => $guildOfficers,
			'members' => $guildMembers,
			'pending' => $pendingMembers,
			'showAccNames' => $showAccNames,
			'Mini' => false,
		);

		return $this->responseView('Moturdrn_GW2Guilds_ViewPublic_Guild_View', 'moturdrn_gw2guilds_roster', $viewParams);
	}

	public function actionRosterMini()
	{
		$guild = $this->_getGuildHelper()->assertGuildValid();

		$visitor = XenForo_Visitor::getInstance();
		if(!$visitor['user_id']) {
			$showAccNames = false;
		}else{
			$showAccNames = true;
		}

		$memberModel = $this->_getMemberModel();
		$guildMembersList = $memberModel->getGuildMembers($guild['guild_id']);

		$guildOfficers = $guildMembers = array();

		foreach($guildMembersList as $guildMember)
		{
			$guildUser = $this->_getUserModel()->getUserById($guildMember['user_id'], array('join' => 0x01));
			$customFields = unserialize($guildUser['custom_fields']);
			$displayName = $guildUser['username'];
			if($guildUser['user_id'] == $guild['guildleader_userid'])
			{
				$guildLeaderSingle = $guildUser;
				$guildLeaderSingle['GW2AccName'] = $customFields['guild_wars_2_id'];
				$guildLeader[] = $guildLeaderSingle;
			}
			elseif(in_array($guildUser['user_id'],explode(',',$guild['guildofficer_userids'])))
			{
				$guildOfficer = $guildUser;
				$guildOfficer['GW2AccName'] = $customFields['guild_wars_2_id'];
				$guildOfficers[$displayName] = $guildOfficer;
			}
			else
			{
				$guildMember = $guildUser;
				$guildMember['GW2AccName'] = $customFields['guild_wars_2_id'];
				$guildMembers[$displayName] = $guildMember;
			}

		}

		$pendingRequests = $this->_getMemberModel()->getPendingRequestsByGuildId($guild['guild_id']);
		$pendingMembers = array();
		foreach($pendingRequests as $pendingRequest)
		{
			$guildUser = $this->_getUserModel()->getUserById($pendingRequest['user_id'], array('join' => 0x01));
			$customFields = unserialize($guildUser['custom_fields']);
			$displayName = $guildUser['username'];
			$pendingMember = $guildUser;
			$pendingMember['GW2AccName'] = $customFields['guild_wars_2_id'];
			$pendingMembers[$displayName] = $pendingMember;
		}

		$array_lowercase = array_map('strtolower', array_keys($guildOfficers));
		array_multisort(array_keys($array_lowercase), SORT_ASC, SORT_STRING, $guildOfficers);
		$array_lowercase = array_map('strtolower', array_keys($guildMembers));
		array_multisort($array_lowercase, SORT_ASC, SORT_STRING, $guildMembers);
		$array_lowercase = array_map('strtolower', array_keys($pendingMembers));
		array_multisort($array_lowercase, SORT_ASC, SORT_STRING, $pendingMembers);
		$viewParams = array(
			'guild'	=> $guild,
			'leader' => $guildLeader,
			'officers' => $guildOfficers,
			'members' => $guildMembers,
			'pending' => $pendingMembers,
			'showAccNames' => $showAccNames,
			'Mini' => true,
		);

		return $this->responseView('Moturdrn_GW2Guilds_ViewPublic_Guild_View', 'moturdrn_gw2guilds_roster', $viewParams);
	}

	public function actionMembersRemove()
	{
		$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));

		$guild = $this->_getGuildHelper()->assertGuildValid();

		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		if (!$user = $this->getModelFromCache('XenForo_Model_User')->getUserById($userId))
		{
			return $this->responseNoPermission();
		}

		$guildOfficers = explode(',', $guild['guildofficer_userids']);

		if(in_array($userId, $guildOfficers))
			$requiredAccessLevel = 40;
		else
			$requiredAccessLevel = 30;

		if($guild['accessLevel'] < $requiredAccessLevel)
			throw $this->responseException($this->responseError('You have insufficient permissions to remove this member.', 400));

		if($existingPending = $this->_getMemberModel()->getGuildMember($guild['guild_id'], $userId)) {
			/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Member   * */
			$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
			$writer->setExistingData($existingPending);
			$writer->delete();
		}

		foreach($guildOfficers as $key => $guildOfficer)
		{
			if($guildOfficer == $userId)
				unset($guildOfficers[$key]);
		}

		$guildOfficers = implode(",", $guildOfficers);
		/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Guild   * */
		$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild');

		$writer->setExistingData($guild['guild_id']);

		$writer->set('guildofficer_userids', $guildOfficers);
		$writer->save();
		
		$this->_getMemberModel()->accessAddOrRemove($userId);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('guilds/roster', $guild)
		);
	}

	public function actionMembersPromote()
	{
		$guild = $this->_getGuildHelper()->assertGuildValid();

		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		if (!$user = $this->getModelFromCache('XenForo_Model_User')->getUserById($userId))
		{
			return $this->responseNoPermission();
		}

		$guildOfficers = explode(',', $guild['guildofficer_userids']);

		if(in_array($userId, $guildOfficers))
			throw $this->responseException($this->responseError('You cannot promote an Officer, to do please transfer Guild Leadership', 400));
		else
			$requiredAccessLevel = 30;

		if($guild['accessLevel'] < $requiredAccessLevel)
			throw $this->responseException($this->responseError('You have insufficient permissions to promote this member.', 400));
		
		if($existingPending = $this->_getMemberModel()->getPendingRequestByUserGuild($guild['guild_id'], $userId)) {
			/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Member   * */
			$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
			$writer->setExistingData($existingPending);
			$writer->set('state', 'accepted');
			$writer->save();
		}else{
			$guildOfficers[] = $userId;
			$guildOfficers = implode(",", $guildOfficers);
			/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Guild   * */
			$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild');

			$writer->setExistingData($guild['guild_id']);

			$writer->set('guildofficer_userids', $guildOfficers);
			$writer->save();
		}
		
		$this->_getMemberModel()->accessAddOrRemove($userId);
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('guilds/roster', $guild)
		);
	}

	public function actionMembersDemote()
	{
		//$this->_checkCsrfFromToken($this->_input->filterSingle('t', XenForo_Input::STRING));

		$guild = $this->_getGuildHelper()->assertGuildValid();

		$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		if (!$user = $this->getModelFromCache('XenForo_Model_User')->getUserById($userId))
		{
			return $this->responseNoPermission();
		}

		$guildOfficers = explode(',', $guild['guildofficer_userids']);

		if(!in_array($userId, $guildOfficers))
			throw $this->responseException($this->responseError('You cannot demote a member to pending, please remove them instead', 400));
		else
			$requiredAccessLevel = 40;

		if($guild['accessLevel'] < $requiredAccessLevel)
			throw $this->responseException($this->responseError('You have insufficient permissions to demote this officer.', 400));

		foreach($guildOfficers as $key => $guildOfficer)
		{
			if($guildOfficer == $userId)
				unset($guildOfficers[$key]);
		}

		$guildOfficers = implode(",", $guildOfficers);
		/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Guild   * */
		$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild');

		$writer->setExistingData($guild['guild_id']);

		$writer->set('guildofficer_userids', $guildOfficers);
		$writer->save();
		
		$this->_getMemberModel()->accessAddOrRemove($userId);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('guilds/roster', $guild)
		);
	}

	public function actionTransfer()
	{
		$guildName = $this->_input->filterSingle('guild_name', XenForo_Input::STRING);

		$guildModel = $this->_getGuildModel();

		if($guildName)
		{
			$guildByName = $guildModel->getGuildByName($guildName);
			if($guildByName)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds/Transfer', $guildByName),
					''
				);
			}
		}

		$guild = $this->_getGuildHelper()->assertGuildValid();

		if(!$this->_getGuildModel()->canTransferGuild($guild, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$visitor = XenForo_Visitor::getInstance();

		if ($this->isConfirmedPost())
		{
			$username = $this->_input->filterSingle('username', XenForo_Input::STRING);
			$user = $this->_getUserModel()->getUserByName($username);
			$actionToDo = $this->_input->filterSingle('action', XenForo_Input::STRING);

			if (!$user)
			{
				return $this->responseError(new XenForo_Phrase('requested_member_not_found'), 404);
			}

			if($user['user_id'] == $guild['guildleader_userid'])
				return $this->responseError('You cannot transfer a Guild to the existing Guild Leader');

			/*
			 * Add new leader to the Guild if not already in
			 */
			if(!$this->_getMemberModel()->getGuildMember($guild['guild_id'], $user['user_id']))
			{
				/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Member   * */
				$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
				$writer->set('guild_id', $guild['guild_id']);
				$writer->set('user_id', $user['user_id']);
				$writer->set('state', 'accepted');
				$writer->preSave();
				$writer->save();
			}

			/*
			 * Remove any pending join requests for the new leader to this Guild
			 */
			if($existingPending = $this->_getMemberModel()->getPendingRequestByUserGuild($guild['guild_id'],$user['user_id']))
			{
				/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Member   * */
				$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
				$writer->setExistingData($existingPending);
				$writer->set('state', 'accepted');
				$writer->save();
			}

			$guildOfficers = explode(',', $guild['guildofficer_userids']);

			/*
			 * Remove new leader from officers list if present
			 */
			foreach($guildOfficers as $key => $guildOfficer)
			{
				if($guildOfficer == $user['user_id'])
					unset($guildOfficers[$key]);
			}

			/*
			 * What is happening to the old leader?
			 */
			if($actionToDo == 'Officer')
			{
				/*
				 * Add old leader to the officer list
				 */
				$guildOfficers[] = $guild['guildleader_userid'];
			}
			elseif($actionToDo == 'Remove')
			{
				/*
				 * Remove old leader from the usergroup
				 */
				$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
				$writer->setExistingData(array('guild_id' => $guild['guild_id'], 'user_id' => $visitor['user_id']));
				$writer->delete();
			}

			/*
			 * Set the new Guild Leader
			 */
			$guildOfficers = implode(",", $guildOfficers);
			/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Guild   * */
			$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild');

			$writer->setExistingData($guild['guild_id']);

			$writer->set('guildleader_userid', $user['user_id']);
			$writer->set('guildofficer_userids', $guildOfficers);
			$writer->save();
			
			$this->_getMemberModel()->leaderAddOrRemove($guild['guildleader_userid']);
			$this->_getMemberModel()->accessAddOrRemove($guild['guildleader_userid']);
			
			$this->_getMemberModel()->leaderAddOrRemove($user['user_id']);
			$this->_getMemberModel()->accessAddOrRemove($user['user_id']);

			if($user['user_id'] != $visitor['user_id'])
			{
				$guildLeader = $this->_getUserModel()->getUserById($guild['guildleader_userid']);

				$messageText = <<<HTML
					Hi {$user['username']},
					Leadership of the Guild {$guild['guild_name']} [{$guild['guild_tag']}] has been transferred to you.

					If any of your Guild members wish to join, they may do so by first registering on this forum, then joining via the Guild system.

					By joining the Guild, your members will be listed underneath the Guild Roster.

					{$visitor['username']}
HTML;


				$input = array(
					'recipients' => $user['username'],
					'title' => 'Guild Transferred',
					'open_invite' => 0,
					'conversation_locked' => 0,
					'attachment_hash' => '',
					'message' => $messageText,
				);

				$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $visitor->toArray());
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $input['message']);
				$conversationDw->set('user_id', $visitor['user_id']);
				$conversationDw->set('username', $visitor['username']);
				$conversationDw->set('title', $input['title']);
				$conversationDw->set('open_invite', $input['open_invite']);
				$conversationDw->set('conversation_open', $input['conversation_locked'] ? 0 : 1);
				$conversationDw->addRecipientUserNames(explode(',', $input['recipients'])); // checks permissions

				$messageDw = $conversationDw->getFirstMessageDw();
				$messageDw->set('message', $input['message']);
				$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

				$conversationDw->preSave();

				$conversationDw->save();
				$conversation = $conversationDw->getMergedData();

				$this->getModelFromCache('XenForo_Model_Draft')->deleteDraft('conversation');

				$this->getModelFromCache('XenForo_Model_Conversation')->markConversationAsRead(
					$conversation['conversation_id'], XenForo_Visitor::getUserId(), XenForo_Application::$time
				);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('guilds/roster', $guild)
			);
		}
		else
		{
			$viewParams = array(
				'guild' => $guild,
			);

			return $this->responseView('Moturdrn_GW2Guilds_ViewPublic_Guild_Transfer', 'moturdrn_gw2guilds_transfer', $viewParams);
		}
	}

	public function actionMembersAssign()
	{
		$this->_assertPostOnly();
		$guild = $this->_getGuildHelper()->assertGuildValid();

		$username = $this->_input->filterSingle('username', XenForo_Input::STRING);
		$user = $this->_getUserModel()->getUserByName($username);

		if (!$user)
		{
			return $this->responseError(new XenForo_Phrase('requested_member_not_found'), 404);
		}

		if(!$guild['canEdit'])
			return $this->responseError('You cannot add members to the roster', 400);

		if($this->_getMemberModel()->getGuildMember($guild['guild_id'], $user['user_id']))
			return $this->responseError('Member already in Guild');

		if($existingPending = $this->_getMemberModel()->getPendingRequestByUserGuild($guild['guild_id'],$user['user_id']))
			return $this->responseError('Member already applied to Guild, reject or approve their request');

		/**     @var $writer Moturdrn_GW2Guilds_DataWriter_Member   * */
		$writer = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Member');
		$writer->set('guild_id', $guild['guild_id']);
		$writer->set('user_id', $user['user_id']);
		$writer->set('username', $user['username']);
		$writer->set('state', 'accepted');
		$writer->preSave();
		$writer->save();
		
		$this->_getMemberModel()->accessAddOrRemove($user['user_id']);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('guilds/roster', $guild)
		);
	}

	public function actionActivate()
	{
		$guildName = $this->_input->filterSingle('guild_name', XenForo_Input::STRING);

		$guildModel = $this->_getGuildModel();

		if($guildName)
		{
			$guildByName = $guildModel->getGuildByName($guildName);
			if($guildByName)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds/Activate', $guildByName),
					''
				);
			}
		}

		ini_set('max_execution_time', 300);
		$guild = $this->_getGuildHelper()->assertGuildValid();

		if(!$this->_getGuildModel()->canEditGuild($guild, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$visitor = XenForo_Visitor::getInstance();

		if(!$this->_getGuildModel()->isGW2Guildsadmin($error))
		{
			$dw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild', XenForo_DataWriter::ERROR_SILENT);
			$dw->setExistingData($guild['guild_id']);
			$dw->set('status', 'Pending (Change)');
			$dw->save();
		}
		else {
			$dw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild', XenForo_DataWriter::ERROR_SILENT);
			$dw->setExistingData($guild['guild_id']);
			$dw->set('status', 'Active');
			$dw->save();

			$this->_getMemberModel()->leaderAddOrRemove($guild['guildleader_userid']);

			$memberModel = $this->_getMemberModel();
			$guildMembersList = $memberModel->getGuildMembers($guild['guild_id']);

			foreach($guildMembersList as $guildMemberId)
			{
				$guildUser = $this->_getUserModel()->getUserById($guildMemberId['user_id'], array('join' => 0x01));
				$this->_getMemberModel()->accessAddOrRemove($guildUser['user_id']);
			}

			if($guild['status'] == 'Pending (New)' && $guild['guildleader_userid'] != $visitor['user_id'])
			{
				$guildLeader = $this->_getUserModel()->getUserById($guild['guildleader_userid']);

				$messageText = <<<HTML
					Hi {$guildLeader['username']},
					Your registration of the Guild {$guild['guild_name']} [{$guild['guild_tag']}] has been approved.

					If any of your Guild members wish to join, they may do so by first registering on this forum, then joining via the Guild system.

					By joining the Guild, your members will be listed underneath the Guild Roster.

					{$visitor['username']}
HTML;


				$input = array(
					'recipients' => $guildLeader['username'],
					'title' => 'Guild Approved',
					'open_invite' => 0,
					'conversation_locked' => 0,
					'attachment_hash' => '',
					'message' => $messageText,
				);

				$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $visitor->toArray());
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $input['message']);
				$conversationDw->set('user_id', $visitor['user_id']);
				$conversationDw->set('username', $visitor['username']);
				$conversationDw->set('title', $input['title']);
				$conversationDw->set('open_invite', $input['open_invite']);
				$conversationDw->set('conversation_open', $input['conversation_locked'] ? 0 : 1);
				$conversationDw->addRecipientUserNames(explode(',', $input['recipients'])); // checks permissions

				$messageDw = $conversationDw->getFirstMessageDw();
				$messageDw->set('message', $input['message']);
				$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

				$conversationDw->preSave();

				$conversationDw->save();
				$conversation = $conversationDw->getMergedData();

				$this->getModelFromCache('XenForo_Model_Draft')->deleteDraft('conversation');

				$this->getModelFromCache('XenForo_Model_Conversation')->markConversationAsRead(
					$conversation['conversation_id'], XenForo_Visitor::getUserId(), XenForo_Application::$time
				);
			}
			elseif($guild['status'] == 'Pending (Changed)' && $guild['guildleader_userid'] != $visitor['user_id'])
			{
				$guildLeader = $this->_getUserModel()->getUserById($guild['guildleader_userid']);

				$messageText = <<<HTML
					Hi {$guildLeader['username']},
					The request to mark the Guild {$guild['guild_name']} [{$guild['guild_tag']}] as active has been approved.

					If any of your Guild members wish to join, they may do so by first registering on this forum, then joining via the Guild system.

					By joining the Guild, your members will be listed underneath the Guild Roster.

					{$visitor['username']}
HTML;


				$input = array(
					'recipients' => $guildLeader['username'],
					'title' => 'Guild Request Approved',
					'open_invite' => 0,
					'conversation_locked' => 0,
					'attachment_hash' => '',
					'message' => $messageText,
				);

				$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $visitor->toArray());
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $input['message']);
				$conversationDw->set('user_id', $visitor['user_id']);
				$conversationDw->set('username', $visitor['username']);
				$conversationDw->set('title', $input['title']);
				$conversationDw->set('open_invite', $input['open_invite']);
				$conversationDw->set('conversation_open', $input['conversation_locked'] ? 0 : 1);
				$conversationDw->addRecipientUserNames(explode(',', $input['recipients'])); // checks permissions

				$messageDw = $conversationDw->getFirstMessageDw();
				$messageDw->set('message', $input['message']);
				$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

				$conversationDw->preSave();

				$conversationDw->save();
				$conversation = $conversationDw->getMergedData();

				$this->getModelFromCache('XenForo_Model_Draft')->deleteDraft('conversation');

				$this->getModelFromCache('XenForo_Model_Conversation')->markConversationAsRead(
					$conversation['conversation_id'], XenForo_Visitor::getUserId(), XenForo_Application::$time
				);
			}
			elseif($guild['guildleader_userid'] != $visitor['user_id'])
			{
				$guildLeader = $this->_getUserModel()->getUserById($guild['guildleader_userid']);

				$messageText = <<<HTML
					Hi {$guildLeader['username']},
					The Guild {$guild['guild_name']} [{$guild['guild_tag']}] has been marked as Active.

					If any of your Guild members wish to join, they may do so by first registering on this forum, then joining via the Guild system.

					By joining the Guild, your members will be listed underneath the Guild Roster.

					{$visitor['username']}
HTML;


				$input = array(
					'recipients' => $guildLeader['username'],
					'title' => 'Guild Approved',
					'open_invite' => 0,
					'conversation_locked' => 0,
					'attachment_hash' => '',
					'message' => $messageText,
				);

				$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $visitor->toArray());
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $input['message']);
				$conversationDw->set('user_id', $visitor['user_id']);
				$conversationDw->set('username', $visitor['username']);
				$conversationDw->set('title', $input['title']);
				$conversationDw->set('open_invite', $input['open_invite']);
				$conversationDw->set('conversation_open', $input['conversation_locked'] ? 0 : 1);
				$conversationDw->addRecipientUserNames(explode(',', $input['recipients'])); // checks permissions

				$messageDw = $conversationDw->getFirstMessageDw();
				$messageDw->set('message', $input['message']);
				$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

				$conversationDw->preSave();

				$conversationDw->save();
				$conversation = $conversationDw->getMergedData();

				$this->getModelFromCache('XenForo_Model_Draft')->deleteDraft('conversation');

				$this->getModelFromCache('XenForo_Model_Conversation')->markConversationAsRead(
					$conversation['conversation_id'], XenForo_Visitor::getUserId(), XenForo_Application::$time
				);
			}
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('guilds','')
		);

	}

	public function actionInactivate()
	{
		$guildName = $this->_input->filterSingle('guild_name', XenForo_Input::STRING);

		$guildModel = $this->_getGuildModel();

		if($guildName)
		{
			$guildByName = $guildModel->getGuildByName($guildName);
			if($guildByName)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds/Inactivate', $guildByName),
					''
				);
			}
		}

		ini_set('max_execution_time', 300);
		$guild = $this->_getGuildHelper()->assertGuildValid();

		if(!$this->_getGuildModel()->canEditGuild($guild, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$visitor = XenForo_Visitor::getInstance();

		$dw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild', XenForo_DataWriter::ERROR_SILENT);
		$dw->setExistingData($guild['guild_id']);
		$dw->set('status', 'Inactive');
		$dw->save();

		$this->_getMemberModel()->leaderAddOrRemove($guild['guildleader_userid']);
		
		$memberModel = $this->_getMemberModel();
		$guildMembersList = $memberModel->getGuildMembers($guild['guild_id']);
		foreach($guildMembersList as $guildMember)
		{
			$guildUser = $this->_getUserModel()->getUserById($guildMember['user_id'], array('join' => 0x01));
			$this->_getMemberModel()->accessAddOrRemove($guildUser['user_id']);
		}

		if($guild['status'] == 'Pending (Changed)' && $guild['guildleader_userid'] != $visitor['user_id'])
		{
			$guildLeader = $this->_getUserModel()->getUserById($guild['guildleader_userid']);

			$messageText = <<<HTML
					Hi {$guildLeader['username']},
					Your request to make the Guild {$guild['guild_name']} [{$guild['guild_tag']}] active has been rejected.

					This may be due to the Guild not being active on Gunnar's Hold, or for other reasons.

					{$visitor['username']}
HTML;


			$input = array(
				'recipients' => $guildLeader['username'],
				'title' => 'Guild Request rejected',
				'open_invite' => 0,
				'conversation_locked' => 0,
				'attachment_hash' => '',
				'message' => $messageText,
			);

			$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
			$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $visitor->toArray());
			$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $input['message']);
			$conversationDw->set('user_id', $visitor['user_id']);
			$conversationDw->set('username', $visitor['username']);
			$conversationDw->set('title', $input['title']);
			$conversationDw->set('open_invite', $input['open_invite']);
			$conversationDw->set('conversation_open', $input['conversation_locked'] ? 0 : 1);
			$conversationDw->addRecipientUserNames(explode(',', $input['recipients'])); // checks permissions

			$messageDw = $conversationDw->getFirstMessageDw();
			$messageDw->set('message', $input['message']);
			$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

			$conversationDw->preSave();

			$conversationDw->save();
			$conversation = $conversationDw->getMergedData();

			$this->getModelFromCache('XenForo_Model_Draft')->deleteDraft('conversation');

			$this->getModelFromCache('XenForo_Model_Conversation')->markConversationAsRead(
				$conversation['conversation_id'], XenForo_Visitor::getUserId(), XenForo_Application::$time
			);
		}
		elseif($guild['guildleader_userid'] != $visitor['user_id'])
		{
			$guildLeader = $this->_getUserModel()->getUserById($guild['guildleader_userid']);

			$messageText = <<<HTML
					Hi {$guildLeader['username']},
					The Guild {$guild['guild_name']} [{$guild['guild_tag']}] has been marked as Inactive.

					This may be due to the Guild not being active on Gunnar's Hold, or for other reasons.

					{$visitor['username']}
HTML;


			$input = array(
				'recipients' => $guildLeader['username'],
				'title' => 'Guild Marked Inactive',
				'open_invite' => 0,
				'conversation_locked' => 0,
				'attachment_hash' => '',
				'message' => $messageText,
			);

			$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
			$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $visitor->toArray());
			$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $input['message']);
			$conversationDw->set('user_id', $visitor['user_id']);
			$conversationDw->set('username', $visitor['username']);
			$conversationDw->set('title', $input['title']);
			$conversationDw->set('open_invite', $input['open_invite']);
			$conversationDw->set('conversation_open', $input['conversation_locked'] ? 0 : 1);
			$conversationDw->addRecipientUserNames(explode(',', $input['recipients'])); // checks permissions

			$messageDw = $conversationDw->getFirstMessageDw();
			$messageDw->set('message', $input['message']);
			$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

			$conversationDw->preSave();

			$conversationDw->save();
			$conversation = $conversationDw->getMergedData();

			$this->getModelFromCache('XenForo_Model_Draft')->deleteDraft('conversation');

			$this->getModelFromCache('XenForo_Model_Conversation')->markConversationAsRead(
				$conversation['conversation_id'], XenForo_Visitor::getUserId(), XenForo_Application::$time
			);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('guilds', '')
		);
	}

	public function actionDelete()
	{
		$guildName = $this->_input->filterSingle('guild_name', XenForo_Input::STRING);

		$guildModel = $this->_getGuildModel();

		if($guildName)
		{
			$guildByName = $guildModel->getGuildByName($guildName);
			if($guildByName)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_CANONICAL_PERMANENT,
					XenForo_Link::buildPublicLink('guilds/Delete', $guildByName),
					''
				);
			}
		}

		ini_set('max_execution_time', 300);
		$guild = $this->_getGuildHelper()->assertGuildValid();

		if (!$this->_getGuildModel()->canDeleteGuild($guild, $key))
		{
			throw $this->getErrorOrNoPermissionResponseException($key);
		}

		$visitor = XenForo_Visitor::getInstance();

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Moturdrn_GW2Guilds_DataWriter_Guild');
			$dw->setExistingData($guild['guild_id']);

			$dw->delete();

			if($guild['status'] == 'Pending (New)' && $guild['guildleader_userid'] != $visitor['user_id'])
			{
				$guildLeader = $this->_getUserModel()->getUserById($guild['guildleader_userid']);

				$messageText = <<<HTML
					Hi {$guildLeader['username']},
					Your registration of the Guild {$guild['guild_name']} [{$guild['guild_tag']}] has been rejected.

					This may be due to the Guild not being active on Gunnar's Hold, or for other reasons.

					{$visitor['username']}
HTML;


				$input = array(
					'recipients' => $guildLeader['username'],
					'title' => 'Guild Rejected',
					'open_invite' => 0,
					'conversation_locked' => 0,
					'attachment_hash' => '',
					'message' => $messageText,
				);

				$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $visitor->toArray());
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $input['message']);
				$conversationDw->set('user_id', $visitor['user_id']);
				$conversationDw->set('username', $visitor['username']);
				$conversationDw->set('title', $input['title']);
				$conversationDw->set('open_invite', $input['open_invite']);
				$conversationDw->set('conversation_open', $input['conversation_locked'] ? 0 : 1);
				$conversationDw->addRecipientUserNames(explode(',', $input['recipients'])); // checks permissions

				$messageDw = $conversationDw->getFirstMessageDw();
				$messageDw->set('message', $input['message']);
				$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

				$conversationDw->preSave();

				$conversationDw->save();
				$conversation = $conversationDw->getMergedData();

				$this->getModelFromCache('XenForo_Model_Draft')->deleteDraft('conversation');

				$this->getModelFromCache('XenForo_Model_Conversation')->markConversationAsRead(
					$conversation['conversation_id'], XenForo_Visitor::getUserId(), XenForo_Application::$time
				);
			}
			elseif($guild['guildleader_userid'] != $visitor['user_id'])
			{
				$guildLeader = $this->_getUserModel()->getUserById($guild['guildleader_userid']);

				$messageText = <<<HTML
					Hi {$guildLeader['username']},
					The Guild {$guild['guild_name']} [{$guild['guild_tag']}] has been deleted from our database.

					This may be due to the Guild no longer being active on Gunnar's Hold, or for other reasons.

					{$visitor['username']}
HTML;


				$input = array(
					'recipients' => $guildLeader['username'],
					'title' => 'Guild Deleted',
					'open_invite' => 0,
					'conversation_locked' => 0,
					'attachment_hash' => '',
					'message' => $messageText,
				);

				$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $visitor->toArray());
				$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_MESSAGE, $input['message']);
				$conversationDw->set('user_id', $visitor['user_id']);
				$conversationDw->set('username', $visitor['username']);
				$conversationDw->set('title', $input['title']);
				$conversationDw->set('open_invite', $input['open_invite']);
				$conversationDw->set('conversation_open', $input['conversation_locked'] ? 0 : 1);
				$conversationDw->addRecipientUserNames(explode(',', $input['recipients'])); // checks permissions

				$messageDw = $conversationDw->getFirstMessageDw();
				$messageDw->set('message', $input['message']);
				$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_ATTACHMENT_HASH, $input['attachment_hash']);

				$conversationDw->preSave();

				$conversationDw->save();
				$conversation = $conversationDw->getMergedData();

				$this->getModelFromCache('XenForo_Model_Draft')->deleteDraft('conversation');

				$this->getModelFromCache('XenForo_Model_Conversation')->markConversationAsRead(
					$conversation['conversation_id'], XenForo_Visitor::getUserId(), XenForo_Application::$time
				);
			}

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('guilds', ''),
				'Guild Removed'
			);
		}
		else
		{
			$viewParams = array(
				'guild' => $guild,
			);

			return $this->responseView('Moturdrn_GW2Guilds_ViewPublic_Guild_Delete', 'moturdrn_gw2guilds_delete', $viewParams);
		}
	}

	/**
	 * Session activity details.
	 * @see XenForo_Controller::getSessionActivityDetailsForList()
	 */
	public static function getSessionActivityDetailsForList(array $activities)
	{
		$guildIds = array();
		$guildNames = array();
		foreach ($activities as $activity)
		{
			if (!empty($activity['params']['guild_id']))
			{
				$guildIds[$activity['params']['guild_id']] = intval($activity['params']['guild_id']);
			}

			if (!empty($activity['params']['guild_name']))
			{
				$guildNames[$activity['params']['guild_name']] = $activity['params']['guild_name'];
			}
		}

		/** @var Moturdrn_GW2Guilds_Model_Guild $guildModel */
		$guildModel = XenForo_Model::create('Moturdrn_GW2Guilds_Model_Guild');
		if ($guildNames)
		{
			$guildNames = $guildModel->getGuildsIdsFromNames($guildNames);
			foreach ($guildNames as $guildID => $guild)
			{
				$guildIds[$guildID] = $guild;
			}
		}

		$guildData = array();
		if ($guildIds)
		{
			$guilds = $guildModel->getGuildsByIds($guildIds);

			foreach ($guilds as $guild)
			{
				$guildData[$guild['guild_id']] = array(
					'guild_name' =>  $guild['guild_name'],
					'url' => XenForo_Link::buildPublicLink('guilds', $guild)
				);
			}
		}

		$output = array();
		foreach ($activities as $key => $activity)
		{
			$guild = false;
			$list = false;
			if (!empty($activity['params']['guild_id']))
			{
				$guildID = $activity['params']['guild_id'];
				if (isset($guildData[$guildID]))
				{
					$guild = $guildData[$guildID];
				}
			}
			else if (!empty($activity['params']['guild_name']))
			{
				$guildname = $activity['params']['guild_name'];
				if (isset($guildNames[$guildname]))
				{
					$guildID = $guildNames[$guildname];
					if (isset($guildData[$guildID]))
					{
						$guild = $guildData[$guildID];
					}
				}
			}
			else
			{
				$list = true;
			}

			if(!empty($activity['controller_action']))
			{
				switch($activity['controller_action'])
				{
					case 'Roster':
						$action = 'Viewing Roster';
						break;
					case 'Add':
						$action = 'Adding Guild';
						$guild['guild_name'] = '';
						$guild['url'] = '';
						break;
					case 'Edit':
						$action = 'Modifying Guild';
						break;
					default:
						$action = 'Viewing Guild';
				}
			}

			if ($guild)
			{
				$output[$key] = array(
					$action,
					$guild['guild_name'],
					$guild['url'],
					false
				);
			}
			else
			{
				$output[$key] = ($list) ? 'Viewing Guild List' : 'Viewing Guilds';
			}
		}

		return $output;
	}

    /**
     * @return Moturdrn_GW2Guilds_Model_Guild
     */
	protected function _getGuildModel()
	{
	    /** @var Moturdrn_GW2Guilds_Model_Guild $model */
		$model = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Guild');
		return $model;
	}

    /**
     * @return Moturdrn_GW2Guilds_Model_Member
     */
	protected function _getMemberModel()
	{
	    /** @var Moturdrn_GW2Guilds_Model_Member $model */
		$model = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Member');
		return $model;
	}
	
	protected function _getGuildHelper()
	{
		return $this->getHelper('Moturdrn_GW2Guilds_ControllerHelper_Guild');
	}

    /**
     * @return Moturdrn_GW2Guilds_Model_Pending
     */
	protected function _getPendingModel()
	{
	    /** @var Moturdrn_GW2Guilds_Model_Pending $model */
		$model = $this->getModelFromCache('Moturdrn_GW2Guilds_Model_Pending');
		return $model;
	}

	protected function _getGuildAddOrEditResponse(array $guild)
	{

		if($guild && !$this->_getGuildModel()->canEditGuild($guild, $error))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}
		elseif(!$this->_getGuildModel()->canCreateGuild($errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($error);
		}

		$viewParams = array(
			'guild' => $guild
		);

		return $this->responseView('Moturdrn_GW2Guilds_ViewPublic_Guild_Add', 'moturdrn_gw2guilds_add', $viewParams);
	}

	/**
	 * Gets the user group model.
	 *
	 * @return XenForo_Model_UserGroup
	 */
	protected function _getUserGroupModel()
	{
	    /** @var XenForo_Model_UserGroup $model */
		$model = $this->getModelFromCache('XenForo_Model_UserGroup');
		return $model;
	}

	/**
	 * Gets the user model.
	 *
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
	    /** @var XenForo_Model_User $model */
		$model = $this->getModelFromCache('XenForo_Model_User');
		return $model;
	}

	/**
	 * Get permission model.
	 *
	 * @return XenForo_Model_Permission
	 */
	protected function _getPermissionModel()
	{
	    /** @var XenForo_Model_Permission $model */
	    $model = $this->getModelFromCache('XenForo_Model_Permission');
	    return $model;
	}

	protected function _getNodeModel()
	{
		return $this->getModelFromCache('XenForo_Model_Node');
	}

	protected function getWrapper($selectedGroup, $selectedLink, XenForo_ControllerResponse_View $subView)
	{
		$guildHelper = new Moturdrn_GW2Guilds_ControllerHelper_Guild($this);
		return $guildHelper->getWrapper($selectedGroup, $selectedLink, $subView);
	}
}