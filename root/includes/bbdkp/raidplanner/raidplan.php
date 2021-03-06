<?php
/**
*
* @author Sajaki
* @package bbDKP Raidplanner
* @copyright (c) 2011 Sajaki
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @version 0.10.0
*/
namespace bbdkp\raidplanner;
use bbdkp\raidplanner\raidmessenger;
use bbdkp\controller\raids\RaidController;
use bbdkp\controller\raids\Raiddetail;
use bbdkp\controller\points\PointsController;
/**
 * @ignore
 */
if ( !defined('IN_PHPBB') OR !defined('IN_BBDKP') )
{
	exit;
}

/**
 * implements a raid plan
 *
 */
class Raidplan
{
	/**
	 * pk
	 * raidplan_id
	 *
	 * @var int
	 */
    protected $id;
    protected $eventlist;
	
	/**
	 * raidplan event type 
	 * etype_id
	 *
	 * @var int
	 */
    protected $event_type;
	
	/**
	 * Invite time timestamp
	 * raidplan_invite_time
	 *
	 * @var int
	 */
    protected $invite_time;
	
	/**
	 * Start time timestamp
	 * raidplan_start_time
	 *
	 * @var int
	 */
    protected $start_time;
	
	/**
	 * endtime timestamp
	 * raidplan_end_time
	 *
	 * @var int
	 */
    protected $end_time;
	
	/**
	 * 1 if allday event, 0 if timed event
	 * raidplan_all_day
	 *
	 * @var int
	 */
    protected $all_day;
	
	/**
	 * day of alldayevent (dd-mm-yyyy)
	 * raidplan_day
	 *
	 * @var string
	 */
    protected $day;

	/**
	 * one line subject
	 * raidplan_subject VARCHAR 255
	 *
	 * @var string
	 */
	public $subject;
	
	/**
	 * raidplan_body MEDIUMTEXT
	 * 
	 * @var unknown_type
	 */
    protected $body;
    protected $bbcode = array();
	
	/**
	 * poster_id
	 *
	 * @var unknown_type
	 */
    protected $poster;

	/**
	 * access level 0 = personal, 1 = groups, 2 = all 
	 * default to 2
	 * @var int
	 */
    protected $accesslevel = 2;


    protected $group_id;
    protected $group_id_list;
	
	/**
	 * array of possible roles
	 *
	 * @var array
	 */
    protected $roles= array();

	/**
	 * array of signoffs
	 *
	 * @var array
	 */
    protected $signoffs= array();

	/**
	 * raidteam int
	 *
	 * @var int
	 */
    protected $raidteam;
	
	
	/**
	 * Team name 
	 *
	 * @var string
	 */
    protected $raidteamname;
	
	/**
	 * array of raid roles, subarray of signups per role
	 *
	 * @var array
	 */
    protected $raidroles= array();

	/**
	 * aray of signups
	 *
	 * @var array
	 */
    protected $signups =array();
	
	/**
	 * all my eligible chars
	 *
	 * @var array
	 */
    protected $mychars = array();
	
	/**
	 * can user see raidplan ?
	 *
	 * @var boolean
	 */
    protected $auth_cansee = false;
    protected $auth_canedit = false;
    protected $auth_candelete = false;
    protected $auth_canadd = false;
    protected $auth_canaddsignups = false;
    protected $auth_addrecurring = false;

	// if raidplan is recurring then id > 0
    protected $recurr_id = 0;
	
	/**
	 * url of the poster
	 *
	 * @var string
	 */
    protected $poster_url = '';
	
	/**
	 * string representing invited groups
	 *
	 * @var string
	 */
    protected $invite_list = '';
		
	/**
	 * signups allowed ?
	 *
	 * @var boolean
	 */
    protected $signups_allowed;
	
	/**
	 * If raid is locked due to authorisation ?
	 *
	 * @var boolean
	 */
	public $locked;
	
	/**
	 * if raid signups are frozen ?
	 */
    protected $frozen;
	
	/**
	 * If user has no characters bound then set nochar to true
	 *
	 * @var boolean
	 */
    protected $nochar;
	
	/**
	 * If you currently signed up as available
	 *
	 * @var boolean
	 */
    protected $signed_up;
	
	/**
	 * If you currently signed up as maybe
	 *
	 * @var boolean
	 */
    protected $signed_up_maybe;
	
	
	/**
	 * If you are currently signed off
	 *
	 * @var boolean
	 */
    protected $signed_off;
	
	/**
	 * If you currently confirmed
	 *
	 * @var boolean
	 */
    protected $confirmed;
	
	/**
	 * bbdkp raid_id 
	 *
	 * @var unknown_type
	 */
    protected $raid_id;
	
	/**
	 * redirect link for raid
	 *
	 * @var string
	 */
	protected $link;
	
	/**
	 * constructor
	 *
	 * @param int $id
	 */
	function __construct($id=0)
	{
		global $phpEx, $phpbb_root_path;
		
		if (!class_exists('\bbdkp\raidplanner\rpevents'))
		{
			include($phpbb_root_path . 'includes/bbdkp/raidplanner/rpevents.' . $phpEx);
		}
		$this->eventlist= new rpevents();
		
		if($id !=0)
		{
			$this->id=$id;
			// fetch raid object from db
			$this->make_obj();
		}
		
	}

    /**
     * raidplan class property getter
     * @param string $fieldName
     */
    public function __get($fieldName)
    {
        global $user;

        if (property_exists($this, $fieldName))
        {
            return $this->$fieldName;
        }
        else
        {
            trigger_error($user->lang['ERROR'] . '  '. $fieldName, E_USER_WARNING);
        }
    }

    /**
     * raidplan class property setter
     * @param string $property
     * @param string $value
     */
    public function __set($property, $value)
    {
        global $user;
        switch ($property)
        {
            case 'xxx':
                // is readonly
                break;
            default:
                if (property_exists($this, $property))
                {
                    $this->$property = $value;
                }
                else
                {
                    trigger_error($user->lang['ERROR'] . '  '. $property, E_USER_WARNING);
                }
        }
    }

