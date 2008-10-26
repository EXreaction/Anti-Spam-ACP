<?php
/**
 * @author Nathan Guse (EXreaction) http://lithiumstudios.org
 * @author David Lewis (Highway of Life) highwayoflife@gmail.com
 * @package phpBB3 UMIF - Unified MOD Install File
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (!class_exists('umif'))
{
	include($phpbb_root_path . 'umif/umif.' . $phpEx);
}

/**
 * UMIF - Unified MOD Installation File class Front End
 */
class umif_frontend extends umif
{
	/**
	* Constructor
	*/
	function umif_frontend($title = '', $auto_display_results = false)
	{
		global $db, $phpbb_root_path, $template, $user;

		// we must call the main constructor
		$this->umif();
		$this->auto_display_results = $auto_display_results;

		$user->add_lang('install');

		// Setup the template
		$template->set_custom_template($phpbb_root_path . 'umif/style/', 'umif');
		$template->set_filenames(array(
			'body' => 'index_body.html',
		));

		$title_explain = (isset($user->lang[$title . '_EXPLAIN'])) ? $user->lang[$title . '_EXPLAIN'] : '';
		$title = (isset($user->lang[$title])) ? $user->lang[$title] : $title;

		page_header($title);

		$template->assign_vars(array(
			'SQL_LAYER'			=> $db->sql_layer,
			'UMIF_ROOT_PATH'	=> $phpbb_root_path . 'umif/',

			'L_TITLE'			=> $title,
			'L_TITLE_EXPLAIN'	=> $title_explain,
		));
	}

	/**
	* Display Stages
	*
	* Outputs the stage list
	*
	* @param array $stages The list of stages.
	*	Either send the array like: array('CONFIGURE', 'INSTALL') or you can send it like array('CONFIGURE' => array('url' => $url), 'INSTALL' => array('url' => $url)) or you can use a mixture of the two.
	* @param int $selected The current stage
	*/
	function display_stages($stages, $selected = 1)
	{
		global $template, $user;

		$i = 1;
		foreach ($stages as $stage => $data)
		{
			if (!is_array($data))
			{
				$stage = $data;
				$data = array();
			}

			$template->assign_block_vars('l_block', array(
				'L_TITLE'			=> (isset($user->lang[$stage])) ? $user->lang[$stage] : $stage,
				'U_TITLE'			=> (isset($data['url'])) ? $data['url'] : false,
				'S_COMPLETE'		=> ($i <= $selected) ? true : false,
				'S_SELECTED'		=> ($i == $selected) ? true : false,
			));

			$i++;
		}
	}

	/**
	* Confirm Box
	*
	* Displays an inline confirm box (makes it possible to have a nicer looking confirm box shown if you want to use stages)
	*
	* @param boolean $check True for checking if confirmed (without any additional parameters) and false for displaying the confirm box
	* @param string $title Title/Message used for confirm box.
	*		message text is _CONFIRM appended to title.
	*		If title cannot be found in user->lang a default one is displayed
	*		If title_CONFIRM cannot be found in user->lang the text given is used.
	* @param string $hidden Hidden variables
	*/
	function confirm_box($check, $title = '', $hidden = '')
	{
		if (!$check)
		{
			global $template;
			$template->assign_var('S_CONFIRM', true);
		}

		if (is_array($hidden))
		{
			$hidden = build_hidden_fields($hidden);
		}

		return confirm_box($check, $title, $hidden, $html_body = 'index_body.html');
	}

	/**
	* Display Options
	*
	* Display a set of options from an inputted array.
	*
	* @param array $options This is the array of options.  Format it like you would if you were using the setup in acp_board except only enter what would go in the 'vars' array.
	*/
	function display_options($options)
	{
		global $phpbb_root_path, $phpEx, $template, $user;

		foreach ($options as $name => $vars)
		{
			if (!is_array($vars) && strpos($name, 'legend') === false)
			{
				continue;
			}

			if (strpos($name, 'legend') !== false)
			{
				$template->assign_block_vars('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
				);

				continue;
			}

			$type = explode(':', $vars['type']);

			$l_explain = '';
			if ($vars['explain'] && isset($vars['lang_explain']))
			{
				$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
			}
			else if ($vars['explain'])
			{
				$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
			}

			$content = $this->build_cfg_template($type, $name, $vars);

			if (!sizeof($content))
			{
				continue;
			}

			$template->assign_block_vars('options', array(
				'KEY'			=> $name,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> $content['tpl'],

				// Find user link
				'S_FIND_USER'	=> (isset($content['find_user'])) ? true : false,
				'U_FIND_USER'	=> (isset($content['find_user'])) ? append_sid("{$phpbb_root_path}memberlist.$phpEx", array('mode' => 'searchuser', 'form' => 'select_user', 'field' => 'username', 'select_single' => 'true', 'form' => 'admin_tool_kit', 'field' => $content['find_user_field'])) : '',
			));
		}
	}

