<?php

/**
 * Squid web proxy class.
 *
 * @category   apps
 * @package    web-proxy
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\web_proxy;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('web_proxy');
clearos_load_language('network');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\OS as OS;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\base\Tuning as Tuning;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\network\Network as Network;
use \clearos\apps\network\Network_Status as Network_Status;
use \clearos\apps\network\Network_Utils as Network_Utils;
use \clearos\apps\web_proxy\Squid_Firewall as Squid_Firewall;

clearos_load_library('base/Daemon');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/OS');
clearos_load_library('base/Shell');
clearos_load_library('base/Tuning');
clearos_load_library('network/Iface_Manager');
clearos_load_library('network/Network');
clearos_load_library('network/Network_Status');
clearos_load_library('network/Network_Utils');
clearos_load_library('web_proxy/Squid_Firewall');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\File_Not_Found_Exception as File_Not_Found_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/File_Not_Found_Exception');
clearos_load_library('base/Validation_Exception');


///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Squid web proxy class.
 *
 * @category   apps
 * @package    web-proxy
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Squid extends Daemon
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const FILE_CONFIG = '/etc/squid/squid.conf';
    const FILE_ACLS_CONFIG = '/etc/squid/squid_acls.conf';
    const FILE_AUTH_CONFIG = '/etc/squid/squid_auth.conf';
    const FILE_LANS_CONFIG = '/etc/squid/squid_lans.conf';
    const FILE_PORT_CONFIG = '/etc/squid/squid_http_port.conf';
    const FILE_ECAP_SQUID_CONFIG = '/etc/squid/squid_ecap.conf';
    const FILE_ECAP_XML_CONFIG = '/etc/clearos/ecap-adapter.conf';
    const FILE_HTTP_ACCESS_CONFIG = '/etc/squid/squid_http_access.conf';
    const FILE_WHITELISTS_CONFIG = '/etc/squid/squid_whitelists.conf';
    const FILE_APP_CONFIG = '/etc/clearos/web_proxy.conf';
    const PATH_SPOOL = '/var/spool/squid';
    const PATH_TEMPLATES = '/var/clearos/web_proxy/errors';
    const COMMAND_CLEAR_CACHE = '/usr/sbin/app-web-proxy-clear-cache';

    const CONSTANT_NO_OFFSET = -1;
    const CONSTANT_UNLIMITED = 0;

    const STATUS_ONLINE = 'online';
    const STATUS_OFFLINE = 'offline';
    const STATUS_UNKNOWN = 'unknown';
    
    const DEFAULT_CHILDREN = 10;
    const DEFAULT_MAX_FILE_DOWNLOAD_SIZE = 0;
    const DEFAULT_MAX_OBJECT_SIZE = 4095;
    const DEFAULT_REPLY_BODY_MAX_SIZE_VALUE = 'none';
    const DEFAULT_CACHE_SIZE = 102400;
    const DEFAULT_CACHE_DIR_VALUE = 'ufs /var/spool/squid 100 16 256';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $is_loaded = FALSE;
    protected $config = array();
    protected $file_pam_auth = NULL;
    protected $file_squid_unix_group = NULL;
    protected $error_templates = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Squid constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);

        parent::__construct('squid');

        $this->error_templates = clearos_app_base('web_proxy') . '/deploy/templates';

        // Handle embedded lib/lib64 paths in configuration files
        //-------------------------------------------------------

        $lib = (file_exists('/usr/lib64/squid')) ? 'lib64' : 'lib';

        if (clearos_version() >= 7)
            $this->file_pam_auth = "/usr/$lib/squid/basic_pam_auth";
        else
            $this->file_pam_auth = "/usr/$lib/squid/pam_auth";

        if (clearos_version() >= 7)
            $this->file_squid_unix_group = "/usr/$lib/squid/ext_unix_group_acl";
        else
            $this->file_squid_unix_group = "/usr/$lib/squid/squid_unix_group";
    }

    /**
     * Add exception site.
     *
     * @param string $site site
     *
     * @return boolean FALSE if site already exists
     * @throws Engine_Exception
     */

    public function add_exception_site($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_site($site));

        $sites = $this->get_exception_sites();
        $sites[] = $site;
        sort($sites);

        $this->_set_exception_sites($sites);
    }

    /**
     * Auto configures web proxy.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function auto_configure()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Bail if auto configure disabled
        //--------------------------------

        if (! $this->get_auto_configure_state())
            return;

        // Grab some network info first
        //-----------------------------

        $iface_manager = new Iface_Manager();
        $ips = $iface_manager->get_most_trusted_ips();
        $lans = $iface_manager->get_most_trusted_networks(TRUE, TRUE);

        $firewall = new Squid_Firewall();
        $is_firewall_transparent = $firewall->get_proxy_transparent_state();
        $is_proxy_filter_running = $firewall->get_proxy_filter_state();

        $network = new Network();
        $mode = $network->get_mode();
        $is_standalone = (($mode === Network::MODE_STANDALONE) || ($mode === Network::MODE_TRUSTED_STANDALONE)) ? TRUE : FALSE;

        // Handle error templates
        //-----------------------

        $folder = new Folder($this->error_templates);
        $templates = $folder->get_listing();

        foreach ($templates as $template) {
            $target = preg_replace('/\.template$/', '', $template);

            $file = new File($this->error_templates . '/' . $template);
            $contents = $file->get_contents();
            $contents = preg_replace('/PCN_LAN_IP/s', $ips[0], $contents);
            $current_contents = '';

            $file = new File(self::PATH_TEMPLATES . '/' . $target);

            if ($file->exists())
                $current_contents = $file->get_contents();

            if (trim($current_contents) != trim($contents)) {
                if ($file->exists())
                    $file->delete();

                $file->create('root', 'root', '0644');
                $file->add_lines("$contents\n");
            }
        }

        // Handle proxy port listener
        //---------------------------

        $reload_squid = FALSE;

        $transparent = ($is_firewall_transparent && !$is_standalone && !$is_proxy_filter_running) ? ' intercept' : '';

        if (! in_array('localhost4', $ips))
            array_unshift($ips, 'localhost4');

        if (! in_array('localhost6', $ips))
            array_unshift($ips, 'localhost6');

        $current_lines = '';
        $new_lines = "# Created automatically based on network configuration\n";

        foreach ($ips as $ip) {
            if (preg_match('/^localhost/', $ip))
                $new_lines .= "http_port $ip:3128\n";
            else
                $new_lines .= "http_port $ip:3128$transparent\n";
        }

        $file = new File(self::FILE_PORT_CONFIG);

        if ($file->exists())
            $current_lines = $file->get_contents();

        if (trim($current_lines) != trim($new_lines)) {
            clearos_log('web_proxy', 'auto-configuration - updating port configuration');

            if ($file->exists())
                $file->delete();

            $file->create('root', 'root', '0644');
            $file->add_lines($new_lines);

            $reload_squid = TRUE;
        }

        // LAN ACL definitions
        //--------------------

        if (empty($lans)) {
            $lans = array(
                '10.0.0.0/8',
                '172.16.0.0/12',
                '192.168.0.0/16',
            );
        }

        $lan_list = '';

        foreach ($lans as $lan)
            $lan_list .= " $lan";

        $current_lines = '';

        $new_lines = "# Created automatically based on network configuration\n";
        $new_lines .= "acl webconfig_lan src$lan_list\n";
        $new_lines .= "acl webconfig_to_lan dst$lan_list\n";

        $file = new File(self::FILE_LANS_CONFIG);

        if ($file->exists())
            $current_lines = $file->get_contents();

        if (trim($current_lines) != trim($new_lines)) {
            clearos_log('web_proxy', 'auto-configuration - updating LAN configuration');

            if ($file->exists())
                $file->delete();

            $file->create('root', 'root', '0644');
            $file->add_lines($new_lines);

            $reload_squid = TRUE;
        }

        // Reload Squid if a change occurred
        //----------------------------------

        if ($reload_squid)
            $this->reset();
    }

    /**
     * Bumps the priority of an ACL.
     *
     * @param string  $name     time name
     * @param integer $priority use value greater than zero to bump up
     *
     * @return void
     * @throws Engine_Exception
     */

    public function bump_time_acl_priority($name, $priority)
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = $this->_load_configlet(self::FILE_HTTP_ACCESS_CONFIG);
        $file = new File(self::FILE_HTTP_ACCESS_CONFIG, TRUE);

        $last = '';
        $counter = 1;

        foreach ($config['http_access']['line'] as $acl) {
            if (!preg_match("/^(deny|allow) cleargroup-/", $acl)) {
                $counter++;
                continue;
            }

            if (preg_match("/^(deny|allow) cleargroup-$name\s+/", $acl)) {
                // Found ACL
                $file->delete_lines("/^http_access $acl$/");

                if ($priority > 0)
                    $file->add_lines_before('http_access ' . $acl . "\n", "/^" . $last . "$/");
                else
                    $file->add_lines_after('http_access ' . $acl . "\n", "/^http_access " . $config['http_access']['line'][$counter + 1] . "$/");

                break;
            }

            $last = 'http_access ' . $acl;
            $counter++;
        }
    }

    /**
     * Deletes the proxy cache.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function clear_cache()
    {
        clearos_profile(__METHOD__, __LINE__);

        // TODO: migrate shell calls to folder class

        $shell = new Shell();
        $spool_folder = new Folder(self::PATH_SPOOL, TRUE);
        $hold_folder = new Folder(self::PATH_SPOOL . '/old', TRUE);

        if ($hold_folder->exists()) 
            $shell->execute('/bin/rm', '-rf ' . self::PATH_SPOOL . '/old', TRUE);

        $hold_folder->create('root', 'root', '0755');

        // Shutdown Squid
        //---------------

        $was_running = $this->get_running_state();

        if ($was_running)
            $this->set_running_state(FALSE);

        // Move subdirectories into temporary old directory
        //-------------------------------------------------
        
        $spool_subdir_list = $spool_folder->get_listing();

        foreach ($spool_subdir_list as $spool_subdir) {
            if ($spool_subdir !== 'old')
                $shell->execute('/bin/mv', '/var/spool/squid/' . $spool_subdir . ' /var/spool/squid/old', TRUE);
        }

        // Restart Squid
        //--------------

        if ($was_running)
            $this->set_running_state(TRUE);

        $shell->execute('/bin/rm', '-rf ' . self::PATH_SPOOL . '/old', TRUE);
    }

    /**
     * Delete exception site.
     *
     * @param string $site site
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_exception_site($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_site($site, FALSE));

        $trimmed_sites = array();

        $sites = $this->get_exception_sites();

        foreach ($sites as $raw_site) {
            if ($raw_site != $site)
                $trimmed_sites[] = $raw_site;
        }

        $this->_set_exception_sites($trimmed_sites);
    }

    /**
     * Deletes an ACL.
     *
     * @param string $name acl name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_time_acl($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = $this->_load_configlet(self::FILE_HTTP_ACCESS_CONFIG);

        $type = 'allow';

        foreach ($config['http_access']['line'] as $acl) {
            if (preg_match("/^(deny|allow) cleargroup-$name .*$/", $acl, $match)) {
                $type = $match[1];
                break;
            }
        }

        $this->_delete_parameter("acl cleargroup-$name (external system_group|src|arp)", self::FILE_ACLS_CONFIG);
        $this->_delete_parameter("http_access $type cleargroup-$name", self::FILE_HTTP_ACCESS_CONFIG);
    }

    /**
     * Deletes a time definition.
     *
     * @param string $name time name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete_time_definition($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $config = $this->_load_configlet(self::FILE_HTTP_ACCESS_CONFIG);

        //  Delete any ACL's using this time definition
        foreach ($config['http_access']['line'] as $acl) {
            if (preg_match("/^(deny|allow) cleargroup-(.*) (cleartime-$name|!cleartime-$name).*$/", $acl, $match)) {
                $type = $match[1];
                $aclname = $match[2];
                $this->_delete_parameter("http_access $type cleargroup-$aclname", self::FILE_HTTP_ACCESS_CONFIG);

                // User
                try {
                    $this->_delete_parameter("acl cleargroup-$aclname external system_group", self::FILE_ACLS_CONFIG);
                } catch (Exception $e) {
                    // Ignore
                }

                // IP
                try {
                    $this->_delete_parameter("acl cleargroup-$aclname src", self::FILE_ACLS_CONFIG);
                } catch (Exception $e) {
                    // Ignore
                }

                // MAC
                try {
                    $this->_delete_parameter("acl cleargroup-$aclname arp", self::FILE_ACLS_CONFIG);
                } catch (Exception $e) {
                    // Ignore
                }
            }
        }

        // Delete time definition
        $this->_delete_parameter("acl cleartime-$name time", self::FILE_ACLS_CONFIG);
    }

    /**
     * Returns allow/deny mapping.
     *
     * @return array a mapping of access types
     */

    public function get_access_types()
    {
        $type = array(
            'allow' => lang('web_proxy_allow'),
            'deny' => lang('web_proxy_deny')
        );

        return $type;
    }

    /**
     * Returns all defined ACL rules.
     *
     * @return array a list of time-based ACL rules.
     * @throws Engine_Exception
     */

    public function get_acl_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();

        $config = $this->_load_configlet(self::FILE_HTTP_ACCESS_CONFIG);

        $file = new File(self::FILE_ACLS_CONFIG, TRUE);

        foreach ($config['http_access']['line'] as $line => $acl) {
            if (!preg_match("/^(deny|allow) cleargroup-.*$/", $acl))
                continue;

            $temp = array();
            $parts = explode(' ', $acl);
            $temp['type'] = $parts[0];
            $temp['name'] = substr($parts[1], 11, strlen($parts[1]));
            $temp['logic'] = !preg_match("/^!/", $parts[2]);

            try {
                list($dow, $tod) = preg_split('/ /', $file->lookup_value("/^acl " . preg_replace("/^!/", "", $parts[2]) . " time/"));
            } catch (File_No_Match_Exception $e) {
                continue;
            } 

            $temp['time'] = preg_replace("/.*cleartime-/", "", $parts[2]);
            $temp['dow'] = $dow;
            $temp['tod'] = $tod;
            $temp['groups'] = '';

            try {
                $temp['groups'] = trim($file->lookup_value("/^acl cleargroup-" . $temp['name'] . " external system_group/"));
                $temp['ident'] = 'group';
            } catch (File_No_Match_Exception $e) {
                $temp['groups'] = '';
            }

            try {
                $temp['ips'] = trim($file->lookup_value("/^acl cleargroup-" . $temp['name'] . " src/"));
                $temp['ident'] = 'src';
            } catch (File_No_Match_Exception $e) {
                $temp['ips'] = '';
            }

            try {
                $temp['macs'] = trim($file->lookup_value("/^acl cleargroup-" . $temp['name'] . " arp/"));
                $temp['ident'] = 'arp';
            } catch (File_No_Match_Exception $e) {
                $temp['macs'] = '';
            }

            $list[] = $temp;
        }

        return $list;
    }

    /**
     * Returns auto-configure state.
     *
     * @return boolean state of auto-configure mode
     */

    public function get_auto_configure_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_APP_CONFIG);
            $value = $file->lookup_value("/^auto_configure\s*=\s*/i");
        } catch (File_Not_Found_Exception $e) {
            return TRUE;
        } catch (File_No_Match_Exception $e) {
            return TRUE;
        } catch (Exception $e) {
            throw new Engine_Exception($e->get_message());
        }

        if (preg_match('/yes/i', $value))
            return TRUE;
        else
            return FALSE;
    }

    /**
     * Returns authentication details.
     *
     * @return array authentication details
     * @throws Engine_Exception
     */

    public function get_basic_authentication_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $info = array();

        if (isset($this->config["auth_param"])) {

            foreach ($this->config["auth_param"]["line"] as $line) {
                $items = preg_split("/\s+/", $line, 3);

                if ($items[1] == "program") {
                    if ($items[0] != "basic")
                        throw new Engine_Exception(lang('web_proxy_custom_configuration_detected'));
                    $info['program'] = $items[2];
                } else if ($items[1] == "children") {
                    $info['children'] = $items[2];
                } else if ($items[1] == "credentialsttl") {
                    $info['credentialsttl'] = $items[2];
                } else if ($items[1] == "realm") {
                    $info['realm'] = $items[2];
                }
            }
        }

        return $info;
    }

    /**
     * Returns the cache size (in kilobytes).
     *
     * @return integer cache size in kilobytes
     * @throws Engine_Exception
     */

    public function get_cache_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['cache_dir'])) {
            $items = preg_split('/\s+/', $this->config['cache_dir']['line'][1]);

            if (isset($items[2]))
                return $this->_size_in_kilobytes($items[2], 'MB');
            else
                return self::DEFAULT_CACHE_SIZE;
        } else {
            return self::DEFAULT_CACHE_SIZE;
        }
    }

    /**
     * Returns Internet connection status.
     *
     * @return string connection status
     */

    public function get_connection_status()
    {
        clearos_profile(__METHOD__, __LINE__);

        $network_status = new Network_Status();
        $status = $network_status->get_connection_status();

        if ($status === Network_Status::STATUS_ONLINE)
            return self::STATUS_ONLINE;
        else if ($status === Network_Status::STATUS_OFFLINE)
            return self::STATUS_OFFLINE;
        else
            return self::STATUS_UNKNOWN;
    }

    /**
     * Returns Internet connection status message.
     *
     * @return string connection status message
     */

    public function get_connection_status_message()
    {
        clearos_profile(__METHOD__, __LINE__);

        $status = $this->get_connection_status();

        if ($status === self::STATUS_ONLINE)
            return lang('web_proxy_online');
        else if ($status === self::STATUS_OFFLINE)
            return lang('web_proxy_offline');
        else
            return lang('web_proxy_unavailable');
    }

    /**
     * Returns the state of content filter.
     *
     * @return boolean state of content filter
     * @throws Engine_Exception
     */

    public function get_content_filter_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        return TRUE;
    }

    /**
     * Returns the days of the week options.
     *
     * @return array 
     */

    public function get_days_of_week()
    {
        clearos_profile(__METHOD__, __LINE__);

        $dow = array(
            'M' => lang('base_monday'),
            'T' => lang('base_tuesday'),
            'W' => lang('base_wednesday'),
            'H' => lang('base_thursday'),
            'F' => lang('base_friday'),
            'A' => lang('base_saturday'),
            'S' => lang('base_sunday')
        );

        return $dow;
    }

    /**
     * Returns method of identification mapping.
     *
     * @return array a mapping of ID types
     */

    public function get_exception_sites()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_WHITELISTS_CONFIG);

        $lines = $file->get_contents_as_array();
        $sites = array();

        foreach ($lines as $line) {
            $matches = array();
            if (preg_match('/acl\s+whitelist_destination_domains\s+dstdomain\s+(.*)/', $line, $matches))
                $sites[] = preg_replace('/^\./', '', $matches[1]);
        }

        return $sites;
    }

    /**
     * Returns method of identification mapping.
     *
     * @return array a mapping of ID types
     */

    public function get_identification_types()
    {
        $type = array(
            'group' => lang('web_proxy_group'),
            'src' => lang('network_ip'),
            'arp' => lang('network_mac_address')
        );

        return $type;
    }

    /**
     * Returns the maximum file download size (in kilobytes).
     *
     * @return integer maximum file download size in kilobytes
     * @throws Engine_Exception
     */

    public function get_maximum_file_download_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        if (isset($this->config["reply_body_max_size"])) {
            $items = preg_split("/\s+/", $this->config["reply_body_max_size"]["line"][1]);

            if (isset($items[0]))
                return $items[0];
            else
                return self::DEFAULT_MAX_FILE_DOWNLOAD_SIZE;
        } else {
            return self::DEFAULT_MAX_FILE_DOWNLOAD_SIZE;
        }
    }

    /**
     * Returns the maximum object size (in kilobytes).
     *
     * @return int maximum object size in kilobytes
     * @throws Engine_Exception
     */

    public function get_maximum_object_size()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        if (isset($this->config["maximum_object_size"])) {
            $items = preg_split("/\s+/", $this->config["maximum_object_size"]["line"][1]);

            if (isset($items[0])) {
                if (isset($items[1]))
                    return $this->_size_in_kilobytes($items[0], $items[1]);
                else
                    return $items[0];
            } else {
                return self::DEFAULT_MAX_OBJECT_SIZE;
            }
        } else {
            return self::DEFAULT_MAX_OBJECT_SIZE;
        }
    }

    /**
     * Returns NTLM state.
     *
     * @return boolean TRUE if NTLM mode is desired
     * @throws Engine_Exception
     */

    public function get_ntlm_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_APP_CONFIG);

        if (!$file->exists())
            return TRUE;

        try {
            $state_value = $file->lookup_value('/ntlm\s*=\s*/');
        } catch (File_No_Match_Exception $e) {
            return TRUE;
        }

        $state = (preg_match('/yes/i', $state_value)) ? TRUE : FALSE;

        return $state;
    }

    /**
     * Returns redirect_program parameter.
     *
     * @return string redirect program
     * @throws Engine_Exception
     */

    public function get_redirect_program()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config["redirect_program"]))
            return $this->config["redirect_program"]["line"][1];
    }

    /**
     * Returns all time-based ACL definitions.
     *
     * @return array a list of time-based ACL definitions.
     * @throws Engine_Exception
     */

    public function get_time_definition_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();

        $config = $this->_load_configlet(self::FILE_ACLS_CONFIG);

        foreach ($config['acl']['line'] as $line => $acl) {
            if (!preg_match("/^cleartime-.*$/", $acl))
                continue;

            $temp = array();
            $parts = explode(' ', $acl);
            $temp['name'] = substr($parts[0], 10, strlen($parts[0]));
            $temp['dow'] = str_split($parts[2]);
            list($temp['start'], $temp['end']) = explode('-', $parts[3]);

            $list[] = $temp;
        }

        return $list;
    }

    /**
     * Returns tuning level.
     *
     * @return string tuning level
     * @throws Engine_Exception
     */

    public function get_tuning()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (clearos_app_installed('performance_tuning')) {
            clearos_load_library('performance_tuning/Performance_Tuning');

            $performance = new \clearos\apps\performance_tuning\Performance_Tuning();
            $tuning = $performance->get_web_proxy_tuning();
        } else {
            $tuning['level'] = Tuning::LEVEL_HOME_NETWORK;
            $tuning['children'] = self::DEFAULT_CHILDREN;
        }

        return $tuning;
    }
    
    /**
     * Returns state of user authentication.
     *
     * @return boolean TRUE if user authentication is enabled.
     * @throws Engine_Exception
     */

    public function get_user_authentication_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        if (isset($this->config['http_access'])) {
            foreach ($this->config['http_access']['line'] as $line) {
                if (preg_match('/^allow\s+webconfig_lan\s+password$/', $line))
                    return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * Returns TRUE if the YouTube EDU header is enabled.
     *
     * @return boolean TRUE if YouTube EDU is enabled.
     * @throws Engine_Exception
     */

    public function get_youtube_edu_enabled()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_ECAP_SQUID_CONFIG, TRUE);

        $value = trim($file->lookup_value("/^ecap_enable\s*/i"));

        return (preg_match('/off/', $value) ? FALSE : TRUE);
    }

    /**
     * Returns the YouTube EDU ID.
     *
     * @return string YouTube EDU ID
     * @throws Engine_Exception
     */

    public function get_youtube_edu_id()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_ECAP_XML_CONFIG, TRUE);
        $xml_source = $file->get_contents();

        $xml = simplexml_load_string($xml_source);
        if ($xml === FALSE) return '';

        foreach ($xml->header as $i => $hdr) {
            if ($hdr['name'] != 'X-YouTube-Edu-Filter') continue;
            return $xml->header[0];
        }

        return '';
    }

    /**
     * Runs clear cache.
     *
     * @param boolean $background background flag
     *
     * @return void
     * @throws Engine_Exception
     */

    public function run_clear_cache($background = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $options['background'] = TRUE;

        $shell = new Shell();
        $shell->execute(self::COMMAND_CLEAR_CACHE, '', TRUE, $options);
    }

    /**
     * Sets user authentication state;
     *
     * @param boolean $state state
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_user_authentication_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->set_basic_authentication_info_default();

        if ($state) {
            $this->_set_parameter('http_access allow webconfig_lan', 'password', self::CONSTANT_NO_OFFSET, '');
            $this->_set_parameter('http_access allow localhost', 'password', self::CONSTANT_NO_OFFSET, '');
        } else {
            $this->_set_parameter('http_access allow webconfig_lan', '', self::CONSTANT_NO_OFFSET, '');
            $this->_set_parameter('http_access allow localhost', '', self::CONSTANT_NO_OFFSET, '');
        }
    }

    /**
     * Sets basic authentication default values.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_basic_authentication_info_default()
    {
        clearos_profile(__METHOD__, __LINE__);

        $os = new OS();
        $name = $os->get_name();
        $realm = $name . ' - ' . lang('web_proxy_web_proxy');

        $tuning = $this->get_tuning();

        // TODO: deal with custom tuning
        if ($tuning['level'] == Tuning::LEVEL_CUSTOM)
            $children = 60;
        else
            $children = $tuning['children'];

        // Open configuration
        //-------------------

        $file = new File(self::FILE_AUTH_CONFIG);

        $lines = "# This file is managed by the ClearOS API.  Use squid.conf for customization.\n";

        // Add NTLM if desired and possible
        //---------------------------------

        if ($this->get_ntlm_state() && clearos_library_installed('samba_common/Samba')) {
            clearos_load_library('samba_common/Samba');
            $samba = new \clearos\apps\samba_common\Samba();

            if ($samba->is_initialized()) {
                $domain = $samba->get_workgroup();

                // TODO: hard coded web_proxy_plugin below
                $lines .= "# NTLM\n";
                $lines .= "auth_param ntlm program /usr/bin/ntlm_auth --helper-protocol=squid-2.5-ntlmssp " .
                    "--require-membership-of=$domain+web_proxy_plugin\n";
                $lines .= "auth_param ntlm children $children\n";
                $lines .= "auth_param ntlm keep_alive on\n";
            }
        }

        // Basic authentication
        //---------------------

        $lines .= "# Basic\n";
        $lines .= "auth_param basic children $children\n";
        $lines .= "auth_param basic realm $realm\n";
        $lines .= "auth_param basic credentialsttl 2 hours\n";
        $lines .= "auth_param basic program $this->file_pam_auth\n";
        // TODO - IPv4 hack below
        $lines .= "external_acl_type system_group ipv4 %LOGIN $this->file_squid_unix_group -p\n";

        if ($file->exists()) 
            $file->delete();

        $file->create('root', 'root', '0644');
        $file->add_lines($lines);
    }

    /**
     * Sets the cache size.
     *
     * @param integer $size size in kilobytes
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_cache_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_cache_size($size));

        $size = round($size / 1024); // MB for cache_dir
        $this->_set_parameter('cache_dir', $size, 3, self::DEFAULT_CACHE_DIR_VALUE);
    }

    /**
     * Sets the maximum download size.
     *
     * @param int $size size in kilobytes
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_maximum_file_download_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_maximum_file_download_size($size));

        if ($size == 'none') {
            $this->_set_parameter('reply_body_max_size', $size, self::CONSTANT_NO_OFFSET, self::DEFAULT_REPLY_BODY_MAX_SIZE_VALUE);
        } else {
            $this->_set_parameter('reply_body_max_size', "$size KB", self::CONSTANT_NO_OFFSET, self::DEFAULT_REPLY_BODY_MAX_SIZE_VALUE);
        }
    }

    /**
     * Sets the maximum object size.
     *
     * @param int $size size in kilobytes
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_maximum_object_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_maximum_object_size($size));

        $size = round($size);
        $this->_set_parameter('maximum_object_size', $size . ' KB', self::CONSTANT_NO_OFFSET, '');
    }

    /**
     * Sets NTLM state.
     *
     * @param boolean $state state of NTLM mode
     *
     * @return void
     * @throws Engine_Exception
     */

    public function set_ntlm_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_state($state));

        $file = new File(self::FILE_APP_CONFIG);

        if (! $file->exists())
            $file->create('root', 'root', '0644');

        $state_value = ($state) ? 'yes' : 'no';

        $match = $file->replace_lines("/^ntlm\s*=/i", "ntlm = $state_value\n");

        if (! $match)
            $file->add_lines("ntlm = $state_value\n");
    }

    /**
     * Adds (or updates) a time-based ACL.
     *
     * @param string  $name       ACL name
     * @param string  $type       ACL type (allow or deny)
     * @param string  $time       time definition
     * @param boolean $time_logic TRUE if within time definition, FALSE if NOT within
     * @param array   $addgroup   group to apply ACL
     * @param array   $addips     array containing IP addresses or network notation to apply ACL
     * @param array   $addmacs    array containing MAC addresses to apply ACL
     * @param boolean $update     TRUE if we are updating an existing entry
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_time_acl($name, $type, $time, $time_logic, $addgroup, $addips, $addmacs, $update = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);
 
        Validation_Exception::is_valid($this->validate_name($name));

        $ips = '';
        $macs = '';

        // Check for existing
        if (!$update) {
            $acls = $this->get_acl_list();
            foreach ($acls as $acl) {
                if ($name == $acl['name'])
                    throw new Validation_Exception(lang('web_proxy_access_control_list_exists'));
            }
        }

        if ($type != 'allow' && $type != 'deny')
            throw new Validation_Exception(lang('base_parameter_invalid'));

        $timelist = $this->get_time_definition_list();
        $timevalid = FALSE;

        foreach ($timelist as $timename) {
            if ($time == $timename['name']) {
                $timevalid = TRUE;
                break;
            }
        }
            
        if (!$timevalid)
            throw new Validation_Exception(lang('web_proxy_time_definition_invalid'));

        $network = new Network();

        foreach ($addips as $ip) {
            if (empty($ip))
                continue;
            $ip = trim($ip);

            if (preg_match("/^(.*)-(.*)$/i", trim($ip), $match)) {
                if (! Network_Utils::is_valid_ip(trim($match[1])))
                    throw new Validation_Exception(lang('network_ip_invalid'));
                if (! Network_Utils::is_valid_ip(trim($match[2])))
                    throw new Validation_Exception(lang('network_ip_invalid'));
            } else {
                if (! Network_Utils::is_valid_ip(trim($ip)))
                    throw new Validation_Exception(lang('network_ip_invalid'));
            }

            $ips .= ' ' . trim($ip);
        }

        foreach ($addmacs as $mac) {
            if (empty($mac))
                continue;
            $mac = trim($mac);

            if (! Network_Utils::is_valid_mac($mac))
                throw new Validation_Exception(lang('network_mac_address_invalid'));

            $macs .= ' ' . $mac;
        }

        // Implant into acl section
        //-------------------------

        $file = new File(self::FILE_ACLS_CONFIG, TRUE);

        $file->delete_lines("/acl cleargroup-$name\s+.*/");

        if (strlen($addgroup) > 0) {
            // Group based
            $replacement = "acl cleargroup-$name external system_group " . $addgroup . "\n";
            $match = $file->replace_lines("/acl cleargroup-$name\s+.*/", $replacement);

            if (! $match)
                $file->add_lines($replacement);
        } else if (strlen($ips) > 0) {
            // IP based
            $replacement = "acl cleargroup-$name src " . trim($ips) . "\n";
            $match = $file->replace_lines("/acl cleargroup-$name\s+.*/", $replacement);

            if (! $match)
                $file->add_lines($replacement);
        } else if (strlen($macs) > 0) {
            // IP based
            $replacement = "acl cleargroup-$name arp " . trim($macs) . "\n";
            $match = $file->replace_lines("/acl cleargroup-$name\s+.*/", $replacement);

            if (! $match)
                $file->add_lines($replacement);
        } else {
            throw new Engine_Exception(lang('base_ooops'));
        }

        $file = new File(self::FILE_HTTP_ACCESS_CONFIG);

        $replacement = "http_access $type cleargroup-$name " . ($time_logic ? "" : "!") . "cleartime-$time\n";
        $match = $file->replace_lines("/http_access (allow|deny) cleargroup-$name .*$/", $replacement);

        if (! $match)
            $file->add_lines("http_access $type cleargroup-$name " . ($time_logic ? "" : "!") . "cleartime-$time\n");
    }

    /**
     * Adds (or updates) a time definition for use with an ACL.
     *
     * @param string  $name   time name
     * @param array   $dow    an array of days of week
     * @param string  $start  start hour/min
     * @param string  $end    end hour/min
     * @param boolean $update TRUE if we are updating an existing entry
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_time_definition($name, $dow, $start, $end, $update = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);
 
        // Validate
        // --------
        Validation_Exception::is_valid($this->validate_name($name));

        // Check for existing
        if (!$update) {
            $times = $this->get_time_definition_list();
            foreach ($times as $time) {
                if ($name == $time['name'])
                    throw new Validation_Exception(lang('web_proxy_time_definition_exists'));
            }
        }

        Validation_Exception::is_valid($this->validate_day_of_week($dow));

        $formatted_dow = implode('', array_values($dow));

        if (strtotime($start) > strtotime($end))
            throw new Validation_Exception(lang('web_proxy_start_time_later_than_end_time'));
        else
            $time_range = $start . '-' . $end; 
        
        if (! $this->is_loaded)
            $this->_load_config();

        // Implant into acl section
        //-------------------------

        $file = new File(self::FILE_ACLS_CONFIG, TRUE);

        $replacement = "acl cleartime-$name time $formatted_dow " . $time_range . "\n";
        $match = $file->replace_lines("/acl cleartime-$name time.*$/", $replacement);

        if (! $match)
            $file->add_lines($replacement);

        $this->is_loaded = FALSE;
        $this->config = array();
    }

    /**
     * Enables/disables YouTube EDU ID
     *
     * @param string  $enable enable/disable YouTube EDU ID header
     * @param array   $id     YouTube EDU ID
     *
     * @return void
     * @throws Engine_Exception, Validation_Exception
     */

    public function set_youtube_edu($enable, $id)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($enable) {
            Validation_Exception::is_valid($this->validate_youtube_edu_id($id));

            $this->_delete_parameter('acl youtube dstdomain .youtube.com', self::FILE_ACLS_CONFIG);
        
            $file = new File(self::FILE_ACLS_CONFIG, TRUE);

            $file->delete_lines("/acl youtube dstdomain\s+.*/");
            $file->add_lines("acl youtube dstdomain .youtube.com\n");

            $ecap_enable = 'ecap_enable on';
        } else {
            // Leave alone or Squid freaks out
            // $this->_delete_parameter('acl youtube dstdomain .youtube.com', self::FILE_ACLS_CONFIG);
            $ecap_enable = 'ecap_enable off';
        }

        $file = new File(self::FILE_ECAP_SQUID_CONFIG, TRUE);

        $file->replace_one_line("/^ecap_enable\s*/i", "$ecap_enable\n");

        if (strlen($id)) {
            $file = new File(self::FILE_ECAP_XML_CONFIG, TRUE);
            $xml_source = $file->get_contents();

            $xml = simplexml_load_string($xml_source);
            if ($xml === FALSE) return;

            foreach ($xml->header as $i => $hdr) {
                if ($hdr['name'] != 'X-YouTube-Edu-Filter') continue;
                $xml->header[0] = $id;
                break;
            }

            $file->delete_lines('/.*/');
            $file->add_lines($xml->asXML());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Deletes a parameter.
     *
     * @param string $key    parameter
     * @param string $config configuration file
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _delete_parameter($key, $config = self::FILE_CONFIG)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        $this->is_loaded = FALSE;
        $this->config = array();

        $file = new File($config, TRUE);

        $match = $file->delete_lines("/^$key\s+/i");
    }

    /**
     * Loads configuration.
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG, TRUE);

        $lines = $file->get_contents_as_array();

        $matches = array();

        foreach ($lines as $line) {
            if (preg_match("/^#/", $line) || preg_match("/^\s*$/", $line))
                continue;

            $items = preg_split("/\s+/", $line, 2);

            // ACL lists are ordered, so an index is required
            if (isset($this->config[$items[0]]))
                $this->config[$items[0]]['count']++;
            else
                $this->config[$items[0]]['count'] = 1;

            // $count is just to make code more readable
            $count = $this->config[$items[0]]['count'];

            $this->config[$items[0]]['line'][$count] = $items[1];
        }

        $this->is_loaded = TRUE;
    }

    /**
     * Loads configlet.
     *
     * @param string $configlet configlet file
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_configlet($configlet)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File($configlet, TRUE);

        $lines = $file->get_contents_as_array();

        $matches = array();
        $config = array();

        foreach ($lines as $line) {
            if (preg_match("/^#/", $line) || preg_match("/^\s*$/", $line))
                continue;

            $items = preg_split("/\s+/", $line, 2);

            // ACL lists are ordered, so an index is required
            if (isset($config[$items[0]]))
                $config[$items[0]]['count']++;
            else
                $config[$items[0]]['count'] = 1;

            // $count is just to make code more readable
            $count = $config[$items[0]]['count'];

            $config[$items[0]]['line'][$count] = $items[1];
        }

        return $config;
    }

    /**
     * Sets exception sites.
     *
     * @param array $sites exception sites
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_exception_sites($sites)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_WHITELISTS_CONFIG . ".tmp", TRUE);

        if ($file->exists())
            $file->delete();

        $file->create('root', 'root', '0644');

        if (empty($sites)) {
            $lines = array();
        } else {
            $lines[] = "# Please specify one domain per line";
            $lines[] = "# ACL definitions";

            foreach ($sites as $site)
                $lines[] = "acl whitelist_destination_domains dstdomain ." . $site;

            $lines[] = '';
            $lines[] = '# Access rule';
            $lines[] = 'http_access allow whitelist_destination_domains';
            $lines[] = 'http_access allow CONNECT whitelist_destination_domains';
        }

        $file->dump_contents_from_array($lines);

        $file->move_to(self::FILE_WHITELISTS_CONFIG);
    }

    /**
     * Generic set routine.
     *
     * @param string $key     key name
     * @param string $value   value for the key
     * @param string $offset  value offset
     * @param string $default default value for key if it does not exist
     *
     * @access private
     * @return void
     * @throws Engine_Exception
     */

    protected function _set_parameter($key, $value, $offset, $default)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        // Do offset magic
        //----------------

        $fullvalue = '';

        if ($offset == self::CONSTANT_NO_OFFSET) {
            $fullvalue = $value;
        } else {
            if (isset($this->config[$key])) {
                $items = preg_split('/\s+/', $this->config[$key]['line'][1]);
                $items[$offset-1] = $value;
                foreach ($items as $item)
                    $fullvalue .= $item . ' ';
            } else {
                $fullvalue = $default;
            }
        }

        $this->is_loaded = FALSE;
        $this->config = array();

        // Update tag if it exists
        //------------------------

        $replacement = trim("$key $fullvalue"); // space cleanup
        $file = new File(self::FILE_CONFIG, TRUE);
        $match = $file->replace_one_line("/^$key\s*/i", "$replacement\n");

        if (!$match) {
            try {
                $file->add_lines_after("$replacement\n", "/^# {0,1}$key /");
            } catch (File_No_Match_Exception $e) {
                $file->add_lines_before("$replacement\n", "/^#/");
            }
        }
    }

    /**
     * Returns the size in kilobytes.
     *
     * @param integer $size  size
     * @param string  $units units
     *
     * @access private
     * @return integer size in kilobytes
     * @throws Engine_Exception, Validation_Exception
     */

    protected function _size_in_kilobytes($size, $units)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^\d+$/', $size))
            throw new Validation_Exception(lang('web_proxy_size_invalid'));

        if ($units == '') {
            return $size / 1024;
        } else if ($units == 'KB') {
            return $size;
        } else if ($units == 'MB') {
            return $size * 1024;
        } else if ($units == 'GB') {
            return $size * 1024*1024;
        } else {
            throw new Validation_Exception(lang('web_proxy_size_invalid'));
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for cache size.
     *
     * @param integer $size cache size
     *
     * @return string error message if cache size is invalid
     */

    public function validate_cache_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ( (!preg_match('/^\d+/', $size)) || ($size < 0) )
            return lang('web_proxy_cache_size_invalid');
    }

    /**
     * Validation routine for day of week.
     *
     * @param string $dow name
     *
     * @return boolean
     */

    public function validate_day_of_week($dow)
    {
        clearos_profile(__METHOD__, __LINE__);
    }
 
    /**
     * Validation routine for maximum file download size.
     *
     * @param integer $size maximum file download size
     *
     * @return string error message if maximum file download size is invalid
     */

    public function validate_maximum_file_download_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($size === 'none')
            return;

        if ( (!preg_match('/^\d+/', $size)) || ($size < 0) )
            return lang('web_proxy_size_invalid');
    }

    /**
     * Validation routine for maximum object size.
     *
     * @param integer $size maximum object size
     *
     * @return string error message if maximum object size is invalid
     */

    public function validate_maximum_object_size($size)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ( (!preg_match('/^\d+/', $size)) || ($size < 0) )
            return lang('web_proxy_size_invalid');
    }

    /**
     * Validation routine for a name.
     *
     * @param string $name name
     *
     * @return boolean
     */

    public function validate_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^([A-Za-z0-9\-\.\_]+)$/", $name))
            return lang('web_proxy_name_invalid');
        // REF: http://wiki.squid-cache.org/SquidFaq/SquidAcl#Maximum_length_of_an_acl_name
        // Plus padding for prefixes like 'cleargroup-'
        if (strlen($name) > 20)
            return lang('web_proxy_name_longer_than_20');
    }

    /**
     * Validation routine for site.
     *
     * @param string  $site             site
     * @param boolean $check_uniqueness checks uniqueness
     *
     * @return string error message if site is invalid
     */

    public function validate_site($site, $check_uniqueness = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! Network_Utils::is_valid_domain($site, TRUE))
            return lang('web_proxy_site_invalid');

        if ($check_uniqueness) {
            $current = $this->get_exception_sites();

            if (in_array($site, $current))
                return lang('web_proxy_site_already_exists');
        }
    }

    /**
     * Validation routine for state.
     *
     * @param boolean $state state
     *
     * @return string error message if state is invalid
     */

    public function validate_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!clearos_is_valid_boolean($state))
            return lang('base_state_invalid');
    }

    /**
     * Validation routine for time acl definition.
     *
     * @param int $time time index
     *
     * @return boolean
     */

    public function validate_time_acl($time)
    {
        clearos_profile(__METHOD__, __LINE__);
        if ((int)$time < 0)
            return lang('web_proxy_time_definition_invalid');
    }

    /**
     * Validation routine for YouTube EDU ID.
     *
     * @param string $id YouTube EDU ID.
     *
     * @return boolean
     */

    public function validate_youtube_edu_id($id)
    {
        clearos_profile(__METHOD__, __LINE__);
        if (!strlen($id))
            return lang('web_proxy_youtube_id_invalid');
    }
}

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
