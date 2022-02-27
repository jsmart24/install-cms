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

class JSmart
{
    private static $instance;

    var $application    = NULL;
    var $action         = 'main';

    static $config      = [];
    static $lang        = [];
    static $message     = '';
    static $title       = '';
    static $content     = [];

    public function __construct()
    {
        self::$instance =& $this;

        if (file_exists(DOCROOT . 'install/config.php'))
        {
            include DOCROOT . 'install/config.php';

            if (isset($config) && count($config)) {
                self::$config = $config;
            }
        }

        if (file_exists(DOCROOT . 'install/language.php'))
        {
            require_once DOCROOT . 'install/language.php';

            if (isset($lang) && is_array($lang)) {
                self::$lang = $lang;
            }
        }

        session_start();

        define('BASE_URL', $this->base_url());

        if ($action = $this->config_item('action')) {
            $this->action = $action;
        }

        $this->application();
    }

// ********************************************************************************
// Views
// ********************************************************************************
    public function views($_path, $_vars = [])
    {
        if (!file_exists($_path)) {
            show_error('Unable to load the requested file: '.$_path);
        }

        if (is_array($_vars)) {
            extract($_vars);
        }

        ob_start();

        if ((bool) @ini_get('short_open_tag') === FALSE) {
            echo eval('?>'.preg_replace("/;*\s*\?>/", "; ?>", str_replace('<?=', '<?php echo ', file_get_contents($_path))));
        }
        else {
            include $_path;
        }

        $buffer = ob_get_contents();

        @ob_end_clean();

        return $buffer;
    }

// ********************************************************************************
// Config Item
// ********************************************************************************
    public function config_item($item)
    {
        if (isset(self::$config[$item])) {
            return self::$config[$item];
        }

        return FALSE;
    }

// ********************************************************************************
// Config Set
// ********************************************************************************
    public function config_set($config = [])
    {
        if (is_array($config) && count($config))
        {
            if (is_array(self::$config) && count(self::$config)) {
                $config = array_merge(self::$config, $config);
            }

            $_config = "<?php defined('JSmart_CMS') or exit('Access denied');\n";

            foreach ($config as $key => $value)
            {
                $key   = trim(preg_replace("/[^a-zA-Z0-9_.]/i", '', $key));
                $value = trim(str_replace(['$', '"'], ["\$", "\""], $value));
            }

            $_config .= "?>";

            file_write(DOCROOT . 'install/config.php', $_config);

            $this->php_cache_clean(DOCROOT . 'install/config.php');

            return TRUE;
        }

        return FALSE;
    }

// ********************************************************************************
// Input GET
// ********************************************************************************
    public function input_get($key)
    {
        return isset($_GET[$key]) ? $_GET[$key] : FALSE;
    }

// ********************************************************************************
// Input POST
// ********************************************************************************
    public function input_post($key)
    {
        return isset($_POST[$key]) ? $_POST[$key] : FALSE;
    }

// ********************************************************************************
// Input POST or CONFIG
// ********************************************************************************
    public function input_post_config($key, $default = '')
    {
        $post   = $this->input_post($key);
        $config = $this->config_item($key);

        return ($post ? $post : ($config ? $config : $default));
    }

// ***************************************************************************
// is ajax request
// ***************************************************************************
    public function is_ajax_request()
    {
        return ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : '') === 'XMLHttpRequest');
    }

// ********************************************************************************
// Base URL
// ********************************************************************************
    public function base_url($url = '')
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $base_url = $_SERVER['HTTP_X_FORWARDED_PROTO'];
        }
        else {
            $base_url  = isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
        }

        $base_url .= '://' . $_SERVER['HTTP_HOST'];
        $base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);

        return $base_url . $url;
    }

// ********************************************************************************
// Refresh
// ********************************************************************************
    public function refresh()
    {
        if ($this->is_ajax_request()) {
            header("Content-type: application/json; charset=utf-8");
            exit(json_encode(['success' => TRUE]));
        }

        header('Refresh: 0');

        exit();
    }

// ********************************************************************************
// PHP Cache Clean
// ********************************************************************************
    public function php_cache_clean($file = FALSE)
    {
        static $php_cache;

        if (empty($php_cache))
        {
            $php_cache = 'disable';

            if (ini_get('opcache.enable')) {
                $php_cache = 'opcache';
            }
        }

        if ($php_cache !== 'disable')
        {
            if ($php_cache == 'opcache')
            {
                if ($file && function_exists('opcache_invalidate')) {
					opcache_invalidate($file);
                }
                elseif (function_exists('opcache_reset')) {
					opcache_reset();
				}

                return TRUE;
            }
        }

        return FALSE;
    }

