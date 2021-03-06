<?php

/**
 * Web proxy cache view.
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

$this->lang->load('web_proxy');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/web_proxy')
    );
} else {
    $read_only = TRUE;
    $buttons = array(
        anchor_edit('/app/web_proxy/settings/edit'),
        anchor_custom('/app/web_proxy/settings/delete', lang('web_proxy_reset_cache')),
    );
//        anchor_javascript('reset_cache', lang('web_proxy_reset_cache'), 'high')

}

///////////////////////////////////////////////////////////////////////////////
// Form open
///////////////////////////////////////////////////////////////////////////////

echo form_open('web_proxy/settings/edit'); 
echo form_header(lang('base_settings'));

echo fieldset_header(lang('web_proxy_cache'));
echo field_dropdown('cache', $cache_options, $cache, lang('web_proxy_maximum_cache_size'), $read_only);
echo field_dropdown('object', $object_options, $object, lang('web_proxy_maximum_object_size'), $read_only);
echo field_dropdown('download', $download_options, $download, lang('web_proxy_maximum_file_download_size'), $read_only);
echo fieldset_footer();

echo fieldset_header(lang('base_tuning'));
echo field_dropdown('levels', $levels, $level, lang('base_performance_level'), TRUE);
echo fieldset_footer();

echo fieldset_header(lang('web_proxy_youtube_for_schools'));
echo field_toggle_enable_disable('youtube_edu_enable', $youtube_edu_enable, lang('web_proxy_youtube_for_schools'), $read_only);
echo field_input('youtube_edu_id', $youtube_edu_id, lang('web_proxy_youtube_id'), $read_only);
echo fieldset_footer();

echo field_button_set($buttons);

echo form_footer(); 
echo form_close();

// vi: expandtab shiftwidth=4 softtabstop=4 tabstop=4
