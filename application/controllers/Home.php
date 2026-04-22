<?php
defined('BASEPATH') OR exit('No direct script access allowed');
	// use Xendit\Xendit;
	class Home extends CI_controller
	{		
		 public function __construct()
        	{
                parent::__construct();
		    
		}

		public function index()
		{	
			$ip    = $this->input->ip_address(); // Mendapatkan IP user
			$date  = date("Y-m-d"); // Mendapatkan tanggal sekarang
			$waktu = time(); //
			$timeinsert = date("Y-m-d H:i:s");
			  
			// Cek berdasarkan IP, apakah user sudah pernah mengakses hari ini
			$s = $this->db->query("SELECT * FROM pengunjung WHERE ip='".$ip."' AND date='".$date."'")->num_rows();
			$ss = isset($s)?($s):0;
			  
			 
			// Kalau belum ada, simpan data user tersebut ke database
			if($ss == 0){
			$this->db->query("INSERT INTO pengunjung(ip, date, hits, online, time) VALUES('".$ip."','".$date."','1','".$waktu."','".$timeinsert."')");
			}
			 
			// Jika sudah ada, update
			else{
			$this->db->query("UPDATE pengunjung SET hits=hits+1, online='".$waktu."' WHERE ip='".$ip."' AND date='".$date."'");
			}

			$data['kategori'] = $this->product_model->kategori();
			$data['kategorihome'] = $this->product_model->kategorihome();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
			$where = array('invoice.email'=>$email);
			$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['uk2'] = $this->product_model->ambiluk2besar();
			
			$data['product'] = $this->product_model->producthome();
    
		    $idproduk = array();
			    foreach ($data['product'] as $key => $value) {
			        // array_push($idproduk,$value['idproduk']);
			        // atau
			        array_push($idproduk,$value->id_product);
			    }
			    $data['promo'] = $this->product_model->promo($idproduk);

			    foreach ($data['product'] as $key => $value) {

			        foreach ($data['promo'] as $promo_value) {
			            if($promo_value->id_product ==  $value->id_product) {
			                $data['product'][$key]->promo = $promo_value;
			            }
			        }

			    }

			$data['productbest'] = $this->product_model->productbest();
			 $idproduk = array();
			    foreach ($data['productbest'] as $key => $value) {
			        // array_push($idproduk,$value['idproduk']);
			        // atau
			        array_push($idproduk,$value->id_product);
			    }
			    $data['promo'] = $this->product_model->promo($idproduk);

			    foreach ($data['productbest'] as $key => $value) {

			        foreach ($data['promo'] as $promo_value) {
			            if($promo_value->id_product ==  $value->id_product) {
			                $data['productbest'][$key]->promo = $promo_value;
			            }
			        }

			    }

			$data['gambar_product'] = $this->product_model->all_gambar();
			$data['gambar_banner'] = $this->fitur_model->gambar_banner();
			$data['bannerutamav1'] = $this->fitur_model->bannerutamav1();
			$data['bannertengahv1'] = $this->fitur_model->bannertengahv1();
			$data['bannertengahv2'] = $this->fitur_model->bannertengahv2();
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			


			$this->load->view('tmplt/header',$data);
			$this->load->view('index',$data);
			$this->load->view('tmplt/footer');
		}

		public function search()
		{
			$keyword = $this->input->post('keyword');
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
			$where = array('invoice.email'=>$email);
			$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();

			$data['product'] = $this->product_model->productsearch($keyword);
			 $idproduk = array();
			    foreach ($data['product'] as $key => $value) {
			        // array_push($idproduk,$value['idproduk']);
			        // atau
			        array_push($idproduk,$value->id_product);
			    }
			    $data['promo'] = $this->product_model->promo($idproduk);

			    foreach ($data['product'] as $key => $value) {

			        foreach ($data['promo'] as $promo_value) {
			            if($promo_value->id_product ==  $value->id_product) {
			                $data['product'][$key]->promo = $promo_value;
			            }
			        }

			    }
			$data['gambar_product'] = $this->product_model->all_gambar();
			$data['bannermenu'] = $this->fitur_model->bannermenu();

			$this->load->view('tmplt/header',$data);
			$this->load->view('search',$data);
			$this->load->view('tmplt/footer');
		}

		public function notfound()
		{	
			$data['kategori'] = $this->product_model->kategori();
			$data['kategorihome'] = $this->product_model->kategorihome();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();
			$email = isset($this->session->userdata['logged_in']['email'])?$this->session->userdata['logged_in']['email'] : '';
			$where = array('invoice.email'=>$email);
			$data['invo'] = $this->invoice_model->cekstatuspay($where);
			$data['suminvo'] = $this->invoice_model->suminvo($where);
			
			$data['product'] = $this->product_model->producthome();
			$data['productbest'] = $this->product_model->productbest();
			$data['gambar_product'] = $this->product_model->all_gambar();
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			


			$this->load->view('tmplt/header',$data);
			$this->load->view('404',$data);
			$this->load->view('tmplt/footer');
		}

		public function welcome()
		{	
			// $data['kategori'] = $this->product_model->kategori();
			// $data['kategorihome'] = $this->product_model->kategorihome();
			// $data['sub_kategori'] = $this->product_model->all_sub_kategori();
			// $data['invo'] = $this->invoice_model->cekstatuspay();
		
			$this->load->view('welcome');
		
		}

		public function confirm_payment()
		{	
			$this->load->view('konfirmpayment');
		}

		public function proses_konfirm_payment()
		{
			$idinvoice 	= $this->input->post('idinvoice');
			$atasnama 	= $this->input->post('atasnama');
			$via 		= $this->input->post('via');
			$tanggal 	= $this->input->post('tanggal');
			$bayar 		= $this->input->post('bayar');
			$gambar 	= $_FILES['gambar'];

			if ($gambar='') {}
			else 
			{
				$config ['upload_path'] = './asset/images/buktitf';
				$config ['allowed_types'] = 'jpg|jpeg|png|gif';

				$this->load->library('upload',$config);
				if(!$this->upload->do_upload('gambar'))
				{
					echo "Gambar Gagal di Upload";
				}
				else
				{
					$gambar = $this->upload->data('file_name');
				}
			}
			
				$data = array
				(
					'idinvoice' 	=> $idinvoice,
					'atasnama' 		=> $atasnama,
					'via' 			=> $via,
					'tanggal' 		=> $tanggal,
					'bayar' 		=> $bayar,
					'bukti' 		=> $gambar
				);

				$this->db->insert('konfirm_payment', $data);
      			$this->session->set_flashdata('bukti','proof of your payment has been successfully sent');
				redirect('home/confirm_payment');
		}
	}
?>