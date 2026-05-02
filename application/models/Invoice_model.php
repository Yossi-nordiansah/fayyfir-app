<?php
	
	class Invoice_model extends CI_model
	{
		public function index()
		{
		/*$char = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','w','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
		shuffle($char);

		$num_rows = 4;
		$token = '';
		for($i=0;$i<$num_rows;$i++){
			$token .= $char[mt_rand(0,$num_rows)];
		}	
		$token='ALSRF'.date('dmy').$token;

		date_default_timezone_set('Asia/Jakarta');
		$nama = $this->input->post('nama');
		$alamat = $this->input->post('alamat');
		$email = $this->input->post('email');
		$notelp = $this->input->post('notelp');
		$total = $this->input->post('total');

		$invoice = array(
			'id' 		=> $token,
			'nama'		=> $nama,
			'alamat'	=> $alamat,
			'notelp'	=> $notelp,
			'email'	=> $email,

			'totalbayar' => $total,
			'tglpesan'	=> date('Y-m-d H:i:s'),
			'btsbayar'	=> date('Y-m-d H:i:s', mktime(date('H'),date('i'),date('s'),date('m'),date('d') + 1 ,date('Y'))),
			'status'	=> 'n'
		);

		$this->db->insert('invoice', $invoice);
		$idinvoice = $this->db->insert_id();
		$the_session = array("token" => $token);
		$this->session->set_userdata($the_session);

		foreach ($this->cart->contents() as $items) {
			$data = array(
				'idinvoice'		=> $token,
				'id'			=> $items['id'],
				'nama'			=> $items['name'],
				'jumlah'		=> $items['qty'],
				'ukuran'		=> $items['ukuran'],
				'harga'			=> $items['price'],
			);

			$this->db->insert('pesanan', $data); 
		}
		return TRUE;
		*/
		}
		public function insert_invoice($totalbayar)
		{
			$char = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','w','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
			shuffle($char);

			$num_rows = 3;
			$token = '';
			for($i=0;$i<$num_rows;$i++){
				$token .= $char[mt_rand(0,$num_rows)];
			}	
			$token='AS'.date('dmy').$token;

			date_default_timezone_set('Asia/Jakarta');
			$nama = $this->session->userdata['logged_in']['nama'];
			$email = $this->session->userdata['logged_in']['email'];
			$totalbayar2 = $this->input->post('totaldisc');
			$potongan = $this->input->post('potongan');
			$voucher = $this->input->post('voucher');

			$invoice = array(
			'id' 		=> $token,
			'nama'		=> $nama,
			
		
			'email'	=> $email,

			'totalbayar' => $totalbayar,
			'potongan' => $potongan,
			'totalbayar2' => $totalbayar2,
			'kodevoucher' => $voucher,
			'tglpesan'	=> date('Y-m-d H:i:s'),
			'btsbayar'	=> date('Y-m-d H:i:s', mktime(date('H'),date('i'),date('s'),date('m'),date('d') + 1 ,date('Y'))),
			'status'	=> 'n'
		);
			
			$this->db->insert('invoice', $invoice);
		
			return $token;
			
		}

		public function cekstatuspay($where)
		{
			$this->db->select('*');
		    $this->db->from('invoice');
			$this->db->join('ekspedisi','ekspedisi.id_invoice=invoice.id');
		    $this->db->where($where);

			return $this->db->order_by('ekspedisi.id_ekspedisi','DESC')->get('',3)->result();
		}

		public function suminvo($where)
		{
          $tgl = date('Y-m-d');
			$this->db->select('*');
		    $this->db->from('invoice');
			$this->db->join('ekspedisi','ekspedisi.id_invoice=invoice.id');
		    $this->db->where($where);
		    $this->db->where("(invoice.status = 'n' and ekspedisi.ongkir != 'null' and invoice.btsbayar > '$tgl')");

			return $this->db->order_by('ekspedisi.id_ekspedisi','DESC')->get('')->num_rows();
		}

		public function insert_pesanan($token){
			foreach ($this->cart->contents() as $items) {
			$data = array(
				'idinvoice'			=> $token,
				'id_product'		=> $items['product_id'],
				'nama'				=> $items['name'],
				'jumlah'			=> $items['qty'],
				'kategori'			=> $items['category'],
				'harga'				=> $items['price'],
				'hargasetdiskon'	=> $items['pricedisc'],
				'diskon'			=> $items['disc'],
				'gambar'			=> $items['gambar'],
				'berat'				=> $items['berat'],
				'ukuran'			=> $items['ukuran']
			);
			$this->db->insert('pesanan', $data);

				$stokkeranjang 	= $items['qty'];
				$ukrn 			= $items['ukuran'];
				$idpro 			= $items['product_id'];

				$data = $this->db->query("SELECT id_ukuran2 FROM ukuran2 WHERE id_product = $idpro and ukuran = '$ukrn'")->result();
				foreach ($data as $ukuran) {$uk = $ukuran->id_ukuran2;}

				if (!empty($uk)) {
					$this->db->query("UPDATE ukuran2 set stok = stok - ".$stokkeranjang." where id_product = $idpro and id_ukuran2 = $uk");
					// $this->db->set('stok', 'stok - 1')->where('id_product', $idpro)->update('product');
				}else{
					$this->db->query("UPDATE product set stok = stok - ".$stokkeranjang.", terjual = terjual + ".$stokkeranjang." where id_product = $idpro" );
					// $this->db->set('stok', 'stok - 1')->where('id_product', $idpro)->where('id_ukuran2', $ukuran)->update('ukuran2');
				}

			}
			return true;

		}
		
		public function insert_ekspedisi($token)
		{
			$data = array(
				'id_invoice'		=> $token,
			);

			$this->db->insert('ekspedisi', $data);
			return true;
		}

		public function tampildata()
		{
			$this->db->select('*');
		    $this->db->from('invoice');
			$this->db->join('ekspedisi','ekspedisi.id_invoice=invoice.id');
			$result = $this->db->order_by('id', 'DESC')->get('');
			if ($result->num_rows() > 0) {
				return $result->result();
			}
			else{
				return false;
			}
		}

		public function ambil_data($id)
		{

			 $this->db->select('*');
			 $this->db->select('pesanan.nama as `namaproduk`');
		     $this->db->from('pesanan');
		     $this->db->join('invoice','invoice.id = pesanan.idinvoice');
		     $this->db->where('pesanan.idinvoice',$id); 
			$result = $this->db->get();
			return $result->result();
		}
		public function get_data($id)
		{

			 $this->db->select('*');
			 $this->db->select('pesanan.nama as `namaproduk`');
		     $this->db->from('pesanan');
		     $this->db->join('invoice','invoice.id = pesanan.idinvoice');
		     $this->db->where('invoice.id',$id); 
		     $this->db->group_by('idinvoice');
			$result = $this->db->get();
			return $result->result();
		}

		public function idinvoice($id)
		{
			// return $this->db->get_where('invoice',array('id' => $id));
			$result = $this->db->where('id', $id)->limit(1)->get('invoice');
			if ($result->num_rows() > 0) {
				return $result->row();
			}
			else{
				return false;
			}
		}

		public function idpesanancus($id)
		{
			$this->db->select('*');
			$this->db->select('pesanan.nama as `namaproduk`');
			$this->db->from('pesanan');
			$this->db->join('ekspedisi','ekspedisi.id_invoice=pesanan.idinvoice');
		    $this->db->join('invoice','invoice.id = pesanan.idinvoice');
			$this->db->join('alamat','alamat.id_alamat = ekspedisi.id_alamat');
			$this->db->where('idinvoice', $id);
			$result = $this->db->get();
			if ($result->num_rows() > 0) {
				return $result->result();
			}
			else{
				return false;
			}
		}

		public function idpesanan($id)
		{
			 $this->db->select('*');
		     $this->db->from('pesanan');
		     $this->db->join('invoice','invoice.id = pesanan.idinvoice');
		     $this->db->where('pesanan.idinvoice',$id); 
			$result = $this->db->get();
			if ($result->num_rows() > 0) {
				return $result->result();
			}
			else{
				return false;
			}
		}
		public function cek_alamat($iduser){

			$this->db->select('*');
		     $this->db->from('alamat');
		     $this->db->join('ekspedisi','alamat.id_alamat = ekspedisi.id_alamat');
			$this->db->where('iduser', $iduser);
			$this->db->order_by('id_ekspedisi','DESC');
			$this->db->limit(1);
			$query = $this->db->get();
			return $query->result();
		}

		public function tambahalamat($data,$table)
		{
			 $this->db->insert($table, $data);
			 return $this->db->insert_id();
		}

		public function ubahalamat($where,$data,$table)
		{
			$this->db->where($where);
			$this->db->update($table,$data);
		}
		public function updatedata($where,$data,$table)
		{
			$this->db->where($where);
			$this->db->update($table,$data);
		}

		public function get_history($where){
			 $this->db->select('*');
		     $this->db->from('invoice');
		     $this->db->join('pesanan','pesanan.idinvoice = invoice.id');
		     $this->db->join('user','user.email = invoice.email');
			 $this->db->join('ekspedisi','ekspedisi.id_invoice=pesanan.idinvoice');
		     $this->db->join('alamat','alamat.iduser = user.iduser');
		     $this->db->group_by('invoice.id');
		     $this->db->where($where); 
		     
			$result = $this->db->get();
			return $result->result();
		}

		public function get_payment($where){
			 $this->db->select('*');
		     $this->db->from('pesanan');
		     $this->db->join('invoice','invoice.id = pesanan.idinvoice');
		     $this->db->join('user','user.email = invoice.email');
		     $this->db->join('alamat','alamat.iduser = user.iduser');
		     $this->db->group_by('idinvoice');

		     $this->db->where($where); 
			$result = $this->db->get();
			return $result->result();
		}
		public function get_payment_unpaid($where){
			$tgl = date('Y-m-d H:i:s');
			 $this->db->select('*');
		     $this->db->from('pesanan');
		     $this->db->join('invoice','invoice.id = pesanan.idinvoice');
		     $this->db->join('user','user.email = invoice.email');
		     $this->db->join('alamat','alamat.iduser = user.iduser');
		     $this->db->where('btsbayar >',$tgl);

		     $this->db->where($where); 
			$result = $this->db->get();
			return $result->result();
		}
		public function get_payment_expired($where){
			$tgl = date('Y-m-d H:i:s');
			 $this->db->select('*');
		     $this->db->from('pesanan');
		     $this->db->join('invoice','invoice.id = pesanan.idinvoice');
		     $this->db->join('user','user.email = invoice.email');
		     $this->db->join('alamat','alamat.iduser = user.iduser');
		     $this->db->where('btsbayar <',$tgl);

		     $this->db->where($where); 
			$result = $this->db->get();
			return $result->result();
		}
		public function insertdata($table,$data)
		{
			 $this->db->insert($table, $data);
		}
		
		public function get_eks($token)
		{
			$this->db->where('id_invoice',$token);
			$result = $this->db->get('ekspedisi');
			return $result->result();
		}
		public function ekspedisi_cek($id, $idinvoice)
		{
			$this->db->where('id_alamat',$id);
			$this->db->where('id_invoice',$idinvoice);
			$result = $this->db->get('ekspedisi');
			return $result->num_rows();
		}

		public function buktitransfer()
		{
			$this->db->select('*');
		     $this->db->from('konfirm_payment');
			return $this->db->get('')->result();
		}

	}