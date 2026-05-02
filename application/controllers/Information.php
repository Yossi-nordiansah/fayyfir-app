<?php

	class Information extends CI_controller
	{
		public function about()
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['kategorihome'] = $this->product_model->kategorihome();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
			$where = array('invoice.email'=>$email);
			$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| About';

			$this->load->view('tmplt/header',$data);
			$this->load->view('about');
			$this->load->view('tmplt/footer');
		}
		
		public function gallery()
    {
        $data['kategori'] = $this->product_model->kategori();
        $data['kategorihome'] = $this->product_model->kategorihome();
        $data['sub_kategori'] = $this->product_model->all_sub_kategori();
        
        $email = isset($this->session->userdata['logged_in']['email']) ? $this->session->userdata['logged_in']['email'] : '';
        $where = array('invoice.email' => $email);
        
        $data['invo'] = $this->invoice_model->cekstatuspay($where);
        $data['suminvo'] = $this->invoice_model->suminvo($where);
        $data['bannermenu'] = $this->fitur_model->bannermenu();
        $data['titel'] = '| Gallery';
    
        // Ambil data gallery aktif dari model
        $data['gallery'] = $this->fitur_model->gallery();
    
        // Load view
        $this->load->view('tmplt/header', $data);
        $this->load->view('gallery', $data); // pastikan kamu punya view gallery.php
        $this->load->view('tmplt/footer');
    }

		public function contact()
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['kategorihome'] = $this->product_model->kategorihome();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
			$where = array('invoice.email'=>$email);
			$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Contact';

			$this->load->view('tmplt/header',$data);
			$this->load->view('contact');
			$this->load->view('tmplt/footer');
		}

		public function returnandexchange()
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['kategorihome'] = $this->product_model->kategorihome();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
			$where = array('invoice.email'=>$email);
			$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Returns & Exchanges';

			$this->load->view('tmplt/header',$data);
			$this->load->view('returnandexchange');
			$this->load->view('tmplt/footer');
		}
	}

?>