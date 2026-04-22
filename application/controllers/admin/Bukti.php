<?php
defined('BASEPATH') OR exit('No direct script access allowed');

	class Bukti extends CI_controller
	{		
		public function __construct()
        {
                parent::__construct();
		    	if(!isset($this->session->userdata['logged_admin']['status']) && $this->session->userdata['logged_admin']['level'] != 'admin')
				{
				redirect(base_url("admin/authadmin"));
				}
		}
		public function index()
		{
			$data['bukti']= $this->invoice_model->buktitransfer();

			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/buktitf',$data);
			$this->load->view('tmpltadmin/footer');
		}
		
	}

	