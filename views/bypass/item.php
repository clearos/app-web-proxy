<?php

/**
 * Web proxy bypass view.
 *
 * @category   apps
 * @package    web-proxy
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2012 ClearFoundation
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
$this->lang->load('firewall');
$this->lang->load('network');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'view') {
    $read_only = TRUE;
    $form_path = '/web_proxy/bypass/view';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/web_proxy/bypass')
    );
} else if ($form_type === 'add') {
    $read_only = FALSE;
    $form_path = '/web_proxy/bypass/add';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/web_proxy/bypass/')
    );
} else {
    $read_only = FALSE;
    $form_path = '/web_proxy/bypass/edit';
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/web_proxy/bypass/'),
        anchor_delete('/app/web_proxy/bypass/delete/' . $interface)
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('web_proxy_web_proxy_bypass'));

echo field_input('nickname', $nickname, lang('firewall_nickname'), $read_only);
echo field_input('address', $address, lang('network_address'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
