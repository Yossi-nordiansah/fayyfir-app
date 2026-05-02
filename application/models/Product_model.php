<?php
  
	
  
	class Product_model extends CI_model
  
	{
  
		public function product()
  
		{
  
			$this->db->select('*');
  
			$this->db->from('product');
  
			$this->db->join('kategori','kategori.id_kategori = product.id_kategori');
  
			$this->db->join('sub_kategori','sub_kategori.id_sub_kategori = product.id_sub_kategori');
  
			$this->db->join('gambar_product','gambar_product.id_product = product.id_product');
  
			$this->db->where('product.status','y');
  
			$this->db->group_by('gambar_product.id_product');
  
			return $this->db->order_by('product.id_product','DESC')->get('')->result();
  
		}
  

  
		public function productoff()
  
		{
  
			$this->db->select('*');
  
			$this->db->from('product');
  
			$this->db->join('kategori','kategori.id_kategori = product.id_kategori');
  
			$this->db->join('sub_kategori','sub_kategori.id_sub_kategori = product.id_sub_kategori');
  
			$this->db->join('gambar_product','gambar_product.id_product = product.id_product');
  
			$this->db->where('product.status','n');
  
			$this->db->group_by('gambar_product.id_product');
  
			return $this->db->order_by('product.id_product','DESC')->get('')->result();
  
		}
  

  
		public function producthome()
  
		{
  
			$this->db->select('*');
  
			$this->db->from('product');
  
			$this->db->join('kategori','kategori.id_kategori = product.id_kategori');
  
			$this->db->join('sub_kategori','sub_kategori.id_sub_kategori = product.id_sub_kategori');
  
			$this->db->join('gambar_product','gambar_product.id_product = product.id_product');
  
			$this->db->where('product.status','y');
  
			$this->db->where('product.new','y');
  
			$this->db->group_by('gambar_product.id_product');
  
			return $this->db->order_by('rand()')->get('',8)->result();
  
		}
  

  
		public function productsearch($keyword)
  
		{
  
			$this->db->select('*');
  
			$this->db->from('product');
  
			$this->db->join('kategori','kategori.id_kategori = product.id_kategori');
  
			$this->db->join('sub_kategori','sub_kategori.id_sub_kategori = product.id_sub_kategori');
  
			$this->db->join('gambar_product','gambar_product.id_product = product.id_product');
  
			$this->db->like('nama_product',$keyword);
  
			$this->db->or_like('nama_kategori',$keyword);
  
			$this->db->where('product.status','y');
  
			$this->db->group_by('gambar_product.id_product');
  
			return $this->db->order_by('product.id_product','DESC')->get('')->result();
  
		}
  

  
		public function productbest()
  
		{
  
			$this->db->select('*');
  
			$this->db->from('product');
  
			$this->db->join('kategori','kategori.id_kategori = product.id_kategori');
  
			$this->db->join('sub_kategori','sub_kategori.id_sub_kategori = product.id_sub_kategori');
  
			$this->db->join('gambar_product','gambar_product.id_product = product.id_product');
  
			$this->db->where('product.status','y');
  
			$this->db->where('product.best_seller','y');
  
			$this->db->group_by('gambar_product.id_product');
  
			return $this->db->order_by('rand()')->get('',12)->result();
  
		}
  

  
		public function promo($idproduk)
  
		{
  
			$this->db->select('*');
  
		    $this->db->from('list_promo');
  
		    $this->db->join('promo','promo.id_promo = list_promo.id_promo');
  
		    $this->db->where("promo.tgl_mulai <= CURDATE() and promo.tgl_selesai >= CURDATE()");
  
		    // $this->db->where_in('list_promo.id_product', $idproduk);
  
		    return $this->db->get('')->result();
  
		}
  

  
		public function gambar_product($where,$table)
  
		{
  
			$this->db->where($where);
  
			return $this->db->get($table)->result();
  
		}
  

  
		public function kategori()
  
		{
  
			$this->db->where('status', 'y');
  
			return $this->db->get('kategori')->result();
  
		}
  
    public function add_gambar_kategori($data)
    {
        $this->db->insert('gambar_kategori', $data);
    }
    
    public function get_gambar_kategori_by_idkategori($id_kategori)
    {
        return $this->db->where('id_kategori', $id_kategori)->get('gambar_kategori')->result();
    }
    
    public function delete_gambar_kategori($id_gambar)
    {
        $this->db->where('id_gambar', $id_gambar)->delete('gambar_kategori');
    }
    
    public function add_gambar_kategori_proses2($data)
    {
        $this->db->insert('gambar_kategori_proses2', $data);
    }
    
    public function get_gambar_kategori_proses2_by_idkategori($id_kategori)
    {
        return $this->db->where('id_kategori', $id_kategori)->get('gambar_kategori_proses2')->result();
    }
    
    public function delete_gambar_kategori_proses2($id_gambar)
    {
        $this->db->where('id_gambar', $id_gambar)->delete('gambar_kategori_proses2');
    }
  
		public function uk2()
  
		{
  
			$this->db->where('id_product', 0);
  
			return $this->db->get('ukuran2')->result();
  
		}		
  

  
		public function kategorihome()
  
		{
  
			$this->db->where('status', 'y');
  
			return $this->db->order_by('rand()')->get('kategori')->result();
  
		}
  

  
		public function all_gambar()
  
		{
  
			return $this->db->get('gambar_product')->result();
  
		}
  
		// 🔹 Ambil semua gambar proses (Our Process) berdasarkan id_kategori
    public function get_gambar_kategori_by_id($id_kategori)
    {
        return $this->db->where('id_kategori', $id_kategori)
                        ->get('gambar_kategori')
                        ->result();
    }
    
    // 🔹 (Optional) Ambil semua gambar kategori tanpa filter — digunakan di halaman index
    public function all_gambar_kategori()
    {
        return $this->db->get('gambar_kategori')->result();
    }
    
    // 🔹 Ambil semua gambar brand (Our Brad) berdasarkan id_kategori
    public function get_gambar_kategori_proses2_by_id($id_kategori)
    {
        return $this->db->where('id_kategori', $id_kategori)
                        ->get('gambar_kategori_proses2')
                        ->result();
    }
    
    // 🔹 (Optional) Ambil semua gambar_kategori_proses2 tanpa filter — digunakan di halaman index
    public function all_gambar_kategori_proses2()
    {
        return $this->db->get('gambar_kategori_proses2')->result();
    }
  
		public function all_sub_kategori()
  
		{
  
			$this->db->where('status', 'y');
  
			return $this->db->get('sub_kategori')->result();
  
		}
  

  
		public function sub_kategori($kategori)
  
		{
  
			$this->db->where('id_kategori',$kategori);
  
			$this->db->where('status', 'y');
  
			return $this->db->get('sub_kategori')->result();
  
		}
  
		public function sub_kategori_by_id($kategori)
  
		{
  
			$this->db->where('id_kategori',$kategori);
  
			$this->db->where('status', 'y');
  
			return $this->db->get('sub_kategori')->result();
  
		}
  
		public function add_kategori($data,$table)
  
		{
  
			 $this->db->insert($table, $data);
  
		}
  
		public function update_kategori($where,$data,$table)
  
		{
  
			$this->db->where($where);
  
			$this->db->update($table,$data);
  
		}
  
		public function delete_kategori($where,$data,$table)
  
		{
  
			$this->db->where($where);
  
			$this->db->update($table,$data);
  
		}
  
		public function add_sub_kategori($data,$table)
  
		{
  
			 $this->db->insert($table, $data);
  
		}
  
		public function add_product($data,$table)
  
		{
  
			 $this->db->insert($table, $data);
  
			 return $this->db->insert_id();
  
		}
  
		public function edit_product($where,$table)
  
		{
  
			return $this->db->get_where($table, $where);
  
		}
  
		public function ukuran2($where,$table)
  
		{
  
			return $this->db->get_where($table, $where);
  
		}
  
		public function update_product($where,$data,$table)
  
		{
  
			$this->db->where($where);
  
			$this->db->update($table,$data);
  
		}
  
		public function delete_product($where,$data,$table)
  
		{
  
			$this->db->where($where);
  
			$this->db->update($table,$data);
  
		}
  
		public function hapus_gambar($where,$table)
  
		{
  
			 $this->db->where($where);
  
			 $this->db->delete($table);
  
		}
  

  
		public function seleksi_product($nama_kategori,$nama_sub_kategori)
  
		{
  

  
			if($nama_sub_kategori == NULL)
  
			{
  
				$where= array('nama_kategori'=>$nama_kategori);
  
			}
  
			else{
  
				$where = array(	'nama_kategori'=>$nama_kategori,
  
									'nama_sub_kategori'=>$nama_sub_kategori);
  
			}
  
			$this->db->select('*');
  
			$this->db->from('product');
  
			$this->db->join('kategori','kategori.id_kategori = product.id_kategori');
  
			$this->db->join('sub_kategori','sub_kategori.id_sub_kategori = product.id_sub_kategori');
  
			$this->db->join('gambar_product','gambar_product.id_product = product.id_product');
  
			$this->db->where('product.status','y');
  
			$this->db->where($where);
  
			$this->db->group_by('product.nama_product');
  
			$this->db->group_by('gambar_product.id_product');
  
			return $this->db->order_by('rand()')->get('')->result();
  
		}
  

  
		public function detail_product($id)
  
		{
  

  
			$this->db->select('*');
  
			$this->db->from('product');
  
			$this->db->join('kategori','kategori.id_kategori = product.id_kategori');
  
			$this->db->join('sub_kategori','sub_kategori.id_sub_kategori = product.id_sub_kategori');
  
			$this->db->join('gambar_product','gambar_product.id_product = product.id_product');
  
			$this->db->where('product.status','y');
  
			$this->db->where(array('product.id_product' => $id));
  
			return $this->db->get('')->result();
  
		}
  

  
		public function opsi_product($nama_kategori)
  
		{
  

  
			$this->db->select('*');
  
			$this->db->from('product');
  
			$this->db->join('kategori','kategori.id_kategori = product.id_kategori');
  
			$this->db->join('sub_kategori','sub_kategori.id_sub_kategori = product.id_sub_kategori');
  
			$this->db->join('gambar_product','gambar_product.id_product = product.id_product');
  
			$this->db->where('product.status','y');
  
			$this->db->where('product.new','y');
  
			$this->db->or_where('product.best_seller','y');
  
			$this->db->where(array('nama_kategori' => $nama_kategori));
  
			return $this->db->order_by('rand()')->get('')->result();
  
			// return $this->db->get('')->result();
  
		}
  

  
		public function ambiluk2()
  
		{
  
			return $this->db->get('ukuran2')->result();
  
		}
  

  
		public function ambiluk2besar()
  
		{
  
			$this->db->select('*');
  
			$this->db->from('ukuran2');
  
			return $this->db->order_by('ukuran2.id_ukuran2', 'DESC')->get('')->result();
  
		}
  

  
		public function findid($id_product)
  
		{
  
			$this->db->select('*');
  
			$this->db->from('product');
  
			$this->db->join('kategori','kategori.id_kategori = product.id_kategori');
  
			$this->db->join('sub_kategori','sub_kategori.id_sub_kategori = product.id_sub_kategori');
  
			$this->db->join('gambar_product','gambar_product.id_product = product.id_product');
  
			$this->db->where('product.status','y');
  
			$this->db->where('.product.id_product', $id_product);
  
			$this->db->limit(1);
  

  
			$result = $this->db->get('');
  
			
  
			if ($result->num_rows() > 0) {
  
				return $result->row();
  
			}
  
			else{
  
				return array();
  
			}
  
		}
  
	}
  
?>