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
*/

/**
 * @ignore
 */
if ( !defined('IN_PHPBB') OR !defined('IN_BBDKP') )
{
	exit;
}

/**
 * the base class
 *
 */
abstract class calendar
{
	/**
	 * core date object. 
	 *
	 * @var array
	 */
	public $date = array();
	
	/**
	 * month names
	 *
	 * @var unknown_type
	 */
	public $month_names = array();
	
	/**
	 * selectors
	 *
	 */
	public $month_sel_code = "";
	public $day_sel_code = "";
	public $year_sel_code = "";
	public $mode_sel_code = "";
	
	/**
	 * 
	 *
	 * @var unknown_type
	 */
	public $group_options;
	public $period_start;
	public $period_end;
	public $timestamp;
	
	/**
	 * 
	 */
	function __construct($arg)
	{
		global $auth, $db, $user, $config; 
		
		// always refresh the date...
		$temp_now_time = time() + $user->timezone + $user->dst;
		
		//get the selected date and set it into an array
		$this->date['day'] = request_var('calD', '');
		$this->date['month'] = request_var('calM', '');
		$this->date['month_no'] = request_var('calM', '');
		$this->date['year'] = request_var('calY', '');
		
		if( $this->date['day'] == "" )
		{
			$this->date['day'] = gmdate("d", $temp_now_time);
		}

		$this->month_names[1] = "January";
		$this->month_names[2] = "February";
		$this->month_names[3] = "March";
		$this->month_names[4] = "April";
		$this->month_names[5] = "May";
		$this->month_names[6] = "June";
		$this->month_names[7] = "July";
		$this->month_names[8] = "August";
		$this->month_names[9] = "September";
		$this->month_names[10] = "October";
		$this->month_names[11] = "November";
		$this->month_names[12] = "December";
			
		if( $this->date['month'] == "" )
		{
			$this->date['month'] = gmdate("F", $temp_now_time);
			$this->date['month_no'] = gmdate("n", $temp_now_time);
			$this->date['prev_month'] = gmdate("n", $temp_now_time) - 1;
			$this->date['next_month'] = gmdate("n", $temp_now_time) + 1;
	
		}
		else
		{

			$this->date['month'] = $this->month_names[$this->date['month']];
			$this->date['prev_month'] = $this->date['month'] - 1;
			$this->date['next_month'] = $this->date['month'] + 1;
		}
	
		if( $this->date['year'] == "" )
		{
			$this->date['year']	= gmdate('Y', $temp_now_time);
		}
		
		// make sure this day exists - ie there is no February 31st.
		$number_days = gmdate("t", gmmktime( 0,0,0,$this->date['month_no'], 1, $this->date['year']));
		if( $number_days < $this->date['day'] )
		{
		    $this->date['day'] = $number_days;
		}

		
		$this->timestamp = 	mktime(0, 0, 0, $this->date['month_no'], $this->date['day'], $this->date['year']);
				
		$first_day_of_week = $config['rp_first_day_of_week'];
		$sunday= $monday= $tuesday= $wednesday= $thursday= $friday= $saturday='';
		
		$this->group_options = $this->get_sql_group_options();
	}
	
	protected function Get1DoM($nowDate) 
	{
		$fdate = 0;
		if (is_numeric($nowDate)) 
		{
			$fdate = strtotime(date('Y',$nowDate) . '-' . date('m',$nowDate) . '-01');
		}
		return $fdate;
		
		
	}
	
	protected function GetLDoM($nowDate) 
	{
		$ldate = 0;
		if (is_numeric($nowDate)) 
		{
			$dateSoM = strtotime(date('Y',$nowDate) . '-' . date('m',$nowDate) . '-01');
			$dateCog = strtotime('+1 month',$dateSoM);
			$dateEoM = strtotime('-1 day',$dateCog );
			if (is_numeric($dateEoM)) 
			{
				$ldate = $dateEoM;
			}
		}
		return $ldate;
	}
	
	/**
	 * Displays week, month, day or raidplan, see implementations
	 * 
	 */
	public abstract function display();
	
