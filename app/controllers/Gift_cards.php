<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Gift_cards extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }

        $this->load->library('form_validation');
        $this->load->model('gift_cards_model');
    }

    public function add()
    {
        $this->form_validation->set_rules('card_no', lang('card_no'), 'trim|is_unique[gift_cards.card_no]|required');
        $this->form_validation->set_rules('value', lang('value'), 'required');

        if ($this->form_validation->run() == true) {
            $data = ['card_no' => $this->input->post('card_no'),
                'value'        => $this->input->post('value'),
                'balance'      => $this->input->post('value'),
                'expiry'       => $this->input->post('expiry') ? $this->input->post('expiry') : null,
                'created_by'   => $this->session->userdata('user_id'),
            ];
        } elseif ($this->input->post('add_gift_card')) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('gift_cards/add');
        }

        if ($this->form_validation->run() == true && $this->gift_cards_model->addGiftCard($data)) {
            $this->session->set_flashdata('message', lang('gift_card_added'));
            redirect('gift_cards');
        } else {
            $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['customers']  = $this->site->getAllCustomers();
            $this->data['page_title'] = lang('new_gift_card');
            $bc                       = [['link' => site_url('gift_cards'), 'page' => lang('gift_cards')], ['link' => '#', 'page' => lang('new_gift_card')]];
            $meta                     = ['page_title' => lang('new_gift_card'), 'bc' => $bc];
            $this->page_construct('gift_cards/add', $this->data, $meta);
        }
    }

    public function delete($id = null)
    {
        if (DEMO) {
            $this->session->set_flashdata('error', lang('disabled_in_demo'));
            redirect($_SERVER['HTTP_REFERER'] ?? 'welcome');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        if ($this->gift_cards_model->deleteGiftCard($id)) {
            $this->session->set_flashdata('success_message', lang('category_deleted'));
            redirect('gift_cards', 'refresh');
        }
    }

    public function edit($id = null)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', $this->lang->line('access_denied'));
            redirect('pos');
        }
        $this->form_validation->set_rules('card_no', lang('card_no'), 'trim|required');
        $gift_card = $this->gift_cards_model->getGiftCardByID($id);
        if ($this->input->post('card_no') != $gift_card->card_no) {
            $this->form_validation->set_rules('card_no', lang('card_no'), 'is_unique[gift_cards.card_no]');
        }
        $this->form_validation->set_rules('value', lang('value'), 'required');

        if ($this->form_validation->run() == true) {
            $data = ['card_no' => $this->input->post('card_no'),
                'value'        => $this->input->post('value'),
                'balance'      => ($this->input->post('value') - $gift_card->value) + $gift_card->balance,
                'expiry'       => $this->input->post('expiry') ? $this->input->post('expiry') : null,
            ];
        } elseif ($this->input->post('edit_gift_card')) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('gift_cards/edit');
        }

        if ($this->form_validation->run() == true && $this->gift_cards_model->updateGiftCard($id, $data)) {
            $this->session->set_flashdata('message', lang('gift_card_updated'));
            redirect('gift_cards');
        } else {
            $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['gift_card']  = $gift_card;
            $this->data['customers']  = $this->site->getAllCustomers();
            $this->data['page_title'] = lang('edit_gift_card');
            $bc                       = [['link' => site_url('gift_cards'), 'page' => lang('gift_cards')], ['link' => '#', 'page' => lang('edit_gift_card')]];
            $meta                     = ['page_title' => lang('edit_gift_card'), 'bc' => $bc];
            $this->page_construct('gift_cards/edit', $this->data, $meta);
        }
    }

    public function get_gift_cards()
    {
        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select($this->db->dbprefix('gift_cards') . '.id as id, card_no, value, balance, (' . $this->db->dbprefix('users') . ".first_name || ' ' || " . $this->db->dbprefix('users') . '.last_name) as created_by, expiry', false);
        } else {
            $this->datatables->select($this->db->dbprefix('gift_cards') . '.id as id, card_no, value, balance, CONCAT(' . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . '.last_name) as created_by, expiry', false);
        }
        $this->datatables->join('users', 'users.id=gift_cards.created_by', 'left')
            ->from('gift_cards');
        $this->datatables->add_column('Actions', "<div class='text-center'><div class='btn-group'><a href='" . site_url('gift_cards/view/$1') . "' title='" . lang('view_gift_card') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'><i class='fa fa-eye'></i></a> <a href='" . site_url('gift_cards/edit/$1') . "' title='" . lang('edit_gift_card') . "' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> <a href='" . site_url('gift_cards/delete/$1') . "' onClick=\"return confirm('" . lang('alert_x_gift_card') . "')\" title='" . lang('delete_gift_card') . "' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", 'id')
        ->unset_column('id');

        echo $this->datatables->generate();
    }

    public function index()
    {
        $this->data['error']      = validation_errors() ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('gift_cards');
        $bc                       = [['link' => '#', 'page' => lang('gift_cards')]];
        $meta                     = ['page_title' => lang('gift_cards'), 'bc' => $bc];
        $this->page_construct('gift_cards/index', $this->data, $meta);
    }

    public function sell_gift_card()
    {
        $error  = null;
        $gcData = $this->input->get('gcdata');
        if (empty($gcData[0])) {
            $error = lang('value') . ' ' . lang('is_required');
        }
        if (empty($gcData[1])) {
            $error = lang('card_no') . ' ' . lang('is_required');
        }

        $data = ['card_no' => $gcData[0],
            'value'        => $gcData[1],
            'balance'      => $gcData[1],
            'expiry'       => $gcData[2] ? $gcData[2] : null,
            'created_by'   => $this->session->userdata('user_id'),
        ];

        if (!$error) {
            if ($this->gift_cards_model->addGiftCard($data)) {
                echo json_encode(['result' => 'success', 'message' => lang('gift_card_added')]);
            }
        } else {
            echo json_encode(['result' => 'failed', 'message' => $error]);
        }
    }

    public function validate($no)
    {
        if ($gc = $this->site->getGiftCardByNO($no)) {
            if ($gc->expiry) {
                if ($gc->expiry >= date('Y-m-d')) {
                    echo json_encode($gc);
                } else {
                    echo json_encode(false);
                }
            } else {
                echo json_encode($gc);
            }
        } else {
            echo json_encode(false);
        }
    }

    public function view($id = null)
    {
        $this->data['page_title'] = lang('gift_card');
        $gift_card                = $this->site->getGiftCardByID($id);
        $this->data['gift_card']  = $gift_card;
        $this->data['customer']   = $this->site->getCustomerByID($gift_card->customer_id);
        // $this->data['topups'] = $this->sales_model->getAllGCTopups($id);
        $this->load->view($this->theme . 'gift_cards/view', $this->data);
    }
}