// ********************************************************************************
// getInstance
// ********************************************************************************
    static public function &getInstance()
    {
        return self::$instance;
    }

// ********************************************************************************
// SET Title
// ********************************************************************************
    public function setTitle($value)
    {
        self::$title = $value;
    }

// ********************************************************************************
// SET Message
// ********************************************************************************
    public function setMessage($value)
    {
        self::$message = '<div class="message">' . $value . '</div>';
    }

// ********************************************************************************
// SET Content
// ********************************************************************************
    public function setContent($value)
    {
        self::$content[] = $value;
    }

// ********************************************************************************
// Lang
// ********************************************************************************
    public function lang($key)
    {
        if (isset(self::$lang[$key])) {
            return self::$lang[$key];
        }

        return $key;
    }

// ********************************************************************************
// HTML Form
// ********************************************************************************
    public function html_form($data = [])
    {
        $html = [
            '<div class="form">'
        ];

        foreach ($data as $name => $field)
        {
            $html[] = '<div class="form-group">';
            $html[] = '<label class="form-label">' . $field['title'] . ':</label>';
            $html[] = '<div class="input-group">';
            $html[] = '<input type="text" name="' . $name . '" value="' . $field['value'] . '" class="form-field">';

            if (isset($field['description'])) {
                $html[] = '<p class="form-help">';
                $html[] = $field['description'];
                $html[] = '</p>';
            }

            $html[] = '</div>';
            $html[] = '</div>';
        }

        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

// ********************************************************************************
// HTML Requirements
// ********************************************************************************
    public function html_requirements($data = [], $description = '')
    {
        $html = [
            '<div class="form">',
            '<table class="check-server">',
            '<tbody>'
        ];

        foreach ($data as $value)
        {
            $html[] = '<tr>';
            $html[] = '<td>' . $value['title'] . '</td>';

            if (isset($value['value'])){
                $html[] = '<td>' . ($value['value'] ? '<b class="check-accept">Да</b>' : '<b class="check-decline">Нет</b>') . '</td>';
            }

            $html[] = '</tr>';
        }

        $html[] = '</tbody>';
        $html[] = '</table>';

        if ($description) {
            $html[] = '<p class="check-notation">' . $description . '</p>';
        }

        $html[] = '</div>';

        return implode(PHP_EOL, $html);
    }

// ********************************************************************************
// HTML Progress Bar
// ********************************************************************************
    public function html_progress_bar()
    {
        $html = [
            '<div class="progress-bar" data-progress-bar="0">',
            '<div id="bar-width" class="bar" style="width:0%"></div>',
            '<span id="bar-percent">0%</span>',
            '</div>',
            '<div id="progress-bar-status"></div>',
        ];

        return implode(PHP_EOL, $html);
    }

// ********************************************************************************
// HTML JS Callback
// ********************************************************************************
    public function html_js_callback($code)
    {
        $html = [
            '<script type="text/javascript">',
            '$(document).ready(function () {',
        ];

        $html[] = $code;

        $html[] = '});';
        $html[] = '</script>';

        return implode(PHP_EOL, $html);
    }

// ********************************************************************************
// HTML Iframe
// ********************************************************************************
    public function html_iframe($src)
	{
		return '<iframe src="' . $src . '"></iframe>';
	}

// ********************************************************************************
// License Query
// ********************************************************************************
    public function license_query($license_key)
    {
        if ($license_key = trim($license_key))
        {
            if ($query = http_query('https://' . DATA_SERVER . '/license/activation?' . http_build_query(['key' => $license_key, 'host' => $_SERVER['HTTP_HOST'], 'hash' => md5(time())])))
            {
                $query = json_decode($query, TRUE);

                if (!empty($query['success'])) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

// ********************************************************************************
// Application
// ********************************************************************************
    public function application()
    {
        $application = DOCROOT . 'install/application.php';

        if (file_exists($application))
        {
            require_once $application;

            $this->application = new Application();
        }

        if (method_exists($this->application, $this->action))
        {
            $return = call_user_func_array([&$this->application, $this->action], []);

            $data = [
                'title'     => self::$title,
                'message'   => self::$message,
                'content'   => implode(PHP_EOL, self::$content),
                'action'    => $this->action,
                'button'    => $this->lang('button'),
                'onclick'   => '',
                'actions'   => $this->application->actions(),
				'time'		=> time()
            ];

            if (!empty($return) && is_array($return)) {
                $data = array_merge($data, $return);
            }
        }
        else {
            show_error('Error, Not Found.');
        }

        $display = $this->views(DOCROOT . 'install/template.php', $data);

        if ($this->is_ajax_request())
        {
            preg_match("#<body>(.+?)<\/body>#is", $display, $matches);

            $display = json_encode(['body' => $matches[0]]);

            header("Content-type: application/json; charset=utf-8");
        }

        exit($display);
    }
}

class JSmart_Zip  {

    var $dirs 	   = [];
    var $files 	   = [];
    var $data 	   = [];
    var $root_dirs = [];

    function open ($name)
    {
        // Read zip file //
        $fh = fopen ($name, 'rb');
        $zip_file = fread ($fh, filesize($name));
        fclose ($fh);

        // Sections //
        $zip_data = explode("\x50\x4b\x05\x06", $zip_file);

        // Cut entries from the central directory //
        $zip_data = explode ("\x50\x4b\x01\x02", $zip_file);
        $zip_data = explode ("\x50\x4b\x03\x04", $zip_data[0]);
        array_shift ($zip_data);

        foreach ($zip_data as $filedata)
        {
            $unpackeda = unpack("v1version/v1general_purpose/v1compress_method/v1file_time/v1file_date/V1crc/V1size_compressed/V1size_uncompressed/v1filename_length", $filedata);

            // Check for encryption
            $isencrypted = (($unpackeda['general_purpose'] & 0x0001) ? true : false);

            // Check for value block after compressed data
            if($unpackeda['general_purpose'] & 0x0008)
            {
                $unpackeda2 = unpack("V1crc/V1size_compressed/V1size_uncompressed", substr($filedata, -12));

                $unpackeda['crc'] = $unpackeda2['crc'];
                $unpackeda['size_compressed'] = $unpackeda2['size_uncompressed'];
                $unpackeda['size_uncompressed'] = $unpackeda2['size_uncompressed'];

                unset($unpackeda2);
            }

            $row['name'] = substr($filedata, 26, $unpackeda['filename_length']);

            if (substr($row['name'], -1) == '/')
            {
                $this->dirs[] = $row['name'];

                $root_dir = explode('/', $row['name']);

                if (isset($root_dir[0])) {
                    $this->root_dirs[$root_dir[0]] = $root_dir[0];
                }

                continue;
            }
            else
            {
                $this->files[] = $row['name'];
            }

            $row['dir']  = dirname($row['name']);
            $row['dir']  = ($row['dir'] == '.' ? '' : $row['dir']);
            $row['name'] = basename($row['name']);


            $filedata = substr($filedata, 26 + $unpackeda['filename_length']);

            if ($isencrypted)
            {
                $row['error'] = "Encryption is not supported.";
            }
            else
            {
                // compress method //
                switch ($unpackeda['compress_method'])
                {
                    case 0: break; // Not compressed, continue

                    case 8: $filedata = gzinflate($filedata); break;

                    case 12: // BZIP2
                        if(!extension_loaded("bz2")) { @dl((strtolower(substr(PHP_OS, 0, 3)) == "win") ? "php_bz2.dll" : "bz2.so"); }

                        if(extension_loaded("bz2")) { $filedata = bzdecompress($filedata); }
                        else { $row['error'] = "Required BZIP2 Extension not available."; }
                        break;

                    default:
                        $row['error'] = "Compression method ({$unpackeda['compress_method']}) not supported.";
                }
            }

            $id = ($row['dir']) ? $row['dir'] . '/' . $row['name'] : $row['name'];

            $this->data[$id] = $filedata;
        }

        return TRUE;
    }

    function extract ($_dir = '')
    {
        $_dir = ($_dir) ? rtrim ($_dir, '/') . '/' : '';

        // dirs //
        if (count($this->dirs))
        {
            foreach ($this->dirs as $dir) {

                @mkdir($_dir . $dir, 0777, TRUE);

                if (is_dir($_dir . $dir) == FALSE) {
                    $this->error[] = $_dir . $dir;
                }
            }
        }

        // files //
        if (count($this->data))
        {
            foreach ($this->data as $file => $data) {

                if (!is_dir(dirname($file))) {
                    @mkdir(dirname($file), 0777, TRUE);
                }

                if (file_exists($_dir . $file) && is_writable($_dir . $file) == FALSE) {
                    $this->error[] = $_dir . $file;
                }

                @file_put_contents($_dir . $file, $data);
            }
        }

        if (empty($this->error))
        {
            return TRUE;
        }

        return FALSE;
    }
}