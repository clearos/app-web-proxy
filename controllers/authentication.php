<?php

/**
 * Web proxy general authentication controller.
 *
 * @category   apps
 * @package    web-proxy
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2014 ClearFoundation
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
 * Web proxy general authentication controller.
 *
 * @category   apps
 * @package    web-proxy
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/web_proxy/
 */

class Authentication extends ClearOS_Controller
{
    /**
     * Web proxy general authentication overview.
     *
     * @return view
     */

    function index()
    {
        $this->_form('view');
    }

    /**
     * Web proxy general authentication edit.
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
        $this->load->library('web_proxy/Squid_Firewall');

        $ntlm_available = FALSE;

        if (clearos_library_installed('samba_common/Samba')) {
            $this->load->library('samba_common/Samba');
            $ntlm_available = $this->samba->is_initialized();
        }

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                if ($this->input->post('mode')) {
                    $mode = $this->input->post('mode');
                    if ($mode == 1) {
                        $this->squid_firewall->set_proxy_transparent_state(TRUE);
                        $this->squid->set_user_authentication_state(FALSE);
                    } else if ($mode == 2) {
                        $this->squid_firewall->set_proxy_transparent_state(FALSE);
                        $this->squid->set_ntlm_state(FALSE);
                        $this->squid->set_user_authentication_state(TRUE);
                    } else if ($mode == 3) {
                        $this->squid_firewall->set_proxy_transparent_state(FALSE);
                        $this->squid->set_ntlm_state(TRUE);
                        $this->squid->set_user_authentication_state(TRUE);
                    } else if ($mode == 4) {
                        $this->squid_firewall->set_proxy_transparent_state(FALSE);
                        $this->squid->set_user_authentication_state(FALSE);
                    }
                } else {
                    $this->squid->set_ntlm_state($this->input->post('ntlm'));
                    $this->squid->set_user_authentication_state($this->input->post('user_authentication'));
                }

                // The network configuration needs to updated when the transparent
                // mode changes around, ergo auto_configure().

                $this->squid->auto_configure();
                $this->squid->reset(TRUE);

                $this->page->set_status_updated();
                redirect('/web_proxy/authentication');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['form_type'] = $form_type;

            $data['transparent_capable'] = $this->squid_firewall->get_proxy_transparent_capability();
            $data['transparent'] = $this->squid_firewall->get_proxy_transparent_state();
            $data['user_authentication'] = $this->squid->get_user_authentication_state();
            $data['ntlm'] = $this->squid->get_ntlm_state();
            $data['ntlm_available'] = $ntlm_available;

            $data['modes']['1'] = lang('web_proxy_transparent_and_no_user_authentication');
            $data['modes']['2'] = lang('web_proxy_non_transparent_with_user_authentication');
            if ($ntlm_available)
                $data['modes']['3'] = lang('web_proxy_non_transparent_with_user_authentication_and_ntlm');
            $data['modes']['4'] = lang('web_proxy_non_transaprent_Without_user_authentication');

            if ($data['transparent'] && !$data['user_authentication'])
                $data['mode'] = 1;
            else if (!$data['transparent'] && $data['user_authentication'] && !$data['ntlm'])
                $data['mode'] = 2;
            else if (!$data['transparent'] && $data['user_authentication'] && $data['ntlm'])
                $data['mode'] = 3;
            else if (!$data['transparent'] && !$data['user_authentication'])
                $data['mode'] = 4;
            else 
                $data['mode'] = 1;

        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
 
        // Load views
        //-----------

        $this->page->view_form('web_proxy/authentication', $data, lang('web_proxy_authentication'));
    }
}
