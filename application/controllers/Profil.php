<?php

	class Profil extends CI_controller
	{
		public function index()
		{
			if(!isset($this->session->userdata['logged_in']['status']))
			{
			redirect(base_url("auth/login"));
			}
			else
			{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
	      	$where = array('invoice.email'=>$email);
	      	$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Profile';

			$id = $this->session->userdata['logged_in']['iduser'];
			$data['alamat'] = $this->invoice_model->cek_alamat($id);


			$this->load->view('tmplt/header',$data);
			$this->load->view('profil',$data);
			$this->load->view('tmplt/footer');
			}
		}

		public function address()
		{
			if(!isset($this->session->userdata['logged_in']['status']))
			{
			redirect(base_url("auth/login"));
			}
			else
			{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
	      	$where = array('invoice.email'=>$email);
	      	$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Profile Address';

			$id = $this->session->userdata['logged_in']['iduser'];
			$data['alamat'] = $this->invoice_model->cek_alamat($id);


			$this->load->view('tmplt/header',$data);
			$this->load->view('address',$data);
			$this->load->view('tmplt/footer');
			}
		}

		public function orderhistory()
		{
			if(!isset($this->session->userdata['logged_in']['status']))
			{
			redirect(base_url("auth/login"));
			}
			else
			{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
	      	$where = array('invoice.email'=>$email);
	      	$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Order History';

			$data['history'] = $this->invoice_model->get_history($where);

			$this->load->view('tmplt/header',$data);
			$this->load->view('orderhistory',$data);
			$this->load->view('tmplt/footer');
			}
		}

		public function tracking()
		{
			if(!isset($this->session->userdata['logged_in']['status']))
			{
			redirect(base_url("auth/login"));
			}
			else
			{

			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
	      	$where = array('invoice.email'=>$email);
	      	$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$kurir = $this->input->get('kurir');
			$resi = $this->input->get('noresi');
			$data['titel'] = '| Tracking';

			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => "https://pro.rajaongkir.com/api/waybill",
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS => "waybill=".$resi."&courier=".$kurir."",
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
			  $data['tracking'] = json_decode($response,true);
			}


			$this->load->view('tmplt/header',$data);
			$this->load->view('tracking',$data);
			$this->load->view('tmplt/footer');
			}
		}

		public function kota($provinsi)
		{
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => "https://pro.rajaongkir.com/api/city?&province=".$provinsi,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "GET",
			  CURLOPT_HTTPHEADER => array(
			    "key: f286c6e4cbcb3435907d35f08c1aac8c"
			  ),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
			  echo "cURL Error #:" . $err;
			} else {
			  $kota = json_decode($response,true);

			  if ($kota['rajaongkir']['status']['code'] == '200') {
			  	foreach ($kota['rajaongkir']['results'] as $kt) {
                    echo "<option value='".$kt['city_id'].'-'.$kt['city_name'].'-'.$kt['postal_code']."'>".$kt['city_name']."</option>";

			  	}
			  }
			}
		}

		public function subkota($city)
		{
			$curl = curl_init();

			curl_setopt_array($curl, array(
			  CURLOPT_URL => "https://pro.rajaongkir.com/api/subdistrict?city=".$city,
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "GET",
			  CURLOPT_HTTPHEADER => array(
			    "key: f286c6e4cbcb3435907d35f08c1aac8c"
			  ),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
			  echo "cURL Error #:" . $err;
			} else {
			  $kota = json_decode($response,true);

			  if ($kota['rajaongkir']['status']['code'] == '200') {
			  	foreach ($kota['rajaongkir']['results'] as $kt) {
                    echo "<option value='".$kt['subdistrict_id'].'-'.$kt['subdistrict_name'].'-'.$kt['postal_code']."'>".$kt['subdistrict_name']."</option>";

			  	}
			  }
			}
		}

		public function detailorder($id)
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
	      	$where = array('invoice.email'=>$email);
	      	$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Detail Order';

			$data['invoice'] = $this->invoice_model->idinvoice($id);
			$data['pesanan'] = $this->invoice_model->idpesanancus($id);
			$this->load->view('tmplt/header',$data);
			$this->load->view('detailpesanan', $data);
			$this->load->view('tmplt/footer');
		}

		public function profilupdate()
		{
			$iduser 	= $this->input->post('iduser');
			$nama 		= $this->input->post('nama');
			$email 		= $this->input->post('email');
			$notelp 	= $this->input->post('notelp');
			$username 	= $this->input->post('username');

			$data = array
			(
				'nama' 		=> $nama,
				'email'		=> $email,
				'notelp' 	=> $notelp,
				'username' 	=> $username
			);

			$where = array
			(
				'iduser' => $iduser
			);

			$this->product_model->update_kategori($where,$data, 'user');
			redirect($_SERVER['HTTP_REFERER']);
		}




		
	}

?>