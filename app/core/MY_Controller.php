<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->db->dbdriver == 'mysqli') {
            $this->db->query('SET SESSION sql_mode = ""');
        }
        $this->Settings = $this->site->getSettings();
        if ($spos_language = $this->input->cookie('spos_language', true)) {
            $this->Settings->selected_language = $spos_language;
            $this->config->set_item('language', $spos_language);
            $this->lang->load('app', $spos_language);
        } else {
            $this->Settings->selected_language = $this->Settings->language;
            $this->config->set_item('language', $this->Settings->language);
            $this->lang->load('app', $this->Settings->language);
        }
        $this->Settings->pin_code = $this->Settings->pin_code ? md5($this->Settings->pin_code) : null;
        $this->theme              = $this->Settings->theme . '/views/';
        $this->data['assets']     = base_url() . 'themes/default/assets/';
        $this->data['Settings']   = $this->Settings;
        $this->loggedIn           = $this->tec->logged_in();
        $this->data['loggedIn']   = $this->loggedIn;
        $this->data['store']      = $this->site->getStoreByID($this->session->userdata('store_id'));
        $this->data['categories'] = $this->site->getAllCategories();
        $this->Admin              = $this->tec->in_group('admin') ? true : null;
        $this->data['Admin']      = $this->Admin;
        $this->m                  = strtolower($this->router->fetch_class());
        $this->v                  = strtolower($this->router->fetch_method());
        $this->data['m']          = $this->m;
        $this->data['v']          = $this->v;
    }

    public function page_construct($page, $data = [], $meta = [])
    {
        if (empty($meta)) {
            $meta['page_title'] = $data['page_title'];
        }
        $meta['message']         = $data['message'] ?? $this->session->flashdata('message');
        $meta['error']           = $data['error']   ?? $this->session->flashdata('error');
        $meta['warning']         = $data['warning'] ?? $this->session->flashdata('warning');
        $meta['ip_address']      = $this->input->ip_address();
        $meta['Admin']           = $data['Admin'];
        $meta['loggedIn']        = $data['loggedIn'];
        $meta['Settings']        = $data['Settings'];
        $meta['assets']          = $data['assets'];
        $meta['store']           = $data['store'];
        $meta['suspended_sales'] = $this->site->getUserSuspenedSales();
        $meta['qty_alert_num']   = $this->site->getQtyAlerts();
        $this->session->unset_userdata('error');
        $this->session->unset_userdata('message');
        $this->session->unset_userdata('warning');
        $this->load->view($this->theme . 'header', $meta);
        $this->load->view($this->theme . $page, $data);
        $this->load->view($this->theme . 'footer');
    }
}
