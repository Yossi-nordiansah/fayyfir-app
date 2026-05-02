<?php

	class Kategori extends CI_controller
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
			$data['kategori'] = $this->product_model->kategori();
			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/kategori',$data);
			$this->load->view('tmpltadmin/footer');
		}

		public function sub_kategori()
		{
		    $id_kategori = $this->input->post('id_kategori');
		    $sub = $this->product_model->sub_kategori($id_kategori);
		    echo json_encode($sub);
		}

    public function tambah_kategori()
    {
        // ambil input (pastikan nama variabel konsisten)
        $nama_kategori   = $this->input->post('nama_kategori');
        $desc_ind        = $this->input->post('desc_ind');
        $desc_eng        = $this->input->post('desc_eng');
        $specifications  = $this->input->post('specifications');
    
        // ✅ pastikan library upload sudah siap
        $this->load->library('upload');
    
        // =========================
        // 🔸 Upload Gambar Utama
        // =========================
        $gambar_filename = ''; // default kosong
        if (!empty($_FILES['gambar']['name'])) {
            $config = [
                'upload_path'   => './asset/images/categories/',
                'allowed_types' => 'jpg|jpeg|png|gif',
                'encrypt_name'  => true,
                'max_size'      => 2048 // 2MB
            ];
    
            $this->upload->initialize($config);
            if ($this->upload->do_upload('gambar')) {
                $gambar_filename = $this->upload->data('file_name');
            } else {
                // opsional: bisa tambahkan notifikasi error upload
                $gambar_filename = '';
            }
        }
    
        // =========================
        // 🔸 Upload Banner Kategori
        // =========================
        $banner_filename = '';
        if (!empty($_FILES['banner_kategori']['name'])) {
            $config = [
                'upload_path'   => './asset/images/categories/',
                'allowed_types' => 'jpg|jpeg|png|gif',
                'encrypt_name'  => true,
                'max_size'      => 4096 // 4MB
            ];
    
            $this->upload->initialize($config);
            if ($this->upload->do_upload('banner_kategori')) {
                $banner_filename = $this->upload->data('file_name');
            } else {
                $banner_filename = '';
            }
        }
    
        // =========================
        // 🔸 Simpan Kategori ke DB
        // =========================
        $data = [
            'nama_kategori'   => $nama_kategori,
            'desc_ind'        => $desc_ind,
            'desc_eng'        => $desc_eng,
            'specifications'  => $specifications,
            'gambar'          => $gambar_filename,
            'banner_kategori' => $banner_filename,
            'status'          => 'y'
        ];
    
        $this->product_model->add_kategori($data, 'kategori');
        $insert_id = $this->db->insert_id();
    
        // =========================
        // 🔸 Upload Gambar Gallery
        // =========================
        if (!empty($_FILES['gambar_kategori']['name'][0])) {
            $files = $_FILES['gambar_kategori'];
            $count = count($files['name']);
    
            for ($i = 0; $i < $count; $i++) {
                if (empty($files['name'][$i])) continue;
    
                $_FILES['file']['name']     = $files['name'][$i];
                $_FILES['file']['type']     = $files['type'][$i];
                $_FILES['file']['tmp_name'] = $files['tmp_name'][$i];
                $_FILES['file']['error']    = $files['error'][$i];
                $_FILES['file']['size']     = $files['size'][$i];
    
                $config = [
                    'upload_path'   => './asset/images/categories/',
                    'allowed_types' => 'jpg|jpeg|png|gif',
                    'encrypt_name'  => true,
                    'max_size'      => 4096
                ];
    
                $this->upload->initialize($config);
                if ($this->upload->do_upload('file')) {
                    $file_name = $this->upload->data('file_name');
                    $this->product_model->add_gambar_kategori([
                        'id_kategori'     => $insert_id,
                        'gambar_kategori' => $file_name
                    ]);
                }
            }
        }
        
        // =========================
        // 🔸 Upload Gambar Brand
        // =========================
        if (!empty($_FILES['gambar_kategori_proses2']['name'][0])) {
            $files = $_FILES['gambar_kategori_proses2'];
            $count = count($files['name']);
    
            for ($i = 0; $i < $count; $i++) {
                if (empty($files['name'][$i])) continue;
    
                $_FILES['file']['name']     = $files['name'][$i];
                $_FILES['file']['type']     = $files['type'][$i];
                $_FILES['file']['tmp_name'] = $files['tmp_name'][$i];
                $_FILES['file']['error']    = $files['error'][$i];
                $_FILES['file']['size']     = $files['size'][$i];
    
                $config = [
                    'upload_path'   => './asset/images/categories/',
                    'allowed_types' => 'jpg|jpeg|png|gif',
                    'encrypt_name'  => true,
                    'max_size'      => 4096
                ];
    
                $this->upload->initialize($config);
                if ($this->upload->do_upload('file')) {
                    $file_name = $this->upload->data('file_name');
                    $this->product_model->add_gambar_kategori_proses2([
                        'id_kategori'     => $insert_id,
                        'gambar_kategori_proses2' => $file_name
                    ]);
                }
            }
        }
    
        redirect('admin/kategori');
    }
    
    public function update_kategori()
    {
        $id_kategori = $this->input->post('id_kategori');
        $nama_kategori = $this->input->post('nama_kategori');
        $desc_ind = $this->input->post('desc_ind');
        $desc_eng = $this->input->post('desc_eng');
        $specifications = $this->input->post('specifications');
    
        // ✅ Pastikan library upload sudah di-load
        $this->load->library('upload');
    
        // 🔹 Ambil data lama dari database
        $row = $this->db->where('id_kategori', $id_kategori)->get('kategori')->row();
    
        // =========================
        // 🔸 Upload Gambar Utama
        // =========================
        $gambar_filename = $row->gambar; // default: gambar lama
        if (!empty($_FILES['gambar']['name'])) {
            // hapus gambar lama jika ada
            $old_path = './asset/images/categories/' . $row->gambar;
            if (!empty($row->gambar) && file_exists($old_path)) {
                @unlink($old_path);
            }
    
            $config = [
                'upload_path'   => './asset/images/categories/',
                'allowed_types' => 'jpg|jpeg|png|gif',
                'encrypt_name'  => true,
                'max_size'      => 2048, // 2MB
            ];
    
            $this->upload->initialize($config);
            if ($this->upload->do_upload('gambar')) {
                $gambar_filename = $this->upload->data('file_name');
            }
        }
    
        // =========================
        // 🔸 Upload Banner Kategori
        // =========================
        $banner_filename = $row->banner_kategori; // default: banner lama
        if (!empty($_FILES['banner_kategori']['name'])) {
            // hapus banner lama jika ada
            $old_banner = './asset/images/categories/' . $row->banner_kategori;
            if (!empty($row->banner_kategori) && file_exists($old_banner)) {
                @unlink($old_banner);
            }
    
            $config = [
                'upload_path'   => './asset/images/categories/',
                'allowed_types' => 'jpg|jpeg|png|gif',
                'encrypt_name'  => true,
                'max_size'      => 4096, // 4MB
            ];
    
            $this->upload->initialize($config);
            if ($this->upload->do_upload('banner_kategori')) {
                $banner_filename = $this->upload->data('file_name');
            }
        }
    
        // =========================
        // 🔸 Simpan Perubahan ke DB
        // =========================
        $data = [
            'nama_kategori'   => $nama_kategori,
            'desc_ind'        => $desc_ind,
            'desc_eng'        => $desc_eng,
            'specifications'  => $specifications,
            'gambar'          => $gambar_filename,
            'banner_kategori' => $banner_filename,
        ];
    
        $where = ['id_kategori' => $id_kategori];
        $this->product_model->update_kategori($where, $data, 'kategori');
    
        // =========================
        // 🔸 Upload Gallery Tambahan
        // =========================
        if (!empty($_FILES['gambar_kategori']['name'][0])) {
            $files = $_FILES['gambar_kategori'];
            $count = count($files['name']);
    
            for ($i = 0; $i < $count; $i++) {
                if (empty($files['name'][$i])) continue;
    
                $_FILES['file']['name']     = $files['name'][$i];
                $_FILES['file']['type']     = $files['type'][$i];
                $_FILES['file']['tmp_name'] = $files['tmp_name'][$i];
                $_FILES['file']['error']    = $files['error'][$i];
                $_FILES['file']['size']     = $files['size'][$i];
    
                $config = [
                    'upload_path'   => './asset/images/categories/',
                    'allowed_types' => 'jpg|jpeg|png|gif',
                    'encrypt_name'  => true,
                    'max_size'      => 4096,
                ];
    
                $this->upload->initialize($config);
                if ($this->upload->do_upload('file')) {
                    $file_name = $this->upload->data('file_name');
    
                    // simpan ke tabel gambar_kategori
                    $this->product_model->add_gambar_kategori([
                        'id_kategori'     => $id_kategori,
                        'gambar_kategori' => $file_name,
                    ]);
                }
            }
        }
        
        // =========================
        // 🔸 Upload Brand
        // =========================
        if (!empty($_FILES['gambar_kategori_proses2']['name'][0])) {
            $files = $_FILES['gambar_kategori_proses2'];
            $count = count($files['name']);
    
            for ($i = 0; $i < $count; $i++) {
                if (empty($files['name'][$i])) continue;
    
                $_FILES['file']['name']     = $files['name'][$i];
                $_FILES['file']['type']     = $files['type'][$i];
                $_FILES['file']['tmp_name'] = $files['tmp_name'][$i];
                $_FILES['file']['error']    = $files['error'][$i];
                $_FILES['file']['size']     = $files['size'][$i];
    
                $config = [
                    'upload_path'   => './asset/images/categories/',
                    'allowed_types' => 'jpg|jpeg|png|gif',
                    'encrypt_name'  => true,
                    'max_size'      => 4096,
                ];
    
                $this->upload->initialize($config);
                if ($this->upload->do_upload('file')) {
                    $file_name = $this->upload->data('file_name');
    
                    // simpan ke tabel gambar_kategori
                    $this->product_model->add_gambar_kategori_proses2([
                        'id_kategori'     => $id_kategori,
                        'gambar_kategori_proses2' => $file_name,
                    ]);
                }
            }
        }
    
        // 🔹 Kembali ke halaman kategori admin
        redirect('admin/kategori');
    }

		public function delete_kategori($id)
		{
				
		$data = array
		(
			'status' 		=> 'n',
		);

		$where = array
		(
			'id_kategori' => $id
		);

		$this->product_model->delete_kategori($where,$data, 'kategori');
		redirect('admin/kategori');
		}
		
		public function hapus_gambar_kategori($id_gambar, $id_kategori)
    {
        $img = $this->db->where('id_gambar', $id_gambar)->get('gambar_kategori')->row();
        if ($img) {
            if (!empty($img->gambar_kategori) && file_exists('./asset/images/categories/' . $img->gambar_kategori)) {
                @unlink('./asset/images/categories/' . $img->gambar_kategori);
            }
            $this->db->where('id_gambar', $id_gambar)->delete('gambar_kategori');
        }
        redirect('admin/kategori'); // atau kembali ke halaman edit id_kategori
    }

		public function tambah_sub_kategori()
		{
			$nama_sub_kategori 	= $this->input->post('nama_sub_kategori');
			$kategori 			= $this->input->post('kategori');
			
				$data = array
				(
					'nama_sub_kategori' 	=> $nama_sub_kategori,
					'id_kategori' 			=> $kategori
				);

	
				$this->product_model->add_sub_kategori($data, 'sub_kategori');
				redirect('admin/kategori');
		}


		
	}

?>