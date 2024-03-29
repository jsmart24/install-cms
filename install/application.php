<?php defined('JSmart_CMS') or exit('Access denied');
/**
 * JSmart CMS
 * ===========================================================================
 * @author Vadim Shestakov
 * ---------------------------------------------------------------------------
 * @link https://jsmart.ru/
 * ---------------------------------------------------------------------------
 * @license https://jsmart.ru/cms/eula
 * ---------------------------------------------------------------------------
 * @copyright 2018 Vadim Shestakov
 * ===========================================================================
 */

class Application extends JSmart
{
    public function __construct()
    {
        JSmart::getInstance();

        $this->setTitle($this->lang('title'));
    }

// ********************************************************************************
// Actions
// ********************************************************************************
    public function actions()
    {
        return [
            'main'          => $this->lang('action_main'),
            'requirements'  => $this->lang('action_requirements'),
            'license'       => $this->lang('action_license'),
            'database'      => $this->lang('action_database'),
			'user'          => $this->lang('action_user'),
            'download'      => $this->lang('action_download'),
            'install'       => $this->lang('action_install')
        ];
    }

// ********************************************************************************
// Main
// ********************************************************************************
    public function main()
    {
        if ($this->input_post('next') && $this->input_post('agreement'))
        {
            $this->config_set(['action' => 'requirements']);

            $this->refresh();
        }

        $this->setContent(http_query('https://install.jsmart.ru/source/eula.html'));

        return [
            'onclick'   => 'return agreement(\'' . $this->lang('license_agreement') . '\');'
        ];
    }

// ********************************************************************************
// Requirements
// ********************************************************************************
    public function requirements()
    {
        $check = $this->_requirements_check();

        if ($this->input_post('next'))
        {
            if ($check['result'] == TRUE)
            {
                $this->config_set(['action' => 'license']);

                $this->refresh();
            }
            else {
                $this->setMessage($this->lang('requirement_error'));
            }
        }

        $requirements = [
            ['title' => $this->lang('requirement_phpversion'), 'value' => $check['requirements']['phpversion']],
            ['title' => $this->lang('requirement_mysqli_connect'), 'value' => $check['requirements']['mysqli_connect']],
            ['title' => $this->lang('requirement_mod_rewrite'), 'value' => $check['requirements']['mod_rewrite']],
            ['title' => $this->lang('requirement_mbstring'), 'value' => $check['requirements']['mbstring']],
            ['title' => $this->lang('requirement_zlib'), 'value' => $check['requirements']['zlib']],
            ['title' => $this->lang('requirement_xml'), 'value' => $check['requirements']['xml']],
            ['title' => $this->lang('requirement_iconv'), 'value' => $check['requirements']['iconv']],
            ['title' => $this->lang('requirement_safe_mode'), 'value' => $check['requirements']['safe_mode']],
            ['title' => $this->lang('requirement_file_uploads'), 'value' => $check['requirements']['file_uploads']]
        ];

        $this->setContent($this->html_requirements($requirements));
    }

// ********************************************************************************
// License
// ********************************************************************************
    public function license()
    {
        if ($this->input_post('next'))
        {
            if ($license_query = $this->license_query($this->input_post('license_key')))
            {
                $this->config_set(['action' => 'database', 'license_key' => $this->input_post('license_key')]);

                $this->refresh();
            }
            else {
                $this->setMessage($this->lang('license_key_error'));
            }
        }

        $fields = [
            'license_key' => [
                'title' => $this->lang('license_key'),
                'value' => $this->input_post_config('license_key'),
                'description' => $this->lang('license_key_description')
            ]
        ];

        $this->setContent($this->html_form($fields));

    }

// ********************************************************************************
// Database
// ********************************************************************************
    public function database()
    {
        if ($this->input_post('next'))
        {
            if ($database = $this->_database_check($this->input_post('db_host'), $this->input_post('db_name'), $this->input_post('db_user'), $this->input_post('db_pass')))
            {
                $this->config_set([
                    'action'    => 'user',
                    'db_host'   => $this->input_post('db_host'),
                    'db_name'   => $this->input_post('db_name'),
                    'db_user'   => $this->input_post('db_user'),
                    'db_pass'   => $this->input_post('db_pass')
                ]);

                $this->refresh();
            }
            else {
                $this->setMessage($this->lang('database_error'));
            }
        }

        $form = [
            'db_host'    => [
                'title' => $this->lang('db_hostname'),
                'value' => $this->input_post_config('db_host', 'localhost'),
            ],
            'db_name'    => [
                'title' => $this->lang('db_database'),
                'value' => $this->input_post_config('db_name'),
            ],
            'db_user'    => [
                'title' => $this->lang('db_username'),
                'value' => $this->input_post_config('db_user'),
            ],
            'db_pass'    => [
                'title' => $this->lang('db_password'),
                'value' => $this->input_post_config('db_pass'),
            ]
        ];

        $this->setContent($this->html_form($form));
    }

// ********************************************************************************
// User
// ********************************************************************************
	public function user()
	{
		if ($this->input_post('next'))
		{
			$user = $this->_user_check($this->input_post('user_login'), $this->input_post('user_email'), $this->input_post('user_password'));

			if ($user === TRUE)
			{
				$this->config_set([
					'action'    	=> 'download',
					'user_login'	=> $this->input_post('user_login'),
					'user_email'   	=> $this->input_post('user_email'),
					'user_password'	=> $this->input_post('user_password')
				]);

				$this->refresh();
			}
			elseif ($user == 'login_error') {
				$this->setMessage($this->lang('user_login_error'));
			}
			elseif ($user == 'email_error') {
				$this->setMessage($this->lang('user_email_error'));
			}
			elseif ($user == 'password_error') {
				$this->setMessage($this->lang('user_password_error'));
			}
		}

		$form = [
			'user_login' => [
				'title' => $this->lang('user_login'),
				'value' => $this->input_post_config('user_login', 'admin'),
			],
			'user_email' => [
				'title' => $this->lang('user_email'),
				'value' => $this->input_post_config('user_email'),
			],
			'user_password' => [
				'title' => $this->lang('user_password'),
				'value' => $this->input_post_config('user_password'),
				'description' => $this->lang('user_password_description')
			]
		];

		$this->setContent($this->html_form($form));
	}

// ********************************************************************************
// Download
// ********************************************************************************
    public function download()
    {
        if ($copyfiles = $this->_copyfiles())
        {
            if ($copyfiles === TRUE){
                $this->config_set(['copyfiles' => '1']);
            }
            elseif (is_array($copyfiles) && isset($copyfiles['error_dirs']))
            {
                $error_dirs = [];

                foreach ($copyfiles['error_dirs'] as $dir) {
                    $error_dirs[] = ['title' => $dir];
                }

                $this->setMessage($this->lang('download_dir_error'));
                $this->setContent($this->html_requirements($error_dirs));
            }
            else {
                $this->setMessage($this->lang('download_error'));
            }
        }

        if ($this->input_post('next') && $this->config_item('copyfiles')) {
            $this->config_set(['action' => 'install']);
            $this->refresh();
        }

        if ($copyfiles === TRUE)
        {
        	$_SESSION['install_location'] = '';

            $this->setContent($this->html_js_callback('copyfiles_progress();'));
            $this->setContent($this->html_progress_bar());
        }
    }

// ********************************************************************************
// Install
// ********************************************************************************
    public function install()
    {
        if ($this->input_post('next'))
        {
            $this->config_set(['action' => 'done']);

            $this->refresh();
        }

		$this->setContent($this->html_js_callback('install_progress();'));

        $this->setContent($this->html_iframe(BASE_URL . 'frontend_install'));
    }

// ********************************************************************************
// Done
// ********************************************************************************
    public function done()
	{
		if ($this->input_get('done')) {
			@unlink(DOCROOT . 'install.php');
			@unlink(DOCROOT . 'install/application.php');
			@unlink(DOCROOT . 'install/config.php');
			@unlink(DOCROOT . 'install/jsmart.php');
			@unlink(DOCROOT . 'install/language.php');
			@unlink(DOCROOT . 'install/template.php');
			@rmdir(DOCROOT . 'install');
		}

		return [
			'onclick'	=> 'location_url(base_url)',
			'content'	=> $this->lang('install_done')
		];
	}

// ********************************************************************************
// Requirements Check
// ********************************************************************************
    private function _requirements_check()
    {
        $requirements = [
            'phpversion'        => (phpversion() >= '7.4') ? TRUE : FALSE,
            'mysqli_connect'    => (boolean) function_exists('mysqli_connect'),
            'mbstring'          => (boolean) extension_loaded('mbstring'),
            'zlib'              => (boolean) extension_loaded('zlib'),
            'xml'               => (boolean) extension_loaded('xml'),
            'iconv'             => (boolean) extension_loaded('iconv'),
            'safe_mode'         => (boolean) ini_get('safe_mode') ? FALSE : TRUE,
            'file_uploads'      => (boolean) ini_get('file_uploads') ? TRUE : FALSE,
            'mod_rewrite'       => $this->_mod_rewrite_check()
        ];

        $result = TRUE;

        foreach ($requirements as $key => $value)
        {
            if ($value === FALSE) {
                $result = FALSE;
                break;
            }
        }

        return [
            'result'        => $result,
            'requirements'  => $requirements
        ];
    }

// ********************************************************************************
// Database Check
// ********************************************************************************
    private function _database_check($host = 'localhost', $db_name, $db_user, $db_pass)
    {
        if (function_exists('mysqli_connect'))
        {
			$host		= trim(str_replace(['$', '"'], ["\$", "\""], $host));
			$db_name 	= trim(str_replace(['$', '"'], ["\$", "\""], $db_name));
			$db_user 	= trim(str_replace(['$', '"'], ["\$", "\""], $db_user));
			$db_pass	= trim(str_replace(['$', '"'], ["\$", "\""], $db_pass));

            $connect = @mysqli_connect($host, $db_user, $db_pass);

            if ($connect) {
                mysqli_query($connect, "CREATE DATABASE IF NOT EXISTS {$db_name}");
            }

            if ($connect && mysqli_select_db($connect, $db_name)) {
                return TRUE;
            }
        }

        return FALSE;
    }

// ********************************************************************************
// User Check
// ********************************************************************************
    private function _user_check($login, $email, $password)
	{
		if (preg_match("/[\||\'|\<|\>|\[|\]|\"|\!|\?|\$|\@|\/|\\\|\&\~\*\{\+]/", $login)) {
			return 'login_error';
		}

		if (!preg_match('/("([^"]+)"\s<)?[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z0-9]{2,6})>?$/xi', $email)) {
			return 'email_error';
		}

		if (strlen($password) < 8) {
			return 'password_error';
		}

		return TRUE;
	}

// ********************************************************************************
// Copy Files
// ********************************************************************************
    private function _copyfiles()
    {
        if ($this->config_item('copyfiles')) {
            return TRUE;
        }

        if ($install = http_query('https://' . DATA_SERVER . '/distribution/get?' . http_build_query(['key' => $this->config_item('license_key'), 'host' => $_SERVER['HTTP_HOST'], 'hash' => md5(time())])))
        {
            file_write(DOCROOT . 'install/install.zip', $install);

            if (is_file(DOCROOT . 'install/install.zip'))
            {
                $zip = new JSmart_Zip;

                $zip->open(DOCROOT.'install/install.zip');

                @unlink(DOCROOT.'install/install.zip');

                $error_dirs = [];

                foreach ($zip->root_dirs as $root_dir) {
                    if (is_dir(DOCROOT . $root_dir) == TRUE) {
                        $error_dirs[] = $root_dir;
                    }
                }

                if (count($error_dirs) == 0)
                {
                    if ($zip->extract()) {

                    	if (file_exists(DOCROOT . 'application/config/database.php'))
                    	{
							$db_config = file_get_contents(DOCROOT . 'application/config/database.php');

							$db_config = str_replace('{hostname}', $this->config_item('db_host'), $db_config);
							$db_config = str_replace('{username}', $this->config_item('db_user'), $db_config);
							$db_config = str_replace('{password}', $this->config_item('db_pass'), $db_config);
							$db_config = str_replace('{database}', $this->config_item('db_name'), $db_config);
							$db_config = str_replace('{dbdriver}',  'mysqli', $db_config);
							$db_config = str_replace('{dbprefix}', '', $db_config);

							file_put_contents(DOCROOT . 'application/config/database.php', $db_config);
						}

                        return TRUE;
                    }
                }
                else {
                    return [
                        'error_dirs' => $error_dirs
                    ];
                }
            }
        }

        return FALSE;
    }

// ********************************************************************************
// Mod Rewrite Check
// ********************************************************************************
    private function _mod_rewrite_check()
    {
        $mod_rewrite = FALSE;

        if (function_exists('apache_get_modules')) {
            $mod_rewrite = in_array('mod_rewrite', apache_get_modules());
        }

        if ($mod_rewrite == FALSE)
        {
            file_write(DOCROOT . 'install/mod_rewrite.php', "<?php exit('mod_rewrite'); ?>");
            file_write(DOCROOT . 'install/.htaccess', implode(PHP_EOL, ['<ifModule mod_rewrite.c>', 'RewriteEngine On', 'RewriteRule ^mod_rewrite$ mod_rewrite.php [L]', '</ifModule>']));

            $query = http_query($this->base_url('install/mod_rewrite'));

            @unlink(DOCROOT . 'install/mod_rewrite.php');
            @unlink(DOCROOT . 'install/.htaccess');

            if ($query == 'mod_rewrite') {
                return TRUE;
            }
        }

        return $mod_rewrite;
    }
}