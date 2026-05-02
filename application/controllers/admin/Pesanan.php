<?php
defined('BASEPATH') OR exit('No direct script access allowed');

	class Pesanan extends CI_controller
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

			$data['invoice']= $this->invoice_model->tampildata();
			
			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/pesanan', $data);
			$this->load->view('tmpltadmin/footer');
		}

		public function detail($id)
		{	

			// $data['product'] = $this->invoice_model->ambilidinvoice($id)->result();
			// if ($data['product']) {
			// 	$data['product'] = $data['product'][0];
			// }

			// $data['product2'] = $this->invoice_model->ambilidpesanan($id)->result();
			// if ($data['product2']) {
			// 	$data['product2'] = $data['product2'][0];
			// }

			$data['invoice'] = $this->invoice_model->idinvoice($id);
			$data['pesanan'] = $this->invoice_model->idpesanancus($id);
			
			$this->load->view('tmpltadmin/header1');
			$this->load->view('tmpltadmin/header2');
			$this->load->view('admin/detailpesanan', $data);
			$this->load->view('tmpltadmin/footer');
		}

		public function cetak($id)
		 {  
	        $data['invoice'] = $this->invoice_model->idinvoice($id);
			$data['pesanan'] = $this->invoice_model->idpesanancus($id);
		    $this->load->view('admin/printdetail', $data);
		       
		    // ob_start();
		    // $this->load->view('admin/print', $data,true);
		    // $html = ob_get_contents();
		    // ob_end_clean();
		    
		    
		    // require APPPATH.'../assets_ad/html2pdf/autoload.php'; // Load plugin html2pdfnya
		    // $pdf = new Spipu\Html2Pdf\Html2Pdf('P','A4','en');  // Settingan PDFnya
		    // $pdf->WriteHTML($html);
		    // $pdf->Output('Data Transaksi.pdf', 'D');
		 }

		 public function updatebayar($id)
		 {
		 	$data = array
				(
					'status' 	=> 'y'
				);
				$where = array
				(
					'id' 	=> $id
				);
				$this->db->update('invoice',$data,$where);
				redirect('admin/pesanan');
		 }

		 public function updatekirim()
		 {
		 	$idinvoice 		= $this->input->post('id');
			$statuskirim 	= $this->input->post('statuskirim');

		 	$data = array
				(
					'statuskirim' 	=> $statuskirim
				);
				$where = array
				(
					'id' 	=> $idinvoice
				);
				$this->db->update('invoice',$data,$where);
				redirect('admin/pesanan');
		 }

		 public function updateresi()
		 {
		 	$idinvoice 		= $this->input->post('id');
			$noresi 		= $this->input->post('noresi');

		 	$data = array
				(
					'noresi' 	=> $noresi
				);
				$where = array
				(
					'id' 	=> $idinvoice
				);
				$this->db->update('invoice',$data,$where);
				redirect('admin/pesanan');
		 }
	}

	