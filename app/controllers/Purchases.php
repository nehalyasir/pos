<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Purchases extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        }
        $this->load->library('form_validation');
        $this->load->model('purchases_model');
        $this->allowed_types = 'gif|jpg|png|pdf|doc|docx|xls|xlsx|zip';
    }

    public function add()
    {
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        }
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->form_validation->set_rules('date', lang('date'), 'required');

        if ($this->form_validation->run() == true) {
            $total      = 0;
            $quantity   = 'quantity';
            $product_id = 'product_id';
            $unit_cost  = 'cost';
            $i          = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $item_id   = $_POST['product_id'][$r];
                $item_qty  = $_POST['quantity'][$r];
                $item_cost = $_POST['cost'][$r];
                if ($item_id && $item_qty && $unit_cost) {
                    if (!$this->purchases_model->getProductByID($item_id)) {
                        $this->session->set_flashdata('error', $this->lang->line('product_not_found') . ' ( ' . $item_id . ' ).');
                        redirect('purchases/add');
                    }

                    $products[] = [
                        'product_id' => $item_id,
                        'cost'       => $item_cost,
                        'quantity'   => $item_qty,
                        'subtotal'   => ($item_cost * $item_qty),
                    ];

                    $total += ($item_cost * $item_qty);
                }
            }

            if (!isset($products) || empty($products)) {
                $this->form_validation->set_rules('product', lang('order_items'), 'required');
            } else {
                krsort($products);
            }

            $data = [
                'date'        => $this->input->post('date'),
                'reference'   => $this->input->post('reference'),
                'supplier_id' => $this->input->post('supplier'),
                'note'        => $this->input->post('note', true),
                'received'    => $this->input->post('received'),
                'total'       => $total,
                'created_by'  => $this->session->userdata('user_id'),
                'store_id'    => $this->session->userdata('store_id'),
            ];

            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path']   = 'uploads/';
                $config['allowed_types'] = $this->allowed_types;
                $config['max_size']      = '2000';
                $config['overwrite']     = false;
                $config['encrypt_name']  = true;
                $this->upload->initialize($config);

                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->upload->set_flashdata('error', $error);
                    redirect('purchases/add');
                }

                $data['attachment'] = $this->upload->file_name;
            }
            // $this->tec->print_arrays($data, $products);
        }

        if ($this->form_validation->run() == true && $this->purchases_model->addPurchase($data, $products)) {
            $this->session->set_userdata('remove_spo', 1);
            $this->session->set_flashdata('message', lang('purchase_added'));
            redirect('purchases');
        } else {
            $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['suppliers']  = $this->site->getAllSuppliers();
            $this->data['page_title'] = lang('add_purchase');
            $bc                       = [['link' => site_url('purchases'), 'page' => lang('purchases')], ['link' => '#', 'page' => lang('add_purchase')]];
            $meta                     = ['page_title' => lang('add_purchase'), 'bc' => $bc];
            $this->page_construct('purchases/add', $this->data, $meta);
        }
    }

    public function add_expense()
    {
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        }
        $this->load->helper('security');

        $this->form_validation->set_rules('amount', lang('amount'), 'required');
        $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');
        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $date = trim($this->input->post('date'));
            } else {
                $date = date('Y-m-d H:i:s');
            }
            $data = [
                'date'       => $date,
                'reference'  => $this->input->post('reference') ? $this->input->post('reference') : $this->site->getReference('ex'),
                'amount'     => $this->input->post('amount'),
                'created_by' => $this->session->userdata('user_id'),
                'store_id'   => $this->session->userdata('store_id'),
                'note'       => $this->input->post('note', true),
            ];

            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path']   = 'uploads/';
                $config['allowed_types'] = $this->allowed_types;
                $config['max_size']      = '2000';
                $config['overwrite']     = false;
                $config['encrypt_name']  = true;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER['HTTP_REFERER']);
                }
                $photo              = $this->upload->file_name;
                $data['attachment'] = $photo;
            }

            //$this->tec->print_arrays($data);
        } elseif ($this->input->post('add_expense')) {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER['HTTP_REFERER']);
        }

        if ($this->form_validation->run() == true && $this->purchases_model->addExpense($data)) {
            $this->session->set_flashdata('message', lang('expense_added'));
            redirect('purchases/expenses');
        } else {
            $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['page_title'] = lang('add_expense');
            $bc                       = [['link' => site_url('purchases'), 'page' => lang('purchases')], ['link' => site_url('purchases/expenses'), 'page' => lang('expenses')], ['link' => '#', 'page' => lang('add_expense')]];
            $meta                     = ['page_title' => lang('add_expense'), 'bc' => $bc];
            $this->page_construct('purchases/add_expense', $this->data, $meta);
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

        if ($this->purchases_model->deletePurchase($id)) {
            $this->session->set_flashdata('message', lang('purchase_deleted'));
            redirect('purchases');
        }
    }

    public function delete_expense($id = null)
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

        $expense = $this->purchases_model->getExpenseByID($id);
        if ($this->purchases_model->deleteExpense($id)) {
            if ($expense->attachment) {
                unlink($this->upload_path . $expense->attachment);
            }
            $this->session->set_flashdata('message', lang('expense_deleted'));
            redirect('purchases/expenses');
        }
    }

    public function edit($id = null)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->form_validation->set_rules('date', lang('date'), 'required');

        if ($this->form_validation->run() == true) {
            $total      = 0;
            $quantity   = 'quantity';
            $product_id = 'product_id';
            $unit_cost  = 'cost';
            $i          = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $item_id   = $_POST['product_id'][$r];
                $item_qty  = $_POST['quantity'][$r];
                $item_cost = $_POST['cost'][$r];
                if ($item_id && $item_qty && $unit_cost) {
                    if (!$this->site->getProductByID($item_id)) {
                        $this->session->set_flashdata('error', $this->lang->line('product_not_found') . ' ( ' . $item_id . ' ).');
                        redirect('purchases/edit/' . $id);
                    }

                    $products[] = [
                        'product_id' => $item_id,
                        'cost'       => $item_cost,
                        'quantity'   => $item_qty,
                        'subtotal'   => ($item_cost * $item_qty),
                    ];

                    $total += ($item_cost * $item_qty);
                }
            }

            if (!isset($products) || empty($products)) {
                $this->form_validation->set_rules('product', lang('order_items'), 'required');
            } else {
                krsort($products);
            }

            $data = [
                'date'        => $this->input->post('date'),
                'reference'   => $this->input->post('reference'),
                'note'        => $this->input->post('note', true),
                'supplier_id' => $this->input->post('supplier'),
                'received'    => $this->input->post('received'),
                'total'       => $total,
            ];

            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path']   = 'uploads/';
                $config['allowed_types'] = $this->allowed_types;
                $config['max_size']      = '2000';
                $config['overwrite']     = false;
                $config['encrypt_name']  = true;
                $this->upload->initialize($config);

                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->upload->set_flashdata('error', $error);
                    redirect('purchases/add');
                }

                $data['attachment'] = $this->upload->file_name;
            }
            // $this->tec->print_arrays($data, $products);
        }

        if ($this->form_validation->run() == true && $this->purchases_model->updatePurchase($id, $data, $products)) {
            $this->session->set_userdata('remove_spo', 1);
            $this->session->set_flashdata('message', lang('purchase_updated'));
            redirect('purchases');
        } else {
            $this->data['purchase'] = $this->purchases_model->getPurchaseByID($id);
            $inv_items              = $this->purchases_model->getAllPurchaseItems($id);
            $c                      = rand(100000, 9999999);
            foreach ($inv_items as $item) {
                $row       = $this->site->getProductByID($item->product_id);
                $row->qty  = $item->quantity;
                $row->cost = $item->cost;
                $ri        = $this->Settings->item_addition ? $row->id : $c;
                $pr[$ri]   = ['id' => $ri, 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row];
                $c++;
            }

            $this->data['items']      = json_encode($pr);
            $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['suppliers']  = $this->site->getAllSuppliers();
            $this->data['page_title'] = lang('edit_purchase');
            $bc                       = [['link' => site_url('purchases'), 'page' => lang('purchases')], ['link' => '#', 'page' => lang('edit_purchase')]];
            $meta                     = ['page_title' => lang('edit_purchase'), 'bc' => $bc];
            $this->page_construct('purchases/edit', $this->data, $meta);
        }
    }

    public function edit_expense($id = null)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->load->helper('security');
        if ($this->input->get('id')) {
            $id = $this->input->get('id');
        }

        $this->form_validation->set_rules('reference', lang('reference'), 'required');
        $this->form_validation->set_rules('amount', lang('amount'), 'required');
        $this->form_validation->set_rules('userfile', lang('attachment'), 'xss_clean');
        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $date = trim($this->input->post('date'));
            } else {
                $date = date('Y-m-d H:i:s');
            }
            $data = [
                'date'      => $date,
                'reference' => $this->input->post('reference'),
                'amount'    => $this->input->post('amount'),
                'note'      => $this->input->post('note', true),
            ];
            if ($_FILES['userfile']['size'] > 0) {
                $this->load->library('upload');
                $config['upload_path']   = 'uploads/';
                $config['allowed_types'] = $this->allowed_types;
                $config['max_size']      = '2000';
                $config['overwrite']     = false;
                $config['encrypt_name']  = true;
                $this->upload->initialize($config);
                if (!$this->upload->do_upload()) {
                    $error = $this->upload->display_errors();
                    $this->session->set_flashdata('error', $error);
                    redirect($_SERVER['HTTP_REFERER']);
                }
                $photo              = $this->upload->file_name;
                $data['attachment'] = $photo;
            }

            //$this->tec->print_arrays($data);
        } elseif ($this->input->post('edit_expense')) {
            $this->session->set_flashdata('error', validation_errors());
            redirect($_SERVER['HTTP_REFERER']);
        }

        if ($this->form_validation->run() == true && $this->purchases_model->updateExpense($id, $data)) {
            $this->session->set_flashdata('message', lang('expense_updated'));
            redirect('purchases/expenses');
        } else {
            $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['expense']    = $this->purchases_model->getExpenseByID($id);
            $this->data['page_title'] = lang('edit_expense');
            $bc                       = [['link' => site_url('purchases'), 'page' => lang('purchases')], ['link' => site_url('purchases/expenses'), 'page' => lang('expenses')], ['link' => '#', 'page' => lang('edit_expense')]];
            $meta                     = ['page_title' => lang('edit_expense'), 'bc' => $bc];
            $this->page_construct('purchases/edit_expense', $this->data, $meta);
        }
    }

    public function expense_note($id = null)
    {
        if (!$this->Admin) {
            if ($expense->created_by != $this->session->userdata('user_id')) {
                $this->session->set_flashdata('error', lang('access_denied'));
                redirect($_SERVER['HTTP_REFERER'] ?? 'pos');
            }
        }

        $expense                  = $this->purchases_model->getExpenseByID($id);
        $this->data['user']       = $this->site->getUser($expense->created_by);
        $this->data['expense']    = $expense;
        $this->data['page_title'] = $this->lang->line('expense_note');
        $this->load->view($this->theme . 'purchases/expense_note', $this->data);
    }

    /* ----------------------------------------------------------------- */

    public function expenses($id = null)
    {
        $this->data['error']      = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['page_title'] = lang('expenses');
        $bc                       = [['link' => site_url('purchases'), 'page' => lang('purchases')], ['link' => '#', 'page' => lang('expenses')]];
        $meta                     = ['page_title' => lang('expenses'), 'bc' => $bc];
        $this->page_construct('purchases/expenses', $this->data, $meta);
    }

    public function get_expenses($user_id = null)
    {
        $detail_link = anchor('purchases/expense_note/$1', '<i class="fa fa-file-text-o"></i> ' . lang('expense_note'), 'data-toggle="modal" data-target="#myModal2"');
        $edit_link   = anchor('purchases/edit_expense/$1', '<i class="fa fa-edit"></i> ' . lang('edit_expense'), 'data-toggle="modal" data-target="#myModal"');
        $delete_link = "<a href='#' class='po' title='<b>" . $this->lang->line('delete_expense') . "</b>' data-content=\"<p>"
            . lang('r_u_sure') . "</p><a class='btn btn-danger po-delete' href='" . site_url('purchases/delete_expense/$1') . "'>"
            . lang('i_m_sure') . "</a> <button class='btn po-close'>" . lang('no') . "</button>\"  rel='popover'><i class=\"fa fa-trash-o\"></i> "
            . lang('delete_expense') . '</a>';
        $action = '<div class="text-center"><div class="btn-group text-left">'
            . '<button type="button" class="btn btn-default btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">'
            . lang('actions') . ' <span class="caret"></span></button>
        <ul class="dropdown-menu pull-right" role="menu">
            <li>' . $detail_link . '</li>
            <li>' . $edit_link . '</li>
            <li>' . $delete_link . '</li>
        </ul>
    </div></div>';

        $this->load->library('datatables');
        if ($this->db->dbdriver == 'sqlite3') {
            $this->datatables->select($this->db->dbprefix('expenses') . '.id as id, date, reference, amount, note, (' . $this->db->dbprefix('users') . ".first_name || ' ' || " . $this->db->dbprefix('users') . '.last_name) as user, attachment', false);
        } else {
            $this->datatables->select($this->db->dbprefix('expenses') . '.id as id, date, reference, amount, note, CONCAT(' . $this->db->dbprefix('users') . ".first_name, ' ', " . $this->db->dbprefix('users') . '.last_name) as user, attachment', false);
        }
        $this->datatables->from('expenses')
            ->join('users', 'users.id=expenses.created_by', 'left')
            ->group_by('expenses.id');

        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('expenses.store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column('Actions', "<div class='text-center'><div class='btn-group'><a href='" . site_url('purchases/expense_note/$1') . "' title='" . lang('expense_note') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'><i class='fa fa-file-text-o'></i></a> <a href='" . site_url('purchases/edit_expense/$1') . "' title='" . lang('edit_expense') . "' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> <a href='" . site_url('purchases/delete_expense/$1') . "' onClick=\"return confirm('" . lang('alert_x_expense') . "')\" title='" . lang('delete_expense') . "' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", 'id');
        $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }

    public function get_purchases()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->load->library('datatables');
        $this->datatables->select('id, date, reference, total, note, attachment');
        $this->datatables->from('purchases');
        if (!$this->Admin && !$this->session->userdata('view_right')) {
            $this->datatables->where('created_by', $this->session->userdata('user_id'));
        }
        $this->datatables->where('store_id', $this->session->userdata('store_id'));
        $this->datatables->add_column('Actions', "<div class='text-center'><div class='btn-group'><a href='" . site_url('purchases/view/$1') . "' title='" . lang('view_purchase') . "' class='tip btn btn-primary btn-xs' data-toggle='ajax-modal'><i class='fa fa-file-text-o'></i></a> <a href='" . site_url('purchases/edit/$1') . "' title='" . lang('edit_purchase') . "' class='tip btn btn-warning btn-xs'><i class='fa fa-edit'></i></a> <a href='" . site_url('purchases/delete/$1') . "' onClick=\"return confirm('" . lang('alert_x_purchase') . "')\" title='" . lang('delete_purchase') . "' class='tip btn btn-danger btn-xs'><i class='fa fa-trash-o'></i></a></div></div>", 'id');

        $this->datatables->unset_column('id');
        echo $this->datatables->generate();
    }

    public function index()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = lang('purchases');
        $bc                       = [['link' => '#', 'page' => lang('purchases')]];
        $meta                     = ['page_title' => lang('purchases'), 'bc' => $bc];
        $this->page_construct('purchases/index', $this->data, $meta);
    }

    public function suggestions($id = null)
    {
        if ($id) {
            $row      = $this->site->getProductByID($id);
            $row->qty = 1;
            $pr       = ['id' => str_replace('.', '', microtime(true)), 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row];
            echo json_encode($pr);
            die();
        }
        $term = $this->tec->parse_scale_barcode($this->input->get('term', true));
        if (is_array($term)) {
            $bqty   = $term['weight'] ?? null;
            $bprice = $term['price']  ?? null;
            $term   = $term['item_code'];
            $rows   = $this->purchases_model->getProductNames($term, null, true);
        }
        if (!$rows) {
            $bqty   = null;
            $bprice = null;
            $term   = $this->input->get('term', true);
            $rows   = $this->purchases_model->getProductNames($term);
        }
        if ($rows) {
            foreach ($rows as $row) {
                $row->qty = $bqty ?: ($bprice ? $bprice / $row->price : 1);
                $pr[]     = ['id' => str_replace('.', '', microtime(true)), 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row];
            }
            echo json_encode($pr);
        } else {
            echo json_encode([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
        }
    }

    public function view($id = null)
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('pos');
        }
        $this->data['purchase']   = $this->purchases_model->getPurchaseByID($id);
        $this->data['items']      = $this->purchases_model->getAllPurchaseItems($id);
        $this->data['error']      = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['page_title'] = lang('view_purchase');
        $this->load->view($this->theme . 'purchases/view', $this->data);
    }
}
