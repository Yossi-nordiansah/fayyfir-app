<?php
	
	class Fitur_model extends CI_model
	{
		
		public function gambar_banner()
		{
			return $this->db->get('gambar_banner')->result();
		}
		
    // Ambil semua data gallery (untuk admin)
    public function galleryview()
    {
        return $this->db->get('gallery')->result();
    }

    // Ambil hanya gallery aktif (untuk ditampilkan di frontend)
    public function gallery()
    {
        $this->db->select('*');
        $this->db->from('gallery');
        $this->db->where('status', 'y');
        return $this->db->get()->result();
    }

		public function bannerutama()
		{
			$this->db->select('*');
			$this->db->from('gambar_banner');
			$this->db->where("(gambar_banner.kategori = 'bannerutama')");
			return $this->db->get('')->result();
		}

		public function bannertengah()
		{
			$this->db->select('*');
			$this->db->from('gambar_banner');
			$this->db->where("(gambar_banner.kategori = 'bannertengah')");
			return $this->db->get('')->result();
		}

		public function bannermenu()
		{
			$this->db->select('*');
			$this->db->from('gambar_banner');
			$this->db->where("(gambar_banner.kategori = 'bannermenu')");
			return $this->db->get('')->result();
		}
		
		public function bannerkategori()
		{
			$this->db->select('*');
			$this->db->from('kategori');
			$this->db->where("(kategori.status = 'y')");
			return $this->db->get('')->result();
		}
		
		public function bannerutamav1()
		{
			$this->db->select('*');
			$this->db->from('gambar_banner');
			$this->db->where("(gambar_banner.kategori = 'bannerutama' and gambar_banner.mobile = 'n')");
			return $this->db->get('')->result();
		}

		public function bannerutamav2()
		{
			$this->db->select('*');
			$this->db->from('gambar_banner');
			$this->db->where("(gambar_banner.kategori = 'bannerutama' and gambar_banner.mobile = 'y')");
			return $this->db->get('')->result();
		}

		public function bannertengahv1()
		{
			$this->db->select('*');
			$this->db->from('gambar_banner');
			$this->db->where("(gambar_banner.kategori = 'bannertengah' and gambar_banner.mobile = 'n')");
			return $this->db->get('')->result();
		}

		public function bannertengahv2()
		{
			$this->db->select('*');
			$this->db->from('gambar_banner');
			$this->db->where("(gambar_banner.kategori = 'bannertengah' and gambar_banner.mobile = 'y')");
			return $this->db->get('')->result();
		}

		public function voucher()
		{
			$this->db->select('*');
			$this->db->from('voucher');
			$this->db->join('user','user.iduser = voucher.iduser');
			return $this->db->get('')->result();
		}

		public function promo()
		{
			$this->db->select('*');
			$this->db->from('promo');
			return $this->db->get('')->result();
		}

		public function detailvoucher($kode)
		{
			$this->db->select('*');
			$this->db->select('user.nama as `pemkode`');
			$this->db->from('voucher');
			$this->db->join('user','user.iduser = voucher.iduser');
			$this->db->where('voucher.kodevoucher', $kode);
			$result = $this->db->get('');
			if ($result->num_rows() > 0) {
				return $result->row();
			}
			else{
				return false;
			}
		}

	}
?>