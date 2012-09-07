<?php

/**
 * Web proxy bypass controller.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2012 ClearFoundation
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
 * Web proxy bypass controller.
 *
 * @category   Apps
 * @package    Web_Proxy
 * @subpackage Controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2012 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Bypass extends ClearOS_Controller
{
    /**
     * Web proxy bypass overview.
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load('web_proxy');
        $this->load->library('web_proxy/Squid_Firewall');

        // Load view data
        //---------------

        try {
            $data['bypasses'] = $this->squid_firewall->get_proxy_bypass_list();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('web_proxy/bypass/summary', $data, lang('web_proxy_web_proxy_bypass'));
    }

    /**
     * Web proxy bypass add.
     *
     * @return view
     */

    function add()
    {
        $this->_form('add');
    }

    /**
     * Delete entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function delete($ip)
    {
        $confirm_uri = '/app/web_proxy/bypass/destroy/' . $ip;
        $cancel_uri = '/app/web_proxy/bypass';
        $items = array($ip);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys entry view.
     *
     * @param string $ip IP address
     *
     * @return view
     */

    function destroy($ip = NULL)
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid_Firewall');

        // Handle delete
        //--------------

        try {
            $this->squid_firewall->delete_proxy_bypass($ip);

            $this->page->set_status_deleted();
            redirect('/web_proxy/bypass');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Web proxy bypass edit.
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

        $this->lang->load('web_proxy');
        $this->load->library('web_proxy/Squid_Firewall');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('nickname', 'web_proxy/Squid_Firewall', 'validate_name', TRUE);
        $this->form_validation->set_policy('ip', 'web_proxy/Squid_Firewall', 'validate_ip', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok)) {
            try {
                // Update
                $this->squid_firewall->add_proxy_bypass(
                    $this->input->post('nickname'),
                    $this->input->post('ip')
                );


                // clearsync handles reload
                $this->page->set_status_updated();
                redirect('/web_proxy/bypass');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        $data['form_type'] = $form_type;

        // Load views
        //-----------

        $this->page->view_form('web_proxy/bypass/item', $data, lang('web_proxy_web_proxy_bypass'));
    }
}
