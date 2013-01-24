<?php

/**
 * Configuration warning view.
 *
 * @category   ClearOS
 * @package    Web_Proxy
 * @subpackage Views
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

$this->lang->load('web_proxy');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Form 
///////////////////////////////////////////////////////////////////////////////

if ($port == 'disabled') {
    echo infobox_highlight(lang('web_proxy_web_proxy_configuration'), lang('web_proxy_please_disable_proxy_settings'));
} else {
    echo infobox_highlight(lang('web_proxy_web_proxy_configuration'), lang('web_proxy_configuration_settings_warning:') . 
        "<br><br>" .
        lang('network_ip') . ' - ' . $ip . '<br>' .
        lang('network_port') . ' - ' . $port . '<br>'
    );
}