	/* 
	 * "shift" names of weekdays depending on which day we want to display as the first day of the week
	*/
	protected function get_weekday_names( $first_day_of_week, &$sunday, &$monday, &$tuesday, &$wednesday, &$thursday, &$friday, &$saturday )
	{
		global $user;
		switch( $first_day_of_week )
		{
			case 0:
				$sunday = $user->lang['datetime']['Sunday'];
				$monday = $user->lang['datetime']['Monday'];
				$tuesday = $user->lang['datetime']['Tuesday'];
				$wednesday = $user->lang['datetime']['Wednesday'];
				$thursday = $user->lang['datetime']['Thursday'];
				$friday = $user->lang['datetime']['Friday'];
				$saturday = $user->lang['datetime']['Saturday'];
				break;
			case 1:
				$saturday = $user->lang['datetime']['Sunday'];
				$sunday = $user->lang['datetime']['Monday'];
				$monday = $user->lang['datetime']['Tuesday'];
				$tuesday = $user->lang['datetime']['Wednesday'];
				$wednesday = $user->lang['datetime']['Thursday'];
				$thursday = $user->lang['datetime']['Friday'];
				$friday = $user->lang['datetime']['Saturday'];
				break;
			case 2:
				$friday = $user->lang['datetime']['Sunday'];
				$saturday = $user->lang['datetime']['Monday'];
				$sunday = $user->lang['datetime']['Tuesday'];
				$monday = $user->lang['datetime']['Wednesday'];
				$tuesday = $user->lang['datetime']['Thursday'];
				$wednesday = $user->lang['datetime']['Friday'];
				$thursday = $user->lang['datetime']['Saturday'];
				break;
			case 3:
				$thursday = $user->lang['datetime']['Sunday'];
				$friday = $user->lang['datetime']['Monday'];
				$saturday = $user->lang['datetime']['Tuesday'];
				$sunday = $user->lang['datetime']['Wednesday'];
				$monday = $user->lang['datetime']['Thursday'];
				$tuesday = $user->lang['datetime']['Friday'];
				$wednesday = $user->lang['datetime']['Saturday'];
				break;
			case 4:
				$wednesday = $user->lang['datetime']['Sunday'];
				$thursday = $user->lang['datetime']['Monday'];
				$friday = $user->lang['datetime']['Tuesday'];
				$saturday = $user->lang['datetime']['Wednesday'];
				$sunday = $user->lang['datetime']['Thursday'];
				$monday = $user->lang['datetime']['Friday'];
				$tuesday = $user->lang['datetime']['Saturday'];
				break;
			case 5:
				$tuesday = $user->lang['datetime']['Sunday'];
				$wednesday = $user->lang['datetime']['Monday'];
				$thursday = $user->lang['datetime']['Tuesday'];
				$friday = $user->lang['datetime']['Wednesday'];
				$saturday = $user->lang['datetime']['Thursday'];
				$sunday = $user->lang['datetime']['Friday'];
				$monday = $user->lang['datetime']['Saturday'];
				break;
			case 6:
				$monday = $user->lang['datetime']['Sunday'];
				$tuesday = $user->lang['datetime']['Monday'];
				$wednesday = $user->lang['datetime']['Tuesday'];
				$thursday = $user->lang['datetime']['Wednesday'];
				$friday = $user->lang['datetime']['Thursday'];
				$saturday = $user->lang['datetime']['Friday'];
				$sunday = $user->lang['datetime']['Saturday'];
				break;
		}
	}
	
	/* fday is used to determine in what day we are starting with */
	protected function get_fday($day, $month, $year, $first_day_of_week)
	{
		$fday = 0;
	
		
		$fday = gmdate("N",gmmktime(0,0,0, $month, $day, $year));
		$fday = $fday - $first_day_of_week;
		if( $fday < 0 )
		{
			$fday = $fday + 7;
		}
		return $fday;
	}
	
	/**
	 * Generates array of birthdays for the given range for users/founders
	 *
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * @return string
	 */
	protected function generate_birthday_list($from, $end)
	{
		global $db, $user, $config;
		
		$birthday_list = "";
		if ($config['load_birthdays'] && $config['allow_birthdays'])
		{
			
			$day1= date("j", $from);
			$day2= date("j", $end);
			$month= date("n", $from);
			$year= date("Y", $from);
			
			$sql = 'SELECT user_id, username, user_colour, user_birthday
					FROM ' . USERS_TABLE . "
					WHERE user_birthday >= '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $day1, $month,$year )) . "'
					AND user_birthday <= '" . $db->sql_escape(sprintf('%2d-%2d-%4d', $day2, $month,$year )) . "'
					AND user_birthday " . $db->sql_like_expression($db->any_char . '-' . sprintf( ' %s', $month)  .'-' . $db->any_char) . ' 
					AND user_type IN (' . USER_NORMAL . ', ' . USER_FOUNDER . ')
					ORDER BY user_birthday ASC';
			$result = $db->sql_query($sql);
			$oldday= $newday = "";
			while ($row = $db->sql_fetchrow($result))
			{
				$birthday_str = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
				$age = (int) substr($row['user_birthday'], -4);
				$birthday_str .= ' (' . ($year - $age) . ')';
				
				$newday = trim(substr($row['user_birthday'],0, 2));
				
				if($oldday != $newday)
				{
					// new birthday found, make new string
					$daystr = $birthday_str;
					$birthday_list[$newday] = array(
						'day' => $row['user_birthday'],
						'bdays' =>  $user->lang['BIRTHDAYS'].": ". $daystr,
					);
					
					
				}
				else 
				{
					// other bday on same day, add it
					$daystr = $birthday_list[$oldday]['bdays'] .", ". $birthday_str;
					// modify array entry
					$birthday_list[$oldday] = array(
						'day' => $row['user_birthday'],
						'bdays' =>  $daystr,
					);
					
				}
				$oldday = $newday;
				
			}
			$db->sql_freeresult($result);
		}
	
		return $birthday_list;
	}
	
