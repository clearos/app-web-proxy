<?php

/**
 * Web proxy exceptions controller.
 *
 * @category   apps
 * @package    web-proxy
 * @subpackage controllers
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Web proxy exceptions controller.
 *
 * @category   apps
 * @package    web-proxy
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Exceptions extends ClearOS_Controller
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
        $this->load->library('web_proxy/Squid');

        // Load view data
        //---------------

        try {
            $data['exceptions'] = $this->squid->get_exception_sites();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('web_proxy/exceptions/summary', $data, lang('web_proxy_exception_sites'));
    }

    /**
     * Web proxy exception add.
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
     * @param string $site site
     *
     * @return view
     */

    function delete($site)
    {
        $confirm_uri = '/app/web_proxy/exceptions/destroy/' . $site;
        $cancel_uri = '/app/web_proxy/exceptions';
        $items = array($site);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys entry view.
     *
     * @param string $site site
     *
     * @return view
     */

    function destroy($site)
    {
        // Load libraries
        //---------------

        $this->load->library('web_proxy/Squid');

        // Handle delete
        //--------------

        try {
            $this->squid->delete_exception_site($site);
            $this->squid->reset(TRUE);
            $this->page->set_status_deleted();
            redirect('/web_proxy/exceptions');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Web proxy exceptions edit.
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
        $this->load->library('web_proxy/Squid');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('site', 'web_proxy/Squid', 'validate_site', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok)) {
            try {
                $this->squid->add_exception_site($this->input->post('site'));
                $this->squid->reset(TRUE);
                $this->page->set_status_updated();
                redirect('/web_proxy/exceptions');
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

        $this->page->view_form('web_proxy/exceptions/item', $data, lang('web_proxy_exception_sites'));
    }
}
