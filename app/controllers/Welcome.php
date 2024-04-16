<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Welcome extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        if (version_compare($this->Settings->version, '4.0.14', '<=')) {
            $this->load->model('db_update');
            $this->db_update->update();
        }
        $this->load->model('welcome_model');
        if ($register = $this->site->registerData($this->session->userdata('user_id'))) {
            $register_data = ['register_id' => $register->id, 'cash_in_hand' => $register->cash_in_hand, 'register_open_time' => $register->date, 'store_id' => $register->store_id];
            $this->session->set_userdata($register_data);
        }
    }

    public function disabled()
    {
        $this->data['error']      = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('disabled_in_demo');
        $bc                       = [['link' => '#', 'page' => lang('disabled_in_demo')]];
        $meta                     = ['page_title' => lang('disabled_in_demo'), 'bc' => $bc];
        $this->page_construct('disabled', $this->data, $meta);
    }

    public function index()
    {
        $this->data['error']       = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['topProducts'] = $this->welcome_model->topProducts();
        $this->data['chartData']   = $this->welcome_model->getChartData();
        $this->data['page_title']  = lang('dashboard');
        $bc                        = [['link' => '#', 'page' => lang('dashboard')]];
        $meta                      = ['page_title' => lang('dashboard'), 'bc' => $bc];
        $this->page_construct('dashboard', $this->data, $meta);
    }

    public function signing($req = null)
    {
        if (!$req) {
            header('Content-type: text/plain');
            echo file_get_contents('./files/public.pem');
            exit(0);
        }

        $privateKey = openssl_get_privatekey(file_get_contents('./files/private.pem'), 'S3cur3P@ssw0rd');
        $signature  = null;
        openssl_sign($req, $signature, $privateKey);

        if ($signature) {
            header('Content-type: text/plain');
            echo base64_encode($signature);
            exit(0);
        }

        echo '<h1>Error signing message</h1>';
        exit(1);
    }
}
