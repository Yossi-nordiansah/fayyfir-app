<?php
defined('BASEPATH') OR exit('No direct script access allowed');

	class Fitur extends CI_controller
	{		
		public function __construct()
        {
                parent::__construct();
		    	if(!isset($this->session->userdata['logged_admin']['status']) && $this->session->userdata['logged_admin']['level'] != 'admin')
				{
				redirect(base_url("admin/authadmin"));
				}
		}
		public function Gallery()
		{
			$data['gallery'] = $this->fitur_model->gallery();

			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/gallery',$data);
			$this->load->view('tmpltadmin/footer');
		}
		
    public function tambahgallery()
    {
        // Ambil data dari form
        $nama_gambar   = $this->input->post('nama_gambar');
        $desc_indo     = $this->input->post('desc_indo');
        $desc_english  = $this->input->post('desc_english');
        $gambar_gallery = $_FILES['gambar_gallery']['name']; // ambil nama file asli
    
        // Pastikan ada file yang diupload
        if (!empty($gambar_gallery)) {
            // Konfigurasi upload
            $config['upload_path']   = './asset/images/gallery/';
            $config['allowed_types'] = 'jpg|jpeg|png|gif|webp';
            $config['max_size']      = 2048; // (opsional) max 2MB
            $config['file_name']     = time() . '_' . str_replace(' ', '_', $gambar_gallery); // ubah nama agar unik
    
            $this->load->library('upload', $config);
    
            // Proses upload
            if (!$this->upload->do_upload('gambar_gallery')) {
                // Jika gagal upload, tampilkan error
                $error = $this->upload->display_errors();
                echo "Gagal upload gambar: " . $error;
                return;
            } else {
                // Jika berhasil upload, ambil nama file yang disimpan
                $uploaded_data = $this->upload->data();
                $gambar_gallery = $uploaded_data['file_name'];
            }
        } else {
            // Jika tidak ada file diupload
            $gambar_gallery = ''; 
        }
    
        // Data untuk disimpan ke database
        $data = array(
            'nama_gambar'    => $nama_gambar,
            'gambar_gallery' => $gambar_gallery,
            'desc_indo'      => $desc_indo,
            'desc_english'   => $desc_english,
            'status'         => 'y' // default aktif
        );
    
        // Simpan ke database
        $this->db->insert('gallery', $data);
    
        // Redirect ke halaman gallery admin
        redirect('admin/fitur/gallery');
    }
    
    public function deletefoto($id)
    {
        // Pastikan ID valid
        if (!$id) {
            show_error('ID foto tidak ditemukan');
            return;
        }
    
        // Ambil data foto berdasarkan ID
        $foto = $this->db->get_where('gallery', ['id_gambar' => $id])->row();
    
        if ($foto) {
            // Cek apakah file gambar masih ada di folder
            $path = './asset/images/gallery/' . $foto->gambar_gallery;
            if (file_exists($path) && is_file($path)) {
                unlink($path); // hapus file fisik
            }
    
            // Hapus data dari database
            $this->db->where('id_gambar', $id);
            $this->db->delete('gallery');
    
            // Redirect kembali ke halaman gallery
            redirect('admin/fitur/gallery');
        } else {
            // Jika data tidak ditemukan
            show_error('Data foto tidak ditemukan di database');
        }
    }
		
		public function Banner()
		{
			$data['gambar_banner'] = $this->fitur_model->gambar_banner();
			$data['bannerutama'] = $this->fitur_model->bannerutama();
			$data['bannermenu'] = $this->fitur_model->bannermenu();
			$data['bannertengah'] = $this->fitur_model->bannertengah();

			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/banner',$data);
			$this->load->view('tmpltadmin/footer');
		}

		public function tambahbanner()
		{
			$kategori 	= $this->input->post('kategori');
			$mobile 	= $this->input->post('mobile');
			$gambar 	= $_FILES['gambar'];
			if ($gambar='') {}
			else 
			{
				$config ['upload_path'] = './asset/images/banner';
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
					'gambar' 		=> $gambar,
					'kategori' 		=> $kategori,
					'mobile' 		=> $mobile
				);
				
				$this->db->insert('gambar_banner',$data);
				redirect('admin/fitur/banner');
		}

		public function deleteimg($id)
		{
			$where = array('id' => $id);
			$row = $this->db->where('id',$id)->get('gambar_banner')->row();
			unlink('asset/images/banner/'.$row->gambar);

			$this->product_model->hapus_gambar($where, 'gambar_banner');
			redirect('admin/fitur/banner');
		}
		public function voucher()
		{
			$data['voucher'] = $this->fitur_model->voucher();
		
			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/voucher', $data);
			$this->load->view('tmpltadmin/footer');
		}
		public function detailvoucher($kode)
		{
			$data['detvoucher'] = $this->fitur_model->detailvoucher($kode);
		
			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/detailvoucher', $data);
			$this->load->view('tmpltadmin/footer');
		}
		public function generatevoucher()
		{
			$char = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','w','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
			shuffle($char);

			$num_rows = 8;
			$token = '';
			for($i=0;$i<$num_rows;$i++){
				$token .= $char[mt_rand(0,$num_rows)];
			}	
			$hasil = $token;

			echo $hasil;
		}	

		public function tambahvoucher()
		{
			$iduser 	= $this->input->post('iduser');
			$kodevoucher 	= $this->input->post('kodevoucher');
			$diskon 	= $this->input->post('diskon');
		
			$data = array
			(
				'iduser' 	=> $iduser,
				'kodevoucher' 	=> $kodevoucher,
				'diskon' 	=> $diskon
			);

			$this->db->insert('voucher', $data);
			redirect('admin/fitur/voucher');
		}

		public function nonaktifvoucher($id)
		{
			$data = array
			(
				'status' => 'n'
			);

			$where = array
			(
			'idvoucher' => $id
			);

			$this->product_model->update_kategori($where,$data, 'voucher');
			redirect('admin/fitur/voucher');
		}
		public function aktifvoucher($id)
		{
			$data = array
			(
				'status' => 'y'
			);

			$where = array
			(
			'idvoucher' => $id
			);

			$this->product_model->update_kategori($where,$data, 'voucher');
			redirect('admin/fitur/voucher');
		}

		public function promo()
		{
			$data['promo'] = $this->fitur_model->promo();
			$data['kategori'] = $this->product_model->kategori();
			$data['product'] = $this->product_model->product();

			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/promo',$data);
			$this->load->view('tmpltadmin/footer');
		}
		public function tambahpromo()
		{
			$tglawal 	= $this->input->post('tglawal');
			$tglakhir 	= $this->input->post('tglakhir');
			$diskon 	= $this->input->post('diskon');
		
			$data = array
			(
				'tgl_mulai' 	=> $tglawal,
				'tgl_selesai' 	=> $tglakhir,
				'diskon' 	=> $diskon
			);

			$this->db->insert('promo', $data);
			redirect('admin/fitur/promo');
		}
		public function update_promo()
		{
			$id_promo 	= $this->input->post('id_promo');
			$tglawal 	= $this->input->post('tglawal');
			$tglakhir 	= $this->input->post('tglakhir');
			$diskon 	= $this->input->post('diskon');
		
			$data = array
			(
				'tgl_mulai' 	=> $tglawal,
				'tgl_selesai' 	=> $tglakhir,
				'diskon' 	=> $diskon
			);
			$where = array
			(
			'id_promo' => $id_promo
			);

			$this->product_model->update_kategori($where,$data, 'promo');
			redirect('admin/fitur/promo');
		}
		public function tambahlistpromo()
		{
			$idpromo 	= $this->input->post('idpromo');
			$id_produk 	= $this->input->post('idproduk');
			
			foreach ($id_produk as $idpro) {
				$this->db->insert('list_promo', array(
				"id_promo"	=> $idpromo,
				"id_product"	=> $idpro,
				));
			}

			redirect('admin/fitur/promo');
		}
	}

	