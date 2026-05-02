<?php

	class Cart extends CI_controller
	{

		public function index()
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
			$where = array('invoice.email'=>$email);
			$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Cart';

			$this->load->view('tmplt/header',$data);
			$this->load->view('cart',$data);
			$this->load->view('tmplt/footer');
		}

		public function viewcartsamping()
		{
			$data=$this->cart->contents();
			foreach($data as $items)
			{
				echo "
					<div class='minicart-prd row'>
			            <div class='minicart-prd-image image-hover-scale-circle col'>
			              <a href='javascript:void(0)'><img class='lazyload fade-up' src='data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' data-src='".base_url()."asset/images/product/".$items['gambar']."' alt=''>
			              </a>
			            </div>
			            <div class='minicart-prd-info col'>
			              <div class='minicart-prd-tag'>".$items['category']."</div>
			              <h2 class='minicart-prd-name'><a href='#'>".$items['name']."</a></h2>
			              <div class='minicart-prd-qty'><span class='minicart-prd-qty-label'>Qty :</span><span class='minicart-prd-qty-value'>".$items['qty']." - ".$items['ukuran']."</span></div>
			              <div class='minicart-prd-price prd-price'>
			                <!-- <div class='price-old'>$200.00</div> -->
			                ";
			                if ($items['pricedisc']) {
			                	echo"<div class='price-new'>Rp".number_format($items['pricedisc'], 0, ',', '.')."</div><br>
			                	<div class='price-old'>Rp".number_format($items['price'], 0, ',', '.')."</div>";
			                }else{	
			                	echo"<div class='price-new'>Rp".number_format($items['price'], 0, ',', '.')."</div>";
			                }
			              echo"
			              </div>
			            </div>
			            <div class='minicart-prd-action' id='deletecart'>
			              <a href='javascript:void(0)' onclick=deleteCart('".$items['rowid']."') class='js-product-remove'><i class='icon-recycle'></i></a>
			            </div>
			          </div>
				";
			}
		}

		function cartpagedetail(){
	       $data = $this->cart->contents();
	       $total = $this->cart->total_items();
	       if($total > 0){
	        foreach($data as $items){

	        	echo "
	        		<div class='cart-table-prd'>
		                <div class='cart-table-prd-image'>
		                  <a href='javascript:void(0)' class='prd-img'><img class='lazyload fade-up' src='data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==' data-src='".base_url()."asset/images/product/".$items['gambar']."' alt=''></a>
		                </div>
		                <div class='cart-table-prd-content-wrap'>
		                  <div class='cart-table-prd-info'>
		                    <div class='cart-table-prd-price'>
		                      ";
			                if ($items['pricedisc']) {
			                	echo"<div class='price-new'>Rp".number_format($items['pricedisc'], 0, ',', '.')."</div>
			                		<div class='price-old'>Rp".number_format($items['price'], 0, ',', '.')."</div>";
			                }else{	
			                	echo"<div class='price-new'>Rp".number_format($items['price'], 0, ',', '.')."</div>";
			                }
			              echo"
		                    </div>
		                    <h2 class='cart-table-prd-name'><a href='javascript:void(0)'>".$items['name']."</a> - ".$items['ukuran']."</h2>
		                  </div>
		                  <div class='cart-table-prd-qty'>
		                    <div class='qty qty-changer'>
		                      <button onclick=decrementCart('".$items['rowid']."',".$items['qty'].") class='decrease  js-qty-button'></button>
		                      <input type='number' class='qty-input' name='qty' value='". $items['qty'] ."' data-max='5'>
		                      <button onclick=incrementCart('".$items['rowid']."',".$items['qty'].") class='increase  js-qty-button'></button>
		                    </div>
		                  </div>
		                  <div class='cart-table-prd-price-total'>
		                  ";
			                if ($items['pricedisc']) {
			                	echo"Rp ".number_format($items['pricedisc']* $items['qty'], 0, ',', '.')."";
			                }else{	
			                	echo"Rp ".number_format($items['price']* $items['qty'], 0, ',', '.')."";
			                }
			              echo"
		                    
		                  </div>
		                </div>
		                <div class='cart-table-prd-action'>
		                  <a href='javascript:void(0)' onclick=deleteCart('".$items['rowid']."') class='cart-table-prd-remove hapus_cart' data-tooltip='Remove Product'><i class='icon-recycle'></i></a>
		                </div>
		              </div>
	        	";
	        }
	       }else{
	        echo "
	        	<center>
	        	<div class='txt1'><img src='".base_url()."asset/images/pages/tumbleweed.gif' alt=''></div>
				<div class='txt2'>Your shopping cart is empty!</div>
				</center>
	        ";
	       }
	    }

	    function cekkupon()
	    {
	    	$kode = $this->input->post('kodevoucher');
	    	//$the_session = array("kodevoucher" => $this->input->post('kodevoucher'));
	    	$app =$this->db->query("SELECT * FROM voucher where kodevoucher = '$kode'");
	    	if($app->num_rows() >0)
	    	{
	    		echo 1;
	    	}else{
	    		echo 0;
	    	}
			//$this->session->set_userdata($the_session);
	    }
	    function detkupon(){
	    	$kode = $this->input->post('kodevoucher');
	    	//$the_session = array("kodevoucher" => $this->input->post('kodevoucher'));
	    	$app =$this->db->query("SELECT * FROM voucher where kodevoucher = '$kode'")->row();
	    	echo json_encode($app);
	    }
	    
	    function countcart()
	    {
	         $cart = $this->cart->total_items();
	      	echo json_encode($cart);
	    }

		public function totalpay()
		{
				// $total = $this->cart->total();
				// echo "Rp".number_format($total, 0, ',', '.');
			$data=$this->cart->contents();
			$tot=0;
			foreach($data as $items)
			{
				if ($items['pricedisc'] == 0) {
					$tot = $tot+($items['qty']*$items['price']);
				}else{
					$tot = $tot+($items['qty']*$items['pricedisc']);	
				}
			}
				echo "Rp".number_format($tot, 0, ',', '.');

		}

		public function totalpay2()
		{
			$data=$this->cart->contents();
			$tot=0;
			foreach($data as $items)
			{
				if ($items['pricedisc'] == 0) {
					$tot = $tot+($items['qty']*$items['price']);
				}else{
					$tot = $tot+($items['qty']*$items['pricedisc']);	
				}
			}
				echo $tot;
		}

		public function add($id_product)
		{	 
			$pd = $this->product_model->findid($id_product);

			$data = array(
				'id'		=> $pd->id_product.'-'.$this->input->post('ukuran'),
                'product_id' =>$pd->id_product,
				'qty'		=> 1,
				'price'		=> $this->input->post('harga'),
				'pricedisc'	=> $this->input->post('setdiskon'),
				'disc'		=> $this->input->post('diskon'),
				'name'		=> $pd->nama_product,
				'category'	=> $pd->nama_kategori,
				'gambar'	=> $pd->gambar,
				'berat'		=> $pd->berat,
				'ukuran'	=> $this->input->post('ukuran')
			);
			$this->cart->insert($data);
			redirect($_SERVER['REQUEST_URI'], 'refresh');
		}

		public function updateproduct($rowid)
		{
			$data = array(
				'rowid'	=> $rowid,
				'qty'	=> $this->input->post('qty')
			);
			$this->cart->update($data);
			redirect($_SERVER['HTTP_REFERER']);
			
		}

		function decrement()
	    {
	        $rowid = $this->input->post('rowid');
	        $q = $this->input->post('qty');
	        $qty = $q-1;
	         $data = array(
	           'rowid'  => $rowid,
	           'qty'  => $qty,
	          );
	          $this->cart->update($data);
	    }
	     function increment()
	    {
	        $rowid = $this->input->post('rowid');
	        $q = $this->input->post('qty');
	        $qty = $q+1;
	         $data = array(
	           'rowid'  => $rowid,
	           'qty'  => $qty,
	          );
	          $this->cart->update($data);
	    }
		function delete()
	    {
	        $rowid = $this->input->post('rowid');
	        
	         $data = array(
	           'rowid'  => $rowid,
	           'qty'  => 0,
	          );
	          $this->cart->update($data);
	    }

		public function deleteall()
		{
			$this->cart->destroy();
			redirect('cart');
		}

		public function to_checkout()
		{
			if(!isset($this->session->userdata['logged_in']['status']))
			{
			redirect(base_url("auth/login"));
			}
			else
			{
			$totalbayar = $this->input->post('total');
			$id_invoice = $this->invoice_model->insert_invoice($totalbayar);
			$the_session = array("token" => $id_invoice);
			$this->session->set_userdata($the_session);
			$add = $this->invoice_model->insert_pesanan($id_invoice);
			$add = $this->invoice_model->insert_ekspedisi($id_invoice);
			if($add == true){$this->cart->destroy();}
			redirect('checkout');
		
			}
		}	
		
	}

?>