	/*
	 * return group list 
	 */
	private function get_sql_group_options()
	{
		global $user, $auth, $db;
	
		// What groups is this user a member of?
	
		/* don't check for hidden group setting -
		  if the raidplan was made by the admin for a hidden group -
		  members of the hidden group need to be able to see the raidplan in the calendar */
	
		$sql = 'SELECT g.group_id, g.group_name, g.group_type
				FROM ' . GROUPS_TABLE . ' g, ' . USER_GROUP_TABLE . ' ug
				WHERE ug.user_id = '.$db->sql_escape($user->data['user_id']).'
					AND g.group_id = ug.group_id
					AND ug.user_pending = 0
				ORDER BY g.group_type, g.group_name';
		$result = $db->sql_query($sql);
	
		$group_options = '';
		while ($row = $db->sql_fetchrow($result))
		{
			if( $group_options != "" )
			{
				$group_options .= " OR ";
			}
			$group_options .= "group_id = ".$row['group_id']. " OR group_id_list LIKE '%,".$row['group_id']. ",%'";
		}
		$db->sql_freeresult($result);
		return $group_options;
	}
	
	/**
	* Fill smiley templates (or just the variables) with smilies, either in a window or inline
	* 
	*/
	public function generate_calendar_smilies($mode)
	{
		global $auth, $db, $user, $config, $template, $phpEx, $phpbb_root_path;
	
		if ($mode == 'window')
		{
			page_header($user->lang['SMILIES']);
	
			$template->set_filenames(array(
				'body' => 'posting_smilies.html')
			);
		}
	
		$display_link = false;
		if ($mode == 'inline')
		{
			$sql = 'SELECT smiley_id
				FROM ' . SMILIES_TABLE . '
				WHERE display_on_posting = 0';
			$result = $db->sql_query_limit($sql, 1, 0, 3600);
	
			if ($row = $db->sql_fetchrow($result))
			{
				$display_link = true;
			}
			$db->sql_freeresult($result);
		}
	
		$last_url = '';
	
		$sql = 'SELECT *
			FROM ' . SMILIES_TABLE .
			(($mode == 'inline') ? ' WHERE display_on_posting = 1 ' : '') . '
			ORDER BY smiley_order';
		$result = $db->sql_query($sql, 3600);
	
		$smilies = array();
		while ($row = $db->sql_fetchrow($result))
		{
			if (empty($smilies[$row['smiley_url']]))
			{
				$smilies[$row['smiley_url']] = $row;
			}
		}
		$db->sql_freeresult($result);
	
		if (sizeof($smilies))
		{
			foreach ($smilies as $row)
			{
				$template->assign_block_vars('smiley', array(
					'SMILEY_CODE'	=> $row['code'],
					'A_SMILEY_CODE'	=> addslashes($row['code']),
					'SMILEY_IMG'	=> $phpbb_root_path . $config['smilies_path'] . '/' . $row['smiley_url'],
					'SMILEY_WIDTH'	=> $row['smiley_width'],
					'SMILEY_HEIGHT'	=> $row['smiley_height'],
					'SMILEY_DESC'	=> $row['emotion'])
				);
			}
		}
	
		if ($mode == 'inline' && $display_link)
		{
			$template->assign_vars(array(
				'S_SHOW_SMILEY_LINK' 	=> true,
				'U_MORE_SMILIES' 		=> append_sid("{$phpbb_root_path}calendarpost.$phpEx", 'mode=smilies'))
			);
		}
	
		if ($mode == 'window')
		{
			page_footer();
		}
	}
	
}

?>