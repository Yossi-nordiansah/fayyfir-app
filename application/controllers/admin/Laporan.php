<?php
defined('BASEPATH') OR exit('No direct script access allowed');

	class Laporan extends CI_controller
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
        if(isset($_GET['filter']) && ! empty($_GET['filter']))
        	{ // Cek apakah user telah memilih filter dan klik tombol tampilkan
            $filter = $_GET['filter']; // Ambil data filder yang dipilih user
            if($filter == '1'){ // Jika filter nya 1 (per tanggal)
                $tgl = $_GET['tanggal'];
                
                $ket = 'Laporan Penjualan Per Tanggal '.date('d-m-y', strtotime($tgl));
                $url_cetak = 'admin/laporan/cetak?filter=1&tanggal='.$tgl;
                $transaksi = $this->laporan_model->view_by_date($tgl); // Panggil fungsi view_by_date yang ada di TransaksiModel
            }else if($filter == '2'){ // Jika filter nya 2 (per bulan)
                $bulan = $_GET['bulan'];
                $tahun = $_GET['tahun'];
                $nama_bulan = array('', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
                
                $ket = 'Laporan Penjualan Bulan '.$nama_bulan[$bulan].' '.$tahun;
                $url_cetak = 'admin/laporan/cetak?filter=2&bulan='.$bulan.'&tahun='.$tahun;
                $transaksi = $this->laporan_model->view_by_month($bulan, $tahun); // Panggil fungsi view_by_month yang ada di TransaksiModel
            }else{ // Jika filter nya 3 (per tahun)
                $tahun = $_GET['tahun'];
                
                $ket = 'Laporan Penjualan Tahun '.$tahun;
                $url_cetak = 'admin/laporan/cetak?filter=3&tahun='.$tahun;
                $transaksi = $this->laporan_model->view_by_year($tahun); // Panggil fungsi view_by_year yang ada di TransaksiModel
            }
	        }
	        else{ // Jika user tidak mengklik tombol tampilkan
	            $ket = 'Laporan Penjualan';
	            $url_cetak = 'admin/laporan/cetak';
	            $transaksi = $this->laporan_model->view_all(); // Panggil fungsi view_all yang ada di TransaksiModel
	        }
        
		    $data['ket'] = $ket;
		    $data['url_cetak'] = base_url('index.php/'.$url_cetak);
		    $data['transaksi'] = $transaksi;
		    $data['option_tahun'] = $this->laporan_model->option_tahun();
		    $this->load->view('tmpltadmin/header1',$data);
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/laporan', $data);
			$this->load->view('tmpltadmin/footer');
		 }

		 public function cetak()
		 {
	        if(isset($_GET['filter']) && ! empty($_GET['filter'])){ // Cek apakah user telah memilih filter dan klik tombol tampilkan
	            $filter = $_GET['filter']; // Ambil data filder yang dipilih user
	            if($filter == '1'){ // Jika filter nya 1 (per tanggal)
	                $tgl = $_GET['tanggal'];
	                
	                $ket = 'Data Transaksi Tanggal '.date('d-m-y', strtotime($tgl));
	                $transaksi = $this->laporan_model->view_by_date($tgl); // Panggil fungsi view_by_date yang ada di TransaksiModel
	            }else if($filter == '2'){ // Jika filter nya 2 (per bulan)
	                $bulan = $_GET['bulan'];
	                $tahun = $_GET['tahun'];
	                $nama_bulan = array('', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
	                
	                $ket = 'Data Transaksi Bulan '.$nama_bulan[$bulan].' '.$tahun;
	                $transaksi = $this->laporan_model->view_by_month($bulan, $tahun); // Panggil fungsi view_by_month yang ada di TransaksiModel
	            }else{ // Jika filter nya 3 (per tahun)
	                $tahun = $_GET['tahun'];
	                
	                $ket = 'Data Transaksi Tahun '.$tahun;
	                $transaksi = $this->laporan_model->view_by_year($tahun); // Panggil fungsi view_by_year yang ada di TransaksiModel
	            }
	        }else{ // Jika user tidak mengklik tombol tampilkan
	            $ket = 'Semua Data Transaksi';
	            $transaksi = $this->laporan_model->view_all(); // Panggil fungsi view_all yang ada di TransaksiModel
	        }
	        
	        $data['ket'] = $ket;
	        $data['transaksi'] = $transaksi;
		    $this->load->view('admin/print', $data);
		       
		    // ob_start();
		    // $this->load->view('admin/print', $data, true);
		    // $html = ob_get_contents();
		    // ob_end_clean();
		    
		    
		    // require APPPATH.'../assets_ad/html2pdf/autoload.php'; // Load plugin html2pdfnya
		    // $pdf = new Spipu\Html2Pdf\Html2Pdf('P','A4','en');  // Settingan PDFnya
		    // $pdf->WriteHTML($html);
		    // $pdf->Output('Data Transaksi.pdf', 'D');
		 }
	}

