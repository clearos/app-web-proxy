<?php

/**
 * Web proxy cache controller.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Controllers
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Web proxy cache controller.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Caching extends ClearOS_Controller
{
    /**
     * Web proxy cache overview.
     *
     * @return view
     */

    function index()
    {
        $this->_form('view');
    }

    /**
     * Web proxy cache edit.
     *
     * @return view
     */

    function edit()
    {
        $this->_form('edit');
    }

    /**
     * Common view/edit form.
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _form($form_type)
    {
        // Load dependencies
        //------------------

        $this->load->library('web_proxy/Squid');
        $this->lang->load('web_proxy');
        $this->lang->load('base');

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                // Update
                $this->squid->set_cache_size($this->input->post('cache'));
                $this->squid->set_maximum_object_size($this->input->post('object'));
                $this->squid->set_maximum_file_download_size($this->input->post('download'));

                $this->page->set_status_updated();
                redirect('/web_proxy/caching');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;

            $data['cache'] = $this->squid->get_cache_size();
            $data['object'] = $this->squid->get_maximum_object_size();
            $data['download'] = $this->squid->get_maximum_file_download_size();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        $lang_megabytes = lang('base_megabytes');
        $lang_gigabytes = lang('base_gigabytes');

        // Base unit is in kilobytes so we can use integers (no big numbers required)
        $base_size_options = array(
            '1024' => '1 ' . $lang_megabytes,
            '2048' => '2 ' . $lang_megabytes,
            '3072' => '3 ' . $lang_megabytes,
            '4096' => '4 ' . $lang_megabytes,
            '5120' => '5 ' . $lang_megabytes,
            '6144' => '6 ' . $lang_megabytes,
            '7168' => '7 ' . $lang_megabytes,
            '8192' => '8 ' . $lang_megabytes,
            '9216' => '9 ' . $lang_megabytes,
            '10240' => '10 ' . $lang_megabytes,
            '20480' => '20 ' . $lang_megabytes,
            '30720' => '30 ' . $lang_megabytes,
            '40960' => '40 ' . $lang_megabytes,
            '51200' => '50 ' . $lang_megabytes,
            '61440' => '60 ' . $lang_megabytes,
            '71680' => '70 ' . $lang_megabytes,
            '81920' => '80 ' . $lang_megabytes,
            '92160' => '90 ' . $lang_megabytes,
            '102400' => '100 ' . $lang_megabytes,
            '204800' => '200 ' . $lang_megabytes,
            '307200' => '300 ' . $lang_megabytes,
            '409600' => '400 ' . $lang_megabytes,
            '512000' => '500 ' . $lang_megabytes,
            '614400' => '600 ' . $lang_megabytes,
            '715800' => '700 ' . $lang_megabytes,
            '819200' => '800 ' . $lang_megabytes,
            '921600' => '900 ' . $lang_megabytes,
            '1048576' => '1 ' . $lang_gigabytes,
            '2097152' => '2 ' . $lang_gigabytes,
            '3145728' => '3 ' . $lang_gigabytes,
            '4194304' => '4 ' . $lang_gigabytes,
            '5242880' => '5 ' . $lang_gigabytes,
            '6291456' => '6 ' . $lang_gigabytes,
            '7340032' => '7 ' . $lang_gigabytes,
            '8388608' => '8 ' . $lang_gigabytes,
            '9437184' => '9 ' . $lang_gigabytes,
            '10485760' => '10 ' . $lang_gigabytes,
        );

        $big_size_options = array(
            '102400' => '100 ' . $lang_megabytes,
            '512000' => '500 ' . $lang_megabytes,
            '1048576' => '1 ' . $lang_gigabytes,
            '2097152' => '2 ' . $lang_gigabytes,
            '3145728' => '3 ' . $lang_gigabytes,
            '4194304' => '4 ' . $lang_gigabytes,
            '5242880' => '5 ' . $lang_gigabytes,
            '6291456' => '6 ' . $lang_gigabytes,
            '7340032' => '7 ' . $lang_gigabytes,
            '8388608' => '8 ' . $lang_gigabytes,
            '9437184' => '9 ' . $lang_gigabytes,
            '10485760' => '10 ' . $lang_gigabytes,
            '20971520' => '20 ' . $lang_gigabytes,
            '31457280' => '30 ' . $lang_gigabytes,
            '41943040' => '40 ' . $lang_gigabytes,
            '52428800' => '50 ' . $lang_gigabytes,
            '62914560' => '60 ' . $lang_gigabytes,
            '73400320' => '70 ' . $lang_gigabytes,
            '83886080' => '80 ' . $lang_gigabytes,
            '94371840' => '90 ' . $lang_gigabytes,
            '104857600' => '100 ' . $lang_gigabytes,
            '209715200' => '200 ' . $lang_gigabytes,
            '314572800' => '300 ' . $lang_gigabytes,
            '419430400' => '400 ' . $lang_gigabytes,
            '524288000' => '500 ' . $lang_gigabytes,
            '629145600' => '600 ' . $lang_gigabytes,
            '734003200' => '700 ' . $lang_gigabytes,
            '838860800' => '800 ' . $lang_gigabytes,
            '943718400' => '900 ' . $lang_gigabytes,
        );

        $data['cache_options'] = $big_size_options;
        $data['object_options'] = $base_size_options;
        $data['download_options'] = $base_size_options;
        $data['download_options']['none'] = lang('base_unlimited');
 
        // Load views
        //-----------

        $this->page->view_form('web_proxy/cache/form', $data, lang('web_proxy_cache'));
    }

    /**
     * Delete cache dialog
     *
     * @return view
     */

    function delete()
    {
        // Load dependencies
        //------------------
        $this->load->library('web_proxy/Squid');
    
        $confirm_uri = '/app/web_proxy/caching/reset';
        $cancel_uri = '/app/web_proxy';
        $items = array(lang('web_proxy_cache'));

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }


    /**
     * Resets the cache.
     *
     * @return JSON
     */

    function reset()
    {
        // Load dependencies
        //------------------

        $this->load->library('web_proxy/Squid');

        try {
            $this->squid->run_clear_cache();
            redirect('/web_proxy');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
