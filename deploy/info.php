<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'web_proxy';
$app['version'] = '1.6.3';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('web_proxy_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('web_proxy_app_name');
$app['category'] = lang('base_category_gateway');
$app['subcategory'] = lang('base_subcategory_content_filter_and_proxy');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['web_proxy']['title'] = lang('web_proxy_app_name');
$app['controllers']['authentication']['title'] = lang('web_proxy_authentication');
$app['controllers']['settings']['title'] = lang('base_settings');
$app['controllers']['policy']['title'] = lang('base_app_policy');
$app['controllers']['bypass']['title'] = lang('web_proxy_web_proxy_bypass');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-base-core >= 1:1.6.0',
    'app-network-core >= 1:1.5.16',
    'app-events-core',
    'app-firewall-core >= 1:1.4.15',
    'app-web-proxy-plugin-core',
    'app-samba-common-core',
    'app-storage-core >= 1:1.4.7',
    'samba-winbind',
    'squid >= 3.1.10-20',
    'clearos-ecap-adapter',
);

$app['core_directory_manifest'] = array(
    '/etc/clearos/web_proxy.d' => array(),
    '/var/clearos/web_proxy' => array(),
    '/var/clearos/web_proxy/backup' => array(),
    '/var/clearos/web_proxy/errors' => array(),
);

$app['core_file_manifest'] = array(
    'app-web-proxy-clear-cache' => array(
        'target' => '/usr/sbin/app-web-proxy-clear-cache',
        'mode' => '0755',
    ),
    'squid.php'=> array('target' => '/var/clearos/base/daemon/squid.php'),
    'web_proxy.acl'=> array('target' => '/var/clearos/base/access_control/public/web_proxy'),
    'web_proxy_default.conf' => array ( 'target' => '/etc/clearos/storage.d/web_proxy_default.conf' ),
    'network-configuration-event'=> array(
        'target' => '/var/clearos/events/network_configuration/web_proxy',
        'mode' => '0755'
    ),
    'authorize' => array(
        'target' => '/etc/clearos/web_proxy.d/authorize',
        'mode' => '0644',
        'owner' => 'root',
        'group' => 'root',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_acls.conf' => array(
        'target' => '/etc/squid/squid_acls.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_auth.conf' => array(
        'target' => '/etc/squid/squid_auth.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_http_access.conf' => array(
        'target' => '/etc/squid/squid_http_access.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_http_port.conf' => array(
        'target' => '/etc/squid/squid_http_port.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_lans.conf' => array(
        'target' => '/etc/squid/squid_lans.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'squid_whitelists.conf' => array(
        'target' => '/etc/squid/squid_whitelists.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
    'web_proxy.conf' => array (
        'target' => '/etc/clearos/web_proxy.conf',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);

$app['delete_dependency'] = array(
    'app-web-proxy-core',
    'app-web-proxy-plugin-core',
    'squid'
);
