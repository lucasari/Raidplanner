<?php
/**
*
* @author alightner
* @author Sajaki
* @package bbDKP Raidplanner
* @copyright (c) 2009 alightner
* @copyright (c) 2011 Sajaki : refactoring, adapting to bbdkp
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
* 
*/

/**
 * @ignore
 */
if ( !defined('IN_PHPBB') OR !defined('IN_BBDKP') )
{
	exit;
}

/*
 * raidplan functions
 */
class raidplans
{
	/**
	 * event array
	 *
	 * @var array
	 */
	public 	$event;
	public 	$event_count;
	
	function __construct()
	{
		global $db; 
		
		$sql = 'SELECT * FROM ' . EVENTS_TABLE . ' ORDER BY event_id';
		$result = $db->sql_query($sql);
		$this->raid_plan_count = 0;
		while ($row = $db->sql_fetchrow($result))
		{
			$this->event[$row['event_id']]['event_name'] = $row['event_name'];
			$this->event[$row['event_id']]['color'] = $row['event_color'];
			$this->event[$row['event_id']]['imagename'] = $row['event_imagename'];
			$this->event_count++;
		}
		$db->sql_freeresult($result);
		
	}

	/**
	 * gets array with raid days 
	 *
	 * @param int $from
	 * @param int $end
	 * 
	 * @return array
	 */
	public function GetRaiddaylist($from, $end)
	{
		global $db;
		
		// build sql 
		$sql_array = array(
   			'SELECT'    => 'r.raidplan_start_time ', 
			'FROM'		=> array(RP_RAIDS_TABLE => 'r'), 
			'WHERE'		=>  ' ( (raidplan_access_level = 2)
							   OR (poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR (raidplan_access_level = 1 AND ('.$group_options.')) )  
							  AND (raidplan_start_time >= '.$db->sql_escape($from).' AND raidplan_start_time <= '.$db->sql_escape($end). " )",
			'ORDER_BY'	=> 'r.raidplan_start_time ASC');
		
		// filter on event type ?
		$calEType = request_var('calEType', 0);
		if( $calEType != 0)
		{
			$sql_array['WHERE'] .= " AND etype_id = ".$db->sql_escape($calEType)." ";
			$etype_url_opts = "&amp;calEType=".$calEType;
		}
		
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		
		$raiddaylist = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$raiddaylist [] = $row['raidplan_start_time']; 
		}
		
		$db->sql_freeresult($result);
		return $raiddaylist;
		
	}
	
	/**
	 * return raid plan info array to concrete template implementor class
	 * called by display()
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
		global $db, $user, $template, $config, $phpbb_root_path, $auth, $phpEx;
		
		$raidplan_output = array();
		
		//find any raidplans on this day
		$start_temp_date = gmmktime(0,0,0,$month, $day, $year)  - $user->timezone - $user->dst;
		
		switch($mode)
		{
			case "up":
				// get next x upcoming raids  
				// find all day raidplans since 1 days ago
				$start_temp_date = $start_temp_date - 30*86400+1;
				// don't list raidplans more than 2 months in the future
				$end_temp_date = $start_temp_date + 31536000;
				// show only this number of raids
				$x = $config['rp_index_display_next_raidplans'];
				break;
			case "next":
				// display the upcoming raidplans for the next x number of days
				$end_temp_date = $start_temp_date + ( $config['rp_index_display_next_raidplans'] * 86400 );
				$x = 0;
				break;
			default:
				$end_temp_date = $start_temp_date + 86399;
				//return all rows
				$x = 0;
		}
		
		$etype_url_opts = "";
		$raidplan_counter = 0;

		// build sql 
		$sql_array = array(
   			'SELECT'    => 'r.*', 
			'FROM'		=> array(RP_RAIDS_TABLE => 'r'), 
			'WHERE'		=>  ' ( (raidplan_access_level = 2)
							   OR (poster_id = '.$db->sql_escape($user->data['user_id']).' ) OR (raidplan_access_level = 1 AND ('.$group_options.')) )  
							  AND (raidplan_start_time >= '.$db->sql_escape($start_temp_date).' AND raidplan_start_time <= '.$db->sql_escape($end_temp_date). " )",
			'ORDER_BY'	=> 'r.raidplan_start_time ASC');
		
		// filter on event type ?
		$calEType = request_var('calEType', 0);
		if( $calEType != 0)
		{
			$sql_array['WHERE'] .= " AND etype_id = ".$db->sql_escape($calEType)." ";
			$etype_url_opts = "&amp;calEType=".$calEType;
		}
		
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query_limit($sql, $x, 0);

		while ($row = $db->sql_fetchrow($result))
		{

			$fsubj = $subj = censor_text($row['raidplan_subject']);
			if( $config['rp_display_truncated_name'] > 0 )
			{
				if(utf8_strlen($subj) > $config['rp_display_truncated_name'])
				{
					$subj = truncate_string($subj, $config['rp_display_truncated_name']) . '...';
				}
			}
			
			$correct_format = $config['rp_time_format'];
			if( $row['raidplan_end_time'] - $row['raidplan_start_time'] > 86400 )
			{
				$correct_format = $config['rp_date_time_format'];
			}
			
			$pre_padding = 0;
			$post_padding = 0;
			/* if in dayview we need to shift the raid to its time */
			if($mode =="day")
			{
				/* sets the colspan width */
		        if( $row['raidplan_start_time'] > $start_temp_date )
		        {
		          // find pre-padding value...
		          $start_diff = $row['raidplan_start_time'] - $start_temp_date;
		          $pre_padding = round($start_diff/900);
		        }
		
		        if( $row['raidplan_end_time'] < $end_temp_date )
		        {
		          // find pre-padding value...
		          $end_diff = $end_temp_date - $row['raidplan_end_time'];
		          $post_padding = round($end_diff/900);
		        }
			}

