<?php
defined('BASEPATH') OR exit('No direct script access allowed');

	class User extends CI_controller
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
			$data['admin'] = $this->auth_model->dataadmin();
			$data['user'] = $this->auth_model->datauser();
		
			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/user', $data);
			$this->load->view('tmpltadmin/footer');
		}		
	}

	