<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
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
		$date  = date("Y-m-d");
		$pengunjunghariini  = $this->db->query("SELECT * FROM pengunjung WHERE date='".$date."' GROUP BY ip")->num_rows(); // Hitung jumlah pengunjung
		$dbpengunjung = $this->db->query("SELECT COUNT(hits) as hits FROM pengunjung")->row(); 
		$totalpengunjung = isset($dbpengunjung->hits)?($dbpengunjung->hits):0; // hitung total pengunjung
		$bataswaktu = time() - 300; 
		$pengunjungonline  = $this->db->query("SELECT * FROM pengunjung WHERE online > '".$bataswaktu."'")->num_rows(); // hitung pengunjung online
        
        $tgl = date('Y-m-d H:i:s');
		
		$data['totaluser'] = $this->db->query("SELECT * FROM user WHERE level = 'user'")->num_rows(); // Hitung jumlah pengunjung
		$data['totalinvoicesudahbayar'] = $this->db->query("SELECT * FROM invoice WHERE status = 'y'")->num_rows(); // Hitung jumlah pengunjung
		$data['totalinvoicebelumbayar'] = $this->db->query("SELECT * FROM invoice WHERE status = 'n'")->num_rows(); // Hitung jumlah pengunjung
		$data['totalproduct'] = $this->db->query("SELECT * FROM product")->num_rows(); // Hitung jumlah pengunjung
		 
		$data['pengunjunghariini']=$pengunjunghariini;
		$data['totalpengunjung']=$totalpengunjung;
		$data['pengunjungonline']=$pengunjungonline;

		$this->load->view('tmpltadmin/header1');
		$this->load->view('tmpltadmin/header2');
		$this->load->view('admin/dashboard',$data);
		$this->load->view('tmpltadmin/footer');
	}
}
