<?php

	class Checkoutv2 extends CI_controller
	{
		public function confirm($id)
		{
			
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
	      	$where = array('invoice.email'=>$email);
	      	$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Checkout';

			$data['pesanan'] = $this->invoice_model->ambil_data($id);
			$id = $this->session->userdata['logged_in']['iduser'];
			$data['alamat'] = $this->invoice_model->cek_alamat($id);
		
			$this->load->view('tmplt/header');
			$this->load->view('checkout2', $data);
			$this->load->view('tmplt/footer');
			
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
			$this->invoice_model->updatedata($whereeks,$dataeks,'ekspedisi1');
			}
			
			 redirect('checkoutv2/payment/'.$id);

		}


		public function payment($id)
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
      		$where = array('invoice.email'=>$email);
      		$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Payment';

			$data['pesanan'] = $this->invoice_model->ambil_data($id);
			$data['invoice']= $this->invoice_model->get_data($id);
			$data['ekspedisi'] = $this->invoice_model->get_eks($id);
			
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