			/*
			 * $poster_url = '';
			$invite_list = '';
			$raidplans->get_raidplan_invites($row, $poster_url, $invite_list );
			$raidplan_data['POSTER'] = $poster_url;
			$raidplan_data['INVITED'] = $invite_list;
			$raidplan_data['ALL_DAY'] = 0;
			$row['raidplan_all_day'] == 1 
			list($eday['eday_day'], $eday['eday_month'], $eday['eday_year']) = explode('-', $row['raidplan_day']);
			$row['raidplan_start_time'] = gmmktime(0,0,0,$eday['eday_month'], $eday['eday_day'], $eday['eday_year'])- $user->timezone - $user->dst;
			$row['raidplan_end_time'] = $row['raidplan_start_time']+86399;
			*/
			
			$raidplan_output[] = array(
				'PRE_PADDING'			=> $pre_padding,
				'POST_PADDING'			=> $post_padding,
				'PADDING'				=> 96 - $pre_padding - $post_padding, 
				'ETYPE_DISPLAY_NAME' 	=> $this->event[$row['etype_id']]['event_name'], 
				'FULL_SUBJECT' 			=> $fsubj,
				'EVENT_SUBJECT' 		=> $subj, 
				'COLOR' 				=> $this->event[$row['etype_id']]['color'],
				'IMAGE' 				=> $phpbb_root_path . "images/event_images/" . $this->event[$row['etype_id']]['imagename'] . ".png", 
				'S_EVENT_IMAGE_EXISTS'  => (strlen( $this->event[$row['etype_id']]['imagename'] ) > 1) ? true : false,
				'EVENT_URL'  			=> append_sid("{$phpbb_root_path}dkp.$phpEx", "page=planner&amp;view=raidplan&amp;calEid=".$row['raidplan_id'].$etype_url_opts), 
				'EVENT_ID'  			=> $row['raidplan_id'],
				'INVITE_TIME'  			=> $user->format_date($row['raidplan_invite_time'], $correct_format, true), 
				'START_TIME'			=> $user->format_date($row['raidplan_start_time'], $correct_format, true),
				'END_TIME' 				=> $user->format_date($row['raidplan_end_time'], $correct_format, true),
				'DISPLAY_BOLD'			=> ($user->data['user_id'] == $row['poster_id'] ) ? true : false,
				'DISPLAY_BOLD'			=> ($user->data['user_id'] == $row['poster_id'] ) ? true : false,
				'ALL_DAY'				=> ($row['raidplan_all_day'] == 1  ) ? true : false,
				'SHOW_TIME'				=> ($mode == "day" ) ? true : false, 
				'COUNTER'				=> $raidplan_counter++, 
			);

		}
		$db->sql_freeresult($result);
		
		return $raidplan_output;
	}
	
	
	
	/* get_raidplan_data()
	**
	** Given an raidplan id, find all the data associated with the raidplan
	*/
	public function get_raidplan_data($id)
	{
		global $auth, $db, $user, $config;
		if( $id < 1 )
		{
			trigger_error('NO_RAIDPLAN');
		}
		$sql = 'SELECT * FROM ' . RP_RAIDS_TABLE . '
				WHERE raidplan_id = '.$db->sql_escape($id);
		$result = $db->sql_query($sql);
		$raidplan_data = $db->sql_fetchrow($result);
		if( !$raidplan_data )
		{
			trigger_error('NO_RAIDPLAN');
		}
	
	    $db->sql_freeresult($result);
	
		if( $raidplan_data['recurr_id'] > 0 )
		{
		    $raidplan_data['is_recurr'] = 1;
			$sql = 'SELECT * FROM ' . RP_RECURRING . '
						WHERE recurr_id = '.$db->sql_escape( $raidplan_data['recurr_id'] );
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
	    	$db->sql_freeresult($result);

	    	$raidplan_data['frequency_type'] = $row['frequency_type'];
		    $raidplan_data['frequency'] = $row['frequency'];
		    $raidplan_data['final_occ_time'] = $row['final_occ_time'];
		    $raidplan_data['week_index'] = $row['week_index'];
		    $raidplan_data['first_day_of_week'] = $row['first_day_of_week'];
		}
		else
		{
			$raidplan_data['is_recurr'] = 0;
		    $raidplan_data['frequency_type'] = 0;
		    $raidplan_data['frequency'] = 0;
		    $raidplan_data['final_occ_time'] = 0;
		    $raidplan_data['week_index'] = 0;
		    $raidplan_data['first_day_of_week'] = $config["rp_first_day_of_week"];
		}
	}
	

	
	
	/*
	 * doublecheck in db if poster already signed up
	 * 
	 */
	private function check_if_subscribed($user_id, $dkpmember_id, $raidplan_id)
	{
		global $db;
		$signup_id = 0;
		
		$sql = 'select signup_id from ' . RP_SIGNUPS . ' WHERE 
			poster_id = ' . $user_id . ' 
			and raidplan_id = ' . $raidplan_id . ' 
			and dkpmember_id = ' . $dkpmember_id;
		$db->sql_query($sql);
		
		$result = $db->sql_query($sql);
		if($result)
		{
			while ($row = $db->sql_fetchrow($result))
			{
				$signup_id = (int) $row['signup_id'];
			}
		}
		$db->sql_freeresult ( $result );
		return $signup_id; 
	}
	
	/**
	 * get events
	 *
	 */
	private function event_types()
	{
		global $db;
		//find the available events from bbDKP, store them in a global array
		$sql = 'SELECT * FROM ' . EVENTS_TABLE . ' ORDER BY event_id';
		$result = $db->sql_query($sql);
		$this->raid_plan_count = 0;
		while ($row = $db->sql_fetchrow($result))
		{
			$this->raid_plan_ids[$this->raid_plan_count] = $row['event_id'];
			$this->raid_plan_names[$this->raid_plan_count] = $row['event_name'];
			$this->raid_plan_displaynames[$row['event_id']] = $row['event_name'];
			$this->raid_plan_colors[$row['event_id']] = $row['event_color'];
			$this->raid_plan_images[$row['event_id']] = $row['event_imagename'];
			$this->raid_plan_count++;
		}
		$db->sql_freeresult($result);
	}
	
	
	/**
	 * handles signing up to a raid (called from display_plannedraid)
	 *
	 * @param array $raidplan_data
	 * @param array $signup_data
	 */
	public function signup(&$raidplan_data, $signup_data)
	{
		global $user, $db, $config;
			
		// get the chosen raidrole 1-6, this changes the signup value
		$newrole_id = request_var('signuprole', 0);
		// get the attendance value
		$new_signup_val	= request_var('signup_val', 2);
		
		$uid = $bitfield = $options = '';
		$allow_bbcode = $allow_urls = $allow_smilies = true;
		generate_text_for_storage($new_signup_detail, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
		
		// get the chosen raidchar
		$signup_data['dkpmember_id'] = request_var('signupchar', 0);
		// update the ip address and time
		$signup_data['poster_ip'] = $user->ip;
		$signup_data['post_time'] = time();
		$signup_data['signup_count'] =  request_var('signup_count', 1);
		$signup_data['signup_detail'] = utf8_normalize_nfc( request_var('signup_detail', '', true) );
		
		$delta_yes_count = 0;
		$delta_no_count = 0;
		$delta_maybe_count = 0;
		
		// identify the signup. if user returns to signup screen he can change
		$signup_id = request_var('hidden_signup_id', 0);
		
		if ($signup_id ==0)
		{
			//doublecheck in database
			$signup_id = $this->check_if_subscribed($signup_data['poster_id'],$signup_data['dkpmember_id'], $signup_data['raidplan_id']);
		}
			
		// save the user's signup data...
		if( $signup_id > 0)
		{
			
			//get old role
			$old_role_id = (int) $signup_data['role_id'];
			$signup_data['role_id'] = $newrole_id;
			$sql = " select role_signedup from " . RP_RAIDPLAN_ROLES . " where role_id = " . 
			$old_role_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$result = $db->sql_query($sql);
			$db->sql_query($sql);
			$role_signedup = (int) $db->sql_fetchfield('role_signedup',0,$result);  
			$role_signedup = max(0, $role_signedup - 1);
			$db->sql_freeresult ( $result );
			// decrease old role
			$sql = " update " . RP_RAIDPLAN_ROLES . ' set role_signedup = ' . $role_signedup . ' where role_id = ' . 
			$old_role_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$db->sql_query($sql);
			
			// increase new role
			$sql = " update " . RP_RAIDPLAN_ROLES . " set role_signedup = (role_signedup  + 1) where role_id = " . 
			$newrole_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$db->sql_query($sql);
			
			// fetch existing signup value
			if ($signup_data['signup_val'] != $new_signup_val)
			{
				// new role selected 
				
				// decrease the current yes-no-maybe stat
				$old_signup_val = $signup_data['signup_val'];
				$signup_data['signup_val'] = $new_signup_val;
				
				switch($old_signup_val)
				{
					case 0:
						$delta_yes_count -= 1;
						break;
					case 1:
						$delta_no_count -= 1;
						break;
					case 2:
						$delta_maybe_count -= 1;
						break;
				}
				
				// NEW Signup
				switch($new_signup_val)
				{
					case 0:
						$delta_yes_count += 1;
						break;
					case 1:
						$delta_no_count += 1;
						break;
					case 2:
						$delta_maybe_count += 1;
						break;
				}

			}
			
			$sql = 'UPDATE ' . RP_SIGNUPS . '
				SET ' . $db->sql_build_array('UPDATE', array(
					'poster_id'			=> (int) $signup_data['poster_id'],
					'poster_name'		=> (string) $signup_data['poster_name'],
					'poster_colour'		=> (string) $signup_data['poster_colour'],
					'poster_ip'			=> (string) $signup_data['poster_ip'],
					'post_time'			=> (int) $signup_data['post_time'],
					'signup_val'		=> (int) $signup_data['signup_val'],
					'signup_count'		=> (int) $signup_data['signup_count'],
					'signup_detail'		=> (string) $signup_data['signup_detail'],
					'dkpmember_id'		=> $signup_data['dkpmember_id'], 
					'role_id'			=> (int) $newrole_id,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_uid'		=> $uid,
					'bbcode_options'	=> $options,
					)) . "
				WHERE signup_id = $signup_id";
			$db->sql_query($sql);
		}
		else
		{
			//NEW SIGNUP
			$sql = " update " . RP_RAIDPLAN_ROLES . " set role_signedup = (role_signedup  + 1) where role_id = " . 
			$newrole_id . ' and raidplan_id = ' . $signup_data['raidplan_id'];
			$db->sql_query($sql);
				
			switch($new_signup_val)
			{
				case 0:
					$delta_yes_count += 1;
					break;
				case 1:
					$delta_no_count += 1;
					break;
				case 2:
					$delta_maybe_count += 1;
					break;
			}
			
			$signup_data['signup_val'] = $new_signup_val;
			$signup_data['role_id'] = $newrole_id;
			
			$sql = 'INSERT INTO ' . RP_SIGNUPS . ' ' . $db->sql_build_array('INSERT', array(
					'raidplan_id'		=> (int) $signup_data['raidplan_id'],
					'poster_id'			=> (int) $signup_data['poster_id'],
					'poster_name'		=> (string) $signup_data['poster_name'],
					'poster_colour'		=> (string) $signup_data['poster_colour'],
					'poster_ip'			=> (string) $signup_data['poster_ip'],
					'post_time'			=> (int) $signup_data['post_time'],
					'signup_val'		=> (int) $signup_data['signup_val'],
					'signup_count'		=> (int) $signup_data['signup_count'],
					'signup_detail'		=> (string) $signup_data['signup_detail'],
					'dkpmember_id'		=> $signup_data['dkpmember_id'], 
					'role_id'			=> $newrole_id,
					'bbcode_bitfield'	=> $bitfield,
					'bbcode_uid'		=> $uid,
					'bbcode_options'	=> $options,
					)
				);
			$db->sql_query($sql);
			
			$signup_id = $db->sql_nextid();
			$signup_data['signup_id'] = $signup_id;
		}
		
		// update the raidplan id's signup stats
		$sql = 'UPDATE ' . RP_RAIDS_TABLE . ' SET signup_yes = signup_yes + ' . (int) $delta_yes_count . ', signup_no = signup_no + ' . 
			(int) $delta_no_count . ', signup_maybe = signup_maybe + ' . (int) $delta_maybe_count . '
		WHERE raidplan_id = ' . (int) $signup_data['raidplan_id'];
		$db->sql_query($sql);
		
		$raidplan_data['signup_yes'] = $raidplan_data['signup_yes'] + $delta_yes_count;
		$raidplan_data['signup_no'] = $raidplan_data['signup_no'] + $delta_no_count;
		$raidplan_data['signup_maybe'] = $raidplan_data['signup_maybe'] + $delta_maybe_count;
		
		$this->calendar_add_or_update_reply( $signup_data['raidplan_id'] );
		
	}
	
		
	

		
}
?>