	/**
	 * make raidplan object for display
	 * 
	 */
	public function make_obj()
	{
			global $db, $user, $config, $phpEx, $phpbb_root_path, $db;
			
			// reinitialise all properties except eventlist and id
			$this->event_type = 0;
			$this->invite_time = 0;
			$this->start_time = 0;
			$this->end_time = 0;
			$this->all_day=0;
			$this->day='';
			$this->subject='';
			$this->body='';
			$this->bbcode = array();
			$this->poster=0;
			$this->accesslevel=2;
			$this->group_id=0;
			$this->raidteam=0;
			$this->raidteamname="";
			$this->group_id_list=array();
			$this->roles= array();
			$this->signoffs= array();
			$this->raidroles= array();
			$this->signups =array();
			$this->mychars = array();
			$this->auth_cansee = false;
			$this->auth_canedit = false;
			$this->auth_candelete = false;
			$this->auth_canadd = false;
			$this->auth_canaddsignups = false;
			$this->auth_addrecurring = false;
			$this->recurr_id = 0;
			$this->poster_url = '';
			$this->invite_list = '';
			$this->signups_allowed=false;
			$this->locked= true;
			$this->frozen= true;
			$this->confirmed=false;
			$this->nochar= true;
			$this->signed_up=false;
			$this->signed_off=false;
			$this->signed_up_maybe=false;
			$this->raid_id=0;
			$this->link='';
			
			// populate properties
			$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . ' WHERE raidplan_id = '. (int) $this->id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if(!$row)
			{
				trigger_error( 'INVALID_RAIDPLAN' );
			}

			$this->link = generate_board_url() . "/dkp.$phpEx?page=planner&view=raidplan&raidplanid=" . $this->id;
			$this->raid_id = $row['raid_id'];
				
			// check access
			$this->accesslevel=$row['raidplan_access_level'];
			$this->poster=$row['poster_id'];
			$this->group_id=$row['group_id'];
			$this->group_id_list=$row['group_id_list'];

			$this->checkauth();
			if(!$this->auth_cansee)
			{
				trigger_error( 'NOT_AUTHORISED' );
			}
			$this->checkauth_canedit();
			$this->checkauth_candelete();
			$this->checkauth_canadd();
			
			// now go add raid properties
			$this->event_type= $row['etype_id'];
			$this->invite_time=$row['raidplan_invite_time'];
			$this->start_time=$row['raidplan_start_time'];
			$this->end_time=$row['raidplan_end_time'];
			$this->all_day=$row['raidplan_all_day'];
			$this->day=$row['raidplan_day'];
			
			$this->recurr_id = $row['recurr_id'];

			$this->subject=$row['raidplan_subject'];
			$this->body=$row['raidplan_body'];
			
			$this->bbcode['bitfield']= $row['bbcode_bitfield'];
			$this->bbcode['uid']= $row['bbcode_uid'];
			//enable_bbcode & enable_smilies & enable_magic_url always 1
			
			//if signups are allowed 
			$this->signups['no'] = $row['signup_no'];
			$this->signups['maybe'] = $row['signup_maybe'];
			$this->signups['yes'] = $row['signup_yes'];
			$this->signups['confirmed'] = $row['signup_confirmed'];
				
			$this->signups_allowed = true;
			if ($row['track_signups'] == 0)
			{
				//no tracking
				$this->signups_allowed = false;
			}
			
			//if raid invite time is in the past then raid signups are frozen.
			$this->frozen = false;
            if ($config['rp_default_freezetime'] != 0 && $config['rp_enable_past_raids'] == 0)
            {
                //compare invite epoch time plus (raid freeze time in hours times 3600) with the current epoch time. if expired then freeze signups
                if( $this->invite_time + (3600 * (int) $config['rp_default_freezetime'])  < time() )
                {
                    $this->frozen = true;
                }
            }

			//get your raid team
			$this->raidteam = $row['raidteam'];
			
			unset ($row);
			
			$sql = 'SELECT * FROM ' . RP_TEAMS . '
					ORDER BY teams_id';	
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ( $row = $db->sql_fetchrow ( $result ) )
			{
				if($this->raidteam == (int) $row['teams_id'])
				{
					$this->raidteamname = $row ['team_name'];
					break 1;
				}
			}
			$db->sql_freeresult($result);
			unset ($row);
			
			// get array of raid roles with signups and confirmations per role (available+confirmed)
			$this->raidroles = array();
			$this->get_raid_roles();
			
			// attach signups to roles (available+confirmed)
			$this->getSignups();
			
			//get all that signed unavailable 
			$this->get_unavailable();

			// lock signup pane if you have no characters bound to your account
			$this->nochar = false;
			if(count ($this->mychars) == 0)
			{
				$this->nochar = true;
			}
			$this->locked = false;
			
			// are you currently signed up for a raidplan ?
			// check it, and lock signup pane if your char is already registered for a role
			// setting signed_up, signed_up_maybe,confirmed to true locks popup/pane
			$this->signed_up = false;
			$this->signed_up_maybe = false;
			foreach($this->raidroles as $rid => $myrole)
			{
				// there are signups?
				if(is_array($myrole['role_signups']))
				{
					//loop them
					foreach($myrole['role_signups'] as $signid => $asignup)
					{
						if(isset($this->mychars))
						{
							foreach($this->mychars as $chid => $mychar)
							{
								if($mychar['id'] == $asignup->dkpmemberid)
								{
									switch ($asignup->signup_val)
									{
										case 1:
											$this->signed_up_maybe = true;
											break;
										case 2:
											$this->signed_up = true;
											break;
									}
									
								}
							}
											
						}
					}
				}

				if(is_array($myrole['role_confirmations']))
				{
					foreach($myrole['role_confirmations'] as $asignup)
					{
						if(isset($this->mychars))
						{
							foreach($this->mychars as $chid => $mychar)
							{
								if($mychar['id'] == $asignup->dkpmemberid)
								{
									$this->confirmed = true;
								}
							}
						}
					}
				}
			}
			
			// also lock signup pane if your char is signed off
			$this->signed_off = false;
			if(is_array($this->signoffs))
			{
				foreach($this->signoffs as $signoffid => $asignoff)
				{
					if(isset($this->mychars))
					{
						foreach($this->mychars as $chid => $mychar)
						{
							if($mychar['id'] == $asignoff->dkpmemberid)
							{
								$this->signed_off = true;
								$this->signed_up = false;
							}
						}
					}
				}
			}
			
			$sql = 'SELECT user_id, username, user_colour FROM ' . USERS_TABLE . ' WHERE user_id = '.$db->sql_escape($this->poster);
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			$this->poster_url = get_username_string( 'full', $this->poster, $row['username'], $row['user_colour'] );
			
			//depending on access level invite different phpbb groups.
			switch( $this->accesslevel )
			{
				case 0:
					// personal raidplan... only raidplan creator is invited
					$this->invite_list = $this->poster_url;
					break;
				case 1:
					if( $this->group_id != 0 )
					{
						// this is a group raidplan... only phpbb accounts of this group are invited
						$sql = 'SELECT group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
								WHERE group_id = '.$db->sql_escape($this->group_id);
						
						$result = $db->sql_query($sql);
						$group_data = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);
						
						$temp_list = (($group_data['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $group_data['group_name']] : $group_data['group_name']);
						$temp_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=group&amp;g=".$this->group_id);
						$temp_color_start = "";
						$temp_color_end = "";
						if( $group_data['group_colour'] !== "" )
						{
							$temp_color_start = "<span style='color:#".$group_data['group_colour']."'>";
							$temp_color_end = "</span>";
						}
						$this->invite_list = "<a href='".$temp_url."'>".$temp_color_start.$temp_list.$temp_color_end."</a>";
					}
					else 
					{
						// multiple groups invited	
						$group_list = explode( ',', $this->group_id_list );
						$num_groups = sizeof( $group_list );
						for( $i = 0; $i < $num_groups; $i++ )
						{
							if( $group_list[$i] == "")
							{
								continue;
							}
							
							// group raidplan... only phpbb accounts  of specified group are invited
							$sql = 'SELECT group_name, group_type, group_colour FROM ' . GROUPS_TABLE . '
									WHERE group_id = '.$db->sql_escape($group_list[$i]);
							$result = $db->sql_query($sql);
							$group_data = $db->sql_fetchrow($result);
							$db->sql_freeresult($result);
							$temp_list = (($group_data['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $group_data['group_name']] : $group_data['group_name']);
							$temp_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=group&amp;g=".$group_list[$i]);
							$temp_color_start = "";
							$temp_color_end = "";
							if( $group_data['group_colour'] !== "" )
							{
								$temp_color_start = "<span style='color:#".$group_data['group_colour']."'>";
								$temp_color_end = "</span>";
							}
							
							if( $this->invite_list == "" )
							{
								$this->invite_list = "<a href='".$temp_url."'>".$temp_color_start.$temp_list.$temp_color_end."</a>";
							}
							else
							{
								$this->invite_list .=  ", " . "<a href='".$temp_url."'>".$temp_color_start.$temp_list.$temp_color_end."</a>";
							}
						}
					}
					break;
				case 2:
					// public raidplan... everyone is invited
					$this->invite_list = $user->lang['EVERYONE'];
					break;
			}
	}
	
	/**
	 * shows the form to add/edit raidplan
	 */
	public function showadd(RaidCalendar $cal, $raidplan_id)
	{
		global $db, $auth, $user, $config, $template, $phpEx, $phpbb_root_path;
		include($phpbb_root_path . 'includes/functions_display.' . $phpEx);
		
		$delete	= (isset($_POST['delete'])) ? true : false;
		if($delete)
		{
			$this->raidplan_delete();
		}
		
		if($raidplan_id !=0)
		{
			// edit or view existing plan
			$mode='edit';
			$this->checkauth_canedit();
			if(!$this->auth_canedit)
			{
				trigger_error('USER_CANNOT_EDIT_RAIDPLAN');
			}
			
			$this->checkauth_candelete();
		
			// action URL 
			$s_action = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;raidplanid=".$this->id."&amp;mode=showadd");
			$update	= (isset($_POST['updateraid'])) ? true : false;
			if($update)
			{
				
				// confirm this edit
				if (confirm_box(true))
				{
					//get string
					$str = request_var('raidobject', '');
					$raidplan_id = request_var('raidplan_id', 0);
					$str1 = base64_decode($str);
					$raidplanobj = unserialize($str1);
					
					// update database
					$raidplanobj->storeplan($raidplan_id);
					// store the raid roles.
					$raidplanobj->store_raidroles($raidplan_id);
					//remake object
					$raidplanobj->make_obj();
					// display it
					$raidplanobj->display();
					return 0;
				}
				else 
				{
					// collect data
					$error = $this->addraidplan($cal);
					
					// add validations
					if(count($error) > 0)
					{
						trigger_error(implode($error,"<br /> "), E_USER_WARNING);
					}
					else
					{
		 				$str  = serialize($this);
		 				$str1 = base64_encode($str);
						$s_hidden_fields = build_hidden_fields(array(
								'updateraid'	=> true,
								'raidobject'	=> $str1, 
								'raidplan_id'	=> $raidplan_id
							)
						);
	
						$template->assign_vars(array(
							'S_HIDDEN_FIELDS'	 => $s_hidden_fields)
						);
	
						confirm_box(false, $user->lang['CONFIRM_UPDATERAID'], $s_hidden_fields);
						
					}
				}
			}
		}
		else
		{
			$mode='new';
			// add new raidplan
			$s_action = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=showadd");
			$submit	= (isset($_POST['addraid'])) ? true : false;
			if($submit)
			{
 				
				if (confirm_box(true))
				{
					//get string
					$str = request_var('raidobject', '');
					$str1 = base64_decode($str);
					$raidplanobj = unserialize($str1);
					$raidplanobj->storeplan(0);
					// store the raid roles.
					$raidplanobj->store_raidroles(0);
					//make object
					$raidplanobj->make_obj();
					// display it
					$raidplanobj->display();
					return 0;
					
				}
				else
				{
					// collect data
					$error = $this->addraidplan($cal);
					// check access
					$this->checkauth_canadd();
					if(!$this->auth_canadd)
					{	
						trigger_error('USER_CANNOT_POST_RAIDPLAN');
					}
					
					if(count($error) > 0)
					{
						trigger_error(implode($error,"<br /> "), E_USER_WARNING);
					}
					else
					{
		 				$str  = serialize($this);
		 				$str1 = base64_encode($str);
						$s_hidden_fields = build_hidden_fields(array(
								'addraid'	=> true,
								'raidobject'	=> $str1
							)
						);
	
						$template->assign_vars(array(
							'S_HIDDEN_FIELDS'	 => $s_hidden_fields)
						);
						confirm_box(false, $user->lang['CONFIRM_ADDRAID'], $s_hidden_fields);
						
					}
				}
				return 0;
			}
		}

		/*
		 * fill template
		 * 
		 */
		$user->setup('posting');
		$user->add_lang ( array ('posting', 'mods/dkp_common','mods/raidplanner'  ));

		$page_title = ($mode=='new') ? $user->lang['CALENDAR_POST_RAIDPLAN'] : $user->lang['CALENDAR_EDIT_RAIDPLAN'];	

		//count events from bbDKP, put them in a pulldown...
		foreach( $this->eventlist->events as $eventid => $event)
		{
			$selected = '';
			
			if($mode=='new')
			{
				$selected = '';
			}
			else
			{
				if($this->event_type == $eventid)
				{
					$selected = ' selected="selected" ';
				}
			}

			$template->assign_block_vars('bbdkp_events_options', array(
					'KEY' 		=> $eventid,
					'VALUE' 	=> $event['event_name'] ,
					'SELECTED' 	=> $selected,
			));
			
		}
		
		// populate raidplan acces level pulldowns
		$level_sel = array();
		if( $auth->acl_get('u_raidplanner_create_public_raidplans') )
		{
			$level_sel[2] = $user->lang['EVENT_ACCESS_LEVEL_PUBLIC']; 
		}
		if( $auth->acl_get('u_raidplanner_create_group_raidplans') )
		{
			$level_sel[1] = $user->lang['EVENT_ACCESS_LEVEL_GROUP'];
		}
		if( $auth->acl_get('u_raidplanner_create_private_raidplans') )
		{
			$level_sel[0] =  $user->lang['EVENT_ACCESS_LEVEL_PERSONAL'];
		}
		
		foreach($level_sel as $key => $value)
		{
			$template->assign_block_vars('accesslevel_options', array(
					'KEY' 		=> $key,
					'VALUE' 	=> $value ,
					'SELECTED' 	=>  ($this->accesslevel == $key) ? ' selected="selected"' : '', 
			));
		}
		
		// Find what groups this user is a member of and add them to the list of groups to invite
		$disp_hidden_groups = $config['rp_display_hidden_groups'];
		if ( $auth->acl_get('u_raidplanner_nonmember_groups') )
		{
			if( $disp_hidden_groups == 1 )
			{
				$sql = 'SELECT g.group_id, g.group_name, g.group_type
						FROM ' . GROUPS_TABLE . ' g
						ORDER BY g.group_type, g.group_name';
			}
			else
			{
				$sql = 'SELECT g.group_id, g.group_name, g.group_type
						FROM ' . GROUPS_TABLE . ' g
						' . ((!$auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) ? ' 	WHERE g.group_type <> ' . GROUP_HIDDEN : '') . '
						ORDER BY g.group_type, g.group_name';
			}
		}
		else
		{
			if( $disp_hidden_groups == 1 )
			{
				$sql = 'SELECT g.group_id, g.group_name, g.group_type
						FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug
						WHERE ug.user_id = ". $db->sql_escape($user->data['user_id']).'
							AND g.group_id = ug.group_id
							AND ug.user_pending = 0
						ORDER BY g.group_type, g.group_name';
			}
			else
			{
				$sql = 'SELECT g.group_id, g.group_name, g.group_type
						FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . " ug
						WHERE ug.user_id = ". $db->sql_escape($user->data['user_id'])."
							AND g.group_id = ug.group_id" . ((!$auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel')) ? ' 	AND g.group_type <> ' . GROUP_HIDDEN : '') . '
							AND ug.user_pending = 0
						ORDER BY g.group_type, g.group_name';
			}
		}
	
		$result = $db->sql_query($sql);
		
		while ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('group_sel_options', array(
					'KEY' 		=> $row['group_id'],
					'VALUE' 	=> (($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name']) ,
					'SELECTED' 	=> '',
			));
		}
		$db->sql_freeresult($result);
		

		// format and translate to user timezone + dst
		//$invite_date_txt = $user->format_date($this->invite_time, $config['rp_date_time_format'], true);
		//$start_date_txt = $user->format_date($this->start_time, $config['rp_date_time_format'], true);
		//$end_date_txt = $user->format_date($this->end_time, $config['rp_date_time_format'], true);
		
		/**
		 *	populate Raid invite time select 
		 */ 
		$hour_mode = $config['rp_hour_mode'];
		$presetinvhour = intval( ($this->invite_time > 0 ? $user->format_date($this->invite_time, 'G', true) * 60: $config['rp_default_invite_time']) / 60);
		for( $i = 0; $i < 24; $i++ )
		{
			$selected = ($i == $presetinvhour ) ? ' selected="selected"' : '';
			$mod_12 = $i % 12;
			if( $mod_12 == 0 )
			{
				$mod_12 = 12;
			}
			$am_pm = $user->lang['PM'];
			if( $i < 12 )
			{
				$am_pm = $user->lang['AM'];
			}
			$template->assign_block_vars('invhouroptions', array(
					'KEY' 		=> $i,
					'VALUE' 	=> ($hour_mode == 12) ? $mod_12.' '.$am_pm : $i,
					'SELECTED' 	=> ($i == $presetinvhour) ? ' selected="selected"' : '',
			));
			
		}
		
		// minute
		if ( $this->invite_time > 0 )
		{
			$presetinvmin = $user->format_date($this->invite_time, 'i', true);
		}
		else
		{
			$presetinvmin = (int) $config['rp_default_invite_time'] - ($presetinvhour * 60) ;
		}
		
		for( $i = 0; $i <= 59; $i++ )
		{
			$template->assign_block_vars('invminoptions', array(
					'KEY' 		=> $i,
					'VALUE' 	=> $i,
					'SELECTED' 	=> ($i == $presetinvmin) ? ' selected="selected"' : '',
			));
		}
		
		
		/**
		 *	populate Raid start hour pulldown
		 */ 
		$hour_start_selcode = "";
		$presetstarthour = intval( ($this->start_time > 0 ? $user->format_date($this->start_time, 'G', true) * 60: $config['rp_default_start_time']) / 60);
		for( $i = 0; $i < 24; $i++ )
		{
			$mod_12 = $i % 12;
			if( $mod_12 == 0 )
			{
				$mod_12 = 12;
			}
			$am_pm = $user->lang['PM'];
			if( $i < 12 )
			{
				$am_pm = $user->lang['AM'];
			}
			$template->assign_block_vars('starthouroptions', array(
					'KEY' 		=> $i,
					'VALUE' 	=> ($hour_mode == 12) ? $mod_12.' '.$am_pm : $i, 
					'SELECTED' 	=> ($i == $presetstarthour) ? ' selected="selected"' : '',
			));
			
		}
		
		/**
		 *	populate Raid start minute pulldown
		 */
		if($mode=='edit')
		{
			$presetstartmin = $user->format_date($this->start_time, 'i', true);
		}
		else
		{
			$presetstartmin = (int) $config['rp_default_start_time'] - ($presetstarthour * 60) ;
		}
		
		for( $i = 0; $i <= 59; $i++ )
		{
			$template->assign_block_vars('startminoptions', array(
					'KEY' 		=> $i,
					'VALUE' 	=> $i,
					'SELECTED' 	=> ($i == $presetstartmin ) ? ' selected="selected"' : '',
			));
		}
		
		/**
		 * populate end day pulldown
		 */
		for( $i = 1; $i <= $cal->days_in_month; $i++ )
		{
			$template->assign_block_vars('enddayoptions', array(
					'KEY' 		=> $i,
					'VALUE' 	=> $i,
					'SELECTED' 	=> ( (int) $cal->date['day'] == $i ) ? ' selected="selected"' : '',
			));
		}
		
		// month dropdown
		for( $i = 1; $i <= 12; $i++ )
		{
			$template->assign_block_vars('endmonthoptions', array(
					'KEY' 		=> $i,
					'VALUE' 	=> $user->lang['datetime'][$cal->month_names[$i]],
					'SELECTED' 	=> ($cal->date['month_no'] == $i ) ? ' selected="selected"' : '',
			));
		}
		
		$temp_year	= gmdate('Y');
		for( $i = $temp_year-1; $i < ($temp_year+5); $i++ )
		{
			$template->assign_block_vars('endyearoptions', array(
					'KEY' 		=> $i,
					'VALUE' 	=> $i,
					'SELECTED' 	=> ( (int) $cal->date['year'] == $i ) ? ' selected="selected"' : '',
			));
		}
		/**
		 *	populate Raid END time pulldown 
		 */ 
		$presetendhour = intval( ($this->end_time > 0 ? $user->format_date($this->end_time, 'G', true) * 60: $config['rp_default_end_time']) / 60);
		for( $i = 0; $i < 24; $i++ )
		{
			
			$mod_12 = $i % 12;
			if( $mod_12 == 0 )
			{
				$mod_12 = 12;
			}
			$am_pm = $user->lang['PM'];
			if( $i < 12 )
			{
				$am_pm = $user->lang['AM'];
			}

			$template->assign_block_vars('endhouroptions', array(
					'KEY' 		=> $i,
					'VALUE' 	=> ($hour_mode == 12) ? $mod_12.' '.$am_pm : $i, 
					'SELECTED' 	=> ($i == $presetendhour) ? ' selected="selected"' : '',
			));
		}
		
		/**
		 *	populate Raid end minute pulldown
		 */
		if ( $this->end_time > 0 )
		{
			$presetendmin = $user->format_date($this->end_time, 'i', true);
		}
		else
		{
			$presetendmin = (int) $config['rp_default_end_time'] - ($presetendhour * 60) ;
		}
		
		for( $i = 0; $i <= 59; $i++ )
		{
			$template->assign_block_vars('endminuteoptions', array(
					'KEY' 		=> $i,
					'VALUE' 	=> $i,
					'SELECTED' 	=> ($i == $presetendmin) ? ' selected="selected"' : '',
			));
		}
		
		$day_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$cal->date['day'] ."&amp;calM=".$cal->date['month_no']."&amp;calY=".$cal->date['year']);
		$week_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$cal->date['day'] ."&amp;calM=".$cal->date['month_no']."&amp;calY=".$cal->date['year']);
		$month_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$cal->date['day']."&amp;calM=".$cal->date['month_no']."&amp;calY=".$cal->date['year']);

		/*
		 * make raid composition proposal
		 */ 
		if($mode != 'edit')
		{
			// new raid 
			$sql = 'SELECT * FROM ' . RP_TEAMS . '
					ORDER BY teams_id';	
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ( $row = $db->sql_fetchrow ( $result ) )
			{
				$team_id = $row ['teams_id']; 
				$teamname = $row ['team_name']; 
				$teamsize = $row ['team_needed']; 
				$template->assign_block_vars( 'team_row', array (
				'VALUE' => $row ['teams_id'], 
				'SELECTED' => ' selected="selected"', 
				'OPTION' => $row ['team_name'] . ': ' .  $row['team_needed']));
			}
			$db->sql_freeresult($result);
			$this->raidteam = $team_id;
			$this->raidteamname = $teamname;
			// make roles proposal
			$sql_array = array(
			    'SELECT'    => 't.team_needed, r.role_id, r.role_name , r.role_color, r.role_icon ', 
			    'FROM'      => array(
			        RP_TEAMSIZE => 't', 
			        RP_ROLES   	=> 'r'
			    ),
			    'ORDER_BY'  => 'r.role_id', 
			    'WHERE'  	=> 'r.role_id = t.role_id AND t.teams_id = ' . $team_id
				);
			
			$sql = $db->sql_build_query('SELECT', $sql_array);
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
			    $template->assign_block_vars('teamsize', array(
			        'ROLE_COLOR'     => $row['role_color'],
			    	'S_ROLE_ICON_EXISTS'	=>  (strlen($row['role_icon']) > 1) ? true : false,
			        'ROLE_ICON'      => (strlen($row['role_icon']) > 1) ? $phpbb_root_path . "images/bbdkp/raidrole_images/" . $row['role_icon'] . ".png" : '',
			        'ROLE_ID'        => $row['role_id'],
				    'ROLE_NAME'      => $row['role_name'],
			    	'ROLE_NEEDED'    => $row['team_needed'],
			    ));
			}
			$db->sql_freeresult($result);
			
			
		}
		else
		{
			//repopulate dropdown from object
			$sql = 'SELECT * FROM ' . RP_TEAMS . '
					ORDER BY teams_id';	
			$db->sql_query($sql);
			$result = $db->sql_query($sql);
			while ( $row = $db->sql_fetchrow ( $result ) )
			{
				$template->assign_block_vars( 'team_row', array (
				'VALUE' 	=> $row ['teams_id'], 
				'SELECTED' 	=> ($row ['teams_id'] == $this->raidteam) ? ' selected="selected"' : '', 
				'OPTION' 	=> $row ['team_name'] . ': ' .  $row['team_needed']));
			}
			$db->sql_freeresult($result);
			unset($row);
						
			// get roles from object
			
			foreach($this->raidroles as $key => $role)
			{
				$template->assign_block_vars('teamsize', array(
			        'ROLE_COLOR'     => $role['role_color'],
			    	'S_ROLE_ICON_EXISTS'	=>  (strlen($role['role_icon']) > 1) ? true : false,
			        'ROLE_ICON'      => (strlen($role['role_icon']) > 1) ? $phpbb_root_path . "images/bbdkp/raidrole_images/" . $role['role_icon'] . ".png" : '',
					'ROLE_ID'        => $key,
					'ROLE_NAME'      => $role['role_name'],
					'ROLE_NEEDED'    => $role['role_needed'],
				));
			}

			
		}
		
		$message = generate_text_for_edit($this->body, 
		(isset($this->bbcode['uid']) ? $this->bbcode['uid'] : ''), 
		(isset($this->bbcode['bitfield']) ? $this->bbcode['bitfield'] : '') , 7);
		
		// HTML, BBCode, Smilies, Images and Flash status
		$bbcode_status	= ($config['allow_bbcode']) ? true : false;
		$img_status		= ($bbcode_status) ? true : false;
		$flash_status	= ($bbcode_status && $config['allow_post_flash']) ? true : false;
		$url_status		= ($config['allow_post_links']) ? true : false;
		$smilies_status	= ($bbcode_status && $config['allow_smilies']) ? true : false;
		
		if ($smilies_status)
		{
			// Generate smiley listing
			$cal->generate_calendar_smilies('inline');
		}
		
		$inv_d = $cal->date['day'];
		$inv_m = $cal->date['month_no'];
		$inv_y = $cal->date['year'];
		
		$start_hr = request_var('calHr', 0);
		$start_mn = request_var('calMn', 0);
		$start_date = gmmktime(0, 0, 0, $inv_m, $inv_d, $inv_y) - $user->timezone - $user->dst;
		
		$ajaxpath = append_sid($phpbb_root_path . 'styles/' . $user->theme['template_path'] . '/template/planner/raidplan/ajax1.'. $phpEx, "ajax=1");
		$template->assign_vars(array(
			'S_POST_ACTION'				=> $s_action,
			'RAIDPLAN_ID'				=> $this->id,
			'S_EDIT'					=> ($mode == 'edit') ? true : false, 
			'S_DELETE_ALLOWED'			=> $this->auth_candelete, 
			'S_BBCODE_ALLOWED'			=> $bbcode_status,
			'S_SMILIES_ALLOWED'			=> $smilies_status,
			'S_LINKS_ALLOWED'			=> $url_status,
			'S_BBCODE_IMG'				=> $img_status,
			'S_BBCODE_URL'				=> $url_status,
			'S_BBCODE_FLASH'			=> $flash_status,
			'S_BBCODE_QUOTE'			=> false,
			'S_PLANNER_ADD'				=> true,
			'TEAM_ID'					=> $this->raidteam,
			'TEAM_NAME'					=> $this->raidteamname, 
			//'TEAM_SIZE'				=> $teamsize, 
			'L_POST_A'					=> $page_title,
			'SUBJECT'					=> $this->subject,
			'MESSAGE'					=> $message['text'],
			'START_DATE'				=> $user->format_date($start_date, $config['rp_date_format'], true),
			'START_HOUR_SEL'			=> $hour_start_selcode,
			'DAY_VIEW_URL'				=> $day_view_url,
			'WEEK_VIEW_URL'				=> $week_view_url,
			'MONTH_VIEW_URL'			=> $month_view_url,

			
			//'S_RECURRING_OPTS'			=> $raidplan_data['s_recurring_opts'],
			//'S_UPDATE_RECURRING_OPTIONS'=> $raidplan_data['s_update_recurring_options'],
			//'RECURRING_EVENT_CHECK'		=> $recurr_raidplan_check,
			//'RECURRING_EVENT_TYPE_SEL'	=> $recurr_raidplan_freq_sel_code,
			//'RECURRING_EVENT_FREQ_IN'	=> $recurr_raidplan_freq_val_code,
			//'END_RECURR_MONTH_SEL'		=> $end_recurr_month_sel_code,
			//'END_RECURR_DAY_SEL'		=> $end_recurr_day_sel_code,
			//'END_RECURR_YEAR_SEL'		=> $end_recurr_year_sel_code,

			'BBCODE_STATUS'				=> ($bbcode_status) ? 
				sprintf($user->lang['BBCODE_IS_ON'], '<a href="' . append_sid("{$phpbb_root_path}faq.$phpEx", 'mode=bbcode') . '">', '</a>') : 
				sprintf($user->lang['BBCODE_IS_OFF'], '<a href="' . append_sid("{$phpbb_root_path}faq.$phpEx", 'mode=bbcode') . '">', '</a>'),
			'IMG_STATUS'				=> ($img_status) ? $user->lang['IMAGES_ARE_ON'] : $user->lang['IMAGES_ARE_OFF'],
			'FLASH_STATUS'				=> ($flash_status) ? $user->lang['FLASH_IS_ON'] : $user->lang['FLASH_IS_OFF'],
			'SMILIES_STATUS'			=> ($smilies_status) ? $user->lang['SMILIES_ARE_ON'] : $user->lang['SMILIES_ARE_OFF'],
			'URL_STATUS'				=> ($bbcode_status && $url_status) ? $user->lang['URL_IS_ON'] : $user->lang['URL_IS_OFF'],

			//javascript alerts
			'LA_ALERT_OLDBROWSER' 		=> $user->lang['ALERT_OLDBROWSER'],
			'UA_AJAXHANDLER1'		  	=> $ajaxpath,
		));
		
		// Build custom bbcodes array
		display_custom_bbcodes();
		
	}
	
	/**
	 * collects data from form, constructs raidplan object for insert or update
	 *
	 * @param RaidCalendar $cal
	 */
	private function addraidplan(RaidCalendar $cal)
	{

		global $user;
		
		$error = array();
		
		// hidden ID
		$this->id = request_var('raidplanid', 0);
		
		// raidmaster
		$this->poster = $user->data['user_id']; 
		
		// get member group id
		$this->group_id_list = ',';
		$this->group_id = 0;
		
		$group_id_array = request_var('calGroupId', array(0));
		$num_group_ids = sizeof( $group_id_array );
	    if( $num_group_ids == 1 )
	    {
	    	// if only one group pass the groupid
			$this->group_id = $group_id_array[0];
	    }
		elseif( $num_group_ids > 1 )
		{
			// if we want multiple groups then pass the array 
			$group_index = 0;
			for( $group_index = 0; $group_index < $num_group_ids; $group_index++ )
			{
			    if( $group_id_array[$group_index] == "" )
			    {
			    	continue;
			    }
			    $this->group_id_list .= $group_id_array[$group_index] . ",";
			}
		}
		
		$this->accesslevel = request_var('accesslevel', 0);
		switch($this->accesslevel)
		{
			case 0:
				//personal, no signups
				$this->signups_allowed = 0; 
			case 1:
				$this->signups_allowed = 1; 				
				// if we selected group access but didn't actually choose a group then throw error
				if ($num_group_ids < 1)
				{
					$error[] = $user->lang['NO_GROUP_SELECTED'];		
				}
			case 2:
				//all
				$this->signups_allowed = 1; 
		}
		
		//set raid properties
		
		//get raid team
		$this->raidteam = request_var('teamselect', request_var('team_id', 0));

		// get selected role array
		$raidroles = request_var('role_needed', array(0=> 0));
		
		foreach($raidroles as $role_id => $needed)
		{
			$this->raidroles[$role_id] = array(
				'role_needed' => (int) $needed,
			);
		}
		
		$this->signups['yes'] = 0;
		$this->signups['no'] = 0;
		$this->signups['maybe'] = 0;
		
		//set event type 
		$this->event_type = request_var('bbdkp_events', 0);
		
		// invite/start date values from pulldown click
		$inv_d = request_var('calD', 0);
		$inv_m = request_var('calM', 0);
		$inv_y = request_var('calY', 0);

		/// always overrides invite/start date values from calendar click
		//$inv_d = request_var('hiddenCalD', $cal->date['day']);
		//$inv_m = request_var('hiddenCalM', $cal->date['month_no']);
		//$inv_y = request_var('hiddenCalY', $cal->date['year']);
			
		//convert user times to UCT-GMT. all dates are stored in GMT and time is displayed in user board timezone
		$inv_hr = request_var('calinvHr', 0);
		$inv_mn = request_var('calinvMn', 0);
		$this->invite_time = gmmktime($inv_hr, $inv_mn, 0, $inv_m, $inv_d, $inv_y) - $user->timezone - $user->dst;

		$start_hr = request_var('calHr', 0);
		$start_mn = request_var('calMn', 0);
		$this->start_time = gmmktime($start_hr, $start_mn, 0, $inv_m, $inv_d, $inv_y) - $user->timezone - $user->dst;
		
		$end_m = request_var('calMEnd', 0);
		$end_d = request_var('calDEnd', 0);
		$end_y = request_var('calYEnd', 0);
		
		$end_hr = request_var('calEndHr', 0);
		$end_mn = request_var('calEndMn', 0);
		$this->end_time = gmmktime( $end_hr, $end_mn, 0, $end_m, $end_d, $end_y ) - $user->timezone - $user->dst;
		if ($this->end_time < $this->start_time)
		{	
			//check for enddate before begindate
			// if the end hour is earlier than start hour then roll over a day
			$this->end_time += 3600*24;
		}
		
		//if this is not an "all day event"
		$this->all_day=0;
		$this->day = sprintf('%2d-%2d-%4d', $inv_d, $inv_m, $inv_y);

		// recurring ? @todo
						
		// read subjectline
		$this->subject = utf8_normalize_nfc(request_var('subject', '', true)); 

		//read comment section
		$this->body = utf8_normalize_nfc(request_var('message', '', true));
		
		$this->bbcode['uid'] = $this->bbcode['bitfield'] = $options = ''; // will be modified by generate_text_for_storage
		$allow_bbcode = $allow_urls = $allow_smilies = true;
		generate_text_for_storage($this->body, $this->bbcode['uid'], $this->bbcode['bitfield'], $options, $allow_bbcode, $allow_urls, $allow_smilies);

		return $error;
	}
	
	/**
	 * 
	 * insert new or update existing raidplan object
	 *
	 * @param int $raidplan_id
	 */
	private function storeplan($raidplan_id)
	{
		global $db;
		
		$sql_raid = array(
			'etype_id'		 		=> (int) $this->event_type,
			'poster_id'		 		=> $this->poster,
			'sort_timestamp'		=> $this->start_time, 
			'raidplan_invite_time'	=> $this->invite_time,
			'raidplan_start_time'	=> $this->start_time,
			'raidplan_end_time'		=> $this->end_time,
			'raidplan_all_day'		=> $this->all_day,
			'raidplan_day'			=> $this->day,
			'raidteam'				=> $this->raidteam, 	
			'raidplan_subject'		=> $this->subject,
			'raidplan_body'			=> $this->body,	
			'poster_id'				=> $this->poster,
			'raidplan_access_level'	=> $this->accesslevel,
			'group_id'				=> $this->group_id,
			'group_id_list'			=> $this->group_id_list,
			'enable_bbcode'			=> 1,
			'enable_smilies'		=> 1,
			'enable_magic_url'		=> 1,
			'bbcode_bitfield'		=> $this->bbcode['bitfield'],
			'bbcode_uid'			=> $this->bbcode['uid'], 
			'track_signups'			=> $this->signups_allowed,
			'signup_yes'			=> $this->signups['yes'],
			'signup_no'				=> $this->signups['no'],
			'signup_maybe'			=> $this->signups['maybe'],
			'recurr_id'				=> $this->recurr_id,
			);
		
		/*
		 * start transaction
		 */
		$db->sql_transaction('begin');
			
		if($raidplan_id == 0)
		{
			//insert new
			$sql = 'INSERT INTO ' . RP_RAIDS_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_raid);
			$db->sql_query($sql);	
			$raidplan_id = $db->sql_nextid();
			$this->id = $raidplan_id;
			$this->raidmessenger(1);
		}
		else
		{
			// update
			$sql = 'UPDATE ' . RP_RAIDS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $sql_raid) . '
		    WHERE raidplan_id = ' . (int) $raidplan_id;
			$db->sql_query($sql);
			$this->raidmessenger(2);
			
		}
		unset ($sql_raid);
		
		$db->sql_transaction('commit');
		
	}
	
