<?php

	class Checkout extends CI_controller
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
			$data['titel'] = '| Checkout';

			$token = $this->session->userdata['token'];
			$data['pesanan'] = $this->invoice_model->ambil_data($token);
			$id = $this->session->userdata['logged_in']['iduser'];
			$data['alamat'] = $this->invoice_model->cek_alamat($id);
		
			$this->load->view('tmplt/header');
			$this->load->view('checkout', $data);
			$this->load->view('tmplt/footer');
			
		}

		public function tampil_alamat()
		{
			$id = $this->session->userdata['logged_in']['iduser'];
			$data = $this->invoice_model->cek_alamat($id);
			foreach ($data as $almt) 
			{
				if ($almt->provinsi != null) {
				echo "
	                    <p>". $almt->penerima."
	                      <br> ".$almt->alamat."
	                      <br> ". $almt->kecamatan ." - ". $almt->kota ." - ". $almt->provinsi ." - ". $almt->kodepos."
	                      <br> ". $almt->notelp ."
	                    </p>
				";
				}else{
				echo "
	                    <p>". $almt->penerima."
	                      <br> ".$almt->alamat."
	                      <br> ".$almt->negara."
	                      <br> ". $almt->notelp ."
	                    </p>
				";
				}
			}

		}

		public function updateaddress()
		{
			$id 		= $this->input->post('id_alamat');
			$iduser 	= $this->input->post('iduser');
			$idinvoice 	= $this->input->post('idinvoice');
			$alamat 	= $this->input->post('alamat');
			$intership  = $this->input->post('checkinter2');
			$neg 		= $this->input->post('country');
			$prov 		= $this->input->post('provinsi');
			$kt 		= $this->input->post('kota');
			$sbkt 		= $this->input->post('subkota');
			$penerima 	= $this->input->post('penerima');
			$notelp 	= $this->input->post('notelp');

			$negara = explode('-',$neg);
			$id_negara = $negara[0];
			$nama_negara = $negara[1];

			$provinsi = explode('-',$prov);
			$id_provinsi = $provinsi[0];
			$nama_provinsi = $provinsi[1];

			$kota = explode('-',$kt);
			$id_kota = $kota[0];
			$nama_kota = $kota[1];
			$kodepos 	= $kota[2];

			$subkota = explode('-',$sbkt);
			$id_subkota = $subkota[0];
			$nama_subkota = $subkota[1];

			if ($intership == '1') {
				$data = array
				(
					'iduser' 	=> $iduser,
					'alamat'	=> $alamat,
					'negara'	=> $nama_negara,
					'provinsi'	=> '',
					'kota'		=> '',
					'kecamatan'	=> '',
					'kodepos' 	=> $kodepos,
					'penerima' 	=> $penerima,
					'notelp' 	=> $notelp
				);
			}else{
				$data = array
				(
					'iduser' 	=> $iduser,
					'alamat'	=> $alamat,
					'negara'	=> '',
					'provinsi'	=> $nama_provinsi,
					'kota'		=> $nama_kota,
					'kecamatan'	=> $nama_subkota,
					'kodepos' 	=> $kodepos,
					'penerima' 	=> $penerima,
					'notelp' 	=> $notelp
				);
			}

				$where = array
				(
				'id_alamat' => $id
				);

				$cek_eks = $this->invoice_model->ekspedisi_cek($id , $idinvoice);

				if($cek_eks > 0){
				$this->invoice_model->ubahalamat($where,$data, 'alamat');
				
				if ($intership == '1') {
					$dataeks = array(
					'intership'		=>'1',
					'country_id'	=>$id_negara,
					'country'		=>$nama_negara,
					'province_id'	=> '',
					'province'		=> '',
					'city_id'		=> '',
					'city'			=> '',
					'subdistrict_id'=> '',
					'subdistrict'	=> ''
					);
				}else{
					$dataeks = array(
						'intership'		=>'0',
						'country_id'	=>'',
						'country'		=>'',
						'province_id'	=>$id_provinsi,
						'province'		=>$nama_provinsi,
						'city_id'		=>$id_kota,
						'city'			=>$nama_kota,
						'subdistrict_id'=>$id_subkota,
						'subdistrict'	=>$nama_subkota
					);
				}
				$where = array
				(
				'id_invoice' => $idinvoice
				);
				$this->invoice_model->ubahalamat($where,$dataeks, 'ekspedisi');

				}else{
				$this->invoice_model->ubahalamat($where,$data, 'alamat');

				if ($intership == '1') {
					$dataeks = array(
					'intership'		=>'1',
					'country_id'	=>$id_negara,
					'country'		=>$nama_negara,
					'province_id'	=> '',
					'province'		=> '',
					'city_id'		=> '',
					'city'			=> '',
					'subdistrict_id'=> '',
					'subdistrict'	=> ''
					);
				}else{
					$dataeks = array(
						'intership'		=>'0',
						'country_id'	=>'',
						'country'		=>'',
						'province_id'	=>$id_provinsi,
						'province'		=>$nama_provinsi,
						'city_id'		=>$id_kota,
						'city'			=>$nama_kota,
						'subdistrict_id'=>$id_subkota,
						'subdistrict'	=>$nama_subkota
					);
				}
				
				$where = array
				(
				'id_invoice' => $idinvoice
				);
				$this->invoice_model->ubahalamat($where,$dataeks, 'ekspedisi');

				}
				// redirect('checkout');
		}

		public function addaddress()
		{
			$iduser 	= $this->input->post('iduser');
			$idinvoice 	= $this->input->post('idinvoice');
			$alamat 	= $this->input->post('alamat');
			$check 	 	= $this->input->post('checkinter');
			$neg 	 	= $this->input->post('country');
			$prov 		= $this->input->post('provinsi');
			$kt 		= $this->input->post('kota');
			$sbkt 		= $this->input->post('subkota');
			$penerima 	= $this->input->post('penerima');
			$notelp 	= $this->input->post('notelp');

			if ($check == '1') {
				
				$negara = explode('-',$neg);
				$id_negara = $negara[0];
				$nama_negara = $negara[1];

				$id_provinsi = '0';
				$nama_provinsi = '';

				$id_kota = '0';
				$nama_kota = '';
				$kodepos 	= '';

				$id_subkota = '0';
				$nama_subkota = '';
			}else{
				$id_negara = '0';
				$nama_negara = '';
			
				$provinsi = explode('-',$prov);
				$id_provinsi = $provinsi[0];
				$nama_provinsi = $provinsi[1];

				$kota = explode('-',$kt);
				$id_kota = $kota[0];
				$nama_kota = $kota[1];
				$kodepos 	= $kota[2];

				$subkota = explode('-',$sbkt);
				$id_subkota = $subkota[0];
				$nama_subkota = $subkota[1];
			}

				$data = array
				(
					'iduser' 	=> $iduser,
					'alamat'	=> $alamat,
					'negara'	=> $nama_negara,
					'provinsi'	=> $nama_provinsi,
					'kota'		=> $nama_kota,
					'kecamatan'	=> $nama_subkota,
					'kodepos' 	=> $kodepos,
					'penerima' 	=> $penerima,
					'notelp' 	=> $notelp
				);

			$id_alamat = $this->invoice_model->tambahalamat($data, 'alamat');
			$dataeks = array
				(
					'id_alamat'		=>$id_alamat,
					'country_id'	=>$id_negara,
					'country'		=>$nama_negara,
					'province_id'	=>$id_provinsi,
					'province'		=>$nama_provinsi,
					'city_id'		=>$id_kota,
					'city'			=>$nama_kota,
					'subdistrict_id'=>$id_subkota,
					'subdistrict'	=>$nama_subkota,
					'intership'	 	=>$check
				);
				$where = array
				(
				'id_invoice' => $idinvoice
				);
				$this->invoice_model->ubahalamat($where,$dataeks, 'ekspedisi');
				redirect($_SERVER['HTTP_REFERER']);
		}

		public function tampil_ekspedisi()
		{

			$destination = $this->input->post('destination');
			$destination2 = $this->input->post('destinationcountry');
			$weight 	 = $this->input->post('weight');
			$ekspedisi 	 = $this->input->post('ekspedisi');

			if (empty($destination2)) {

				$curl = curl_init();

				curl_setopt_array($curl, array(
				  CURLOPT_URL => "https://pro.rajaongkir.com/api/cost",
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => "",
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 30,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => "POST",
				  CURLOPT_POSTFIELDS => "origin=152&originType=city&destination=".$destination."&destinationType=subdistrict&weight=".$weight."&courier=".$ekspedisi."",
				  CURLOPT_HTTPHEADER => array(
				    "content-type: application/x-www-form-urlencoded",
				    "key: f286c6e4cbcb3435907d35f08c1aac8c"
				  ),
				));

				$response = curl_exec($curl);
				$err = curl_error($curl);

				curl_close($curl);

				if ($err) {
				  echo "cURL Error #:" . $err;
				} else {
				  $biaya = json_decode($response,true);
				 $html = " ";
		            if (empty($biaya['rajaongkir']['results'][0]['costs'])) 
		            {
		            	$html .= "
                         <label>
		            	<input type='radio' name='product'class='card-input-element d-none' readonly>
		               	<div class='card card-body bg-light d-flex flex-row justify-content-between align-items-center'>
		                Sorry, there is no delivery service with this expedition to the recipient's destination
		                </div>
                         </label>
		                ";

					}
					else{
						foreach ($biaya['rajaongkir']['results'][0]['costs'] as $by) 
			            {

			            $ongkir = $by['cost'][0]['value'];
			            $srv = $by['service'];
			            $service = str_replace(' ', '', $srv);

			            	$html .= "
	                         <label>
			            	<input type='radio' name='product' required='' class='card-input-element d-none' value='".$by['service']."-".$by['cost'][0]['value']."'  onclick=show2(".$ongkir.",'".$service."')>
			               	<div class='card card-body bg-light d-flex flex-row justify-content-between align-items-center'>
			                ".$by['service']." ( ".$by['description'].") - Rp. ".number_format($by['cost'][0]['value'],0,',','.')."<br>
			                estimation ". $by['cost'][0]['etd'] ."
			                </div>
	                         </label>
			                ";
						}
			            	
					}
				echo $html;
				}

			}else{

				$curl = curl_init();

				curl_setopt_array($curl, array(
				  CURLOPT_URL => "https://pro.rajaongkir.com/api/v2/internationalCost",
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => "",
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 30,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => "POST",
				  CURLOPT_POSTFIELDS => "origin=152&destination=".$destination2."&weight=".$weight."&courier=".$ekspedisi."",
				  CURLOPT_HTTPHEADER => array(
				    "content-type: application/x-www-form-urlencoded",
				    "key: f286c6e4cbcb3435907d35f08c1aac8c"
				  ),
				));

				$response = curl_exec($curl);
				$err = curl_error($curl);

				curl_close($curl);

				if ($err) {
				  echo "cURL Error #:" . $err;
				} else {
				  $biaya = json_decode($response,true);
				 $html = " ";
		            if (empty($biaya['rajaongkir']['results'][0]['costs'])) 
		            {
			            $html .= "
                         <label>
		            	<input type='radio' name='product'class='card-input-element d-none' readonly>
		               	<div class='card card-body bg-light d-flex flex-row justify-content-between align-items-center'>
		                Sorry, there is no delivery service with this expedition to the recipient's destination
		                </div>
                         </label>
		                ";
					}else{
		            	foreach ($biaya['rajaongkir']['results'][0]['costs'] as $by) 
			            {

			            $ongkir = $by['cost'];
			            $srv = $by['service'];
			            $service = str_replace(' ', '', $srv);

			            	$html .= "
	                         <label>
			            	<input type='radio' name='product' required='' class='card-input-element d-none' value='".$srv."-".$ongkir."'  onclick=show2(".$ongkir.",'".$service."')>
			               	<div class='card card-body bg-light d-flex flex-row justify-content-between align-items-center'>
			                ".$by['service']." - ".$by['currency']." ".number_format($by['cost'],0,',','.')."<br>
			                estimation ". $by['etd'] ."
			                </div>
	                         </label>
			                ";
			            }
					}
				echo $html;
				}

			}
			

		}

		public function updatetransaksi(){
			$id = $this->input->post('idinvoice');
			$id_alamat = $this->input->post('id_alamat');
			$notelp = $this->input->post('notelp');
			$weight = $this->input->post('weight');
			
			$ekspedisi = $this->input->post('ekspedisi');
			$service = $this->input->post('service');
			$ongkir = $this->input->post('ongkir');

			// $ser = $this->input->post('product');


			// $servi = explode('-', $ser);
			// $service = $servi[0];
			// $ongkir = $servi[1];

			$data = ['id_alamat'=>$id_alamat,'notelp'=>$notelp];
			$where = ['id'=>$id];
			$this->invoice_model->updatedata($where,$data,'invoice');

			$eks_old = $this->db->query("SELECT * FROM ekspedisi WHERE id_alamat = '$id_alamat' ORDER BY id_ekspedisi DESC LIMIT 1")->row_array();
			$cek = $this->db->query("SELECT * FROM ekspedisi WHERE id_invoice = '$id' and id_alamat = '0' and country_id = '0' and province_id = '0'");
			if($cek->num_rows()>0){
			$dataeks = array
				(
					'id_alamat'		=>$id_alamat,
					'country_id'	=>$eks_old['country_id'],
					'country'		=>$eks_old['country'],
					'province_id'	=>$eks_old['province_id'],
					'province'		=>$eks_old['province'],
					'city_id'		=>$eks_old['city_id'],
					'city'			=>$eks_old['city'],
					'subdistrict_id'=>$eks_old['subdistrict_id'],
					'subdistrict'	=>$eks_old['subdistrict'],
					'courier'		=>$ekspedisi,
					'service'		=>$service,
					'weight'		=>$weight,
					'ongkir'		=>$ongkir,
				);
			$whereeks = ['id_invoice'=>$id];
			$this->invoice_model->updatedata($whereeks,$dataeks,'ekspedisi');
			}else{

			$eks_old = $this->db->query("SELECT * FROM ekspedisi WHERE id_alamat = $id_alamat ORDER BY id_ekspedisi DESC LIMIT 1")->row_array();
			$dataeks = array
				(
					'id_alamat'		=>$id_alamat,
					'courier'		=>$ekspedisi,
					'service'		=>$service,
					'weight'		=>$weight,
					'ongkir'		=>$ongkir,
				);
			$whereeks = ['id_invoice'=>$id];
			$this->invoice_model->updatedata($whereeks,$dataeks,'ekspedisi');
			}
			
			 redirect('checkout/payment');

		}

		public function payment()
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
      		$where = array('invoice.email'=>$email);
      		$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Payment';

			$token = $this->session->userdata['token'];
			$data['pesanan'] = $this->invoice_model->ambil_data($token);
			$data['invoice']= $this->invoice_model->get_data($token);
			$data['ekspedisi'] = $this->invoice_model->get_eks($token);
			
			$this->load->view('tmplt/header',$data);
			$this->load->view('payment', $data);
			$this->load->view('tmplt/footer');
		}

		public function bayar()
		{

			$token 	= $this->input->post('token');
			$email 	= $this->input->post('email');
			$desc 	= $this->input->post('description');
			$amount = $this->input->post('amount');
			$succes = base_url().'checkout/successfully';

			$where=array('id'=>$token);	
			$data=array('statuslink'=>'y');
			$this->invoice_model->ubahalamat($where,$data,'invoice');

			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => 'https://api.xendit.co/v2/invoices',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS => 'external_id='.$token.'&amount='.$amount.'&payer_email='.$email.'&description='.$desc.'&success_redirect_url='.$succes.'&should_send_email=true',
			  CURLOPT_HTTPHEADER => array(
			    'Authorization: Basic eG5kX2RldmVsb3BtZW50X0RSWDNhVlBScHNTbmRxTGxReG5rcW52TjRnSVB6RGNhTVpWWTNIdWJSd050S1kyOGRmbFd2aGNlaFNsQzo',
			    'Content-Type: application/x-www-form-urlencoded',
			    'Cookie: incap_ses_1115_2182539=P9BRNx+/iXtEuWzb30Z5D1uSFmAAAAAA4KX3v3Np35pHRwDzqyX2sw==; nlbi_2182539=Gcx7HAdrxQ8z0CP8jjCKbQAAAABecyY9oNeG6yR1z3dsVV1w; visid_incap_2182539=OdcqRDZsS9iUsF22Zq1Fu25fEmAAAAAAQUIPAAAAAAAXZOR0HPgkBfbMJ9btW+a3'
			  ),
			));

			// real eG5kX3Byb2R1Y3Rpb25fMGNSbkVDbTUwNTlVWkxsOTkxbzRoZmMyblVWZGszdGNhQlhaUVZRdDhxTktBYjFtU0hLeld3UHBBb3FUOG1wOg
			// trial eG5kX2RldmVsb3BtZW50X0RSWDNhVlBScHNTbmRxTGxReG5rcW52TjRnSVB6RGNhTVpWWTNIdWJSd050S1kyOGRmbFd2aGNlaFNsQzo

			$response = curl_exec($curl);

			curl_close($curl);
			//var_dump(json_decode($response));
			$id = (json_decode($response)->id);

			$dt = (json_decode($response)->invoice_url);
			$status = (json_decode($response)->status);

			redirect($dt);

		}

		public function successfully(){
			$token = $this->session->userdata['token'];
			$where=array('id'=>$token);	
			$data=array('status'=>'y');
			$this->invoice_model->ubahalamat($where,$data,'invoice');
			$this->session->unset_userdata('token');
			redirect('profil/detailorder/'.$token);
		}
	}
?>