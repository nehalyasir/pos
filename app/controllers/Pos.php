<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Pos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->loggedIn) {
            redirect('login');
        }
        $this->load->helper('pos');
        $this->load->model('pos_model');
        $this->load->library('form_validation');
    }

    public function ajaxproducts($category_id = null, $return = null)
    {
        if ($this->input->get('category_id')) {
            $category_id = $this->input->get('category_id');
        } elseif (!$category_id) {
            $category_id = $this->Settings->default_category;
        }
        if ($this->input->get('per_page') == 'n') {
            $page = 0;
        } else {
            $page = $this->input->get('per_page');
        }
        if ($this->input->get('tcp') == 1) {
            $tcp = true;
        } else {
            $tcp = false;
        }

        $products = $this->pos_model->fetch_products($category_id, $this->Settings->pro_limit, $page);
        $pro      = 1;
        $prods    = '<div>';
        if ($products) {
            if ($this->Settings->bsty == 1) {
                foreach ($products as $product) {
                    $count = $product->id;
                    if ($count < 10) {
                        $count = '0' . ($count / 100) * 100;
                    }
                    if ($category_id < 10) {
                        $category_id = '0' . ($category_id / 100) * 100;
                    }
                    $prods .= '<button type="button" data-name="' . $product->name . '" id="product-' . $category_id . $count . "\" type=\"button\" value='" . $product->code . "' class=\"btn btn-name btn-default btn-flat product\">" . $product->name . '</button>';
                    $pro++;
                }
            } elseif ($this->Settings->bsty == 2) {
                foreach ($products as $product) {
                    $count = $product->id;
                    if ($count < 10) {
                        $count = '0' . ($count / 100) * 100;
                    }
                    if ($category_id < 10) {
                        $category_id = '0' . ($category_id / 100) * 100;
                    }
                    $prods .= '<button type="button" data-name="' . $product->name . '" id="product-' . $category_id . $count . "\" type=\"button\" value='" . $product->code . "' class=\"btn btn-img btn-flat product\"><img src=\"" . base_url() . 'uploads/thumbs/' . $product->image . '" alt="' . $product->name . '" style="width: 110px; height: 110px;"></button>';
                    $pro++;
                }
            } elseif ($this->Settings->bsty == 3) {
                foreach ($products as $product) {
                    $count = $product->id;
                    if ($count < 10) {
                        $count = '0' . ($count / 100) * 100;
                    }
                    if ($category_id < 10) {
                        $category_id = '0' . ($category_id / 100) * 100;
                    } 
                    // changes by Sarfaraz 10-08-23
                    $prods .= '<button type="button" data-name="' . $product->name . '" id="product-' . $category_id . $count . "\" type=\"button\" value='".str_replace(' ', '', $product->code)."' class=\"btn btn-both btn-flat product\"><span class=\"bg-img\"><img src=\"" . base_url() . 'uploads/thumbs/' . $product->image . '" alt="' . $product->name . '" style="width: 100px; height: 100px;"></span><span><span>' . $product->name . '</span></span></button>';
                    $pro++;
                }
            }
        } else {
            $prods .= '<h4 class="text-center text-info" style="margin-top:50px;">' . lang('category_is_empty') . '</h4>';
        }

        $prods .= '</div>';

        if (!$return) {
            if (!$tcp) {
                echo $prods;
            } else {
                $category_products = $this->pos_model->products_count($category_id);
                header('Content-Type: application/json');
                echo json_encode(['products' => $prods, 'tcp' => $category_products]);
            }
        } else {
            return $prods;
        }
    }

    public function close_register($user_id = null)
    {
        if (!$this->Admin) {
            $user_id = $this->session->userdata('user_id');
        }
        $this->form_validation->set_rules('total_cash', lang('total_cash'), 'trim|required|numeric');
        $this->form_validation->set_rules('total_cheques', lang('total_cheques'), 'trim|required|numeric');
        $this->form_validation->set_rules('total_cc_slips', lang('total_cc_slips'), 'trim|required|numeric');

        if ($this->form_validation->run() == true) {
            if ($this->Admin) {
                $user_register      = $user_id ? $this->pos_model->registerData($user_id) : null;
                $rid                = $user_register ? $user_register->id : $this->session->userdata('register_id');
                $user_id            = $user_register ? $user_register->user_id : $this->session->userdata('user_id');
                $register_open_time = $user_register ? $user_register->date : $this->session->userdata('register_open_time');
                $cash_in_hand       = $user_register ? $user_register->cash_in_hand : $this->session->userdata('cash_in_hand');
                $ccsales            = $this->pos_model->getRegisterCCSales($register_open_time, $user_id);
                $cashsales          = $this->pos_model->getRegisterCashSales($register_open_time, $user_id);
                $expenses           = $this->pos_model->getRegisterExpenses($register_open_time, $user_id);
                $chsales            = $this->pos_model->getRegisterChSales($register_open_time, $user_id);
                $total_cash         = ($cashsales->paid ? ($cashsales->paid + $cash_in_hand) : $cash_in_hand);
                $total_cash -= ($expenses->total ? $expenses->total : 0);
            } else {
                $rid                = $this->session->userdata('register_id');
                $user_id            = $this->session->userdata('user_id');
                $register_open_time = $this->session->userdata('register_open_time');
                $cash_in_hand       = $this->session->userdata('cash_in_hand');
                $ccsales            = $this->pos_model->getRegisterCCSales($register_open_time);
                $cashsales          = $this->pos_model->getRegisterCashSales($register_open_time);
                $expenses           = $this->pos_model->getRegisterExpenses($register_open_time);
                $chsales            = $this->pos_model->getRegisterChSales($register_open_time);
                $total_cash         = ($cashsales->paid ? ($cashsales->paid + $cash_in_hand) : $cash_in_hand);
                $total_cash -= ($expenses->total ? $expenses->total : 0);
            }

            $data = ['closed_at'           => date('Y-m-d H:i:s'),
                'total_cash'               => $total_cash,
                'total_cheques'            => $chsales->total_cheques,
                'total_cc_slips'           => $ccsales->total_cc_slips,
                'total_cash_submitted'     => $this->input->post('total_cash_submitted'),
                'total_cheques_submitted'  => $this->input->post('total_cheques_submitted'),
                'total_cc_slips_submitted' => $this->input->post('total_cc_slips_submitted'),
                'note'                     => $this->input->post('note'),
                'status'                   => 'close',
                'transfer_opened_bills'    => $this->input->post('transfer_opened_bills'),
                'closed_by'                => $this->session->userdata('user_id'),
            ];

        // $this->tec->print_arrays($data);
        } elseif ($this->input->post('close_register')) {
            $this->session->set_flashdata('error', (validation_errors() ? validation_errors() : $this->session->flashdata('error')));
            redirect('pos');
        }

        if ($this->form_validation->run() == true && $this->pos_model->closeRegister($rid, $user_id, $data)) {
            $this->session->unset_userdata('register_id');
            $this->session->unset_userdata('cash_in_hand');
            $this->session->unset_userdata('register_open_time');
            $this->session->set_flashdata('message', lang('register_closed'));
            redirect('welcome');
        } else {
            if ($this->Admin) {
                $user_register                    = $user_id ? $this->pos_model->registerData($user_id) : null;
                $register_open_time               = $user_register ? $user_register->date : $this->session->userdata('register_open_time');
                $this->data['cash_in_hand']       = $user_register ? $user_register->cash_in_hand : null;
                $this->data['register_open_time'] = $user_register ? $register_open_time : null;
            } else {
                $register_open_time               = $this->session->userdata('register_open_time');
                $this->data['cash_in_hand']       = null;
                $this->data['register_open_time'] = null;
            }
            $this->data['error']           = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
            $this->data['ccsales']         = $this->pos_model->getRegisterCCSales($register_open_time, $user_id);
            $this->data['cashsales']       = $this->pos_model->getRegisterCashSales($register_open_time, $user_id);
            $this->data['chsales']         = $this->pos_model->getRegisterChSales($register_open_time, $user_id);
            $this->data['other_sales']     = $this->pos_model->getRegisterOtherSales($register_open_time, $user_id);
            $this->data['gcsales']         = $this->pos_model->getRegisterGCSales($register_open_time, $user_id);
            $this->data['stripesales']     = $this->pos_model->getRegisterStripeSales($register_open_time, $user_id);
            $this->data['totalsales']      = $this->pos_model->getRegisterSales($register_open_time, $user_id);
            $this->data['expenses']        = $this->pos_model->getRegisterExpenses($register_open_time);
            $this->data['users']           = $this->tec->getUsers($user_id);
            $this->data['suspended_bills'] = $this->pos_model->getSuspendedsales($user_id);
            $this->data['user_id']         = $user_id;
            $this->load->view($this->theme . 'pos/close_register', $this->data);
        }
    }

    public function email_receipt($sale_id = null, $to = null)
    {
        if ($this->input->post('id')) {
            $sale_id = $this->input->post('id');
        }
        if ($this->input->post('email')) {
            $to = $this->input->post('email');
        }
        if (!$sale_id || !$to) {
            die();
        }

        $this->data['error']   = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv                   = $this->pos_model->getSaleByID($sale_id);
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows']       = $this->pos_model->getAllSaleItems($sale_id);
        $this->data['customer']   = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['inv']        = $inv;
        $this->data['sid']        = $sale_id;
        $this->data['noprint']    = null;
        $this->data['page_title'] = lang('invoice');
        $this->data['modal']      = false;
        $this->data['payments']   = $this->pos_model->getAllSalePayments($sale_id);
        $this->data['created_by'] = $this->site->getUser($inv->created_by);

        $receipt = $this->load->view($this->theme . 'pos/view', $this->data, true);
        $message = preg_replace('#\<!-- start -->(.+)\<!-- end -->#Usi', '', $receipt);
        $subject = lang('email_subject') . ' - ' . $this->Settings->site_name;

        try {
            if ($this->tec->send_email($to, $subject, $message)) {
                echo json_encode(['msg' => lang('email_success')]);
            } else {
                echo json_encode(['msg' => lang('email_failed')]);
            }
        } catch (Exception $e) {
            echo json_encode(['msg' => $e->getMessage()]);
        }
    }

    public function get_product($code = null)
    {
        
        if ($this->input->get('code')) {
            $code = $this->input->get('code');
        }
        $combo_items = false;
        if ($product = $this->pos_model->getProductByCode($code)) {
            unset($product->cost, $product->details);
            $product->qty             = 1;
            $product->comment         = '';
            $product->discount        = '0';
            $product->price           = $product->store_price > 0 ? $product->store_price : $product->price;
            $product->real_unit_price = $product->price;
            $product->unit_price      = $product->tax ? ($product->price + (($product->price * $product->tax) / 100)) : $product->price;
            if ($product->type == 'combo') {
                $combo_items = $this->pos_model->getComboItemsByPID($product->id);
            }
            echo json_encode(['id' => str_replace('.', '', microtime(true)), 'item_id' => $product->id, 'label' => $product->name . ' (' . $product->code . ')', 'row' => $product, 'combo_items' => $combo_items]);
        } else {
            echo null;
        }
    }
	
	   public function return($sid = null, $eid = null) // sarfaraz
    {

        if (!$this->Settings->multi_store) {
            $this->session->set_userdata('store_id', 1);
        }
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect($this->Settings->multi_store ? 'stores' : 'welcome');
        }
        if ($this->input->get('hold')) {
            $sid = $this->input->get('hold');
        }
        if ($this->input->get('edit')) {
            $eid = $this->input->get('edit');
        }
        if ($this->input->post('eid')) {
            $eid = $this->input->post('eid');
        }
        if ($this->input->post('did')) {
            $did = $this->input->post('did');
        } else {
            $did = null;
        }
        if ($eid && !$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect($_SERVER['HTTP_REFERER'] ?? 'pos');
        }
        if (!$this->Settings->default_customer) {
            $this->session->set_flashdata('warning', lang('please_update_settings'));
            redirect('settings');
        }
        if (!$this->session->userdata('register_id')) {
            if ($register = $this->pos_model->registerData($this->session->userdata('user_id'))) {
                $register_data = ['register_id' => $register->id, 'cash_in_hand' => $register->cash_in_hand, 'register_open_time' => $register->date];
                $this->session->set_userdata($register_data);
            } else {
                $this->session->set_flashdata('error', lang('register_not_open'));
                redirect('pos/open_register');
            }
        }
        
        $suspend = $this->input->post('suspend') ? true : false;
       
        $this->form_validation->set_rules('customer', lang('customer'), 'trim|required');
        if ($this->form_validation->run() == true) {
            $quantity  = 'quantity';
            $product   = 'product';
            $unit_cost = 'unit_cost';
            $tax_rate  = 'tax_rate';
          
            $date             = $eid ? $this->input->post('date') : date('Y-m-d H:i:s');
            $customer_id      = $this->input->post('customer_id');
            $customer_details = $this->pos_model->getCustomerByID($customer_id);
            $customer         = $customer_details->name;
            $note             = $this->tec->clear_tags($this->input->post('spos_note'));

            $total            = 0;
            $product_tax      = 0;
            $order_tax        = 0;
            $product_discount = 0;
            $order_discount   = 0;
            $percentage       = '%';
            $i                = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $item_id         = $_POST['product_id'][$r];
                $real_unit_price = $this->tec->formatDecimal($_POST['real_unit_price'][$r]);
                $item_quantity   = $_POST['quantity'][$r];
                $item_comment    = $_POST['item_comment'][$r];
                $item_discount   = $_POST['product_discount'][$r] ?? '0';

                if (isset($item_id) && isset($real_unit_price) && isset($item_quantity)) {
                    $product_details = $this->site->getProductByID($item_id);
                    if ($product_details) {
                        $product_name = $product_details->name;
                        $product_code = $product_details->code;
                        $product_cost = $product_details->cost;
                    } else {
                        $product_name = $_POST['product_name'][$r];
                        $product_code = $_POST['product_code'][$r];
                        $product_cost = 0;
                    }
                    if (!$this->Settings->overselling) {
                        if ($product_details->type == 'standard') {
                            if ($product_details->quantity < $item_quantity) {
                                $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                    lang('name') . ': ' . $product_details->name . ' | ' .
                                    lang('ordered') . ': ' . $item_quantity . ' | ' .
                                    lang('available') . ': ' . $product_details->quantity .
                                    ')');
                                redirect('pos');
                            }
                        } elseif ($product_details->type == 'combo') {
                            $combo_items = $this->pos_model->getComboItemsByPID($product->id);
                            foreach ($combo_items as $combo_item) {
                                $cpr = $this->site->getProductByID($combo_item->id);
                                if ($cpr->quantity < $item_quantity) {
                                    $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                        lang('name') . ': ' . $cpr->name . ' | ' .
                                        lang('ordered') . ': ' . $item_quantity . ' x ' . $combo_item->qty . ' = ' . $item_quantity * $combo_item->qty . ' | ' .
                                        lang('available') . ': ' . $cpr->quantity .
                                        ') ' . $product_details->name);
                                    redirect('pos');
                                }
                            }
                        }
                    }
                    $unit_price = $real_unit_price;

                    $pr_discount = 0;
                    if (isset($item_discount)) {
                        $discount = $item_discount;
                        $dpos     = strpos($discount, $percentage);
                        if ($dpos !== false) {
                            $pds         = explode('%', $discount);
                            $pr_discount = $this->tec->formatDecimal((($unit_price * (float)($pds[0])) / 100), 4);
                        } else {
                            $pr_discount = $this->tec->formatDecimal($discount);
                        }
                    }
                    $unit_price       = $this->tec->formatDecimal(($unit_price - $pr_discount), 4);
                    $item_net_price   = $unit_price;
                    $pr_item_discount = $this->tec->formatDecimal(($pr_discount * $item_quantity), 4);
                    $product_discount += $pr_item_discount;

                    $pr_item_tax = 0;
                    $item_tax    = 0;
                    $tax         = '';
                    if (isset($product_details->tax) && $product_details->tax != 0) {
                        if ($product_details && $product_details->tax_method == 1) {
                            $item_tax = $this->tec->exlusiveTax($unit_price, $product_details->tax);
                            $tax      = $product_details->tax . '%';
                        } else {
                            $item_tax = $this->tec->inclusiveTax($unit_price, $product_details->tax);
                            $tax      = $product_details->tax . '%';
                            $item_net_price -= $item_tax;
                        }

                        $pr_item_tax = $this->tec->formatDecimal(($item_tax * $item_quantity), 4);
                    }

                    $product_tax += $pr_item_tax;
                    $subtotal = $this->tec->formatDecimal((($item_net_price * $item_quantity) + $pr_item_tax), 4);

                    $products[] = [
                        'product_id'      => $item_id,
                        'quantity'        => $item_quantity,
                        'unit_price'      => $unit_price,
                        'net_unit_price'  => $item_net_price,
                        'discount'        => $item_discount,
                        'comment'         => $item_comment,
                        'item_discount'   => $pr_item_discount,
                        'tax'             => $tax,
                        'item_tax'        => $pr_item_tax,
                        'subtotal'        => $subtotal,
                        'real_unit_price' => $real_unit_price,
                        'cost'            => $product_cost,
                        'product_code'    => $product_code,
                        'product_name'    => $product_name,
                    ];


                    $Allproducts[] = [ //  sarfaraz
                        $this->site->getProductByID($item_id)
                    ];
                    $AllCategories[] = [ //  sarfaraz
                        $this->site->getAllcategoryByItemID($item_id)
                    ];


                    $total += $this->tec->formatDecimal(($item_net_price * $item_quantity), 4);
                }
            }


            if (empty($products)) {
                $this->form_validation->set_rules('product', lang('order_items'), 'required');
                // } else {
                //     krsort($products);
            }

            if ($this->input->post('order_discount')) {
                $order_discount_id = $this->input->post('order_discount');
                $opos              = strpos($order_discount_id, $percentage);
                if ($opos !== false) {
                    $ods            = explode('%', $order_discount_id);
                    $order_discount = $this->tec->formatDecimal(((($total + $product_tax) * (float)($ods[0])) / 100), 4);
                } else {
                    $order_discount = $this->tec->formatDecimal($order_discount_id);
                }
            } else {
                $order_discount_id = null;
            }
            $total_discount = $this->tec->formatDecimal(($order_discount + $product_discount), 4);

            if ($this->input->post('order_tax')) {
                $order_tax_id = $this->input->post('order_tax');
                $opos         = strpos($order_tax_id, $percentage);
                if ($opos !== false) {
                    $ots       = explode('%', $order_tax_id);
                    $order_tax = $this->tec->formatDecimal(((($total + $product_tax - $order_discount) * (float)($ots[0])) / 100), 4);
                } else {
                    $order_tax = $this->tec->formatDecimal($order_tax_id);
                }
            } else {
                $order_tax_id = null;
                $order_tax    = 0;
            }

            $total_tax   = $this->tec->formatDecimal(($product_tax), 4);
            $grand_total = $this->tec->formatDecimal(($total + $total_tax - $order_discount), 4);
            $paid        = $this->input->post('amount') ? $this->input->post('amount') : 0;
            $round_total = $this->tec->roundNumber($grand_total, $this->Settings->rounding);
            $rounding    = $this->tec->formatDecimal(($round_total - $grand_total));
            if (!$suspend && $customer_details->id == 1 && $this->tec->formatDecimal($paid) < $this->tec->formatDecimal($round_total)) {
                $this->session->set_flashdata('error', lang('select_customer_for_due'));
                redirect($_SERVER['HTTP_REFERER']);
            }
            if (!$eid) {
                $status = 'due';
                if ($this->tec->formatDecimal($round_total) <= $this->tec->formatDecimal($paid)) {
                    $status = 'paid';
                } elseif ($this->tec->formatDecimal($round_total) > $this->tec->formatDecimal($paid) && $paid > 0) {
                    $status = 'partial';
                }
            }
            
            
    
            $hld_ref =  $this->input->post('hold_ref');
            $sld_id =  $this->input->post('sale_id');
            if($hld_ref != '' && $sld_id != '' ){
                $data = [
                    'date'         => $date,
                    'customer_id'       => $customer_id,
                    'customer_name'     => $customer,
                    'total'             => $this->tec->formatDecimal($total, 4),
                    'product_discount'  => $this->tec->formatDecimal($product_discount, 4),
                    'order_discount_id' => $order_discount_id,
                    'order_discount'    => $order_discount,
                    'total_discount'    => $total_discount,
                    'product_tax'       => $this->tec->formatDecimal($product_tax, 4),
                    'order_tax_id'      => $order_tax_id,
                    'order_tax'         => $order_tax,
                    'total_tax'         => $total_tax,
                    'grand_total'       => $grand_total,
                    'total_items'       => $this->input->post('total_items'),
                    'total_quantity'    => $this->input->post('total_quantity'),
                    'rounding'          => $rounding,
                    'paid'              => $paid,
                    'status'            => $status,
                    'created_by'        => $this->session->userdata('user_id'),
                    'note'              => $note,
                    'hold_ref'          => $hld_ref,
                    'sale_return_id'    => $sld_id,
                ];
            }
            else{
                $data['error'] = "Something wrong. Please refresh page.";
                $this->session->set_flashdata($data);
                redirect('pos/return');
            }
			
            if (!$eid) {
                $data['store_id'] = $this->session->userdata('store_id');
            }

            if (!$eid && !$suspend && $paid) {
                if ($this->input->post('paying_gift_card_no')) {
                    $gc = $this->pos_model->getGiftCardByNO($this->input->post('paying_gift_card_no'));
                    if (!$gc || $gc->balance < $amount) {
                        $this->session->set_flashdata('error', lang('incorrect_gift_card'));
                        redirect('pos');
                    }
                }
                $amount  = $this->tec->formatDecimal(($paid > $grand_total ? ($paid - $this->input->post('balance_amount')) : $paid), 4);
				 $doctype=196;
                $payment = [
                    'date'        => $date,
                    'amount'      => $amount,
                    'customer_id' => $customer_id,
                    'paid_by'     => $this->input->post('paid_by'),
                    'cheque_no'   => $this->input->post('cheque_no'),
					'docType'     => $doctype,
                    'cc_no'       => $this->input->post('cc_no'),
                    'gc_no'       => $this->input->post('paying_gift_card_no'),
                    'cc_holder'   => $this->input->post('cc_holder'),
                    'cc_month'    => $this->input->post('cc_month'),
                    'cc_year'     => $this->input->post('cc_year'),
                    'cc_type'     => $this->input->post('cc_type'),
                    'cc_cvv2'     => $this->input->post('cc_cvv2'),
                    'created_by'  => $this->session->userdata('user_id'),
                    'store_id'    => $this->session->userdata('store_id'),
                    'note'        => $this->input->post('payment_note'),
                    'pos_paid'    => $this->tec->formatDecimal($this->input->post('amount'), 4),
                    'pos_balance' => $this->tec->formatDecimal($this->input->post('balance_amount'), 4),
                ];
                $customers = [ // sarfaraz
                    $this->pos_model->getCustomerByID($customer_id)
                ];
				
				if($this->input->post('paid_by') != ''){
                    $payment['paid_by'] = $this->input->post('paid_by');
                }
                else{
                    $this->session->set_flashdata('error', lang('paid_by_not'));
                    redirect('pos/return');
                }

                $data['paid'] = $amount;
            } else {
                $payment = [];
            }

            // $this->tec->print_arrays($data, $products, $payment);
        }
     
        if ($this->form_validation->run() == true && !empty($products)) {
            if ($suspend) {
                unset($data['status'], $data['rounding']);
                if ($this->pos_model->suspendSale($data, $products, $did)) {
                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', lang('sale_saved_to_opened_bill'));
                    redirect('pos');
                } else {
                    $this->session->set_flashdata('error', lang('action_failed'));
                    redirect('pos/' . $did);
                }
            } elseif ($eid) {

                unset($data['status'], $data['paid']);
                if (!$this->Admin) {
                    unset($data['date']);
                }

                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['updated_by'] = $this->session->userdata('user_id');

                if ($this->pos_model->updateSalereturn($eid, $data, $products)) {

                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', lang('sale_updated'));
                    redirect('sales');
                } else {
                    $this->session->set_flashdata('error', lang('action_failed'));
                    redirect('pos/?edit=' . $eid);
                }
            } else {
                if ($return = $this->pos_model->addreturn($data, $products, $payment, $did)) {

                    $this->session->set_userdata('rmspos', 1);
                    $msg = lang('sale_added');
                    if (!empty($return['message'])) {
                        foreach ($return['message'] as $m) {
                            $msg .= '<br>' . $m;
                        }
                    }

                    $this->session->set_flashdata('message', $msg);
                    // ayan  POST  inventory integration on 12/18/2023
                    $POSSales = $this->pos_model->getSalereturnByID($return['sale_id']);
                    $POSSales_items = $this->pos_model->getSalereturnItems($return['sale_id']);
                    $Salesstores = $this->site->getStoreByID($data['store_id']);
					$payment = $this->pos_model->getpaymentbysaleid($return['sale_id']);
                    $paid_by = [];
                    if ($payment->paid_by == 'CC') {
                        $paid_by[] = "Credit Card";
                    } elseif ($payment->paid_by == 'cheque') {
                        $paid_by[] = "Cheque";
                    } elseif ($payment->paid_by == 'cash') {
                        $paid_by[] = "Cash";
                    } elseif ($payment->paid_by == 'gift_card') {
                        $paid_by[] = "Gift Card";
                    } elseif ($payment->paid_by == 'other') {
                        $paid_by[] = "Other";
                    } elseif ($payment->paid_by == 'stripe') {
                        $paid_by[] = "Stripe";
                    }
                    elseif ($payment->paid_by == 'MC'){
                        $paid_by[] = "Meezan Card";
                    }
                    elseif ($payment->paid_by == 'KC'){
                        $paid_by[] = "Keenu Card";
                    }
                    elseif ($payment->paid_by == 'EP'){
                        $paid_by[] = "Easy pasa";
                    }
                    elseif ($payment->paid_by == 'JZ'){
                        $paid_by[] = "Jazz Cash";
                    }
                    elseif ($payment->paid_by == 'CS'){
                        $paid_by[] = "Cosmetic xpert";
                    }
                    elseif ($payment->paid_by == 'SP'){
                        $paid_by[] = "Simplurr";
                    }
                      $GodownID=$this->Settings->default_ErpGodown;  
                        $CustomerID=$this->Settings->default_ErpCustomer; 
                        // $url = $this->Settings->default_actual_link; https://cexperts.itserver.biz/admin/indexyii.php?r=orders/createPosInvoice
                        // $url = '';
                        $setting=true;
					   $CrntUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						$parsedUrl = parse_url($CrntUrl); 
                        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
                        $pathParts = explode('/', trim($path, '/'));
                        $Part = isset($pathParts[0]) ? $pathParts[0] : '';
					
					       $posinvoice = [
                        'products' => $Allproducts,
                        'Category' => $AllCategories,
                        'customers' => $customers,
						'Sales'   => $POSSales,
						'payment' => $payment,
                        'paid_by' =>  $paid_by, 
                        'Salesstores' => $Salesstores,
                        'POSSales_items'   => $POSSales_items,
                        'GodownID'   => $GodownID,
                        'CustomerID'   => $CustomerID,
						'setting'   => $setting,
						 'SiteName' => $Part,
						 'SaleReturn_id' => $POSSales->sale_return_id,
						 'AddByCron' => 'true'
                    ];
                    foreach ($posinvoice as $key => $value) {
                        if ($value === NULL) {
                            $posinvoice[$key] = 'null';
                        }
                    }
						
					$doctype=196;
                     $Syncposinvoices = [
                        'LOGID' => 0,
                        'InvoiceNumber' => $POSSales->hold_ref,
                        'sales_returnid' => $POSSales->id,
                        'SyncDate' => date('Y-m-d H:i:s'),
                        'Status'   => 'pending',
                         'PosSales' => json_encode($posinvoice['Sales']),
                         'POSSales_items' => json_encode($posinvoice['POSSales_items']), 
                         'Customers' =>  json_encode($posinvoice['customers']),
                        'AllProducts'   =>json_encode($posinvoice['products']),
                        'docType'   => $doctype,
                        'orderFlagId'   => 97412,
                         'Salesstores' => json_encode($posinvoice['Salesstores']),
                         'GodownID' => $posinvoice['GodownID'],
                         'CustomerID' => $posinvoice['CustomerID'],
                         'SiteName' => serialize($posinvoice['SiteName']),
                         'Payments' => json_encode($posinvoice['payment']),
						 'paid_by' => serialize($posinvoice['paid_by']),
						 'Sales_id' => $posinvoice['SaleReturn_id'],
						 'active' => 1
						 
                    ];
                    $this->pos_model->Syncposinvoices($Syncposinvoices);
                    // $queryString = http_build_query($posinvoice);
                    // $ch = curl_init();
                    // curl_setopt($ch, CURLOPT_URL, $url);
                    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    // curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
                    // curl_setopt($ch, CURLOPT_POST, TRUE);
                    // $result = curl_exec($ch);
                    // // var_dump($result);
                    // // curl_close($ch);
                    // if (curl_errno($ch)) {
                    //     // Handle cURL error here
                    //     $responseMessage = 'cURL Error: ' . curl_error($ch);
                    //     $syncStatus = 'Error';
                    // } else {
                    //     // Check HTTP status code
                    //     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    //     if ($result) {
                    //         $responseMessage = 'Error: ' . $result;
                    //         $syncStatus = 'Error';
                    //     } else {
                    //         $responseMessage = 'Request was successful.';
                    //         $syncStatus = 'Sync';
                    //     }
                    // }
                    // $fecthResponce = [
                    //     'SyncInvoiceid'   => $this->pos_model->getPOSreturnID(),
                    //     'SyncStatus'      => $syncStatus,
                    //     'SyncDescription' => $responseMessage,
                    // ];

                    // $this->pos_model->inserPOSResponse($fecthResponce);
                    // curl_close($ch);
                    // END POST  inventory integration

                    $dt=196;
                    $redirect_to = $this->Settings->after_sale_page ? 'pos/return' : 'pos/view_return/' . $return['sale_id'].'/'.$dt;
                    if ($this->Settings->auto_print) {
                        if (!$this->Settings->remote_printing) {
                            $this->print_receipt($return['sale_id'], true);
                        } elseif ($this->Settings->remote_printing == 2) {
                            $redirect_to .= '?print=' . $return['sale_id'];
                        }
                    }
                    
                    redirect($redirect_to);
                } else {
                    $this->session->set_flashdata('error', lang('action_failed'));
                    redirect('pos/return');
                }
            }
        } else {
            if (isset($sid) && !empty($sid)) {
                $suspended_sale = $this->pos_model->getSuspendedSaleByID($sid);
                $inv_items      = $this->pos_model->getSuspendedSaleItems($sid);
                krsort($inv_items);
                $c = rand(100000, 9999999);
                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        $row       = json_decode('{}');
                        $row->id   = 0;
                        $row->code = $item->product_code;
                        $row->name = $item->product_name;
                        $row->tax  = 0;
                    }
                    $row->price           = $item->net_unit_price + ($item->item_discount / $item->quantity);
                    $row->unit_price      = $item->unit_price     + ($item->item_discount / $item->quantity)     + ($item->item_tax / $item->quantity);
                    $row->real_unit_price = $item->real_unit_price;
                    $row->discount        = $item->discount;
                    $row->qty             = $item->quantity;
                    $row->comment         = $item->comment;
                    $row->ordered         = $item->quantity;
                    $combo_items          = false;
                    $ri                   = $this->Settings->item_addition ? $row->id : $c;
                    $pr[$ri]              = ['id' => $c, 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row, 'combo_items' => $combo_items];
                    $c++;
                }
                $this->data['items']        = json_encode($pr);
                $this->data['sid']          = $sid;
                $this->data['suspend_sale'] = $suspended_sale;
                $this->data['message']      = lang('suspended_sale_loaded');
            }

            if (isset($eid) && !empty($eid)) {
                $sale      = $this->pos_model->getSaleByID($eid);
                $inv_items = $this->pos_model->getAllSaleItems($eid);
                krsort($inv_items);
                $c = rand(100000, 9999999);
                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        $row = json_decode('{}');
                    }
                    $row->price           = $item->net_unit_price;
                    $row->unit_price      = $item->unit_price;
                    $row->real_unit_price = $item->real_unit_price;
                    $row->discount        = $item->discount;
                    $row->qty             = $item->quantity;
                    $row->comment         = $item->comment;
                    $combo_items          = false;
                    $row->quantity += $item->quantity;
                    if ($row->type == 'combo') {
                        $combo_items = $this->pos_model->getComboItemsByPID($row->id);
                        foreach ($combo_items as $combo_item) {
                            $combo_item->quantity += ($combo_item->qty * $item->quantity);
                        }
                    }
                    $ri      = $this->Settings->item_addition ? $row->id : $c;
                    $pr[$ri] = ['id' => $c, 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row, 'combo_items' => $combo_items];
                    $c++;
                }
                $this->data['items']   = json_encode($pr);
                $this->data['eid']     = $eid;
                $this->data['sale']    = $sale;
                $this->data['message'] = lang('sale_loaded');
            }
            $this->data['error']           = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['reference_note']  = isset($sid) && !empty($sid) ? $suspended_sale->hold_ref : (isset($eid) && !empty($eid) ? $sale->hold_ref : null);
            $this->data['sid']             = isset($sid) && !empty($sid) ? $sid : 0;
            $this->data['eid']             = isset($eid) && !empty($eid) ? $eid : 0;
            $this->data['customers']       = $this->site->getAllCustomers();
            $this->data['AllInvoices']       = $this->site->getAllInvoices(); // sarfaraz 

            $this->data['tcp']             = $this->pos_model->products_count($this->Settings->default_category);
            // $this->data['products']        = $this->ajaxproducts($this->Settings->default_category, 1);
            $this->data['categories']      = $this->site->getAllCategories();
            $this->data['message']         = $this->session->flashdata('message');
            $this->data['suspended_sales'] = $this->site->getUserSuspenedSales();

            $this->data['printer'] = $this->site->getPrinterByID($this->Settings->printer);
            $printers              = [];
            if (!empty($order_printers = json_decode($this->Settings->order_printers))) {
                foreach ($order_printers as $printer_id) {
                    $printers[] = $this->site->getPrinterByID($printer_id);
                }
            }
            $this->data['order_printers'] = $printers;

            if ($saleid = $this->input->get('print', true)) {
                if ($inv = $this->pos_model->getSalereturnByID($saleid)) {
                    if ($this->session->userdata('store_id') != $inv->store_id) {
                        $this->session->set_flashdata('error', lang('access_denied'));
                        redirect('pos/return');
                    }
                    $this->tec->view_rights($inv->created_by, false, 'pos');
                    $this->load->helper('text');
                    $this->data['rows']       = $this->pos_model->getAllSaleItems($saleid);
                    $this->data['customer']   = $this->pos_model->getCustomerByID($inv->customer_id);
                    $this->data['store']      = $this->site->getStoreByID($inv->store_id);
                    $this->data['inv']        = $inv;
                    $this->data['print']      = $saleid;
                    $this->data['payments']   = $this->pos_model->getAllSalePayments($saleid);
                    $this->data['created_by'] = $this->site->getUser($inv->created_by);
                }
            }

            $this->data['page_title'] = lang('pos');
            $bc                       = [['link' => '#', 'page' => lang('pos')]];
            $meta                     = ['page_title' => lang('pos return'), 'bc' => $bc];

            $this->load->view($this->theme . 'pos/return', $this->data, $meta);
        }
    }


    public function GetReturnInv() // Sarfaraz for Get Return Invoice  on Sunday 10 december 2023
    {
       
        $rows = $this->site->getReturnInvoices($this->input->get('sale_id', true));
     

        if ($rows) {
            echo  json_encode($rows);
        } else {
            echo json_encode(lang('no_match_found'));
        }
    }

    public function SaleRetuensuggestions() // sarfaraz
    {
        $sale_id = $this->input->get('sale_id', true);
        $term = $this->tec->parse_scale_barcode($this->input->get('term', true));
        if (is_array($term)) {
            $bqty   = $term['weight'] ?? null;
            $bprice = $term['price']  ?? null;
            $term   = $term['item_code'];
            $rows   = $this->site->getProductReturn($term, $sale_id, null, true);
        }
        if (!$rows) {
            $bqty   = null;
            $bprice = null;
            $term   = $this->input->get('term', true);
            $rows   = $this->site->getProductReturn($term, $sale_id);
        }


        if ($rows) {
            foreach ($rows as $row) {
                unset($row->cost, $row->details);
                // $row->qty             = $bqty ?: ($bprice ? $bprice / $row->price : 1);
                $row->qty             = $row->quantity;
                $row->comment         = '';
                $row->discount        = '0';
                $row->price           = $row->store_price > 0 ? $row->store_price : $row->price;
                $row->real_unit_price = $row->price;
                $row->unit_price      = $row->tax ? ($row->price + (($row->price * $row->tax) / 100)) : $row->price;
                $combo_items          = false;
                if ($row->type == 'combo') {
                    $combo_items = $this->pos_model->getComboItemsByPID($row->id);
                }
                $pr[] = ['id' => str_replace('.', '', microtime(true)), 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row, 'combo_items' => $combo_items];
            }
            echo json_encode($pr);
        } else {
            echo json_encode([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
        }
    }

     public function view_return($sale_id = null,$customerid = 0, $noprint = null)
    {
        
        if ($this->input->get('id')) {
            $sale_id = $this->input->get('id');
        }
        $this->data['message'] = $this->session->flashdata('message');
        $inv = $this->pos_model->getSalereturnByID($sale_id);
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows']       = $this->pos_model->getSalereturnItems($sale_id);
        $this->data['customer']   = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['store']      = $this->site->getStoreByID($inv->store_id);
        $this->data['inv']        = $inv;
        $this->data['sid']        = $sale_id;
        $this->data['noprint']    = $noprint;
        $this->data['modal']      = $noprint ? true : false;
        $this->data['payments']      = $customerid == 0 ? $this->pos_model->getAllSalePayments($sale_id) : $this->pos_model->getAllSaleReturnPayments($sale_id,$customerid);
        $this->data['created_by'] = $this->site->getUser($inv->created_by);
        $this->data['printer']    = $this->site->getPrinterByID($this->Settings->printer);
        $this->data['store']      = $this->site->getStoreByID($inv->store_id);
        $this->data['page_title'] = lang('invoice');
        $this->load->view($this->theme . 'pos/' . ($this->Settings->remote_printing != 1 && $this->Settings->print_img ? 'eview' : 'view_return'), $this->data);
    }
	
    public function index($sid = null, $eid = null)
    {
        if (!$this->Settings->multi_store) {
            $this->session->set_userdata('store_id', 1);
        }
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect($this->Settings->multi_store ? 'stores' : 'welcome');
        }
        if ($this->input->get('hold')) {
            $sid = $this->input->get('hold');
        }
        if ($this->input->get('edit')) {
            $eid = $this->input->get('edit');
        }
        if ($this->input->post('eid')) {
            $eid = $this->input->post('eid');
        }
        if ($this->input->post('did')) {
            $did = $this->input->post('did');
        } else {
            $did = null;
        }
        if ($eid && !$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect($_SERVER['HTTP_REFERER'] ?? 'pos');
        }
        if (!$this->Settings->default_customer) {
            $this->session->set_flashdata('warning', lang('please_update_settings'));
            redirect('settings');
        }
        if (!$this->session->userdata('register_id')) {
            if ($register = $this->pos_model->registerData($this->session->userdata('user_id'))) {
                $register_data = ['register_id' => $register->id, 'cash_in_hand' => $register->cash_in_hand, 'register_open_time' => $register->date];
                $this->session->set_userdata($register_data);
            } else {
                $this->session->set_flashdata('error', lang('register_not_open'));
                redirect('pos/open_register');
            }
        }

        $suspend = $this->input->post('suspend') ? true : false;

        $this->form_validation->set_rules('customer', lang('customer'), 'trim|required');

        if ($this->form_validation->run() == true) {
            $quantity  = 'quantity';
            $product   = 'product';
            $unit_cost = 'unit_cost';
            $tax_rate  = 'tax_rate';
            $date             = $eid ? $this->input->post('date') : date('Y-m-d H:i:s');
            $customer_id      = $this->input->post('customer_id');
            $customer_details = $this->pos_model->getCustomerByID($customer_id);
            $customer         = $customer_details->name;
            $note             = $this->tec->clear_tags($this->input->post('spos_note'));
            $defaultTypeName=$this->Settings->default_holdref;
            $String = substr($defaultTypeName, 0,4);
            $maxNumber = $this->site->GetmaxDocumentTextid($String); // sarfaraz for auto Inv Number
            if ($maxNumber->maxNumber == "" || $maxNumber->maxNumber == "0" || $maxNumber->maxNumber == 0  ) {
            $extractedString = preg_replace('/[^0-9]/', '', $defaultTypeName);
            $numericValue = (int)$extractedString;
            $newValue = str_pad($numericValue, strlen($extractedString), '0', STR_PAD_LEFT);
            $RefmaxOrderNumber = $String . $newValue;
            }
            else{
            $extractedString = preg_replace('/[^0-9]/', '', $maxNumber->maxNumber);
            $numericValue = (int)$extractedString; 
            $numericValue += 1; 
            $newValue = str_pad($numericValue, 4, '0', STR_PAD_LEFT); //rafay 

            $RefmaxOrderNumber = $String . $newValue;
            }

            $total            = 0;
            $product_tax      = 0;
            $order_tax        = 0;
            $product_discount = 0;
            $order_discount   = 0;
            $percentage       = '%';
            $i                = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
            for ($r = 0; $r < $i; $r++) {
                $item_id         = $_POST['product_id'][$r];
                $real_unit_price = $this->tec->formatDecimal($_POST['real_unit_price'][$r]);
                $item_quantity   = $_POST['quantity'][$r];
                $item_comment    = $_POST['item_comment'][$r];
                $item_discount   = $_POST['product_discount'][$r] ?? '0';

                if (isset($item_id) && isset($real_unit_price) && isset($item_quantity)) {
                    $product_details = $this->site->getProductByID($item_id);
                    if ($product_details) {
                        $product_name = $product_details->name;
                        $product_code = $product_details->code;
                        $product_cost = $product_details->cost;
                    } else {
                        $product_name = $_POST['product_name'][$r];
                        $product_code = $_POST['product_code'][$r];
                        $product_cost = 0;
                    }
                    if (!$this->Settings->overselling) {
                        if ($product_details->type == 'standard') {
                            if ($product_details->quantity < $item_quantity) {
                                $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                    lang('name') . ': ' . $product_details->name . ' | ' .
                                    lang('ordered') . ': ' . $item_quantity . ' | ' .
                                    lang('available') . ': ' . $product_details->quantity .
                                    ')');
                                redirect('pos');
                            }
                        } elseif ($product_details->type == 'combo') {
                            $combo_items = $this->pos_model->getComboItemsByPID($product->id);
                            foreach ($combo_items as $combo_item) {
                                $cpr = $this->site->getProductByID($combo_item->id);
                                if ($cpr->quantity < $item_quantity) {
                                    $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                        lang('name') . ': ' . $cpr->name . ' | ' .
                                        lang('ordered') . ': ' . $item_quantity . ' x ' . $combo_item->qty . ' = ' . $item_quantity * $combo_item->qty . ' | ' .
                                        lang('available') . ': ' . $cpr->quantity .
                                        ') ' . $product_details->name);
                                    redirect('pos');
                                }
                            }
                        }
                    }
                    $unit_price = $real_unit_price;

                    $pr_discount = 0;
                    if (isset($item_discount)) {
                        $discount = $item_discount;
                        $dpos     = strpos($discount, $percentage);
                        if ($dpos !== false) {
                            $pds         = explode('%', $discount);
                            $pr_discount = $this->tec->formatDecimal((($unit_price * (float)($pds[0])) / 100), 4);
                        } else {
                            $pr_discount = $this->tec->formatDecimal($discount);
                        }
                    }
                    $unit_price       = $this->tec->formatDecimal(($unit_price - $pr_discount), 4);
                    $item_net_price   = $unit_price;
                    $pr_item_discount = $this->tec->formatDecimal(($pr_discount * $item_quantity), 4);
                    $product_discount += $pr_item_discount;

                    $pr_item_tax = 0;
                    $item_tax    = 0;
                    $tax         = '';
                    if (isset($product_details->tax) && $product_details->tax != 0) {
                        if ($product_details && $product_details->tax_method == 1) {
                            $item_tax = $this->tec->exlusiveTax($unit_price, $product_details->tax);
                            $tax      = $product_details->tax . '%';
                        } else {
                            $item_tax = $this->tec->inclusiveTax($unit_price, $product_details->tax);
                            $tax      = $product_details->tax . '%';
                            $item_net_price -= $item_tax;
                        }

                        $pr_item_tax = $this->tec->formatDecimal(($item_tax * $item_quantity), 4);
                    }

                    $product_tax += $pr_item_tax;
                    $subtotal = $this->tec->formatDecimal((($item_net_price * $item_quantity) + $pr_item_tax), 4);

                    $products[] = [
                        'product_id'      => $item_id,
                        'quantity'        => $item_quantity,
                        'unit_price'      => $unit_price,
                        'net_unit_price'  => $item_net_price,
                        'discount'        => $item_discount,
                        'comment'         => $item_comment,
                        'item_discount'   => $pr_item_discount,
                        'tax'             => $tax,
                        'item_tax'        => $pr_item_tax,
                        'subtotal'        => $subtotal,
                        'real_unit_price' => $real_unit_price,
                        'cost'            => $product_cost,
                        'product_code'    => $product_code,
                        'product_name'    => $product_name,
                    ];
					
					$Allproducts[] = [ //  sarfaraz
						$this->site->getProductByID($item_id)
					] ;
                    $AllCategories[] = [ //  sarfaraz
                        $this->site->getAllcategoryByItemID($item_id)
                    ] ; 
                    
                    // $AllCategories[] = [ //  sarfaraz
                    //     $this->site->getCategoryByID($this->site->getProductByID($item_id)->category_id)
                    // ] ; 
                    // $AllCategories= $this->site->getCategoryByID($POScategory_id);

                     // if ($this->site->getProductByID($item_id)) {
                     //     $POScategory_id = 
                     //        'category_id'.$this->site->getProductByID($item_id)->category_id
                     //    ;
                     // }  elseif (!$POScategory_id) {
                     //    $POScategory_id[] = [
                     //        $this->Settings->default_category
                     //    ];
                     //    }
                     //    // foreach($POScategory_id as $POScategory){
                     //        var_dump($POScategory_id);
                        // }
                    // $AllCategories= $this->site->getCategoryByID($POScategory_id);

                    $total += $this->tec->formatDecimal(($item_net_price * $item_quantity), 4);
                }
            }

           
            if (empty($products)) {
                $this->form_validation->set_rules('product', lang('order_items'), 'required');
                // } else {
            //     krsort($products);
            }

            if ($this->input->post('order_discount')) {
                $order_discount_id = $this->input->post('order_discount');
                $opos              = strpos($order_discount_id, $percentage);
                if ($opos !== false) {
                    $ods            = explode('%', $order_discount_id);
                    $order_discount = $this->tec->formatDecimal(((($total + $product_tax) * (float)($ods[0])) / 100), 4);
                } else {
                    $order_discount = $this->tec->formatDecimal($order_discount_id);
                }
            } else {
                $order_discount_id = null;
            }
            $total_discount = $this->tec->formatDecimal(($order_discount + $product_discount), 4);

            if ($this->input->post('order_tax')) {
                $order_tax_id = $this->input->post('order_tax');
                $opos         = strpos($order_tax_id, $percentage);
                if ($opos !== false) {
                    $ots       = explode('%', $order_tax_id);
                    $order_tax = $this->tec->formatDecimal(((($total + $product_tax - $order_discount) * (float)($ots[0])) / 100), 4);
                } else {
                    $order_tax = $this->tec->formatDecimal($order_tax_id);
                }
            } else {
                $order_tax_id = null;
                $order_tax    = 0;
            }
            $total_tax   = $this->tec->formatDecimal(($product_tax + $order_tax), 4);
            $grand_total = $this->tec->formatDecimal(($total - $order_discount), 4);
            $paid        = $this->input->post('amount') ? $this->input->post('amount') : 0;
            $round_total = $this->tec->roundNumber($grand_total, $this->Settings->rounding);
            $rounding    = $this->tec->formatDecimal(($round_total - $grand_total));
            if (!$suspend && $customer_details->id == 1 && $this->tec->formatDecimal($paid) < $this->tec->formatDecimal($round_total)) {
                $this->session->set_flashdata('error', lang('select_customer_for_due'));
                redirect($_SERVER['HTTP_REFERER']);
            }
            if (!$eid) {
                $status = 'due';
                if ($this->tec->formatDecimal($round_total) <= $this->tec->formatDecimal($paid)) {
                    $status = 'paid';
                } elseif ($this->tec->formatDecimal($round_total) > $this->tec->formatDecimal($paid) && $paid > 0) {
                    $status = 'partial';
                }
            }
        if (!$eid) {
            $data = [
				'date'         => $date,
                'customer_id'       => $customer_id,
                'customer_name'     => $customer,
                'total'             => $this->tec->formatDecimal($total, 4),
                'product_discount'  => $this->tec->formatDecimal($product_discount, 4),
                'order_discount_id' => $order_discount_id,
                'order_discount'    => $order_discount,
                'total_discount'    => $total_discount,
                'product_tax'       => $this->tec->formatDecimal($product_tax, 4),
                'order_tax_id'      => $order_tax_id,
                'order_tax'         => $order_tax,
                'total_tax'         => $total_tax,
                'grand_total'       => $grand_total,
                'total_items'       => $this->input->post('total_items'),
                'total_quantity'    => $this->input->post('total_quantity'),
                'rounding'          => $rounding,
                'paid'              => $paid,
                'status'            => $status,
                'created_by'        => $this->session->userdata('user_id'),
                'note'              => $note,
                'hold_ref'          => $RefmaxOrderNumber,
            ];
        }
        else {
             $data = [
                'date'         => $date,
                'customer_id'       => $customer_id,
                'customer_name'     => $customer,
                'total'             => $this->tec->formatDecimal($total, 4),
                'product_discount'  => $this->tec->formatDecimal($product_discount, 4),
                'order_discount_id' => $order_discount_id,
                'order_discount'    => $order_discount,
                'total_discount'    => $total_discount,
                'product_tax'       => $this->tec->formatDecimal($product_tax, 4),
                'order_tax_id'      => $order_tax_id,
                'order_tax'         => $order_tax,
                'total_tax'         => $total_tax,
                'grand_total'       => $grand_total,
                'total_items'       => $this->input->post('total_items'),
                'total_quantity'    => $this->input->post('total_quantity'),
                'rounding'          => $rounding,
                'paid'              => $paid,
                'status'            => $status,
                'created_by'        => $this->session->userdata('user_id'),
                'note'              => $note
            ];
            }

            if (!$eid) {
                $data['store_id'] = $this->session->userdata('store_id');
            }
            if (!$eid && !$suspend && $paid) {
                if ($this->input->post('paying_gift_card_no')) {
                    $gc = $this->pos_model->getGiftCardByNO($this->input->post('paying_gift_card_no'));
                    if (!$gc || $gc->balance < $amount) {
                        $this->session->set_flashdata('error', lang('incorrect_gift_card'));
                        redirect('pos');
                    }
                }
                $amount  = $this->tec->formatDecimal(($paid > $grand_total ? ($paid - $this->input->post('balance_amount')) : $paid), 4);
				  $doctype=89;
                $payment = [
                    'date'        => $date,
                    'amount'      => $amount,
                    'customer_id' => $customer_id,
                   // 'paid_by'     => $this->input->post('paid_by'),
                    'cheque_no'   => $this->input->post('cheque_no'),
					'docType'     => $doctype,
                    'cc_no'       => $this->input->post('cc_no'),
                    'gc_no'       => $this->input->post('paying_gift_card_no'),
                    'cc_holder'   => $this->input->post('cc_holder'),
                    'cc_month'    => $this->input->post('cc_month'),
                    'cc_year'     => $this->input->post('cc_year'),
                    'cc_type'     => $this->input->post('cc_type'),
                    'cc_cvv2'     => $this->input->post('cc_cvv2'),
                    'created_by'  => $this->session->userdata('user_id'),
                    'store_id'    => $this->session->userdata('store_id'),
                    'note'        => $this->input->post('payment_note'),
                    'pos_paid'    => $this->tec->formatDecimal($this->input->post('amount'), 4),
                    'pos_balance' => $this->tec->formatDecimal($this->input->post('balance_amount'), 4),
                ];
				
        		$customers =[ // sarfaraz
					$this->pos_model->getCustomerByID($customer_id) 
				]; 
				
				if($this->input->post('paid_by') != ''){
                    $payment['paid_by'] = $this->input->post('paid_by');
                }
                else{
                    $this->session->set_flashdata('error', lang('paid_by_not'));
                    redirect('pos');
                }
               

                $data['paid'] = $amount;
            } else {
                $payment = [];
            }

            // $this->tec->print_arrays($data, $products, $payment);
        }

        if ($this->form_validation->run() == true && !empty($products)) {
            if ($suspend) {
                unset($data['status'], $data['rounding']);
                if ($this->pos_model->suspendSale($data, $products, $did)) {
                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', lang('sale_saved_to_opened_bill'));
                    redirect('pos');
                } else {
                    $this->session->set_flashdata('error', lang('action_failed'));
                    redirect('pos/' . $did);
                }
            } elseif ($eid) {
                unset($data['status'], $data['paid']);
                if (!$this->Admin) {
                    unset($data['date']);
                }
                $checkpostingInv=$this->pos_model->checkpostingInv($eid,89);

                if ($checkpostingInv) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $data['updated_by'] = $this->session->userdata('user_id');
                if ($this->pos_model->updateSale($eid, $data, $products)) {
                    $this->session->set_userdata('rmspos', 1);
                    $this->session->set_flashdata('message', lang('sale_updated'));

                    $POSSales = $this->pos_model->getSaleByID($eid);
                    $payment = $this->pos_model->getpaymentbysaleid($eid);
                    $paid_by = [];
                    if ($payment->paid_by == 'CC') {
                        $paid_by[] = "Credit Card";
                    } elseif ($payment->paid_by == 'cheque') {
                        $paid_by[] = "Cheque";
                    } elseif ($payment->paid_by == 'cash') {
                        $paid_by[] = "Cash";
                    } elseif ($payment->paid_by == 'gift_card') {
                        $paid_by[] = "Gift Card";
                    } elseif ($payment->paid_by == 'other') {
                        $paid_by[] = "Other";
                    } elseif ($payment->paid_by == 'stripe') {
                        $paid_by[] = "Stripe";
                    }
                    elseif ($payment->paid_by == 'MC'){
                        $paid_by[] = "Meezan Card";
                    }
                    elseif ($payment->paid_by == 'KC'){
                        $paid_by[] = "Keenu Card";
                    }
                    elseif ($payment->paid_by == 'EP'){
                        $paid_by[] = "Easy pasa";
                    }
                    elseif ($payment->paid_by == 'JZ'){
                        $paid_by[] = "Jazz Cash";
                    }
                    elseif ($payment->paid_by == 'CS'){
                        $paid_by[] = "Cosmetic xpert";
                    }
                    elseif ($payment->paid_by == 'SP'){
                        $paid_by[] = "Simplurr";
                    }
                      

                    $POSSales_items = $this->pos_model->getAllSaleItems($eid);
                    $Salesstores = $this->site->getStoreByID($this->session->userdata('store_id'));
                        $doctype=89;
                        $CrntUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                        $parsedUrl = parse_url($CrntUrl); 
                        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
                        $pathParts = explode('/', trim($path, '/'));
                        $Part = isset($pathParts[0]) ? $pathParts[0] : '';

                    $posinvoice = [
                        'products' => $Allproducts,
                        'Category' => $AllCategories,
                        'customers' => $customers,
                        'Sales'   => $POSSales,
                        'payment' => $payment,
                        'paid_by' =>  $paid_by, 
                        'Salesstores' => $Salesstores,
                        'POSSales_items'   => $POSSales_items,
                    ];
                     
                     $Syncposinvoices = [
                         'PosSales' => json_encode($posinvoice['Sales']),
                         'POSSales_items' => json_encode($posinvoice['POSSales_items']), 
                         'Customers' =>  json_encode($posinvoice['customers']),
                        'AllProducts'   =>json_encode($posinvoice['products']),
                         'Salesstores' => json_encode($posinvoice['Salesstores']),
                         'Payments' => json_encode($posinvoice['payment']),
                         'paid_by' => serialize($posinvoice['paid_by']),
                         'active' => 1
                    ];
                    
                $updateInv=$this->pos_model->UpdateSyncposinvoices($eid,$Syncposinvoices,$doctype);

                    redirect('sales');
                } else {
                    $this->session->set_flashdata('error', lang('action_failed'));
                    redirect('pos/?edit=' . $eid);
                }
            } 
             else {
                $this->session->set_flashdata('error','This Invoice ERP in posted, it means that the update cannot be made.' );
                               redirect('pos/?edit=' . $eid);
             }
             }
               else {
                  if ($sale = $this->pos_model->addSale($data, $products, $payment, $did)) { //20-10-2023 8:48PM                 
				//if ($sale = $this->pos_model->addSale($data, $products, $did)) {
                    $this->session->set_userdata('rmspos', 1);
                    $msg = lang('sale_added');
                    if (!empty($sale['message'])) {
                        foreach ($sale['message'] as $m) {
                            $msg .= '<br>' . $m;
                        }
                    }
                    $this->session->set_flashdata('message', $msg);
					// Sarfaraz  POST  inventory integration on 2023-09-07
					$POSSales = $this->pos_model->getSaleByID($sale['sale_id']);
					$payment = $this->pos_model->getpaymentbysaleid($sale['sale_id']);
                    $paid_by = [];
                    if ($payment->paid_by == 'CC') {
                        $paid_by[] = "Credit Card";
                    } elseif ($payment->paid_by == 'cheque') {
                        $paid_by[] = "Cheque";
                    } elseif ($payment->paid_by == 'cash') {
                        $paid_by[] = "Cash";
                    } elseif ($payment->paid_by == 'gift_card') {
                        $paid_by[] = "Gift Card";
                    } elseif ($payment->paid_by == 'other') {
                        $paid_by[] = "Other";
                    } elseif ($payment->paid_by == 'stripe') {
                        $paid_by[] = "Stripe";
                    }
                    elseif ($payment->paid_by == 'MC'){
                        $paid_by[] = "Meezan Card";
                    }
                    elseif ($payment->paid_by == 'KC'){
                        $paid_by[] = "Keenu Card";
                    }
                    elseif ($payment->paid_by == 'EP'){
                        $paid_by[] = "Easy pasa";
                    }
                    elseif ($payment->paid_by == 'JZ'){
                        $paid_by[] = "Jazz Cash";
                    }
                    elseif ($payment->paid_by == 'CS'){
                        $paid_by[] = "Cosmetic xpert";
                    }
                    elseif ($payment->paid_by == 'SP'){
                        $paid_by[] = "Simplurr";
                    }
                    $POSSales_items = $this->pos_model->getAllSaleItems($sale['sale_id']);
					$Salesstores = $this->site->getStoreByID($data['store_id']);
                 
                        $GodownID=$this->Settings->default_ErpGodown;  
                        $CustomerID=$this->Settings->default_ErpCustomer; 
                        // $url = $this->Settings->default_actual_link;
                        // $url = '';
                        $setting=true;
					  	$CrntUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
						$parsedUrl = parse_url($CrntUrl); 
                        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
                        $pathParts = explode('/', trim($path, '/'));
                        $Part = isset($pathParts[0]) ? $pathParts[0] : '';

                    $posinvoice = [
                        'products' => $Allproducts,
                        'Category' => $AllCategories,
                        'customers' => $customers,
						'Sales'   => $POSSales,
						'payment' => $payment,
                        'paid_by' =>  $paid_by, 
                        'Salesstores' => $Salesstores,
                        'POSSales_items'   => $POSSales_items,
                        'GodownID'   => $GodownID,
                        'CustomerID'   => $CustomerID,
						'setting'   => $setting,
						 'SiteName' => $Part,
						 'AddByCron' => 'true'
                    ];

                    $custmerextra_data = [
                        "id" => $POSSales->customer_id,
                        "name" => $POSSales->customer_name,
                        "cf1" => "",
                        "cf2" => "",
                        "phone" => "",
                        "email" => "customer@tecdiary.com",
                        "store_id" => null,

                    ];

                    foreach ($posinvoice as $key => $value) {
                        if ($value === NULL) {
                            $posinvoice[$key] = 'null';
                        }
                    }

					  $doctype=89;
                     $Syncposinvoices = [
                        'LOGID' => 0,
                        'InvoiceNumber' => $POSSales->hold_ref,
                        'sales_returnid' => $POSSales->id,
                        'SyncDate' => date('Y-m-d H:i:s'),
                        'Status'   => 'pending',
                         'PosSales' => json_encode($posinvoice['Sales']),
                         'POSSales_items' => json_encode($posinvoice['POSSales_items']), 
                         //'Customers' =>  json_encode($posinvoice['customers']),
                        'AllProducts'   =>json_encode($posinvoice['products']),
                        'docType'   => $doctype,
                        'orderFlagId'   => 97412,
                         'Salesstores' => json_encode($posinvoice['Salesstores']),
                         'GodownID' => $posinvoice['GodownID'],
                         'CustomerID' => $posinvoice['CustomerID'],
                         'SiteName' => serialize($posinvoice['SiteName']),
                         'Payments' => json_encode($posinvoice['payment']),
						 'paid_by' => serialize($posinvoice['paid_by']),
						 'active' => 1
						 
                    ];
                    
                    if($posinvoice['customers'] == NULL || $posinvoice['customers'] == 'null' || $posinvoice['customers'] == 0 || empty($posinvoice['customers']) ){
                        $Syncposinvoices['Customers'] = json_encode($custmerextra_data);
                    }
                    else{
                        $Syncposinvoices['Customers'] =  json_encode($posinvoice['customers']);
                    }
                    


					
                    $this->pos_model->Syncposinvoices($Syncposinvoices);
                    // $queryString = http_build_query($posinvoice);
                    // $ch = curl_init();
                    // curl_setopt($ch, CURLOPT_URL, $url);
                    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    // curl_setopt($ch, CURLOPT_POSTFIELDS, $queryString);
                    // curl_setopt($ch, CURLOPT_POST, TRUE);
                    // $result = curl_exec($ch);
                    // echo '<pre>';
                    // print_r($result);
                    // echo '</pre>';
                    // curl_close($ch);
					//   if (curl_errno($ch)) {
                    //     // Handle cURL error here
                    //     $responseMessage = 'cURL Error: ' . curl_error($ch);
                    //     $syncStatus = 'Error';
                    // } else {
                    //     // Check HTTP status code
                    //     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    //     if($result){
                    //         $responseMessage = 'Error: ' . $result;
                    //         $syncStatus = 'Error';
                    //     }
                    //     else {
                    //         $responseMessage = 'Request was successful.';
                    //         $syncStatus = 'Sync';
                    //     }
                    // }
                    // $fecthResponce = [
                    // 'SyncInvoiceid'   => $this->pos_model->getPOSinvID(),
                    // 'SyncStatus'      => $syncStatus,
                    // 'SyncDescription' => $responseMessage,
                    // ];

                    // $this->pos_model->inserPOSResponse($fecthResponce);
                    // curl_close($ch);
                    // END POST  inventory integration
                    $redirect_to = $this->Settings->after_sale_page ? 'pos' : 'pos/view/' . $sale['sale_id'];
                    if ($this->Settings->auto_print) {
                        if (!$this->Settings->remote_printing) {
                            $this->print_receipt($sale['sale_id'], true);
                        } elseif ($this->Settings->remote_printing == 2) {
                            $redirect_to .= '?print=' . $sale['sale_id'];
                        }
                    }
                    redirect($redirect_to);
                } else {
                    $this->session->set_flashdata('error', lang('action_failed'));
                    redirect('pos');
                }
            }
        } else {
            if (isset($sid) && !empty($sid)) {
                $suspended_sale = $this->pos_model->getSuspendedSaleByID($sid);
                $inv_items      = $this->pos_model->getSuspendedSaleItems($sid);
                krsort($inv_items);
                $c = rand(100000, 9999999);
                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        $row       = json_decode('{}');
                        $row->id   = 0;
                        $row->code = $item->product_code;
                        $row->name = $item->product_name;
                        $row->tax  = 0;
                    }
                    $row->price           = $item->net_unit_price + ($item->item_discount / $item->quantity);
                    $row->unit_price      = $item->unit_price     + ($item->item_discount / $item->quantity)     + ($item->item_tax / $item->quantity);
                    $row->real_unit_price = $item->real_unit_price;
                    $row->discount        = $item->discount;
                    $row->qty             = $item->quantity;
                    $row->comment         = $item->comment;
                    $row->ordered         = $item->quantity;
                    $combo_items          = false;
                    $ri                   = $this->Settings->item_addition ? $row->id : $c;
                    $pr[$ri]              = ['id' => $c, 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row, 'combo_items' => $combo_items];
                    $c++;
                }
                $this->data['items']        = json_encode($pr);
                $this->data['sid']          = $sid;
                $this->data['suspend_sale'] = $suspended_sale;
                $this->data['message']      = lang('suspended_sale_loaded');
            }

            if (isset($eid) && !empty($eid)) {
                $sale      = $this->pos_model->getSaleByID($eid);
                $inv_items = $this->pos_model->getAllSaleItems($eid);
                krsort($inv_items);
                $c = rand(100000, 9999999);
                foreach ($inv_items as $item) {
                    $row = $this->site->getProductByID($item->product_id);
                    if (!$row) {
                        $row = json_decode('{}');
                    }
                    $row->price           = $item->net_unit_price;
                    $row->unit_price      = $item->unit_price;
                    $row->real_unit_price = $item->real_unit_price;
                    $row->discount        = $item->discount;
                    $row->qty             = $item->quantity;
                    $row->comment         = $item->comment;
                    $combo_items          = false;
                    $row->quantity += $item->quantity;
                    if ($row->type == 'combo') {
                        $combo_items = $this->pos_model->getComboItemsByPID($row->id);
                        foreach ($combo_items as $combo_item) {
                            $combo_item->quantity += ($combo_item->qty * $item->quantity);
                        }
                    }
                    $ri      = $this->Settings->item_addition ? $row->id : $c;
                    $pr[$ri] = ['id' => $c, 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row, 'combo_items' => $combo_items];
                    $c++;
                }
                $this->data['items']   = json_encode($pr);
                $this->data['eid']     = $eid;
                $this->data['sale']    = $sale;
                $this->data['message'] = lang('sale_loaded');
            }
            $this->data['error']           = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
            $this->data['reference_note']  = isset($sid) && !empty($sid) ? $suspended_sale->hold_ref : (isset($eid) && !empty($eid) ? $sale->hold_ref : null);
            $this->data['sid']             = isset($sid) && !empty($sid) ? $sid : 0;
            $this->data['eid']             = isset($eid) && !empty($eid) ? $eid : 0;
            $this->data['customers']       = $this->site->getAllCustomers();
            $this->data['tcp']             = $this->pos_model->products_count($this->Settings->default_category);
            $this->data['products']        = $this->ajaxproducts($this->Settings->default_category, 1);
            $this->data['categories']      = $this->site->getAllCategories();
            $this->data['message']         = $this->session->flashdata('message');
            $this->data['suspended_sales'] = $this->site->getUserSuspenedSales();

            $this->data['printer'] = $this->site->getPrinterByID($this->Settings->printer);
            $printers              = [];
            if (!empty($order_printers = json_decode($this->Settings->order_printers))) {
                foreach ($order_printers as $printer_id) {
                    $printers[] = $this->site->getPrinterByID($printer_id);
                }
            }
            $this->data['order_printers'] = $printers;

            if ($saleid = $this->input->get('print', true)) {
                if ($inv = $this->pos_model->getSaleByID($saleid)) {
                    if ($this->session->userdata('store_id') != $inv->store_id) {
                        $this->session->set_flashdata('error', lang('access_denied'));
                        redirect('pos');
                    }
                    $this->tec->view_rights($inv->created_by, false, 'pos');
                    $this->load->helper('text');
                    $this->data['rows']       = $this->pos_model->getAllSaleItems($saleid);
                    $this->data['customer']   = $this->pos_model->getCustomerByID($inv->customer_id);
                    $this->data['store']      = $this->site->getStoreByID($inv->store_id);
                    $this->data['inv']        = $inv;
                    $this->data['print']      = $saleid;
                    $this->data['payments']   = $this->pos_model->getAllSalePayments($saleid);
                    $this->data['created_by'] = $this->site->getUser($inv->created_by);
                }
            }

            $this->data['page_title'] = lang('pos');
            $bc                       = [['link' => '#', 'page' => lang('pos')]];
            $meta                     = ['page_title' => lang('pos'), 'bc' => $bc];
            $this->load->view($this->theme . 'pos/index', $this->data, $meta);
        }
    }

    public function language($lang = false)
    {
        if ($this->input->get('lang')) {
            $lang = $this->input->get('lang');
        }
        //$this->load->helper('cookie');
        $folder        = 'app/language/';
        $languagefiles = scandir($folder);
        if (in_array($lang, $languagefiles)) {
            $cookie = [
                'name'   => 'language',
                'value'  => $lang,
                'expire' => '31536000',
                'prefix' => 'spos_',
                'secure' => false,
            ];

            $this->input->set_cookie($cookie);
        }
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function open_drawer()
    {
        $printer = $this->site->getPrinterByID($this->Settings->printer);
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->open_drawer();
    }

    public function open_register()
    {
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        }
        $this->form_validation->set_rules('cash_in_hand', lang('cash_in_hand'), 'trim|required|numeric');

        if ($this->form_validation->run() == true) {
            $data = ['date'    => date('Y-m-d H:i:s'),
                'cash_in_hand' => $this->input->post('cash_in_hand'),
                'user_id'      => $this->session->userdata('user_id'),
                'store_id'     => $this->session->userdata('store_id'),
                'status'       => 'open',
            ];
        }
        if ($this->form_validation->run() == true && $this->pos_model->openRegister($data)) {
            $this->session->set_flashdata('message', lang('welcome_to_pos'));
            redirect('pos');
        } else {
            $this->data['error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');

            $bc   = [['link' => base_url(), 'page' => lang('home')], ['link' => '#', 'page' => lang('open_register')]];
            $meta = ['page_title' => lang('open_register'), 'bc' => $bc];
            $this->page_construct('pos/open_register', $this->data, $meta);
        }
    }

    public function p($bo = 'order')
    {
		$date             = date('Y-m-d H:i:s');
        $customer_id      = $this->input->post('customer_id');
        $customer_details = $this->pos_model->getCustomerByID($customer_id);
        $customer         = $customer_details->name;
        $note             = $this->tec->clear_tags($this->input->post('spos_note'));

        $total            = 0;
        $product_tax      = 0;
        $order_tax        = 0;
        $product_discount = 0;
        $order_discount   = 0;
        $percentage       = '%';
        $i                = isset($_POST['product_id']) ? sizeof($_POST['product_id']) : 0;
        for ($r = 0; $r < $i; $r++) {
            $item_id         = $_POST['product_id'][$r];
            $real_unit_price = $this->tec->formatDecimal($_POST['real_unit_price'][$r]);
            $item_quantity   = $_POST['quantity'][$r];
            $item_comment    = $_POST['item_comment'][$r];
            $item_ordered    = $_POST['item_was_ordered'][$r];
            $item_discount   = $_POST['product_discount'][$r] ?? '0';

            if (isset($item_id) && isset($real_unit_price) && isset($item_quantity)) {
                $product_details = $this->site->getProductByID($item_id);
                if ($product_details) {
                    $product_name = $product_details->name;
                    $product_code = $product_details->code;
                    $product_cost = $product_details->cost;
                } else {
                    $product_name = $_POST['product_name'][$r];
                    $product_code = $_POST['product_code'][$r];
                    $product_cost = 0;
                }
                if (!$this->Settings->overselling) {
                    if ($product_details->type == 'standard') {
                        if ($product_details->quantity < $item_quantity) {
                            $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                lang('name') . ': ' . $product_details->name . ' | ' .
                                lang('ordered') . ': ' . $item_quantity . ' | ' .
                                lang('available') . ': ' . $product_details->quantity .
                                ')');
                            redirect('pos');
                        }
                    } elseif ($product_details->type == 'combo') {
                        $combo_items = $this->pos_model->getComboItemsByPID($product->id);
                        foreach ($combo_items as $combo_item) {
                            $cpr = $this->site->getProductByID($combo_item->id);
                            if ($cpr->quantity < $item_quantity) {
                                $this->session->set_flashdata('error', lang('quantity_low') . ' (' .
                                    lang('name') . ': ' . $cpr->name . ' | ' .
                                    lang('ordered') . ': ' . $item_quantity . ' x ' . $combo_item->qty . ' = ' . $item_quantity * $combo_item->qty . ' | ' .
                                    lang('available') . ': ' . $cpr->quantity .
                                    ') ' . $product_details->name);
                                redirect('pos');
                            }
                        }
                    }
                }
                $unit_price = $real_unit_price;

                $pr_discount = 0;
                if (isset($item_discount)) {
                    $discount = $item_discount;
                    $dpos     = strpos($discount, $percentage);
                    if ($dpos !== false) {
                        $pds         = explode('%', $discount);
                        $pr_discount = $this->tec->formatDecimal((($unit_price * (float)($pds[0])) / 100), 4);
                    } else {
                        $pr_discount = $this->tec->formatDecimal($discount);
                    }
                }
                $unit_price       = $this->tec->formatDecimal(($unit_price - $pr_discount), 4);
                $item_net_price   = $unit_price;
                $pr_item_discount = $this->tec->formatDecimal(($pr_discount * $item_quantity), 4);
                $product_discount += $pr_item_discount;

                $pr_item_tax = 0;
                $item_tax    = 0;
                $tax         = '';
                if (isset($product_details->tax) && $product_details->tax != 0) {
                    if ($product_details && $product_details->tax_method == 1) {
                        $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / 100), 4);
                        $tax      = $product_details->tax . '%';
                    } else {
                        $item_tax = $this->tec->formatDecimal(((($unit_price) * $product_details->tax) / (100 + $product_details->tax)), 4);
                        $tax      = $product_details->tax . '%';
                        $item_net_price -= $item_tax;
                    }

                    $pr_item_tax = $this->tec->formatDecimal(($item_tax * $item_quantity), 4);
                }

                $product_tax += $pr_item_tax;
                $subtotal = (($item_net_price * $item_quantity) + $pr_item_tax);

                $products[] = (object) [
                    'product_id'      => $item_id,
                    'quantity'        => $item_quantity,
                    'unit_price'      => $unit_price,
                    'net_unit_price'  => $item_net_price,
                    'discount'        => $item_discount,
                    'comment'         => $item_comment,
                    'item_discount'   => $pr_item_discount,
                    'tax'             => $tax,
                    'item_tax'        => $pr_item_tax,
                    'subtotal'        => $subtotal,
                    'real_unit_price' => $real_unit_price,
                    'cost'            => $product_cost,
                    'product_code'    => $product_code,
                    'product_name'    => $product_name,
                    'ordered'         => $item_ordered,
                ];

                $total += $item_net_price * $item_quantity;
            }
        }
        if (empty($products)) {
            $this->form_validation->set_rules('product', lang('order_items'), 'required');
        } else {
            krsort($products);
        }

        if ($this->input->post('order_discount')) {
            $order_discount_id = $this->input->post('order_discount');
            $opos              = strpos($order_discount_id, $percentage);
            if ($opos !== false) {
                $ods            = explode('%', $order_discount_id);
                $order_discount = $this->tec->formatDecimal(((($total + $product_tax) * (float)($ods[0])) / 100), 4);
            } else {
                $order_discount = $this->tec->formatDecimal($order_discount_id);
            }
        } else {
            $order_discount_id = null;
        }
        $total_discount = $this->tec->formatDecimal(($order_discount + $product_discount), 4);

        if ($this->input->post('order_tax')) {
            $order_tax_id = $this->input->post('order_tax');
            $opos         = strpos($order_tax_id, $percentage);
            if ($opos !== false) {
                $ots       = explode('%', $order_tax_id);
                $order_tax = $this->tec->formatDecimal(((($total + $product_tax - $order_discount) * (float)($ots[0])) / 100), 4);
            } else {
                $order_tax = $this->tec->formatDecimal($order_tax_id);
            }
        } else {
            $order_tax_id = null;
            $order_tax    = 0;
        }

        $total_tax   = $this->tec->formatDecimal(($product_tax + $order_tax), 4);
        $grand_total = $this->tec->formatDecimal(($this->tec->formatDecimal($total) + $total_tax - $order_discount), 4);
        $paid        = 0;
        $round_total = $this->tec->roundNumber($grand_total, $this->Settings->rounding);
        $rounding    = $this->tec->formatDecimal(($round_total - $grand_total));

        $data = (object) ['date' => $date,
            'customer_id'        => $customer_id,
            'customer_name'      => $customer,
            'total'              => $this->tec->formatDecimal($total),
            'product_discount'   => $this->tec->formatDecimal($product_discount, 4),
            'order_discount_id'  => $order_discount_id,
            'order_discount'     => $order_discount,
            'total_discount'     => $total_discount,
            'product_tax'        => $this->tec->formatDecimal($product_tax, 4),
            'order_tax_id'       => $order_tax_id,
            'order_tax'          => $order_tax,
            'total_tax'          => $total_tax,
            'grand_total'        => $grand_total,
            'total_items'        => $this->input->post('total_items'),
            'total_quantity'     => $this->input->post('total_quantity'),
            'rounding'           => $rounding,
            'paid'               => $paid,
            'created_by'         => $this->session->userdata('user_id'),
            'note'               => $note,
            'hold_ref'           => $this->input->post('hold_ref'),
        ];

        // $this->tec->print_arrays($data, $products);
        $store      = $this->site->getStoreByID($this->session->userdata('store_id'));
        $created_by = $this->site->getUser($this->session->userdata('user_id'));

        if ($bo == 'bill') {
            $printer = $this->site->getPrinterByID($this->Settings->printer);
            $this->load->library('escpos');
            $this->escpos->load($printer);
            $this->escpos->print_receipt($store, $data, $products, false, $created_by, false, true);
        } else {
            $order_printers = json_decode($this->Settings->order_printers);
            $this->load->library('escpos');
            foreach ($order_printers as $printer_id) {
                $printer = $this->site->getPrinterByID($printer_id);
                $this->escpos->load($printer);
                $this->escpos->print_order($store, $data, $products, $created_by);
            }
        }
    }

    public function print_receipt($id, $open_drawer = false)
    {
        $sale       = $this->pos_model->getSaleByID($id);
        $items      = $this->pos_model->getAllSaleItems($id);
        $payments   = $this->pos_model->getAllSalePayments($id);
        $store      = $this->site->getStoreByID($sale->store_id);
        $created_by = $this->site->getUser($sale->created_by);
        $printer    = $this->site->getPrinterByID($this->Settings->printer);
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->print_receipt($store, $sale, $items, $payments, $created_by, $open_drawer);
    }

    public function print_register($re = null)
    {
        if ($this->session->userdata('register_id')) {
            $register    = $this->pos_model->registerData();
            $ccsales     = $this->pos_model->getRegisterCCSales();
            $cashsales   = $this->pos_model->getRegisterCashSales();
            $chsales     = $this->pos_model->getRegisterChSales();
            $other_sales = $this->pos_model->getRegisterOtherSales();
            $gcsales     = $this->pos_model->getRegisterGCSales();
            $stripesales = $this->pos_model->getRegisterStripeSales();
            $totalsales  = $this->pos_model->getRegisterSales();
            $expenses    = $this->pos_model->getRegisterExpenses();
            $user        = $this->site->getUser();

            $total_cash = $cashsales->paid ? ($cashsales->paid + $register->cash_in_hand) : $register->cash_in_hand;
            $total_cash -= ($expenses->total ? $expenses->total : 0);
            $info = [
                (object) ['label' => lang('opened_at'), 'value' => $this->tec->hrld($register->date)],
                (object) ['label' => lang('cash_in_hand'), 'value' => $register->cash_in_hand],
                (object) ['label' => lang('user'), 'value' => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'],
                (object) ['label' => lang('printed_at'),  'value' => $this->tec->hrld(date('Y-m-d H:i:s'))],
            ];

            $reg_totals = [
                (object) ['label' => lang('cash_sale'), 'value' => $this->tec->formatMoney($cashsales->paid ? $cashsales->paid : '0.00') . ' (' . $this->tec->formatMoney($cashsales->total ? $cashsales->total : '0.00') . ')'],
                (object) ['label' => lang('ch_sale'), 'value' => $this->tec->formatMoney($chsales->paid ? $chsales->paid : '0.00') . ' (' . $this->tec->formatMoney($chsales->total ? $chsales->total : '0.00') . ')'],
                (object) ['label' => lang('gc_sale'),  'value' => $this->tec->formatMoney($gcsales->paid ? $gcsales->paid : '0.00') . ' (' . $this->tec->formatMoney($gcsales->total ? $gcsales->total : '0.00') . ')'],
                (object) ['label' => lang('cc_sale'),  'value' => $this->tec->formatMoney($ccsales->paid ? $ccsales->paid : '0.00') . ' (' . $this->tec->formatMoney($ccsales->total ? $ccsales->total : '0.00') . ')'],
                (object) ['label' => lang('stripe'),  'value' => $this->tec->formatMoney($stripesales->paid ? $stripesales->paid : '0.00') . ' (' . $this->tec->formatMoney($stripesales->total ? $stripesales->total : '0.00') . ')'],
                (object) ['label' => lang('other_sale'),  'value' => $this->tec->formatMoney($other_sales->paid ? $other_sales->paid : '0.00') . ' (' . $this->tec->formatMoney($other_sales->total ? $other_sales->total : '0.00') . ')'],
                (object) ['label' => 'line',  'value' => ''],
                (object) ['label' => lang('total_sales'),  'value' => $this->tec->formatMoney($totalsales->paid ? $totalsales->paid : '0.00') . ' (' . $this->tec->formatMoney($totalsales->total ? $totalsales->total : '0.00') . ')'],
                (object) ['label' => lang('cash_in_hand'),  'value' => $this->tec->formatMoney($register->cash_in_hand)],
                (object) ['label' => lang('expenses'),  'value' => $this->tec->formatMoney($expenses->total ? $expenses->total : '0.00')],
                (object) ['label' => 'line',  'value' => ''],
                (object) ['label' => lang('total_cash'),  'value' => $this->tec->formatMoney($total_cash)],
            ];

            $data = (object) [
                'printer' => $this->Settings->local_printers ? '' : json_encode($printer),
                'logo'    => !empty($store->logo) ? base_url('uploads/' . $store->logo) : '',
                'heading' => lang('register_details'),
                'info'    => $info,
                'totals'  => $reg_totals,
            ];

            // $this->tec->print_arrays($data);
            if ($re == 1) {
                return $data;
            } elseif ($re == 2) {
                echo json_encode($data);
            } else {
                $printer = $this->site->getPrinterByID($this->Settings->printer);
                $this->load->library('escpos');
                $this->escpos->load($printer);
                $this->escpos->print_data($data);
                echo json_encode(true);
            }
        } else {
            echo json_encode(false);
        }
    }

    public function promotions()
    {
        $this->load->view($this->theme . 'promotions', $this->data);
    }

    public function receipt_img()
    {
        $data     = $this->input->post('img', true);
        $filename = date('Y-m-d-H-i-s-') . uniqid() . '.png';
        $cd       = !empty($this->input->post('cd')) ? true : false;
        $imgData  = str_replace(' ', '+', $data);
        $imgData  = base64_decode($imgData);
        file_put_contents('files/receipts/' . $filename, $imgData);
        $printer = $this->site->getPrinterByID($this->Settings->printer);
        $this->load->library('escpos');
        $this->escpos->load($printer);
        $this->escpos->print_img($filename, $cd);
        echo 'Printed Image  files/receipts/' . $filename;
        exit;
    }

    public function register_details()
    {
        $register_open_time        = $this->session->userdata('register_open_time');
        $this->data['error']       = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['ccsales']     = $this->pos_model->getRegisterCCSales($register_open_time);
        $this->data['kcsales']     = $this->pos_model->getRegisterKCSales($register_open_time); // sarfaraz
        $this->data['EPsales']     = $this->pos_model->getRegisterEPSales($register_open_time); // sarfaraz
        $this->data['JZsales']     = $this->pos_model->getRegisterJZSales($register_open_time); // sarfaraz
        $this->data['CSsales']     = $this->pos_model->getRegisterCSSales($register_open_time); // sarfaraz
        $this->data['SPsales']     = $this->pos_model->getRegisterSPSales($register_open_time); // sarfaraz
        $this->data['mcsales']     = $this->pos_model->getRegisterMCSales($register_open_time); // sarfaraz
        $this->data['cashsales']   = $this->pos_model->getRegisterCashSales($register_open_time);
        $this->data['chsales']     = $this->pos_model->getRegisterChSales($register_open_time);
        $this->data['other_sales'] = $this->pos_model->getRegisterOtherSales($register_open_time);
        $this->data['gcsales']     = $this->pos_model->getRegisterGCSales($register_open_time);
        $this->data['stripesales'] = $this->pos_model->getRegisterStripeSales($register_open_time);
        $this->data['totalsales']  = $this->pos_model->getRegisterSales($register_open_time);
        $this->data['totalsales_return']  = $this->pos_model->getRegisterSales_return($register_open_time);
        $this->data['expenses']    = $this->pos_model->getRegisterExpenses($register_open_time);
        $this->data['ccreturn']     = $this->pos_model->getRegisterCCSalesReturn($register_open_time);//ayan
        $this->data['kcreturn']     = $this->pos_model->getRegisterKCSalesReturn($register_open_time); // ayan
        $this->data['EPreturn']     = $this->pos_model->getRegisterEPSalesReturn($register_open_time); // ayan
        $this->data['JZreturn']     = $this->pos_model->getRegisterJZSalesReturn($register_open_time); // ayan
        $this->data['CSreturn']     = $this->pos_model->getRegisterCSSalesReturn($register_open_time); // ayan
        $this->data['SPreturn']     = $this->pos_model->getRegisterSPSalesReturn($register_open_time); // ayan
        $this->data['mcreturn']     = $this->pos_model->getRegisterMCSalesReturn($register_open_time); // ayan
        $this->data['cashreturn']   = $this->pos_model->getRegisterCashSalesReturn($register_open_time);//ayan
        $this->data['chreturn']     = $this->pos_model->getRegisterChSalesReturn($register_open_time);  //ayan
        $this->data['other_return'] = $this->pos_model->getRegisterOtherSalesReturn($register_open_time); //ayan
        $this->data['gcreturn']     = $this->pos_model->getRegisterGCSalesReturn($register_open_time); //ayan
        $this->data['stripereturn'] = $this->pos_model->getRegisterStripeSalesReturn($register_open_time); //ayan
        $this->data['totalreturn']  = $this->pos_model->getRegisterSalesReturn($register_open_time); //ayan
        $this->load->view($this->theme . 'pos/register_details', $this->data);
    }

    public function registers()
    {
        $this->data['error']     = (validation_errors()) ? validation_errors() : $this->session->flashdata('error');
        $this->data['registers'] = $this->pos_model->getOpenRegisters();
        $bc                      = [['link' => base_url(), 'page' => lang('home')], ['link' => site_url('pos'), 'page' => lang('pos')], ['link' => '#', 'page' => lang('open_registers')]];
        $meta                    = ['page_title' => lang('open_registers'), 'bc' => $bc];
        $this->page_construct('pos/registers', $this->data, $meta);
    }

    public function shortcuts()
    {
        $this->load->view($this->theme . 'pos/shortcuts', $this->data);
    }

    public function stripe_balance()
    {
        if (!$this->Owner) {
            return false;
        }
        $this->load->model('stripe_payments');
        return $this->stripe_payments->get_balance();
    }

    public function suggestions()
    {
        $term = $this->tec->parse_scale_barcode($this->input->get('term', true));
        if (is_array($term)) {
            $bqty   = $term['weight'] ?? null;
            $bprice = $term['price']  ?? null;
            $term   = $term['item_code'];
            $rows   = $this->pos_model->getProductNames($term, null, true);
        }
        if (!$rows) {
            $bqty   = null;
            $bprice = null;
            $term   = $this->input->get('term', true);
            $rows   = $this->pos_model->getProductNames($term);
        }
        if ($rows) {
            foreach ($rows as $row) {
                unset($row->cost, $row->details);
                $row->qty             = $bqty ?: ($bprice ? $bprice / $row->price : 1);
                $row->comment         = '';
                $row->discount        = '0';
                $row->price           = $row->store_price > 0 ? $row->store_price : $row->price;
                $row->real_unit_price = $row->price;
                $row->unit_price      = $row->tax ? ($row->price + (($row->price * $row->tax) / 100)) : $row->price;
                $combo_items          = false;
                if ($row->type == 'combo') {
                    $combo_items = $this->pos_model->getComboItemsByPID($row->id);
                }
                $pr[] = ['id' => str_replace('.', '', microtime(true)), 'item_id' => $row->id, 'label' => $row->name . ' (' . $row->code . ')', 'row' => $row, 'combo_items' => $combo_items];
            }
            echo json_encode($pr);
        } else {
            echo json_encode([['id' => 0, 'label' => lang('no_match_found'), 'value' => $term]]);
        }
    }

    public function today_sale()
    {
        if (!$this->Admin) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect($_SERVER['HTTP_REFERER']);
        }

        $this->data['error']       = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['ccsales']     = $this->pos_model->getTodayCCSales();
        $this->data['kcsales']     = $this->pos_model->getTodayKCSales(); // sarfaraz
        $this->data['mcsales']     = $this->pos_model->getTodayMCSales(); // sarfaraz
        $this->data['EPsales']     = $this->pos_model->getTodayEPSales(); // sarfaraz
        $this->data['JZsales']     = $this->pos_model->getTodayJZSales(); // sarfaraz
        $this->data['CSsales']     = $this->pos_model->getTodayCSSales(); // sarfaraz
        $this->data['SPsales']     = $this->pos_model->getTodaySPSales(); // sarfaraz
        $this->data['cashsales']   = $this->pos_model->getTodayCashSales();
        $this->data['chsales']     = $this->pos_model->getTodayChSales();
        $this->data['other_sales'] = $this->pos_model->getTodayOtherSales();
        $this->data['gcsales']     = $this->pos_model->getTodayGCSales();
        $this->data['stripesales'] = $this->pos_model->getTodayStripeSales();
        $this->data['totalsales']  = $this->pos_model->getTodaySales();
        $this->data['totalsales_return']  = $this->pos_model->totalsales_return(); //sarfaraz
        // $this->data['expenses'] = $this->pos_model->getTodayExpenses();
        $this->load->view($this->theme . 'pos/today_sale', $this->data);
    }

    public function validate_gift_card($no)
    {
        if ($gc = $this->pos_model->getGiftCardByNO(urldecode($no))) {
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

    public function view($sale_id = null, $noprint = null)
    {
        if ($this->input->get('id')) {
            $sale_id = $this->input->get('id');
        }
        $this->data['error']   = (validation_errors() ? validation_errors() : $this->session->flashdata('error'));
        $this->data['message'] = $this->session->flashdata('message');
        $inv                   = $this->pos_model->getSaleByID($sale_id);
        if (!$this->session->userdata('store_id')) {
            $this->session->set_flashdata('warning', lang('please_select_store'));
            redirect('stores');
        } elseif ($this->session->userdata('store_id') != $inv->store_id) {
            $this->session->set_flashdata('error', lang('access_denied'));
            redirect('welcome');
        }
        $this->tec->view_rights($inv->created_by);
        $this->load->helper('text');
        $this->data['rows']       = $this->pos_model->getAllSaleItems($sale_id);
        $this->data['customer']   = $this->pos_model->getCustomerByID($inv->customer_id);
        $this->data['store']      = $this->site->getStoreByID($inv->store_id);
        $this->data['inv']        = $inv;
        $this->data['sid']        = $sale_id;
        $this->data['noprint']    = $noprint;
        $this->data['modal']      = $noprint ? true : false;
        $this->data['payments']   = $this->pos_model->getAllSalePayments($sale_id);
        $this->data['created_by'] = $this->site->getUser($inv->created_by);
        $this->data['printer']    = $this->site->getPrinterByID($this->Settings->printer);
        $this->data['store']      = $this->site->getStoreByID($inv->store_id);
        $this->data['page_title'] = lang('invoice');
        $this->load->view($this->theme . 'pos/' . ($this->Settings->remote_printing != 1 && $this->Settings->print_img ? 'eview' : 'view'), $this->data);
    }

    public function view_bill()
    {
        $this->load->view($this->theme . 'pos/view_bill', $this->data);
    }
}
