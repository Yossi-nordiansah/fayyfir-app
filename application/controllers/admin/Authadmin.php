<?php
defined('BASEPATH') OR exit('No direct script access allowed');

	class Authadmin extends CI_controller
	{		
		public function index() 
		{
			$this->load->view('admin/login');
		}

		public function proseslogin()
		{
			$username = $this->input->post('username');
			$pwd = $this->input->post('password');

			$password = sha1($pwd);

			$where = array(
				'username'  => $username,
				'password'  => $password,
				'level'		=> 'admin'
				);
			$cek = $this->auth_model->testloginadmin("user",$where)->num_rows();
			$p_login = $this->auth_model->ploginadmin("user",$where);
			foreach($p_login as $p){
				$username = $p->username;
				$iduser = $p->iduser;
				$nama = $p->nama;
				$email = $p->email;
				$notelp = $p->notelp;
				$level = $p->level;
			}

			if($cek > 0){
	 
				$data_session = array(
					'username' 	=> $username,
					'nama'		=> $nama,
					'iduser'	=> $iduser,
					'email'		=> $email,
					'notelp'	=> $notelp,
					'level'		=> $level,
					'status'	=> "login"
					);
	 
				$this->session->set_userdata('logged_admin',$data_session);
	 		
		        redirect('admin/dashboard');
	 
			}else{
				echo "Username dan password salah !";
			}
		}
	 
		public function logout()
		{
			$this->session->sess_destroy();
			$this->session->unset_userdata('username');
			$this->session->unset_userdata('nama');
			$this->session->unset_userdata('is_login');
			redirect('admin/authadmin');
		}
	}

