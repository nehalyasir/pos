<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'libraries/REST_Controller.php');
class Api extends REST_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->database();
     }
        
     /**
      * Get All Data from this method.
      *
      * @return Response
     */
     public function index_get($id = 0)
     {
         if(!empty($id)){
             $data = $this->db->get_where("tec_products", ['id' => $id])->row_array();
         }else{
             $data = $this->db->get("tec_products")->result();
         }
      
         $this->response($data, REST_Controller::HTTP_OK);
     }

     public function Products_get($id = 0)
     {
         if(!empty($id)){
             $data = $this->db->get_where("tec_products", ['id' => $id])->row_array();
         }else{
             $data = $this->db->get("tec_products")->result();
         }
      
         $this->response($data, REST_Controller::HTTP_OK);
     }
    //public function SyncPOSInvoices_get()
//{
 //   $data = $this->db->get_where("tec_Pos_order_sync", ['LOGID' => 0])->row_array();
 //   $this->response($data, REST_Controller::HTTP_OK);
//}
    
//public function SyncPOSInvoices_get()
//{
//  $where_conditions = ['LOGID' => 0, 'status' => 'pending'];
//    $data = $this->db->get_where("tec_Pos_order_sync", $where_conditions)->result();
//    if (!empty($data)) {
//        foreach ($data as $row) {
//            $this->response($row, REST_Controller::HTTP_OK);
//        }
//    } else {
//        echo "";
//    }

    // Send the response (if needed)
 //   $this->response($data, REST_Controller::HTTP_OK);
//}
	
	public function SyncPOSInvoices_get()
	{
    $where_conditions = ['LOGID' => 0, 'status' => 'pending'];
    //$this->db->limit(10); 
    $this->db->order_by('SyncDate', 'asc'); 
    $data = $this->db->get_where("tec_Pos_order_sync", $where_conditions)->result();
    if (!empty($data)) {
        foreach ($data as $row) {
            $this->response($row, REST_Controller::HTTP_OK);
        }
    } else {
         echo "";
    }
		$this->response($data, REST_Controller::HTTP_OK);
	}


    // public function SyncPOSInvoices_get()
//  {
        //$data = $this->db->get_where("tec_Pos_order_sync", ['LOGID' => 0])->row_array();
    //  $data = $this->db->get("tec_Pos_order_sync")->result();
    //  $this->response($data, REST_Controller::HTTP_OK);
    //}
    //  public function SyncPOSInvoices_get()
        //{
       //$data = $this->db->get_where("tec_Pos_order_sync", ['LOGID' => 0])->row_array();
        //if (!empty($data)) {
        //foreach ($data as $row) {
            //$this->response($row, REST_Controller::HTTP_OK);
        //}
        //die();
        //}
        //else {
        //echo "No rows found with LOGID = 0";
        //}
        //}

    
    
    public function UpdateInvStatus_put()
        {
            $data = $this->put();
            $InvoiceNumber = $data['InvoiceNumber'];
            $sales_returnid = $data['sales_returnid'];
            $Status = $data['Status'];
            $LOGID = $data['LOGID'];
            $docType = $data['docType'];
        $data = array(
    'Status' => $Status,
    'LOGID'  => $LOGID  
);
$this->db->where('sales_returnid', $sales_returnid,'docType', $docType);
$this->db->update('tec_Pos_order_sync', $data);
      //   $this->response('DONE', REST_Controller::HTTP_OK);
        }
    

    public function syncinvdelete_get()
     {
         if(!empty($id)){
             $data = $this->db->get_where("tec_syncinvoicedelete_log")->row_array();
         }else{
             $data = $this->db->get("tec_syncinvoicedelete_log")->result();
         }
      
         $this->response($data, REST_Controller::HTTP_OK);
     }
    
     public function delete_get($id = 0)
     {
         if(!empty($id)){
             $data = $this->db->get_where("tec_products", ['id' => $id])->row_array();
         }else{
             $data = $this->db->get("tec_products")->result();
         }
      
         $this->response($data, REST_Controller::HTTP_OK);
     }
       
     /**
      * Get All Data from this method.
      *
      * @return Response
     */
    // Edit by ayan
        public function add_post()
        {
            $data = $this->post();
            $postData = $data['data'];
            if (!empty($postData)) {
                foreach ($postData as $postItem) {
                        $code = $postItem["Sku"];
                        $name = $postItem["Name"];
                        $price = $postItem["price"];
                        $cost = $postItem["cost"];
                        $this->db->where('code', $code);
                        $quantity=0;
                        $store_id=1;
                        $query = $this->db->get('tec_products', 1);
                    if ($query->num_rows() > 0) {
                        $result = $query->row();
                        $decode=json_encode($result);
                        echo "$code"."<br>";
                    } else {
                            $this->db->insert('tec_products', array('code' => $code, 'name' => $name, 'price' => $price,'cost' => $cost));
                            $product_id = $this->db->insert_id();
                    $this->db->insert('tec_product_store_qty', array('product_id' => $product_id, 'store_id' => $store_id, 'quantity' => $quantity,'price' =>$price));
                            //$this->response(['Item insert successfully.'], REST_Controller::HTTP_OK);
                    }
                }
                echo "";
            }

        }



     /**
      * Get All Data from this method.
      *
      * @return Response
     
     public function index_put($id)
     {
         var_dump($id);
         die();
         $input = $this->put();
         $this->db->update('tec_products', $input, array('id'=>$id));
      
         $this->response(['Item updateds successfully.'], REST_Controller::HTTP_OK);
     }*/
     public function items_put($id)
    {
        
        $input = $this->put();
         $putData = $input["data"];
        foreach ($putData as $postItem) {   
        //  var_dump($postItem);
            
            
               $code = $postItem["Sku"];
               $price = $postItem["price"];
            
            
            $this->db->where('code', $code);
             $this->db->update('tec_products', array('price' => $price,'code' => $code));
             $Productid = $this->db->get_where("tec_products", ['code' => $code])->row_array();
             $Productid = $Productid['id'];
             //$this->db->update('tec_product_store_qty', array('price' => $price,'product_id' => $Productid));
            $data = array( 'price' => $price  );
            $this->db->where('product_id', $Productid);
            $this->db->update('tec_product_store_qty', $data);
           //  $storeid = $this->db->get_where("tec_product_store_qty", ['product_id' => $Productid])->row_array();
            // $this->db->update('tec_product_store_qty', array('price' => $price,'product_id' => $Productid));
            
             echo "$code"."<br>";
        }
        
}

    
    
     /** public function Update_put()
     {
            var_dump($_PUT['Sku']);
         die();
         $input = $this->put();
         $this->db->update('tec_products', $input, array('code'=>$sku));
        
         $this->response(['Item updated successfully.'], REST_Controller::HTTP_OK);
     }
     */
     
      
     /**
      * Get All Data from this method.
      *
      * @return Response
     */

     public function items_delete($id)
     {
         $input = $this->delete();
        
         foreach ($input["Sku"] as $record) {

                $this->db->delete('tec_products', array('code'=>$record));
                echo "$record"."<br>";
                //$this->response(['Item deleted successfully.'], REST_Controller::HTTP_OK);

         }
         
     }
}