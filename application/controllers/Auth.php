<?php
defined('BASEPATH') OR exit('No direct script access allowed');

	class Auth extends CI_controller
	{		
		public function login() 
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
      $email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
      $where = array('invoice.email'=>$email);
      $data['invo'] = $this->invoice_model->cekstatuspay($where);
      $data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Login';

			$this->load->view('tmplt/header',$data);
			$this->load->view('login');
			$this->load->view('tmplt/footer');
		}

		public function register() 
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
      $email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
      $where = array('invoice.email'=>$email);
      $data['invo'] = $this->invoice_model->cekstatuspay($where);
      $data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['titel'] = '| Register';


			$this->load->view('tmplt/header',$data);
			$this->load->view('register');
			$this->load->view('tmplt/footer');
		}

		public function prosesregister()
		{
		$this->form_validation->set_rules('username', 'username','trim|required|min_length[4]|max_length[255]');
		$this->form_validation->set_rules('password', 'password','trim|required|min_length[4]|max_length[255]');
		$this->form_validation->set_rules('nama', 'nama','trim|required|min_length[2]|max_length[255]');
		$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]');
		$this->form_validation->set_rules('email', 'Email', 'required|valid_email');


    		if ($this->form_validation->run()==true)
    	   	{
      			$username 	= $this->input->post('username');
      			$pwd		= $this->input->post('password');

      			$password 	= sha1($pwd);
      			$nama 		= $this->input->post('nama');
      			$email 		= $this->input->post('email');
      			$notelp 	= $this->input->post('notelp');
            $aktif      = 'n';

            //generate simple random code
            $set = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $code = substr(str_shuffle($set), 0, 12);
				
      			$cek = $this->db->query("SELECT * FROM user WHERE email = '$email'");
      			if($cek->num_rows()>0){
      			$this->session->set_flashdata('alertemail','Email is already use, please use another email!');
      			redirect('auth/Register');
      			}else{
      			$id = $this->auth_model->register($username,$password,$nama,$email,$notelp,$code,$aktif);


                    $this->load->library('email');
                    $config = array();
                    $config['charset'] = 'utf-8';
                    $config['useragent'] = 'Codeigniter';
                    $config['protocol']= "smtp";
                    $config['mailtype']= "html";
                    $config['smtp_host']= "ssl://mail.alsharifshop.com";//pengaturan smtp
                    $config['smtp_port']= "465";
                    $config['smtp_timeout']= "5";
                    $config['smtp_user']= "info@alsharifshop.com"; // isi dengan email kamu
                    $config['smtp_pass']= "alsharifshop26"; // isi dengan password kamu
                    $config['crlf']="\r\n"; 
                    $config['newline']="\r\n"; 
                    $config['wordwrap'] = TRUE;
                    //memanggil library email dan set konfigurasi untuk pengiriman email
                        
                    $this->email->initialize($config);
                    //konfigurasi pengiriman
                    $this->email->from($config['smtp_user']);
                    $this->email->to($email);
                    $this->email->subject("Activation Account");

                    $message =  "

                    <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
                    <html xmlns='http://www.w3.org/1999/xhtml'>
                      <head>
                        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
                        <title>Alsharif Shop - Email Verification</title>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'/>
                        <link href='https://fonts.googleapis.com/css?family=Nunito:400,600,700,800,900&display=swap' rel='stylesheet'>
                      </head>
                      <body style='margin: 0; padding: 0; box-sizing: border-box;'>
                        <table align='center' cellpadding='0' cellspacing='0' width='95%'>
                          <tr>
                            <td align='center'>
                              <table align='center' cellpadding='0' cellspacing='0' width='600' style='border-spacing: 2px 5px;' bgcolor='#fff'>
                                <tr>
                                  <td align='center' style='padding: 5px 5px 5px 5px;'>
                                    <a href='https://alsharifshop.com' target='_blank'>
                                      <img src='https://alsharifshop.com/asset/images/logoemail.png' alt='Logo' style='width:420px; margin: -100px -100px; border:0;'/>
                                    </a>
                                  </td>
                                </tr>
                                <tr>
                                  <td bgcolor='#fff'>
                                    <table cellpadding='0' cellspacing='0' width='100%%'>
                                      <tr>
                                        <td style='padding: 10px 0 10px 0; font-family: Nunito, sans-serif; font-size: 20px; font-weight: 900'>
                                          Activate Your Alsharif Shop Account
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                </tr>
                                <tr>
                                  <td bgcolor='#fff'>
                                    <table cellpadding='0' cellspacing='0' width='100%%'>
                                      <tr>
                                        <td style='padding: 20px 0 20px 0; font-family: Nunito, sans-serif; font-size: 16px;'>
                                          Hi, <span id='name'>".$nama."</span>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td style='padding: 0; font-family: Nunito, sans-serif; font-size: 16px;'>
                                          Thank you for registering at Alsharif Shop. Please confirm this email to activate your account.
                                        </td>
                                      </tr>
                                      <tr>
                                        <td style='padding: 20px 0 20px 0; font-family: Nunito, sans-serif; font-size: 16px; text-align: center;'>
                                          <a href='https://alsharifshop.com/auth/activate/".$id."/".$code."' style='background-color: #046106; border: none; color: white; padding: 15px 40px; text-align: center; display: inline-block; font-family: Nunito, sans-serif; font-size: 18px; font-weight: bold; cursor: pointer; text-decoration:none;'>
                                            Confirm Email
                                          </a>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td style='padding: 0; font-family: Nunito, sans-serif; font-size: 16px;'>
                                          If you are having trouble clicking the 'Confirm Email' button, click the link below
                                          <p id='url'><a href='https://alsharifshop.com/auth/activate/".$id."/".$code."'>Link</a></p>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td style='padding: 50px 0; font-family: Nunito, sans-serif; font-size: 16px;'>
                                          Regards,
                                          <br>
                                          <p>Alsharifshop</p>
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                      </body>
                    </html>

                                    ";
                    // $message = "<p>Anda melakukan permintaan reset password</p>";
                    // $message .= "<a href='".site_url('auth/resetpassword/'.$resetkey)."'>klik reset password</a>";
                    $this->email->message($message);
                    
                    if($this->email->send())
                    {
                        $this->session->set_flashdata('aktif','Please check your email '.$email.', to activation account');
                        redirect('auth/register');
                    }else
                    {
                        $this->session->set_flashdata('aktif','You have successfully registered, but failed to send email verification. please contact admin for further handling (081290007740 / Whatsapp text only)');
                        redirect('auth/register');
                    }
                    
                    echo "<br><br><a href='".site_url('auth/login')."'>Kembali ke Menu Login</a>";


			             $this->session->set_flashdata('success_register','User Registration Process is successful, please login first');
			             redirect('auth/login');
            			}
            			
            }else
            {
            	$this->session->set_flashdata('error', validation_errors());
            	redirect('auth/register');
            }

		}

    public function activate()
    {
          $id =  $this->uri->segment(3);
          $code = $this->uri->segment(4);

          //fetch user details
          $user = $this->auth_model->getUser($id);

          //if code matches
          if($user['resetpassword'] == $code)
          {
           //update user active status
           $data['aktif'] = 'y';
           $query = $this->auth_model->activate($data, $id);

           if($query){
            $this->session->set_flashdata('message', 'User activated successfully');
           }
           else{
            $this->session->set_flashdata('message', 'Something went wrong in activating account');
           }
          }
          else{
           $this->session->set_flashdata('message', 'Cannot activate account. Code didnt match');
          }

          redirect('auth/login');

    }

		public function proseslogin()
		{
			$email = $this->input->post('email');
			$pwd = $this->input->post('password');

			$password = sha1($pwd);

			$cek = $this->auth_model->testlogin($email,$password)->num_rows();
			
			$p_login = $this->auth_model->plogin($email,$password);
			foreach($p_login as $p){
				$username = $p->username;
				$iduser = $p->iduser;
				$nama = $p->nama;
				$email = $p->email;
        $notelp = $p->notelp;
				$aktif = $p->aktif;
				$level = "admin";
			}

			if($cek > 0){

                if ($aktif == 'n') {
                    $this->session->set_flashdata('salah', 'your account is not active, please check your email to activate it');
                    redirect('auth/login');
                }else{
				$data_session = array(
					'username' 	=> $username,
					'nama'		=> $nama,
					'iduser'	=> $iduser,
					'email'		=> $email,
					'notelp'	=> $notelp,
					'level'		=> $level,
					'status'	=> "login"
					);
                    $this->session->set_userdata('logged_in',$data_session);
                    redirect(base_url('home/welcome'));	
                }
			}else{
                $this->session->set_flashdata('salah', 'Your username or password are wrong, please try again');
                redirect('auth/login');

            }
		}
	 
		public function logout()
		{
			$this->session->sess_destroy();
			$this->session->unset_userdata('username');
			$this->session->unset_userdata('nama');
			$this->session->unset_userdata('is_login');
			redirect('auth/login');
		}

		public function emailresetpassword() 
        {
            $data['kategori'] = $this->product_model->kategori();
            $data['sub_kategori'] = $this->product_model->all_sub_kategori();
            $data['sub_kategori'] = $this->product_model->all_sub_kategori();
            $email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
            $where = array('invoice.email'=>$email);
            $data['invo'] = $this->invoice_model->cekstatuspay($where);
            $data['bannermenu'] = $this->fitur_model->bannermenu();
            $data['titel'] = '| Reset Password';

            $this->load->view('tmplt/header',$data);
            $this->load->view('emailresetpassword');
            $this->load->view('tmplt/footer');
        }

        public function emailresetpasswordvalidation()
        {
            $this->form_validation->set_rules('email', 'Email', 'required|valid_email');
            if ($this->form_validation->run()) {
                
                $email = $this->input->post('email');
                $char = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','w','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
                shuffle($char);

                $num_rows = 10;
                $token = '';
                for($i=0;$i<$num_rows;$i++){
                    $token .= $char[mt_rand(0,$num_rows)];
                }   
                $resetkey='132'.$token;
                // $resetkey = random_string('alnum', 50);

                if ($this->auth_model->updateresetkey($email,$resetkey)) {

                
                    $this->load->library('email');
                    $config = array();
                    $config['charset'] = 'utf-8';
                    $config['useragent'] = 'Codeigniter';
                    $config['protocol']= "smtp";
                    $config['mailtype']= "html";
                    $config['smtp_host']= "ssl://mail.alsharifshop.com";//pengaturan smtp
                    $config['smtp_port']= "465";
                    $config['smtp_timeout']= "5";
                    $config['smtp_user']= "info@alsharifshop.com"; // isi dengan email kamu
                    $config['smtp_pass']= "alsharifshop26"; // isi dengan password kamu
                    $config['crlf']="\r\n"; 
                    $config['newline']="\r\n"; 
                    $config['wordwrap'] = TRUE;
                    //memanggil library email dan set konfigurasi untuk pengiriman email
                        
                    $this->email->initialize($config);
                    //konfigurasi pengiriman
                    $this->email->from($config['smtp_user']);
                    $this->email->to($email);
                    $this->email->subject("Reset your password");

                    $message =  "
                                <!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
                    <html xmlns='http://www.w3.org/1999/xhtml'>
                      <head>
                        <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
                        <title>Alsharif Shop - Email Reset Password</title>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'/>
                        <link href='https://fonts.googleapis.com/css?family=Nunito:400,600,700,800,900&display=swap' rel='stylesheet'>
                      </head>
                      <body style='margin: 0; padding: 0; box-sizing: border-box;'>
                        <table align='center' cellpadding='0' cellspacing='0' width='95%'>
                          <tr>
                            <td align='center'>
                              <table align='center' cellpadding='0' cellspacing='0' width='600' style='border-spacing: 2px 5px;' bgcolor='#fff'>
                                <tr>
                                  <td align='center' style='padding: 5px 5px 5px 5px;'>
                                    <a href='https://alsharifshop.com' target='_blank'>
                                      <img src='https://alsharifshop.com/asset/images/logoemail.png' alt='Logo' style='width:420px; margin: -100px -100px; border:0;'/>
                                    </a>
                                  </td>
                                </tr>
                                <tr>
                                  <td bgcolor='#fff'>
                                    <table cellpadding='0' cellspacing='0' width='100%%'>
                                      <tr>
                                        <td style='padding: 10px 0 10px 0; font-family: Nunito, sans-serif; font-size: 20px; font-weight: 900'>
                                          Reset Your Alsharif Shop Password
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                </tr>
                                <tr>
                                  <td bgcolor='#fff'>
                                    <table cellpadding='0' cellspacing='0' width='100%%'>
                                      <tr>
                                        <td style='padding: 0; font-family: Nunito, sans-serif; font-size: 16px;'>
                                          You make a request to reset the password. Click here to activate reset your password.
                                        </td>
                                      </tr>
                                      <tr>
                                        <td style='padding: 20px 0 20px 0; font-family: Nunito, sans-serif; font-size: 16px; text-align: center;'>
                                          <a href='https://alsharifshop.com/auth/resetpassword/".$resetkey."' style='background-color: #046106; border: none; color: white; padding: 15px 40px; text-align: center; display: inline-block; font-family: Nunito, sans-serif; font-size: 18px; font-weight: bold; cursor: pointer; text-decoration:none;'>
                                            Reset Password
                                          </a>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td style='padding: 0; font-family: Nunito, sans-serif; font-size: 16px;'>
                                          If you are having trouble clicking the 'Reset' button, click the link below
                                          <p id='url'><a href='https://alsharifshop.com/auth/resetpassword/".$resetkey."'>Link</a></p>
                                        </td>
                                      </tr>
                                      <tr>
                                        <td style='padding: 50px 0; font-family: Nunito, sans-serif; font-size: 16px;'>
                                          Regards,
                                          <br>
                                          <p>Alsharifshop</p>
                                        </td>
                                      </tr>
                                    </table>
                                  </td>
                                </tr>
                              </table>
                            </td>
                          </tr>
                        </table>
                      </body>
                    </html>
                    				";
                    // $message = "<p>Anda melakukan permintaan reset password</p>";
                    // $message .= "<a href='".site_url('auth/resetpassword/'.$resetkey)."'>klik reset password</a>";
                    $this->email->message($message);
                    
                    if($this->email->send())
                    {
                        $this->session->set_flashdata('emailreset','Please check your email '.$email.', to reset your password');
                        redirect('auth/emailresetpassword');
                    }else
                    {
                        echo "Berhasil melakukan registrasi, gagal mengirim verifikasi email";
                    }
                    
                    echo "<br><br><a href='".site_url('auth/login')."'>Kembali ke Menu Login</a>";

                }else {
                    $this->session->set_flashdata('emailresetfail','email has not been registered in the system, please create an account first');
                        redirect('auth/emailresetpassword');
                }
            }else{
                
                $data['kategori'] = $this->product_model->kategori();
                $data['sub_kategori'] = $this->product_model->all_sub_kategori();
                $data['sub_kategori'] = $this->product_model->all_sub_kategori();
                $email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
                $where = array('invoice.email'=>$email);
                $data['invo'] = $this->invoice_model->cekstatuspay($where);
                $data['bannermenu'] = $this->fitur_model->bannermenu();
                $data['titel'] = '| Reset Password';

                $this->load->view('tmplt/header',$data);
                $this->load->view('resetpassword');
                $this->load->view('tmplt/footer');
            }
        }

        public function resetpasswordvalidation() 
        {

            $this->form_validation->set_rules('password', 'password','trim|required|min_length[4]|max_length[255]');
            // $this->form_validation->set_rules('password', 'Password', 'required|min_length[4]|matches[retype_password]');
            // $this->form_validation->set_rules('retype_password', 'Retype Password', 'required|min_length[4]|matches[password]');

            if ($this->form_validation->run()) {
                $resetkey = $this->input->post('resetkey');
                $pwd = $this->input->post('password');

                $password = sha1($pwd);

                if ($this->auth_model->resetpassword($resetkey,$password)) {
                $this->session->set_flashdata('success_resetpass','Congratulations, your password has been successfully changed, please login to continue');
                redirect('auth/login');
                }else{
                    echo "error";
                }


            }else{
                
            $data['kategori'] = $this->product_model->kategori();
            $data['sub_kategori'] = $this->product_model->all_sub_kategori();
            $data['sub_kategori'] = $this->product_model->all_sub_kategori();
            $email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
            $where = array('invoice.email'=>$email);
            $data['invo'] = $this->invoice_model->cekstatuspay($where);
            $data['bannermenu'] = $this->fitur_model->bannermenu();
            $data['titel'] = '| Reset Password';

            $this->load->view('tmplt/header',$data);
            $this->load->view('resetpassword');
            $this->load->view('tmplt/footer');
            }
        }

        public function resetpassword() 
        {

            $resetkey = $this->uri->segment(3);

            if (!$resetkey) {
                die('Jangan Dihapus');
            }

            if ($this->auth_model->cekresetkey($resetkey) == 1) {
            $data['kategori'] = $this->product_model->kategori();
            $data['sub_kategori'] = $this->product_model->all_sub_kategori();
            $data['sub_kategori'] = $this->product_model->all_sub_kategori();
            $email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
            $where = array('invoice.email'=>$email);
            $data['invo'] = $this->invoice_model->cekstatuspay($where);
            $data['bannermenu'] = $this->fitur_model->bannermenu();
            $data['titel'] = '| Reset Password';

            $this->load->view('tmplt/header',$data);
            $this->load->view('resetpassword');
            $this->load->view('tmplt/footer');
            }else{
                die('reset key eror');
            }
        }


	}