	/**
	 * inserts or updates raidroles
	 *
	 * @param int $raidplan_id
	 */
	private function store_raidroles($raidplan_id)
	{
		global $db;
		
		/*
		 * start transaction
		 */
		$db->sql_transaction('begin');
		
		foreach($this->raidroles as $role_id => $role)
		{
				
			if($raidplan_id == 0)
			{
				$sql_raidroles = array(
					'raidplan_id'		=> $this->id,				
					'role_id'			=> $role_id,
					'role_needed'		=> $role['role_needed']					
					);
					
				//insert new
				$sql = 'INSERT INTO ' . RP_RAIDPLAN_ROLES . ' ' . $db->sql_build_array('INSERT', $sql_raidroles);
				$db->sql_query($sql);	
				
			}
			else
			{
				// update
				$sql_raidroles = array(
					'role_id'			=> $role_id,
					'role_needed'		=> $role['role_needed']				
					);
				
				$sql = 'UPDATE ' . RP_RAIDPLAN_ROLES . '
	    		SET ' . $db->sql_build_array('UPDATE', $sql_raidroles) . '
			    WHERE raidplan_id = ' . (int) $raidplan_id . ' 
			    AND role_id = ' . $role_id;
				
				$db->sql_query($sql);
			}
		}
						
		$db->sql_transaction('commit');
			
		unset($sql_raidroles);
		unset($role_id);
		unset($role);
		
		
	}
	
