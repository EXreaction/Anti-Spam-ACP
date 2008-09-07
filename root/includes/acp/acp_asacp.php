<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class acp_asacp
{
	var $u_action;
	var $new_config = array();

	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache;
		global $config, $phpbb_admin_path, $phpbb_root_path, $phpEx;

		$user->add_lang(array('acp/board', 'mods/acp_asacp', 'install'));

		$error = $notify = array();
		$submit = (isset($_POST['submit'])) ? true : false;
		$action = request_var('action', '');

		add_form_key('as_acp');
		if ($submit && !check_form_key('as_acp'))
		{
			trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$this->new_config = $config;
		$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $this->new_config;
		$error = $options = array();
		switch ($mode)
		{
			default :
				$options = array(
					'legend1'				=> 'ASACP_SETTINGS',
					'asacp_enable'			=> array('lang' => 'ASACP_ENABLE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),

					'legend2'				=> 'ASACP_REGISTER_SETTINGS',
					'asacp_reg_captcha'		=> array('lang' => 'ASACP_REG_CAPTCHA', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
				);

				$template->assign_vars(array(
					'L_TITLE'			=> $user->lang['ASACP_SETTINGS'],
					'L_TITLE_EXPLAIN'	=> '',
					'S_SETTINGS'		=> true,

					'CURRENT_VERSION'	=> ASACP_VERSION,
					'LATEST_VERSION'	=> $this->asacp_latest_version(),
				));
				$this->tpl_name = 'acp_asacp';
				$this->page_title = 'ASACP_SETTINGS';
			break;
		}

		if (sizeof($options))
		{
			validate_config_vars($options, $cfg_array, $error);
			foreach ($options as $config_name => $null)
			{
				if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
				{
					continue;
				}

				$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];

				if ($submit && !sizeof($error))
				{
					set_config($config_name, $config_value);
				}
			}

			if ($submit && !sizeof($error))
			{
				add_log('admin', 'LOG_ASACP_SETTINGS');

				trigger_error($user->lang['ASACP_SETTINGS_UPDATED'] . adm_back_link($this->u_action));
			}

			foreach ($options as $config_key => $vars)
			{
				if (!is_array($vars) && strpos($config_key, 'legend') === false)
				{
					continue;
				}

				if (strpos($config_key, 'legend') !== false)
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

				$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);

				if (empty($content))
				{
					continue;
				}

				$template->assign_block_vars('options', array(
					'KEY'			=> $config_key,
					'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
					'S_EXPLAIN'		=> $vars['explain'],
					'TITLE_EXPLAIN'	=> $l_explain,
					'CONTENT'		=> $content,
					)
				);

				unset($options[$config_key]);
			}
		}

        $template->assign_vars(array(
			'ERROR'			=> implode('<br />', $error),
			'U_ACTION'		=> $this->u_action,
		));
	}

    function asacp_latest_version()
	{
		global $user, $config;

		$latest_version = antispam::version_check();
		if ($latest_version === false)
		{
			$version = $user->lang['NOT_AVAILABLE'];
			$version .= '<br />' . sprintf($user->lang['CLICK_CHECK_NEW_VERSION'], '<a href="http://www.lithiumstudios.org/phpBB3/viewtopic.php?f=31&amp;t=941">', '</a>');
		}
		else
		{
			$version = $latest_version;
			if (version_compare(ASACP_VERSION, $latest_version, '<'))
			{
				$version .= '<br />' . sprintf($user->lang['CLICK_GET_NEW_VERSION'], '<a href="http://www.lithiumstudios.org/phpBB3/viewtopic.php?f=31&amp;t=941">', '</a>');
			}
		}

		return $version;
	}
}

?>