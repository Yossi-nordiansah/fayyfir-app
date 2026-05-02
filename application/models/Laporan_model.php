<?php
	
	class Laporan_model extends CI_model
	{
	
	public function view_by_date($date)
	{
		$this->db->select('*');
		$this->db->from('invoice');
		$this->db->join('pesanan','pesanan.idinvoice = invoice.id');
		$this->db->join('ekspedisi','ekspedisi.id_invoice = invoice.id');
		$this->db->where('invoice.status', 'y');
	    $this->db->where('DATE(tglpesan)', $date); // Tambahkan where tanggal nya
	    return $this->db->get('')->result();// Tampilkan data transaksi sesuai tanggal yang diinput oleh user pada filter
  	}
	    
  	public function view_by_month($month, $year)
  	{
  		$this->db->select('*');
		$this->db->from('invoice');
		$this->db->join('pesanan','pesanan.idinvoice = invoice.id');
		$this->db->where('invoice.status', 'y');
		$this->db->join('ekspedisi','ekspedisi.id_invoice = invoice.id');
	    $this->db->where('MONTH(tglpesan)', $month); // Tambahkan where bulan
	    $this->db->where('YEAR(tglpesan)', $year); // Tambahkan where tahun
        return $this->db->get('')->result(); // Tampilkan data transaksi sesuai bulan dan tahun yang diinput oleh user pada filter
  	}
	    
	public function view_by_year($year)
	{
		$this->db->select('*');
		$this->db->from('invoice');
		$this->db->join('pesanan','pesanan.idinvoice = invoice.id');
		$this->db->join('ekspedisi','ekspedisi.id_invoice = invoice.id');
		$this->db->where('invoice.status', 'y');
	    $this->db->where('YEAR(tglpesan)', $year); // Tambahkan where tahun    
	    return $this->db->get('')->result(); // Tampilkan data transaksi sesuai tahun yang diinput oleh user pada filter
	}
	    
	public function view_all()
	{
		$this->db->select('*');
		$this->db->from('invoice');
		$this->db->join('pesanan','pesanan.idinvoice = invoice.id');
		$this->db->join('ekspedisi','ekspedisi.id_invoice = invoice.id');
		$this->db->where('invoice.status', 'y');
	    return $this->db->get('')->result(); // Tampilkan semua data transaksi
	}
	    
    public function option_tahun()
    {
        $this->db->select('YEAR(tglpesan) AS tahun'); // Ambil Tahun dari field tglpesan
		$this->db->from('invoice');
		$this->db->join('pesanan','pesanan.idinvoice = invoice.id');
		$this->db->join('ekspedisi','ekspedisi.id_invoice = invoice.id');
		$this->db->where('invoice.status', 'y');
        $this->db->order_by('YEAR(tglpesan)'); // Urutkan berdasarkan tahun secara Ascending (ASC)
        $this->db->group_by('YEAR(tglpesan)'); // Group berdasarkan tahun pada field tglpesan
        
        return $this->db->get()->result(); // Ambil data pada tabel transaksi sesuai kondisi diatas
    }

	}
?>