	/**
	 * delete a Raid plan
	 *
	 */
	public function raidplan_delete()
	{
		// recheck if user can delete
		global $user, $db, $phpbb_root_path, $phpEx;
		
		$this->checkauth_candelete();
		if($this->auth_candelete == false)
		{
			trigger_error('USER_CANNOT_DELETE_RAIDPLAN');
		}
	
		if (confirm_box(true))
		{
			//recall vars
			
			$raidplan_id = request_var('raidplan_id', 0);
			
			if($raidplan_id != 0)
			{
				$this->raidmessenger(3);
				
				$db->sql_transaction('begin');
				
				// delete all the signups for this raidplan before deleting the raidplan
				$sql = 'DELETE FROM ' . RP_SIGNUPS . ' WHERE raidplan_id = ' . $db->sql_escape($raidplan_id);
				$db->sql_query($sql);
		
				// Delete event
				$sql = 'DELETE FROM ' . RP_RAIDS_TABLE . ' WHERE raidplan_id = '.$db->sql_escape($raidplan_id);
				$db->sql_query($sql);
				
				$sql = 'DELETE FROM ' . RP_RAIDPLAN_ROLES . ' WHERE raidplan_id = '.$db->sql_escape($raidplan_id);
				$db->sql_query($sql);
				
				$db->sql_transaction('commit');
				
				$day = gmdate("d", $this->start_time);
				$month = gmdate("n", $this->start_time);
				$year =	gmdate('Y', $this->start_time);

				unset($this);
				
				$meta_info = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calM=".$month."&amp;calY=".$year);
				$message = $user->lang['EVENT_DELETED'];
				
				
				meta_refresh(3, $meta_info);
				$message .= '<br /><br />' . sprintf($user->lang['RETURN_CALENDAR'], '<a href="' . $meta_info . '">', '</a>');
				trigger_error($message);
			}
		
		}
		else
		{
			$s_hidden_fields = build_hidden_fields(array(
					'raidplan_id'=> $this->id,
					'page'	=> 'planner',
					'view'	=> 'raidplan',
					'mode'	=> 'delete')
			);
			
			return confirm_box(false, $user->lang['DELETE_RAIDPLAN_CONFIRM'], $s_hidden_fields);
			
		}
		
		
	}
	
	
	/**
	 * displays a raidplan object
	 *
	 */
	public function display()
	{
		global $auth, $user, $config, $template, $phpEx, $phpbb_root_path;
		
		// check if it is a private appointment
		if( !$this->auth_cansee)
		{
			trigger_error( 'PRIVATE_RAIDPLAN' );
		}
		
		// format the raidplan message
		$bbcode_options = OPTION_FLAG_BBCODE + OPTION_FLAG_SMILIES + OPTION_FLAG_LINKS;
		$message = generate_text_for_display($this->body, $this->bbcode['uid'], $this->bbcode['bitfield'], $bbcode_options);

		// translate raidplan start and end time into user's timezone
		$day = gmdate("d", $this->start_time);
		$month = gmdate("n", $this->start_time);
		$year =	gmdate('Y', $this->start_time);

		/* make the url for the edit button 
		// show if
		// user is registered and belongs to u_raidplanner_edit_raidplans usergroup and did create this raid, or belong to group that can edit any raid
		*/
		$edit_url = "";
		if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_edit_raidplans') &&
	    (($user->data['user_id'] == $this->poster ) || $auth->acl_get('m_raidplanner_edit_other_users_raidplans')))
		{
			$edit_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=showadd&amp;raidplanid=". 
			$this->id."&amp;calD=".$day."&amp;calM=".$month."&amp;calY=".$year);
		}
		
		/* make the url for the delete button */
		$delete_url = "";
		$delete_all_url = "";
		if( $user->data['is_registered'] && $auth->acl_get('u_raidplanner_delete_raidplans') &&
		    (($user->data['user_id'] == $this->poster )|| $auth->acl_get('m_raidplanner_delete_other_users_raidplans') ))
		{
			$delete_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=delete&amp;raidplanid=".
				$this->id."&amp;calD=".$day."&amp;calM=".$month."&amp;calY=".$year);
		}
		
		// url to add raid
		$add_raidplan_url = "";
		if ( $auth->acl_gets('u_raidplanner_create_public_raidplans', 'u_raidplanner_create_group_raidplans', 'u_raidplanner_create_private_raidplans'))
		{
			$add_raidplan_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=showadd&amp;calD=".
			$day."&amp;calM=". $month. "&amp;calY=".$year);
		}
		
		
		$day_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=day&amp;calD=".$day ."&amp;calM=".
		$month."&amp;calY=".$year);
		$week_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=week&amp;calD=".$day ."&amp;calM=".
		$month."&amp;calY=".$year);
		$month_view_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=month&amp;calD=".$day."&amp;calM=".
		$month."&amp;calY=".$year);

		$total_needed = 0;
		/* make url for signup action */
		$signup_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=signup&amp;raidplanid=". $this->id);
		
		//display signups only if this is not a personal appointment
		if($this->accesslevel != 0)
		{
			foreach ($this->mychars as $key => $mychar)
			{
				$template->assign_block_vars('mychars', array(
				        'MEMBER_ID'      	=> $mychar['id'],
						'MEMBER_NAME'  	 	=> $mychar['name'],
						'MEMBER_SELECTED'	=> ($mychar['signedup_val'] >= 1) ? ' selected="selected"' : ''
						
				 ));
			}
			
			unset($key);
			unset($mychar);
			
			//loop all roles
			// @ : role 0 is declined
			foreach($this->raidroles as $key => $role)
			{
				$total_needed += $role['role_needed'];

				// loop signups per role
				$template->assign_block_vars('raidroles', array(
				        'ROLE_ID'        => $key,
						'ROLE_DISPLAY'   => (count($role['role_signups']) > 0 ? true : false),
						'ROLE_NAME'      => $role['role_name'],
				    	'ROLE_NEEDED'    => $role['role_needed'],
				    	'ROLE_SIGNEDUP'  => $role['role_signedup'],
				    	'ROLE_CONFIRMED' => $role['role_confirmed'],
						'ROLE_COLOR'	 => $role['role_color'],
						'S_ROLE_ICON_EXISTS' => (strlen($role['role_icon']) > 1) ? true : false,
				       	'ROLE_ICON' 	 => (strlen($role['role_icon']) > 1) ? $phpbb_root_path . "images/bbdkp/raidrole_images/" . $role['role_icon'] . ".png" : '',
				 ));
				 
				 // loop confirmed signups per role
				 foreach($role['role_confirmations'] as $confirmation)
				 {
				 	$confdetail = new RaidplanSignup();
				 	$confdetail->getSignup($confirmation->signup_id, $this->eventlist->events[$this->event_type]['dkpid'] );
				 	
				 	$edit_text_array = generate_text_for_edit( $confdetail->comment, $confdetail->bbcode['uid'], 7);
					$candeleteconf = false;
					$caneditconf = false;
					$editconfurl = "";
					$deleteconfurl = "";
					
				 	if( $auth->acl_get('m_raidplanner_edit_other_users_signups') || $confirmation->poster_id == $user->data['user_id']  )
					{
						// then if signup is not frozen then show deletion button
						//@todo calculate frozen
						$candeleteconf = true;
						$caneditconf = true;
						$editconfurl = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=editsign&amp;raidplanid=". $this->id . "&amp;signup_id=" . $confdetail->signup_id);
						$deleteconfurl = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=delsign&amp;raidplanid=". $this->id . "&amp;signup_id=" . $confdetail->signup_id);
					}
					
					$signupcolor = '#006B02';
					$signuptext = $user->lang['CONFIRMED'];
					
					$template->assign_block_vars('raidroles.confirmations', array(
						'DKP_CURRENT'	=> ($config['bbdkp_epgp'] == 1) ? $confdetail->priority_ratio : $confdetail->dkp_current,
						'ATTENDANCEP1'	=> $confdetail->attendanceP1,
						'U_MEMBERDKP'	=> $confdetail->dkmemberpurl,
						'SIGNUP_ID' 	=> $confdetail->signup_id, 
						'RAIDPLAN_ID' 	=> $confdetail->raidplan_id, 
	       				'POST_TIME' 	=> $user->format_date($confdetail->signup_time, $config['rp_date_time_format'], true),
						'POST_TIMESTAMP' => $confdetail->signup_time, 
						'DETAILS' 		=> generate_text_for_display($confdetail->comment, $confdetail->bbcode['uid'], $confdetail->bbcode['bitfield'], 7),
						'EDITDETAILS' 	=> $edit_text_array['text'],
						'HEADCOUNT' 	=> $confdetail->signup_count,
						'POSTER' 		=> $confdetail->poster_name, 
						'POSTER_URL' 	=> get_username_string( 'full', $confdetail->poster_id, $confdetail->poster_name, $confdetail->poster_colour ),
						'VALUE' 		=> $confdetail->signup_val, 
						'COLOR' 		=> $signupcolor, 
						'VALUE_TXT' 	=> $signuptext, 
						'CHARNAME'      => $confdetail->dkpmembername,
						'LEVEL'         => $confdetail->level,
						'CLASS'         => $confdetail->classname,
						'COLORCODE'  	=> ($confdetail->colorcode == '') ? '#123456' : $confdetail->colorcode,
				        'CLASS_IMAGE' 	=> (strlen($confdetail->imagename) > 1) ? $confdetail->imagename : '',  
						'S_CLASS_IMAGE_EXISTS' => (strlen($confdetail->imagename) > 1) ? true : false,
				       	'RACE_IMAGE' 	=> (strlen( $confdetail->raceimg) > 1) ? $confdetail->raceimg : '',  
						'S_RACE_IMAGE_EXISTS' => (strlen($confdetail->raceimg) > 1) ? true : false, 
						'S_DELETE_SIGNUP'	=> $candeleteconf, 
						'S_EDIT_SIGNUP' 	=> $caneditconf,
						'S_SIGNUP_EDIT_ACTION' => $editconfurl, 
						'U_DELETE'			=> $deleteconfurl, 
					));
						
				 }

				 // loop available signups per role
				 foreach($role['role_signups'] as $signup)
				 {
				 	$signupdetail = new RaidplanSignup();
				 	$signupdetail->getSignup($signup->signup_id, $this->eventlist->events[$this->event_type]['dkpid'] );
					$edit_text_array = generate_text_for_edit( $signupdetail->comment, $signupdetail->bbcode['uid'], 7);
					
					if( $signupdetail->signup_val == 1 )
					{
						$signupcolor = '#C9B634';
						$signuptext = $user->lang['MAYBE'];
					}
					elseif( $signupdetail->signup_val == 2 )
					{
						$signupcolor = '#FFB100';
						$signuptext = $user->lang['YES'];
					}
					elseif( $signupdetail->signup_val == 3 )
					{
						$signupcolor = '#006B02';
						$signuptext = $user->lang['CONFIRMED'];
					}
					
					// if user can delete other signups ?
				 	$confirm_signup_url = "";
				 	$canconfirmsignup = false;
					if( $auth->acl_get('m_raidplanner_edit_other_users_signups') )
					{
						$canconfirmsignup=true;
						$confirm_signup_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=confirm&amp;raidplanid=". $this->id . "&amp;signup_id=" . $signupdetail->signup_id);
					}
					
					// if user can delete other signups or if own signup
					$candeletesignup= false;
					$caneditsignup = false;
					$deletesignupurl="";
					$editsignupurl="";
					$deletekey=0;
					if( $auth->acl_get('m_raidplanner_edit_other_users_signups') || $signupdetail->poster_id == $user->data['user_id']  )
					{
						// then if signup is not frozen then show deletion button
						//@todo calculate frozen
						$candeletesignup = true;
						$caneditsignup = true;
						$editsignupurl = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=editsign&amp;raidplanid=". $this->id . "&amp;signup_id=" . $signupdetail->signup_id);
						$deletekey = rand(1, 1000);
						$deletesignupurl = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=delsign&amp;raidplanid=". $this->id . "&amp;signup_id=" . $signupdetail->signup_id);
					}
					
					$template->assign_block_vars('raidroles.signups', array(
						'DKP_CURRENT'		=> ($config['bbdkp_epgp'] == 1) ? $signupdetail->priority_ratio : $signupdetail->dkp_current ,
						'ATTENDANCEP1'		=> $signupdetail->attendanceP1,
						'U_MEMBERDKP'		=> $signupdetail->dkmemberpurl,
	       				'SIGNUP_ID' 	 	=> $signupdetail->signup_id,
						'RAIDPLAN_ID' 	 	=> $signupdetail->raidplan_id, 
	       				'POST_TIME' 	 	=> $user->format_date($signupdetail->signup_time, $config['rp_date_time_format'], true),
						'POST_TIMESTAMP' 	=> $signup->signup_time,
						'DETAILS' 			=> generate_text_for_display($signupdetail->comment, $signupdetail->bbcode['uid'], $signupdetail->bbcode['bitfield'], 7),
						'EDITDETAILS' 		=> $edit_text_array['text'],					
						'HEADCOUNT' 		=> $signupdetail->signup_count,
						'POSTER' 			=> $signupdetail->poster_name, 
						'POSTER_URL' 		=> get_username_string( 'full', $signupdetail->poster_id, $signupdetail->poster_name, $signupdetail->poster_colour ),
						'VALUE' 			=> $signupdetail->signup_val, 
						'COLOR' 			=> $signupcolor, 
						'VALUE_TXT' 		=> $signuptext, 
						'CHARNAME'      	=> $signupdetail->dkpmembername,
						'LEVEL'         	=> $signupdetail->level,
						'CLASS'         	=> $signupdetail->classname,
						'COLORCODE'  		=> ($signupdetail->colorcode == '') ? '#123456' : $signupdetail->colorcode,
				        'CLASS_IMAGE' 		=> (strlen($signupdetail->imagename) > 1) ? $signupdetail->imagename : '',  
						'S_CLASS_IMAGE_EXISTS' => (strlen($signupdetail->imagename) > 1) ? true : false,
				       	'RACE_IMAGE' 		=> (strlen($signupdetail->raceimg) > 1) ?$signupdetail->raceimg : '',  
						'S_RACE_IMAGE_EXISTS' => (strlen($signupdetail->raceimg) > 1) ? true : false, 
						'S_DELETE_SIGNUP'	=> 	$candeletesignup, 
						'S_EDIT_SIGNUP' 	=> $caneditsignup,
						'S_SIGNUPMAYBE'		=> $this->signed_up_maybe,
						'S_SIGNUP_EDIT_ACTION' => $editsignupurl, 
						'U_DELETE'			=> $deletesignupurl, 
						'DELETEKEY' 		=> $deletekey, 
						'S_CANCONFIRM'		=> $canconfirmsignup, 
						'U_CONFIRM'			=> $confirm_signup_url,
								 				
					));
						
				 }
			 
			}
			unset($key);
			unset($role);
			
			// display signoffs
			foreach($this->signoffs as $key => $signoff)
			{
					 	
				$signoffdetail = new RaidplanSignup();
				$signoffdetail->getSignup($signoff->signup_id, $this->eventlist->events[$this->event_type]['dkpid'] );
				$edit_text_array = generate_text_for_edit( $signoffdetail->comment, $signoffdetail->bbcode['uid'], 7);
				
				$requeue=false;
				$requeueurl="";
				// allow requeueing your character
				if( $auth->acl_get('m_acl_m_raidplanner_delete_other_users_raidplans') || $signoffdetail->poster_id == $user->data['user_id']  )
				{
					$requeue = true;
					$requeueurl = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=requeue&amp;raidplanid=". $this->id . "&amp;signup_id=" . $signoffdetail->signup_id);
				}
					
				$template->assign_block_vars('unavailable', array(
					'DKP_CURRENT'	=> ($config['bbdkp_epgp'] == 1) ? $signoffdetail->priority_ratio : $signoffdetail->dkp_current,
					'ATTENDANCEP1'	=> $signoffdetail->attendanceP1,
					'U_MEMBERDKP'	=> $signoffdetail->dkmemberpurl,
					'SIGNUP_ID' 	=> $signoff->signup_id,
					'RAIDPLAN_ID' 	=> $signoff->raidplan_id,
	    			'POST_TIME' 	=> $user->format_date($signoff->signup_time, $config['rp_date_time_format'], true),
					'POST_TIMESTAMP' => $signoff->signup_time,
					'DETAILS' 		=> generate_text_for_display($signoff->comment, $signoff->bbcode['uid'], $signoff->bbcode['bitfield'], 7),
					'EDITDETAILS' 	=> $edit_text_array['text'],
					'POSTER' 		=> $signoff->poster_name,
					'POSTER_URL' 	=> get_username_string( 'full', $signoff->poster_id, $signoff->poster_name, $signoff->poster_colour ),
					'VALUE' 		=> $signoff->signup_val,
					'COLOR' 		=> '#FF0000', 
					'VALUE_TXT' 	=> $user->lang['NO'], 
					'CHARNAME'      => $signoff->dkpmembername,
					'LEVEL'         => $signoff->level,
					'CLASS'         => $signoff->classname,
					'COLORCODE'  	=> ($signoff->colorcode == '') ? '#123456' : $signoff->colorcode,
			        'CLASS_IMAGE' 	=> (strlen($signoff->imagename) > 1) ? $signoff->imagename: '',
					'S_CLASS_IMAGE_EXISTS' => (strlen($signoff->imagename) > 1) ? true : false,
			       	'RACE_IMAGE' 	=> (strlen($signoff->raceimg) > 1) ? $signoff->raceimg : '',
					'S_RACE_IMAGE_EXISTS' => (strlen($signoff->raceimg) > 1) ? true : false,
					'S_REQUEUE_ACTION' => $requeueurl,
					'S_REQUEUE_SIGNUP'	=> $requeue, 
				 				
				));
				
				foreach($this->raidroles as $key => $role)
				{
					$template->assign_block_vars('unavailable.raidroles', array(
						'ROLE_ID'        => $key,
						'ROLE_NAME'      => $role['role_name'],
					));
					
				}
			}
			unset($signoff);
			unset($key);
			
		}
		
		// button with url to push raidplan to bbdkp
		// this appears only if 
		// 1) rp_rppushmode == 1
		// 2) the user belongs to group having u_raidplanner_push permission
		// 3) there are confirmations
		$push_raidplan_url = '';
		if ( $auth->acl_gets('u_raidplanner_push') &&  $config['rp_rppushmode'] == 1 && $this->signups['confirmed'] > 0  )
		{
			$push_raidplan_url = append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;mode=push&amp;raidplanid=". $this->id);
		}
			
		// event image on top
		if(strlen( $this->eventlist->events[$this->event_type]['imagename'] ) > 1)
		{
			$eventimg = $phpbb_root_path . "images/bbdkp/event_images/" . $this->eventlist->events[$this->event_type]['imagename'] . ".png";
			
		}
		else 
		{
			$eventimg = $phpbb_root_path . "images/bbdkp/event_images/dummy.png";
		}
			
		// we need to find out the time zone to display
		// if anon then get board timezone or else get the users timezone
		if ($user->data['user_id'] == ANONYMOUS)
		{
		 	//grab board default
		 	$tz = $config['board_timezone'];  
		}
		else
		{
			// get user setting
			$tz = (int) $user->data['user_timezone'];
		}

		
		
		$template->assign_vars( array(
			'S_LOCKED'			=> $this->locked,
			'S_FROZEN'			=> $this->frozen,
			'S_NOCHAR'			=> $this->nochar,
			'S_SIGNED_UP'		=> $this->signed_up, 
			'S_SIGNED_OFF'		=> $this->signed_off, 
			'S_CONFIRMED'		=> $this->confirmed, 
			'S_CANSIGNUP'		=> $this->signups_allowed, 
		 	'S_LEGITUSER'		=> ($user->data['is_bot'] || $user->data['user_id'] == ANONYMOUS) ? false : true,
		 	'S_SIGNUPMAYBE'		=> $this->signed_up_maybe,
			'RAID_TOTAL'		=> $total_needed,
			'TZ'				=> $user->lang['tz'][$tz], 
		
			'CURR_CONFIRMED_COUNT'	 => $this->signups['confirmed'],
			'S_CURR_CONFIRMED_COUNT' => ($this->signups['confirmed'] > 0) ? true: false,
			'CURR_CONFIRMEDPCT'	=> sprintf( "%.2f%%", ($total_needed > 0 ? round(($this->signups['confirmed']) /  $total_needed, 2)*100 : 0)),
		
			'CURR_YES_COUNT'	=> $this->signups['yes'],
			'S_CURR_YES_COUNT'	=> ($this->signups['yes'] + $this->signups['maybe'] > 0) ? true: false,
			'CURR_YESPCT'		=> sprintf( "%.2f%%", ($total_needed > 0 ? round(($this->signups['yes']) /  $total_needed, 2)*100 : 0)),
		
			'CURR_MAYBE_COUNT'	=> $this->signups['maybe'],
			'S_CURR_MAYBE_COUNT' => ($this->signups['maybe'] > 0) ? true: false,
			'CURR_MAYBEPCT'		=> sprintf( "%.2f%%", ($total_needed > 0 ? round(($this->signups['maybe']) /  $total_needed, 2)*100 : 0)), 
			
			'CURR_NO_COUNT'		=> $this->signups['no'],
			'S_CURR_NO_COUNT'	=> ($this->signups['no'] > 0) ? true: false,
			'CURR_NOPCT'		=> sprintf( "%.2f%%", ($total_needed > 0 ? round(($this->signups['no']) /  $total_needed, 2)*100 : 0)),
		
			'CURR_TOTAL_COUNT'  => $this->signups['yes'] + $this->signups['maybe'],

			'ETYPE_DISPLAY_NAME'=> $this->eventlist->events[$this->event_type]['event_name'],
			'EVENT_COLOR'		=> $this->eventlist->events[$this->event_type]['color'],
			'EVENT_IMAGE' 		=> $eventimg, 
            
			'SUBJECT'			=> $this->subject,
			'MESSAGE'			=> $message,
		
			'INVITE_TIME'		=> $user->format_date($this->invite_time, $config['rp_date_time_format'], true),
			'START_TIME'		=> $user->format_date($this->start_time, $config['rp_date_time_format'], true),
			'START_DATE'		=> $user->format_date($this->start_time, $config['rp_date_format'], true),
			'END_TIME'			=> $user->format_date($this->end_time, $config['rp_date_time_format'], true),

			'U_SIGNUP_MODE_ACTION' => $signup_url,  
		 	'RAID_ID'			=> $this->id, 
			'S_PLANNER_RAIDPLAN'=> true,
		
			'IS_RECURRING'		=> $this->recurr_id,
			'POSTER'			=> $this->poster_url,
			'INVITED'			=> $this->invite_list,
			'TEAM_NAME'			=> $this->raidteamname, 
			'U_EDITRAID'		=> $edit_url,
			'U_DELETERAID'		=> $delete_url,	
			'U_ADDRAID'			=> $add_raidplan_url,
			'U_PUSHRAID'		=> $push_raidplan_url,
			
			'DAY_IMG'			=> $user->img('button_calendar_day', 'DAY'),
			'WEEK_IMG'			=> $user->img('button_calendar_week', 'WEEK'),
			'MONTH_IMG'			=> $user->img('button_calendar_month', 'MONTH'),
			'DAY_VIEW_URL'		=> $day_view_url,
			'WEEK_VIEW_URL'		=> $week_view_url,
			'MONTH_VIEW_URL'	=> $month_view_url,
			)
		);

		
	}

	/**
	 * return raid plan info array to send to template for tooltips in day/week/month/upcoming calendar
	 * 
	 * @param int $day		today
	 * @param int $month	this month
	 * @param int $year		this year
	 * @param string	$group_options 
	 * @param string 	$mode
	 * @param int 		$x		  	
	 * @return array
	 */
	public function GetRaidinfo($month, $day, $year, $group_options, $mode)
	{
		global $db, $user, $config, $phpbb_root_path, $phpEx;
		
		$raidplan_output = array();
		
		$x = 0;

		$raidplan_counter = 0;
		// build sql
		
		$day = ($day < 10 ? ' ' .$day : $day);
		$month = ($month < 10 ? ' ' .$month : $month);
		
		$sql_array = array(
   			'SELECT'    => 'r.raidplan_id ',   
			'FROM'		=> array(RP_RAIDS_TABLE => 'r'), 
			'WHERE'		=>  "(raidplan_access_level = 2 
					   OR (r.poster_id = ". $db->sql_escape($user->data['user_id'])." ) 
					   OR (r.raidplan_access_level = 1 AND (" . $group_options. ")) )  
					  AND (r.raidplan_day = '". $db->sql_escape($day . '-'. $month . '-' . $year) . "' ) " ,
			'ORDER_BY'	=> 'r.raidplan_start_time ASC'
		);
		
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query_limit($sql, $x, 0);

		// we need to find out the time zone to display on tooltip
		if ($user->data['user_id'] == ANONYMOUS)
		{
			//grab board default
			$tz = $config['board_timezone'];
		}
		else
		{
			// get user setting
			$tz = (int) $user->data['user_timezone'];
		}
		$timezone = $user->lang['tz'][$tz];
		
		$rpcounter = 0;
		
		while ($row = $db->sql_fetchrow($result))
		{
			unset($this);
			$this->id= $row['raidplan_id'];
			$this->make_obj();
			
			$fsubj = $subj = censor_text($this->subject);
			if( $config['rp_display_truncated_name'] > 0 )
			{
				if(utf8_strlen($subj) > $config['rp_display_truncated_name'])
				{
					$subj = truncate_string($subj, $config['rp_display_truncated_name']) . '...';
				}
			}
			
			$correct_format = $config['rp_time_format'];
			if( $this->end_time - $this->start_time > 86400 )
			{
				$correct_format = $config['rp_date_time_format'];
			}
			
			$pre_padding = 0;
			$padding = 0;
			$post_padding = 0;
			/* if in dayview we need to shift the raid to its time */
			if($mode =="day")
			{
	          // find padding values 
	          $pre_padding = 4 * $user->format_date($this->start_time, "H", true);
	          $padding = 4 * $user->format_date($this->end_time, "H", true) - $pre_padding;
	          $post_padding = 96 - $padding - $pre_padding;
			}
			
			$rolesinfo = array();
			$userchars = array();
			$total_needed = 0;
			
			// only show signup tooltip if user can actually sign up
			if($this->signups_allowed == true 
				&& $this->locked == false
				&& $this->frozen == false
				&& $this->nochar == false
				&& $this->signed_up == false
				&& $this->signed_off == false				
				&& $this->accesslevel != 0 
				&& !$user->data['is_bot'] 
				&& $user->data['user_id'] != ANONYMOUS)
			{
				foreach ($this->mychars as $key => $mychar)
				{
					if($mychar['role_id'] == '')
					{
						$userchars[] = array(
						        'MEMBER_ID'      	=> $mychar['id'],
								'MEMBER_NAME'  	 	=> $mychar['name'],							
								
						 );
					}
				}
				
				foreach($this->raidroles as $key => $role)
				{
					$rolesinfo[] = array(
						'ROLE_ID'        => $key,
						'ROLE_NAME'      => $role['role_name'],
					);
					
					$total_needed += $role['role_needed'];
				}
			}
			
			if(strlen( $this->eventlist->events[$this->event_type]['imagename'] ) > 1)
			{
				$eventimg = $phpbb_root_path . "images/bbdkp/event_images/" . $this->eventlist->events[$this->event_type]['imagename'] . ".png";
				
			}
			else 
			{
				$eventimg = $phpbb_root_path . "images/bbdkp/event_images/dummy.png";
			}
			
			$raidinfo = array(
				'TZ'					=> $timezone, 
				'RAID_ID'				=> $this->id,
				'PRE_PADDING'			=> $pre_padding,
				'POST_PADDING'			=> $post_padding,
				'PADDING'				=> $padding, 
				'ETYPE_DISPLAY_NAME' 	=> $this->eventlist->events[$this->event_type]['event_name'], 
				'FULL_SUBJECT' 			=> $fsubj,
				'EVENT_SUBJECT' 		=> $subj, 
				'COLOR' 				=> $this->eventlist->events[$this->event_type]['color'],
				'IMAGE' 				=> $eventimg, 
				'EVENT_URL'  			=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;raidplanid=".$this->id), 
				'EVENT_ID'  			=> $this->id,

				// for popup
				'S_ANON'				=> ($user->data['user_id'] == ANONYMOUS) ? true : false, 
				'S_LOCKED'				=> $this->locked,
				'S_FROZEN'				=> $this->frozen,
				'S_NOCHAR'				=> $this->nochar,
				'S_SIGNED_UP'			=> $this->signed_up, 
				'S_SIGNED_OFF'			=> $this->signed_off, 	
				'S_CONFIRMED'			=> $this->confirmed,
				'S_SIGNUPMAYBE'			=> $this->signed_up_maybe,
				'S_CANSIGNUP'			=> $this->signups_allowed, 
				'S_LEGITUSER'			=> ($user->data['is_bot'] || $user->data['user_id'] == ANONYMOUS) ? false : true, 
				'S_SIGNUP_MODE_ACTION' 	=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;raidplanid=".$this->id. "&amp;mode=signup"), 

				'INVITE_TIME'  			=> $user->format_date($this->invite_time, $correct_format, true), 
				'START_TIME'			=> $user->format_date($this->start_time, $correct_format, true),
				'END_TIME' 				=> $user->format_date($this->end_time, $correct_format, true),
				
				'DISPLAY_BOLD'			=> ($user->data['user_id'] == $this->poster) ? true : false,
				'ALL_DAY'				=> ($this->all_day == 1  ) ? true : false,
				'SHOW_TIME'				=> ($mode == "day" || $mode == "week" ) ? true : false, 
				'COUNTER'				=> $raidplan_counter++, 
			
				'RAID_TOTAL'			=> $total_needed,
			
				'CURR_CONFIRMED_COUNT'	 => $this->signups['confirmed'],
				'S_CURR_CONFIRMED_COUNT' => ($this->signups['confirmed'] > 0) ? true: false,
				'CURR_CONFIRMEDPCT'		=> sprintf( "%.0f%%", ($total_needed > 0 ? round(($this->signups['confirmed']) /  $total_needed, 2) *100 : 0)),
				
				'CURR_YES_COUNT'		=> $this->signups['yes'],
				'S_CURR_YES_COUNT'		=> ($this->signups['yes'] + $this->signups['maybe'] > 0) ? true: false,
				'CURR_YESPCT'			=> sprintf( "%.0f%%", ($total_needed > 0 ? round(($this->signups['yes']) /  $total_needed, 2) *100 : 0)),
			
				'CURR_MAYBE_COUNT'		=> $this->signups['maybe'],
				'S_CURR_MAYBE_COUNT' 	=> ($this->signups['maybe'] > 0) ? true: false,
				'CURR_MAYBEPCT'			=> sprintf( "%.0f%%", ($total_needed > 0 ? round(($this->signups['maybe']) /  $total_needed, 2) *100 : 0)), 
				
				'CURR_NO_COUNT'			=> $this->signups['no'],
				'S_CURR_NO_COUNT'		=> ($this->signups['no'] > 0) ? true: false,
				'CURR_NOPCT'			=> sprintf( "%.0f%%", ($total_needed > 0 ? round(($this->signups['no']) /  $total_needed, 2) *100 : 0)),
			
				'CURR_TOTAL_COUNT'  	=> $this->signups['yes'] + $this->signups['maybe'],
			
			);
			$rpcounter +=1;
			
			$hourslot = $user->format_date($this->invite_time, 'Hi', true);
			
			$raidplan_output[$hourslot . '_' . $rpcounter] = array(
				'raidinfo' => $raidinfo,
				'userchars' => $userchars,
				'raidroles' => $rolesinfo
			);
			
		}
		$db->sql_freeresult($result);
		
		return $raidplan_output;
	}	

	/**
	 * checks if user is allowed to *see* raid
	 *
	 * @return void
	 */
	private function checkauth()
	{
		global $user, $db;
		
		$this->auth_cansee = false;
		
		if ($this->poster == $user->data['user_id'])
		{
			//own raids - creator always can see
			$this->auth_cansee = true;
		}
		else 
		{
			// if not own raid then look at access level.	
			switch($this->accesslevel)
			{
				case 0:
					// personal raidplan... only raidplan creator is invited
					$this->auth_cansee = false;
					break;
				case 1:
					// group raidplan... only members of specified phpbb usergroup are invited
					// is this user a member of the group?
					if($this->group_id !=0)
					{
						$sql = 'SELECT g.group_id
								FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
								WHERE ug.user_id = '.$db->sql_escape($user->data['user_id']).'
									AND g.group_id = ug.group_id
									AND g.group_id = '.$db->sql_escape($this->group_id).'
									AND ug.user_pending = 0';
						$result = $db->sql_query($sql);
						if($result)
						{
							$row = $db->sql_fetchrow($result);
							if( $row['group_id'] == $this->group_id )
							{
								$this->auth_cansee = true;
							}
						}
						$db->sql_freeresult($result);
					}
					else 
					{
						$group_list = explode( ',', $this->group_id_list);
						$num_groups = sizeof( $group_list );
						$group_options = '';
						for( $i = 0; $i < $num_groups; $i++ )
						{
						    if( $group_list[$i] == "" )
						    {
						    	continue;
						    }
							if( $group_options != "" )
							{
								$group_options = $group_options . " OR ";
							}
							$group_options = $group_options . "g.group_id = ".$group_list[$i];
						}
						$sql = 'SELECT g.group_id
								FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
								WHERE ug.user_id = '.$db->sql_escape($user->data['user_id']).'
									AND g.group_id = ug.group_id
									AND ('.$group_options.')
									AND ug.user_pending = 0';
						$result = $db->sql_query($sql);
						if( $result )
						{
							$this->auth_cansee = true;
						}
						$db->sql_freeresult($result);
					}
					break;
				case 2:
					// public raidplan... everyone is invited
					$this->auth_cansee = true;
					break;
			}
		}
	}

	
	
	
	/**
	 * checks if user can post new raid
	 *
	 * @return void
	 */
	private function checkauth_canadd()
	{
		global $auth;
		$this->auth_canadd = false;
		switch ($this->accesslevel)
		{
			case 0:
				// can create personal appointment ?
				if ( $auth->acl_get('u_raidplanner_create_private_raidplans') )
				{
					$this->auth_canadd = true;
				}
				break;
			case 1:
				// can create group raid ? -- only group members can attend
				if ( $auth->acl_get('u_raidplanner_create_group_raidplans') )
				{
					$this->auth_canadd = true;
				}
				break;
			case 2:
				//can make public raid ? -- every member can attend
				if ( $auth->acl_get('u_raidplanner_create_public_raidplans') )
				{
					$this->auth_canadd = true;
				}
				break;
				
		}
		
	}
	
	/**
	 * checks if user can edit the raid(s)
	 *
	 * @return void
	 */
	private function checkauth_canedit()
	{
		global $user, $auth, $config;
		
		$this->auth_canedit = true;
		
		if ($user->data['is_bot'])
		{
			$this->auth_canedit = false;
		}
		elseif ($user->data['is_registered'] )
		{
			// has user right to edit raidplans?
			if (!$auth->acl_get('u_raidplanner_edit_raidplans') )
			{
				$this->auth_canedit = false;
			}
			else
			{
				// has user right to edit others raids ?
				if (!$auth->acl_get('m_raidplanner_edit_other_users_raidplans') && ($user->data['user_id'] != $this->poster) )
				{
					$this->auth_canedit = false;
				}
				
				// @todo testing 
				// if raid expired then no edits possible even if user can edit own raids...
				// this way officers cant fiddle with statistics

                if ($config['rp_default_expiretime'] != 0 && $config['rp_enable_past_raids'] == 0)
                {
                    if (time() + $user->timezone + $user->dst - date('Z') - $this->end_time > $config['rp_default_expiretime']*60)
                    {
                        // assign editing expired raids only to administrator.
                        if (!$auth->acl_get('a_raid_config') )
                        {
                            $this->auth_canedit = false;
                        }
                    }

                }


				
			}
		}
	}
	
	/**
	 * checks if user can delete raid(s)
	 *
	 * @return void
	 */
	private function checkauth_candelete()
	{
		global $user, $auth;
		$this->auth_candelete = false;
		
		if ($user->data['is_registered'] )
		{
			if($auth->acl_get('u_raidplanner_delete_raidplans'))
			{
				$this->auth_candelete = true;

				// is raidleader trying to delete other raid ?
				if ($user->data['user_id'] != $this->poster) 
				{
					if (! $auth->acl_get('m_raidplanner_delete_other_users_raidplans'))
					{
						$this->auth_candelete = false;
					}
				}
			}
			
		}
		
	}
	
	/**
	 * checks if the new event is one that members can sign up to (rsvp) only valid for accesslevel 1/2 
	 *
	 * @return void
	 */
	private function checkauth_canaddsignup()
	{
		global $auth;
		$this->auth_canaddsignups = false;
		if( $auth->acl_get('u_raidplanner_track_signups'))
		{
			$this->auth_canaddsignups = true;
		}
		
	}

	
	/**
	 * builds raid roles property, needed sor displaying signups
	 *
	 */
	private function get_raid_roles()
	{
		global $db;
		
		$sql_array = array(
	    	'SELECT'    => 'rr.raidplandet_id, rr.role_needed, rr.role_signedup, rr.role_confirmed, 
	    					r.role_id, r.role_name, r.role_color, r.role_icon ', 
	    	'FROM'      => array(
				RP_ROLES   => 'r',
				RP_RAIDPLAN_ROLES   => 'rr'
	    	),
	    	'WHERE'		=>  'r.role_id = rr.role_id and rr.raidplan_id = ' . $this->id, 
	    	'ORDER_BY'  => 'r.role_id'
			);
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		$signups = array();
		$confirmations = array();
		$this->raidroles = array();
		while ( $row = $db->sql_fetchrow ( $result ) )
		{
			$this->raidroles[$row['role_id']]['role_name'] = $row['role_name'];
			$this->raidroles[$row['role_id']]['role_color'] = $row['role_color'];
			$this->raidroles[$row['role_id']]['role_icon'] = $row['role_icon']; 
			$this->raidroles[$row['role_id']]['role_needed'] = $row['role_needed']; 
			$this->raidroles[$row['role_id']]['role_signedup'] = $row['role_signedup']; 
			$this->raidroles[$row['role_id']]['role_confirmed'] = $row['role_confirmed']; 
			$this->raidroles[$row['role_id']]['role_confirmations'] =  $confirmations;
			$this->raidroles[$row['role_id']]['role_signups'] =  $signups;
		}
		$db->sql_freeresult($result);
	}
	
		
	/**
	 * builds roles property, needed when you make new raid
	 *
	 */
	private function get_roles()
	{
		global $db;
		
		$sql_array = array(
	    	'SELECT'    => 'r.role_id, r.role_name, r.role_color, r.role_icon ', 
	    	'FROM'      => array(
				RP_ROLES   => 'r'
	    	),
	    	'ORDER_BY'  => 'r.role_id'
			);
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		while ( $row = $db->sql_fetchrow ( $result ) )
		{
			$this->roles[$row['role_id']]['role_name'] = $row['role_name'];
			$this->roles[$row['role_id']]['role_color'] = $row['role_color'];
			$this->roles[$row['role_id']]['role_icon'] = $row['role_icon']; 
		}
		$db->sql_freeresult($result);
	}
	
	/**
	 * selects all signups that have a role, then makes signup objects, returns array of objects to role code
	 * 0 unavailable 1 maybe 2 available 3 confirmed
	 * 
	 */
	private function getSignups()
	{
		global $db, $phpEx, $phpbb_root_path, $db;

		if (!class_exists('\bbdkp\raidplanner\RaidplanSignup'))
		{
			require("{$phpbb_root_path}includes/bbdkp/raidplanner/RaidplanSignup.$phpEx");
		}
		$rpsignup = new RaidplanSignup();
		
		// fill mychars array for popup
		$this->mychars = $rpsignup->getmychars($this->id);
		
		//fill signups array 
		foreach ($this->raidroles as $roleid => $role)
		{
			$sql = "select * from " . RP_SIGNUPS . " where raidplan_id = " . $this->id . " and signup_val > 0 and role_id  = " . $roleid;
			$result = $db->sql_query($sql);
			$signups = array();
			while ($row = $db->sql_fetchrow($result))
			{
				//bind all public object vars of signup class instance to signup array and add to role array 
				$rpsignup->getSignup($row['signup_id'], $this->eventlist->events[$this->event_type]['dkpid']);
				if($rpsignup->signup_val == 1 || $rpsignup->signup_val == 2)
				{
					// maybe + available
					$this->raidroles[$roleid]['role_signups'][] = $rpsignup;
				}
				elseif($rpsignup->signup_val == 3)
				{
					//confirmed
					$this->raidroles[$roleid]['role_confirmations'][] = $rpsignup;
				}
				
			}
			$db->sql_freeresult($result);
		}
		
		unset($roleid);
		unset($role);

	}
	
	/**
	 * get all those that signed unavailable
	 * 0 unavailable 1 maybe 2 available 3 confirmed
	 *
	 */
	public function get_unavailable()
	{
		global $db, $config, $phpbb_root_path, $db, $phpEx;
		
		if (!class_exists('\bbdkp\raidplanner\RaidplanSignup'))
		{
			require("{$phpbb_root_path}includes/bbdkp/raidplanner/RaidplanSignup.$phpEx");
		}
		$rpsignup = new RaidplanSignup();
		
		$sql = "select * from " . RP_SIGNUPS . " where raidplan_id = " . $this->id . " and signup_val = 0";
		$result = $db->sql_query($sql);
		$this->signoffs = array();		
		
		while ($row = $db->sql_fetchrow($result))
		{
			$rpsignup->getSignup($row['signup_id'], $this->eventlist->events[$this->event_type]['dkpid']);
			//get all public object vars to signup array and bind to role
			$this->signoffs[] = $rpsignup;
		}
		$db->sql_freeresult($result);
	}
	
	/**
	 * gets array with raid days 
	 * @param int $from
	 * @param int $end
	 * 
	 * @return array
	 */
	public function GetRaiddaylist($from, $end)
	{
		//GMT: Tue, 01 Nov 2011 00:00:00 GMT
		global $user, $db;
		
		// build sql 
		$sql_array = array(
   			'SELECT'    => 'r.raidplan_start_time ', 
			'FROM'		=> array(RP_RAIDS_TABLE => 'r' ), 
			'WHERE'		=>  ' r.raidplan_start_time >= '. $db->sql_escape($from) . ' 
							 AND r.raidplan_start_time <= '. $db->sql_escape($end) ,
			'ORDER_BY'	=> 'r.raidplan_start_time ASC');
		
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		$raiddaylist = array();
		while ($row = $db->sql_fetchrow($result))
		{
			
			$day = $user->format_date($row['raidplan_start_time'], "d", true);
			$month =  $user->format_date($row['raidplan_start_time'], "n", true);
			$year =  $user->format_date($row['raidplan_start_time'], "Y", true);
			
			// key is made to be unique
			$raiddaylist [$month . '-' . $day . '-' . $year] = array(
				'sig' => $month . '-' . $day . '-' . $year, 
				'month' => $month,
				'day' => $day,
				'year' => $year
			); 
		}
		
		$db->sql_freeresult($result);
		return $raiddaylist;
		
	}

    /**
     * raidmessenger
     *
     * eventhandler for
     * raidplan add
     *   send to all who have a dkp member with points
     * raidplan update
     *   send to raidplan participants
     * raidplan delete
     *   send to raidplan participants
     *
     * @param $trigger
     */
    private function raidmessenger($trigger)
	{
		global $user, $config;
		global $phpEx, $phpbb_root_path;

		include_once($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);
		include_once($phpbb_root_path . 'includes/functions.' . $phpEx);
		include_once($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);
		include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);

		if (!class_exists('\bbdkp\raidplanner\raidmessenger'))
		{
			require("{$phpbb_root_path}includes/bbdkp/raidplanner/raidmessenger.$phpEx");
		}
		$rpm = new raidmessenger();
		$rpm->get_notifiable_users($trigger, $this->id);

		$emailrecipients = array();
		$messenger = new \messenger();
		foreach($rpm->send_user_data as $id => $row)
		{
			$data=array();
			// get template
			switch ($trigger)
			{
				case 1:
					$messenger->template('raidplan_add', $row['user_lang']);
					$subject = '[' . $user->lang['RAIDPLANNER']  . '] ' . $user->lang['NEWRAID'] . ': ' . $this->eventlist->events[$this->event_type]['event_name'] . ' ' . $user->format_date($this->start_time, $config['rp_date_time_format'], true);
					break;
				case 2:
					$messenger->template('raidplan_update', $row['user_lang']);
					$subject =  '[' . $user->lang['RAIDPLANNER']  . '] ' . $user->lang['UPDRAID'] . ': ' . $this->eventlist->events[$this->event_type]['event_name'] . ' ' .$user->format_date($this->start_time, $config['rp_date_time_format'], true);
					break;						
				case 3:
					$messenger->template('raidplan_delete', $row['user_lang']);
					$subject =  '[' . $user->lang['RAIDPLANNER']  . '] ' . $user->lang['DELRAID'] . ': ' . $this->eventlist->events[$this->event_type]['event_name'] . ' ' . $user->format_date($this->start_time, $config['rp_date_time_format'], true);
					break;						
			}
		   
		   $userids = array($this->poster);
		   $rlname = array();
		   user_get_id_name($userids, $rlname);
		   
		   $messenger->assign_vars(array(
		   		'RAIDLEADER'		=> $rlname[$this->poster],
				'USERNAME'			=> htmlspecialchars_decode($row['username']),
				'EVENT_SUBJECT'		=> $subject, 
		   		'EVENT'				=> $this->eventlist->events[$this->event_type]['event_name'], 
				'INVITE_TIME'		=> $user->format_date($this->invite_time, $config['rp_date_time_format'], true),
				'START_TIME'		=> $user->format_date($this->start_time, $config['rp_date_time_format'], true),
				'END_TIME'			=> $user->format_date($this->end_time, $config['rp_date_time_format'], true),
				'TZ'				=> $user->lang['tz'][(int) $user->data['user_timezone']],
				'U_RAIDPLAN'		=> generate_board_url() . "/dkp.$phpEx?page=planner&amp;view=raidplan&amp;raidplanid=".$this->id
			));
			
			$messenger->msg = trim($messenger->tpl_obj->assign_display('body'));
			$messenger->msg = str_replace("\r\n", "\n", $messenger->msg);
			
			$messenger->msg = utf8_normalize_nfc($messenger->msg);
    		$uid = $bitfield = $options = ''; // will be modified by generate_text_for_storage
    		$allow_bbcode = $allow_smilies = $allow_urls = true;
    		generate_text_for_storage($messenger->msg, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
    		$messenger->msg = generate_text_for_display($messenger->msg, $uid, $bitfield, $options); 
    		
    		$data = array( 
			    'address_list'      => array('u' => array($row['user_id'] => 'to')),
			    'from_user_id'      => $user->data['user_id'],
			    'from_username'     => $user->data['username'],
			    'icon_id'           => 0,
			    'from_user_ip'      => $user->data['user_ip'],
			     
			    'enable_bbcode'     => true,
			    'enable_smilies'    => true,
			    'enable_urls'       => true,
			    'enable_sig'        => true,
			
			    'message'           => $messenger->msg, 
			    'bbcode_bitfield'   => $this->bbcode['bitfield'],
			    'bbcode_uid'        => $this->bbcode['uid'],
			);
			
			if($config['rp_pm_rpchange'] == 1 &&  (int) $row['user_allow_pm'] == 1)
			{
				// send a PM
				submit_pm('post',$subject, $data, false);
			}
			
			if($config['rp_email_rpchange'] == 1 && $row['user_email'] != '')
			{
				//send email, reuse messenger object
			   $email = $messenger;
			   $emailrecipients[]=$row['username'];
			   $email->to($row['user_email'], $row['username']);
			   $email->anti_abuse_headers($config, $user);
			   $email->send(0);
			}
			
		}
		
		if($config['rp_email_rpchange'] == 1 && isset($email))
		{
			$email->save_queue();
			$emailrecipients = implode(', ', $emailrecipients);
			add_log('admin', 'LOG_MASS_EMAIL', $emailrecipients);
		}
		
		
	}
	

	/**
	 * adds raid to bbdkp
	 *
	 */
	public function raidplan_push()
	{
		global $db, $user, $config, $phpbb_root_path, $phpEx ;

        if (!class_exists('\bbdkp\controller\raids\RaidController'))
        {
            require("{$phpbb_root_path}includes/bbdkp/controller/raids/RaidController.$phpEx");
        }
		// check if raid exists in bbdkp
		if ($this->raid_id > 0)
		{
            $RaidController = new RaidController;

            $raidinfo = array (
                'raid_id' 	 => (int) $this->raid_id,
                'event_id' 	 => $this->event_type,
                'raid_value' => (float) $this->eventlist->events[$this->event_type]['value'],
                'time_bonus' => 0,
                'raid_note'  => $this->body,
                'raid_start' => $this->start_time,
                'raid_end' 	 => $this->end_time,
            );

            $RaidController->update_raid($raidinfo);

            //get all confirmed raiders
			$raid_attendees = array();
			foreach($this->raidroles as $key => $role)
			{
				 foreach($role['role_confirmations'] as $confirmation)
				 {
				 	$raid_attendees[] = $confirmation->dkpmemberid;
				 }
			}
			
			// now check if any of them are not registered in dkp, if they are not then add them
            $raiddetail = new Raiddetail($this->raid_id);
            $raiddetail->Get($this->raid_id);

			$registered = array();
            foreach ($raiddetail->raid_details as $member_id => $attendee)
            {
                $registered[] = (int) $member_id;
            }
            //
			$to_add = array_diff($raid_attendees, $registered);

			if (count($to_add) > 0)
			{
				foreach($to_add as $member_id)
				{
                    $newraider = new Raiddetail($this->raid_id);
                    $newraider->raid_value = (float) $this->eventlist->events[$this->event_type]['value'];
                    $newraider->time_bonus = 0;
                    $newraider->zerosum_bonus = 0;
                    $newraider->raid_decay = 0;
                    $newraider->dkpid = $this->eventlist->events[$this->event_type]['dkpid'];
                    $newraider->member_id = $member_id;
                    $newraider->create();
                    unset($newraider);
				}
			}
			
		}
		else
		{
            //new raid

			if($config['rp_rppushmode'] == 0 && $this->signups['confirmed'] > 0 )
			{
				// automatic mode, don't ask permisison
				$raid_attendees = array();
				foreach($this->raidroles as $key => $role)
				{
					 foreach($role['role_confirmations'] as $confirmation)
					 {
					 	$raid_attendees[] = $confirmation->dkpmemberid;
					 }
				}
				
				// timebonus is hardcoded to zero but could be changed later...
				$raid = array(
						'raid_note' 				=> $this->body, 
						'raid_value' 				=> $this->eventlist->events[$this->event_type]['value'],
                        'raid_timebonus'	        => request_var ('hidden_raid_timebonus', 0.00 ),
                        'zerosum_bonus'	            => 0,
                        'raid_decay'	            => 0,
						'raid_start'			 	=> $this->start_time,
						'raid_end' 					=> $this->end_time,  
						'event_name'				=> $this->eventlist->events[$this->event_type]['event_name'], 
						'event_id' 					=> $this->event_type,
						'dkpid'						=> $this->eventlist->events[$this->event_type]['dkpid'],
						'raid_attendees' 			=> $raid_attendees
				);
				$this->exec_pushraid($raid);
				
			}
			else
			{
				
				//insert
				if (confirm_box ( true )) 
				{
					// recall hidden vars
					$raid = array(
						'raid_note' 		=> utf8_normalize_nfc (request_var ( 'hidden_raid_note', ' ', true )), 
						'raid_value' 		=> request_var ('hidden_raid_value', 0.00 ), 
						'raid_timebonus'	=> request_var ('hidden_raid_timebonus', 0.00 ),
                        'zerosum_bonus'	    => 0,
                        'raid_decay'	    => 0,
						'raid_start' 		=> request_var ('hidden_startraid_date', 0), 
						'raid_end'			=> request_var ('hidden_endraid_date', 0),
						'event_name'		=> utf8_normalize_nfc (request_var ( 'hidden_raid_name', ' ', true )), 
						'event_id' 			=> request_var ('hidden_event_id', 0),
						'dkpid'				=> request_var ('hidden_dkpid', 0),
						'raid_attendees' 	=> request_var ('hidden_raid_attendees', array ( 0 => 0 )), 
					); 
		
					$this->exec_pushraid($raid);

				}
				else
				{
					
					// store raidinfo as hidden vars
					// this clears the $_POST array
					$raid_attendees = array();
					foreach($this->raidroles as $key => $role)
					{
						 foreach($role['role_confirmations'] as $confirmation)
						 {
						 	$raid_attendees[] = $confirmation->dkpmemberid;
						 }
					}
					
					// timebonus is hardcoded to zero but could be changed later...
					$s_hidden_fields = build_hidden_fields(array(
							'hidden_raid_id' 			=> $this->raid_id,
							'hidden_raid_note' 			=> $this->body, 
							'hidden_event_id' 			=> $this->event_type,
							'hidden_raid_name'			=> $this->eventlist->events[$this->event_type]['event_name'], 
							'hidden_raid_value' 		=> $this->eventlist->events[$this->event_type]['value'],
							'hidden_dkpid'				=> $this->eventlist->events[$this->event_type]['dkpid'],
							'hidden_raid_timebonus' 	=> 0,
							'hidden_startraid_date' 	=> $this->start_time,
							'hidden_endraid_date' 		=> $this->end_time,  
							'hidden_raid_attendees' 	=> $raid_attendees, 
							'add'    					=> true, 
					)
					);
					
					confirm_box(false, sprintf($user->lang['CONFIRM_CREATE_RAID'], 
						$this->eventlist->events[$this->event_type]['event_name']) , $s_hidden_fields);			
				}
				
			}
			
			
		}

        unset($RaidController);

	}
	
	/**
	 * private subroutine for raidplan_push
	 *
	 * @param array $raid
	 */
	private function exec_pushraid($raid)
	{
		global $db, $phpbb_root_path, $phpEx;

        if (!class_exists('\bbdkp\controller\raids\RaidController'))
        {
            require("{$phpbb_root_path}includes/bbdkp/controller/raids/RaidController.$phpEx");
        }
        if (!class_exists('\bbdkp\controller\points\PointsController'))
        {
            require("{$phpbb_root_path}includes/bbdkp/controller/points/PointsController.$phpEx");
        }

        $RaidController = new RaidController($raid['dkpid']);
        $RaidController->init_newraid();
        $event = $RaidController->eventinfo[$raid['event_id']];
        $raidinfo = array(
            'raid_note' 		=> (string) $raid['raid_note'],
            'event_id' 			=> $raid['event_id'],
            'raid_start' 		=> (int) $raid['raid_start'],
            'raid_end'			=> (int) $raid['raid_end'],
        );

        $raid_detail = array();
		foreach ( $raid['raid_attendees'] as $member_id )
		{
			$raid_detail[] = array(
				'member_id'    => (int) $member_id,
				'raid_value'   => (float) $raid['raid_value'],
                'zerosum_bonus'   => (float) $raid['zerosum_bonus'],
				'time_bonus'   => (float) $raid['raid_timebonus'],
                'raid_decay'   => (float) $raid['raid_decay'],
				);
		}

        $raid_id = $RaidController->add_raid($raidinfo, $raid_detail);

        $PointsController = new PointsController();
        $PointsController->add_points($raid_id);

		//store raid_id
		$sql = 'UPDATE ' . RP_RAIDS_TABLE . ' SET raid_id = '  . $raid_id . ' WHERE raidplan_id = ' . $this->id;
		$db->sql_query($sql);

        unset($RaidController);
        unset($PointsController);
		return $raid_id;
	}


    /**
     * kick raider after raid was pushed
     *
     * @param int $member_id
     *
     */
    public function deleteraider($member_id)
	{

		global $phpbb_root_path, $phpEx, $db;
        if (!class_exists('\bbdkp\controller\raids\RaidController'))
        {
            require("{$phpbb_root_path}includes/bbdkp/controller/raids/RaidController.$phpEx");
        }
        $RaidController = new RaidController($this->raid_id);
        $RaidController->deleteraider($this->raid_id,$member_id);
        unset($RaidController);
	}
	
}

?>