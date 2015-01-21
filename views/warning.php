<?php

/**
 * Warning view.
 *
 * @category   apps
 * @package    web-proxy
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Form 
///////////////////////////////////////////////////////////////////////////////

echo form_open('web_proxy/warning');
echo form_header(lang('web_proxy_web_proxy'));

echo field_view(lang('base_status'), $message, 'warning');
echo field_view(lang('web_proxy_web_address'), $url, 'web_address');
echo field_view(lang('network_ip'), $ip, 'ip');
echo field_view(lang('web_proxy_internet_connection_status'), loading(), 'status');

echo form_footer();
echo form_close();