	/**
	* Display results
	*
	* Display the results from the previous command, or you may enter your own command/result if you would like.
	*
	* @param string $command The command you would like shown (leave blank to use the last command saved in $this->command)
	* @param string $result The result you would like shown (leave blank to use the last result saved in $this->result)
	*/
	function display_results($command = '', $result = '')
	{
		global $template, $user;

		$command = ($command) ? $command : $this->command;
		$command = (isset($user->lang[$command])) ? $user->lang[$command] : $command;
		$result = ($result) ? $result : $this->result;
		$result = (isset($user->lang[$result])) ? $user->lang[$result] : $result;

		$template->assign_block_vars('results', array(
			'COMMAND'	=> $command,
			'RESULT'	=> $result,
			'S_SUCCESS'	=> ($result == $user->lang['SUCCESS']) ? true : false,
		));
	}

	/**
	* Done
	*
	* This should be called when everything is done for this page.
	*/
	function done()
	{
		page_footer();
	}

	/**
	* Build configuration template for acp configuration pages
	*
	* Slightly modified from adm/index.php
	*/
	function build_cfg_template($tpl_type, $name, $vars)
	{
		global $user;

		$tpl = array();

		$default = (isset($vars['default'])) ? request_var($name, $vars['default']) : request_var($name, '');

		switch ($tpl_type[0])
		{
			case 'text':
				// If requested set some vars so that we later can display the link correct
				if (isset($vars['select_user']) && $vars['select_user'] === true)
				{
					$tpl['find_user']		= true;
					$tpl['find_user_field']	= $name;
				}
			case 'password':
				$size = (int) $tpl_type[1];
				$maxlength = (int) $tpl_type[2];

				$tpl['tpl'] = '<input id="' . $name . '" type="' . $tpl_type[0] . '"' . (($size) ? ' size="' . $size . '"' : '') . ' maxlength="' . (($maxlength) ? $maxlength : 255) . '" name="' . $name . '" value="' . $default . '" />';
			break;

			case 'textarea':
				$rows = (int) $tpl_type[1];
				$cols = (int) $tpl_type[2];

				$tpl['tpl'] = '<textarea id="' . $name . '" name="' . $name . '" rows="' . $rows . '" cols="' . $cols . '">' . $default . '</textarea>';
			break;

			case 'radio':
				$name_yes	= ($default) ? ' checked="checked"' : '';
				$name_no	= (!$default) ? ' checked="checked"' : '';

				$tpl_type_cond = explode('_', $tpl_type[1]);
				$type_no = ($tpl_type_cond[0] == 'disabled' || $tpl_type_cond[0] == 'enabled') ? false : true;

				$tpl_no = '<label><input type="radio" name="' . $name . '" value="0"' . $name_no . ' class="radio" /> ' . (($type_no) ? $user->lang['NO'] : $user->lang['DISABLED']) . '</label>';
				$tpl_yes = '<label><input type="radio" id="' . $name . '" name="' . $name . '" value="1"' . $name_yes . ' class="radio" /> ' . (($type_no) ? $user->lang['YES'] : $user->lang['ENABLED']) . '</label>';

				$tpl['tpl'] = ($tpl_type_cond[0] == 'yes' || $tpl_type_cond[0] == 'enabled') ? $tpl_yes . $tpl_no : $tpl_no . $tpl_yes;
			break;

			case 'checkbox':
				$checked	= ($default) ? ' checked="checked"' : '';

				$tpl['tpl'] = '<input type="checkbox" id="' . $name . '" name="' . $name . '"' . $checked . ' />';
			break;

			case 'select':
			case 'custom':

				$return = '';

				if (isset($vars['function']))
				{
					$call = $vars['function'];
				}
				else
				{
					break;
				}

				if (isset($vars['params']))
				{
					$args = array();
					foreach ($vars['params'] as $value)
					{
						switch ($value)
						{
							case '{CONFIG_VALUE}':
								$value = $default;
							break;

							case '{KEY}':
								$value = $name;
							break;
						}

						$args[] = $value;
					}
				}
				else
				{
					$args = array($default, $name);
				}

				$return = call_user_func_array($call, $args);

				if ($tpl_type[0] == 'select')
				{
					$multiple	= ((isset($vars['multiple']) && $vars['multiple']) ? ' multiple="multiple"' : '');
					$tpl['tpl']		= '<select id="' . $name . '" name="' . $name . (!empty($multiple) ? '[]' : '') . '"' . $multiple . '>' . $return . '</select>';
				}
				else
				{
					$tpl['tpl'] = $return;
				}

			break;

			default:
			break;
		}

		if (isset($vars['append']))
		{
			$tpl['tpl'] .= $vars['append'];
		}

		return $tpl;
	}
}

?>