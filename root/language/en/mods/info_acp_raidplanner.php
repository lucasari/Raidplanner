<?php
/**
 * bbdkp acp language file for raidplanner module
 * 
 * @package bbDkp
 * @copyright 2010 bbdkp <http://code.google.com/p/bbdkp/>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * 
 */

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

// Create the lang array if it does not already exist
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// Merge the following language entries into the lang array
$lang = array_merge($lang, array(
	'ACP_CAT_RAIDPLANNER' 				=> 'Raidplanner', //main tab 
	'ACP_RAIDPLANNER' 					=> 'Raidplanner', //category

	'RP_PLANNER_SETTINGS'  		        => 'Planner Settings', 	//module
    'RP_CAL_SETTINGS'  		            => 'Calendar Settings', 	//module
    'RP_ROLES'  		                => 'Raid Roles', 	//module
    'RP_TEAMS'  		                => 'Raid Teams', 	//module
    'RP_COMPOSITION'  		            => 'Raid Composition',  	//module

	'ACP_RAIDPLANNER_SETTINGS_EXPLAIN' 	=> 'Here you can configure Raid Planner settings',
	'ACP_RAIDPLANNER_EVENTSETTINGS'		=> 'Event Settings', //module
));

?>