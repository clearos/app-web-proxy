<?php

/**
 * Web proxy exception sites add/edit view.
 *
 * @category   apps
 * @package    web-proxy
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2014 ClearFoundation
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
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'view') {
    $read_only = TRUE;
    $form_path = '/web_proxy/exceptions/view';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/web_proxy/exceptions')
    );
} else if ($form_type === 'add') {
    $read_only = FALSE;
    $form_path = '/web_proxy/exceptions/add';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/web_proxy/exceptions/')
    );
} else {
    $read_only = FALSE;
    $form_path = '/web_proxy/exceptions/edit';
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/web_proxy/exceptions/'),
        anchor_delete('/app/web_proxy/exceptions/delete/' . $interface)
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('web_proxy_exception_sites'));

echo field_input('site', $site, lang('web_proxy_site'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
