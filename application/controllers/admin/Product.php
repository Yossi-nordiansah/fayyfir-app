<?php

	class Product extends CI_controller
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
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();

			$data['product'] = $this->product_model->product();
			$data['productoff'] = $this->product_model->productoff();

			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/product',$data);
			$this->load->view('tmpltadmin/footer');
		}

		public function productoff()
		{
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->all_sub_kategori();


			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/productoff',$data);
			$this->load->view('tmpltadmin/footer');
		}

		public function edit($id_product,$id_kategori,$id_sub_kategori)
		{
			$where = array('id_product'=>$id_product);
			$data['ukuran2'] = $this->product_model->ukuran2($where,'ukuran2')->result();
			$data['product'] = $this->product_model->edit_product($where,'product')->result();
			$data['gambar'] = $this->product_model->gambar_product($where, 'gambar_product');
			$data['kategori'] = $this->product_model->kategori();
			$data['sub_kategori'] = $this->product_model->sub_kategori_by_id($id_kategori);
			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/editproduct',$data);
			$this->load->view('tmpltadmin/footer');

		}

		public function kategori()
		{
			$nama_kategori = rawurldecode($this->uri->segment(4));
			$nama_sub_kategori = rawurldecode($this->uri->segment(5));

		
			$data['product'] = $this->product_model->seleksi_product($nama_kategori,$nama_sub_kategori);
			$data['namakategori'] = $nama_kategori;
			$data['namasubkategori'] = $nama_sub_kategori;
			$data['gambar_product'] = $this->product_model->all_gambar();

			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/productkategori',$data);
			$this->load->view('tmpltadmin/footer');
		}

		public function addproduct()
		{

			$data['kategori'] = $this->product_model->kategori();
			$data['ukuran2'] = $this->product_model->uk2();
			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/addproduct',$data);
			$this->load->view('tmpltadmin/footer');
		}

		public function sub_kategori()
		{
		    $id_kategori = $this->input->post('id_kategori');
		    $sub = $this->product_model->sub_kategori($id_kategori);
		    echo json_encode($sub);
		}


		public function tambah_aksi()
		{
			$nama_product 	= $this->input->post('nama_product');
			$desc_id 		= $this->input->post('desc_id');
			$desc_en 		= $this->input->post('desc_en');
			$size_pro 		= $this->input->post('size_pro');
			$pack_pro 		= $this->input->post('pack_pro');
			$ship_pro 		= $this->input->post('ship_pro');
			$ukuran 		= $this->input->post('ukuran');
			$stok 			= $this->input->post('stok');
			$berat 			= $this->input->post('berat');
			$harga 			= $this->input->post('harga');
			$new 			= $this->input->post('new');
			$best_seller 	= $this->input->post('best_seller');
			$kategori 		= $this->input->post('kategori');
			$sub_kategori 	= $this->input->post('sub_kategori');

			$config ['upload_path'] = './asset/images/product';
			$config ['allowed_types'] = 'jpg|jpeg|png|gif';
			$new_name = time().$_FILES["userfiles"]['name'];
			$config['file_name'] = $new_name;


			$this->load->library('upload',$config);
			$jumlah_berkas = count($_FILES['gambar']['name']);

				
				$data = array
				(
					'nama_product' 		=> $nama_product,
					'desc_id' 			=> $desc_id,
					'desc_en' 			=> $desc_en,
					'size_pro'    => $size_pro,
					'pack_pro'    => $pack_pro,
					'ship_pro'    => $ship_pro,
					'ukuran' 			=> $ukuran,
					'stok' 				=> $stok,
					'berat' 			=> $berat,
					'harga' 			=> $harga,
					'new' 				=> $new,
					'best_seller' 		=> $best_seller,
					'id_kategori' 		=> $kategori,
					'id_sub_kategori' 	=> $sub_kategori
				);

				$id_product = $this->product_model->add_product($data, 'product');

				for($i = 0; $i < $jumlah_berkas;$i++){
					if(!empty($_FILES['gambar']['name'][$i])){
					
					$_FILES['file']['name'] = $_FILES['gambar']['name'][$i];
					$_FILES['file']['type'] = $_FILES['gambar']['type'][$i];
					$_FILES['file']['tmp_name'] = $_FILES['gambar']['tmp_name'][$i];
					$_FILES['file']['error'] = $_FILES['gambar']['error'][$i];
					$_FILES['file']['size'] = $_FILES['gambar']['size'][$i];
		   
					if($this->upload->do_upload('file')){
						
						$uploadData = $this->upload->data();

						$dataimg['gambar'] = $uploadData['file_name'];
						$dataimg['id_product'] = $id_product;
						$this->db->insert('gambar_product',$dataimg);
						}
					}


				$data = array
				(
					'id_product' 	=> $id_product
				);
				$where = array
				(
					'id_product' 	=> 0
				);
				$this->db->update('ukuran2',$data,$where);

				}	
				redirect('admin/product');
		}

		public function tambahuk2()
		{
			$ukuran 	= $this->input->post('ukuran');
			$harga 		= $this->input->post('harga');
			$stok 		= $this->input->post('stok');
			
				$data = array
				(
					'ukuran' 	=> $ukuran,
					'stok' 		=> $stok,
					'harga' 	=> $harga
				);

				$data = $this->product_model->add_kategori($data, 'ukuran2');
				echo json_encode($data);
		}

		public function dataukuran()
		{
			$data =  $this->product_model->uk2();
			echo json_encode($data);
		}


		public function update_aksi()
		{
			$back 			= $this->input->post('back');
			$id_product 		= $this->input->post('id_product');
			$nama_product 		= $this->input->post('nama_product');
			$desc_id 		= $this->input->post('desc_id');
			$desc_en 		= $this->input->post('desc_en');
			$size_pro 		= $this->input->post('size_pro');
			$pack_pro 		= $this->input->post('pack_pro');
			$ship_pro 		= $this->input->post('ship_pro');
			$ukuran 		= $this->input->post('ukuran');
			$stok 			= $this->input->post('stok');
			$berat 			= $this->input->post('berat');
			$harga 			= $this->input->post('harga');
			$new 			= $this->input->post('new');
			$best_seller 		= $this->input->post('best_seller');
			$kategori 		= $this->input->post('kategori');
			$sub_kategori 		= $this->input->post('sub_kategori');
				
				$data = array
				(
					'nama_product' 		=> $nama_product,
					'desc_id' 			=> $desc_id,
					'desc_en' 			=> $desc_en,
					'size_pro'    => $size_pro,
					'pack_pro'    => $pack_pro,
					'ship_pro'    => $ship_pro,
					'ukuran' 			=> $ukuran,
					'stok' 				=> $stok,
					'berat' 			=> $berat,
					'harga' 			=> $harga,
					'new' 				=> $new,
					'best_seller' 		=> $best_seller,
					'id_kategori' 		=> $kategori,
					'id_sub_kategori' 	=> $sub_kategori
				);

				$where = array
				(
				'id_product' => $id_product
				);

				$this->product_model->update_product($where,$data, 'product');

				redirect($back);
		}

		public function delete_aksi()
		{
			$id_product = $this->uri->segment('4');
				
				$data = array
				(
					'status' 		=> 'n',
				);

				$where = array
				(
				'id_product' => $id_product
				);

				$this->product_model->delete_product($where,$data, 'product');
				redirect('admin/product');
		}

		public function tambah_gambar()
		{
			$id_product 	= $this->input->post('id_product');
			$prev			= $this->input->post('prev');

			$config ['upload_path'] = './asset/images/product';
			$config ['allowed_types'] = 'jpg|jpeg|png|gif';
			$new_name = time().$_FILES["userfiles"]['name'];
			$config['file_name'] = $new_name;


			$this->load->library('upload',$config);
			$jumlah_berkas = count($_FILES['gambar']['name']);

				for($i = 0; $i < $jumlah_berkas;$i++){
					if(!empty($_FILES['gambar']['name'][$i])){
					
					$_FILES['file']['name'] = $_FILES['gambar']['name'][$i];
					$_FILES['file']['type'] = $_FILES['gambar']['type'][$i];
					$_FILES['file']['tmp_name'] = $_FILES['gambar']['tmp_name'][$i];
					$_FILES['file']['error'] = $_FILES['gambar']['error'][$i];
					$_FILES['file']['size'] = $_FILES['gambar']['size'][$i];
		   
					if($this->upload->do_upload('file')){
						
						$uploadData = $this->upload->data();

						$dataimg['gambar'] = $uploadData['file_name'];
						$dataimg['id_product'] = $id_product;
						$this->db->insert('gambar_product',$dataimg);
						}
					}
				}	
		        redirect($prev);
		}

		public function deleteimg($id,$prev)
		{
			$back = $prev;
			$where = array('id_gambar' => $id);
			$row = $this->db->where('id_gambar',$id)->get('gambar_product')->row();
			unlink('asset/images/product/'.$row->gambar);

			$this->product_model->hapus_gambar($where, 'gambar_product');
			redirect($back);
		}

		public function producton($id_product)
		{
				
				$data = array
				(
					'status' 		=> 'y',
				);

				$where = array
				(
				'id_product' => $id_product
				);
				
				$this->product_model->update_product($where,$data, 'product');
				redirect('admin/product');
		}

	}

?>