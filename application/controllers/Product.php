<?php  
class Product extends CI_Controller  
{  
    public function __construct()  
    {  
        parent::__construct();  
    }  
  
    public function index()
    {
        $data['kategori'] = $this->product_model->kategori();
        $data['sub_kategori'] = $this->product_model->all_sub_kategori();
        $email = isset($this->session->userdata['logged_in']['email']) ? $this->session->userdata['logged_in']['email'] : '';
        $where = ['invoice.email' => $email];
        $data['invo'] = $this->invoice_model->cekstatuspay($where);
        $data['suminvo'] = $this->invoice_model->suminvo($where);
        $data['uk2'] = $this->product_model->ambiluk2();
        $data['gambar_product'] = $this->product_model->all_gambar();
        $data['product'] = $this->product_model->product();

        $idproduk = [];
        foreach ($data['product'] as $value) {
            array_push($idproduk, $value->id_product);
        }

        $data['promo'] = $this->product_model->promo($idproduk);
        foreach ($data['product'] as $key => $value) {
            foreach ($data['promo'] as $promo_value) {
                if ($promo_value->id_product == $value->id_product) {
                    $data['product'][$key]->promo = $promo_value;
                }
            }
        }

        // 🚀 Tambahan baru: ambil semua gambar_kategori (optional di index)
        $data['gambar_kategori'] = $this->product_model->all_gambar_kategori();
        
        // 🚀 Tambahan baru: ambil semua gambar_kategori_proses2 (optional di index)
        $data['gambar_kategori_proses2'] = $this->product_model->all_gambar_kategori_proses2();

        $this->load->view('tmplt/header', $data);
        $this->load->view('product', $data);
        $this->load->view('tmplt/footer');
    }

    public function shop()
    {
        $data['sub_kategori'] = $this->product_model->all_sub_kategori();
        $data['kategori'] = $this->product_model->kategori();
        $email = isset($this->session->userdata['logged_in']['email']) ? $this->session->userdata['logged_in']['email'] : '';
        $where = ['invoice.email' => $email];
        $data['invo'] = $this->invoice_model->cekstatuspay($where);
        $data['suminvo'] = $this->invoice_model->suminvo($where);
        $data['uk2'] = $this->product_model->ambiluk2besar();
        $data['bannermenu'] = $this->fitur_model->bannermenu();
        $data['titel'] = '| Product';

        // 🔹 Ambil parameter kategori & subkategori
        $nama_kategori = rawurldecode($this->uri->segment(3));
        $nama_sub_kategori = rawurldecode($this->uri->segment(4));

        // 🔹 Ambil data kategori dari database
        $kategori_data = $this->db->get_where('kategori', ['nama_kategori' => $nama_kategori])->row();

        $desc_en = '';
        $specifications = '';
        $banner_kategori = '';
        $id_kategori = null;

        if ($kategori_data) {
            $id_kategori = $kategori_data->id_kategori;
            $desc_en = is_resource($kategori_data->desc_eng) ? stream_get_contents($kategori_data->desc_eng) : $kategori_data->desc_eng;
            $specifications = is_resource($kategori_data->specifications) ? stream_get_contents($kategori_data->specifications) : $kategori_data->specifications;
            $banner_kategori = $kategori_data->banner_kategori;
        }

        // 🔹 Ambil produk berdasarkan kategori
        $data['product'] = $this->product_model->seleksi_product($nama_kategori, $nama_sub_kategori);

        $idproduk = [];
        foreach ($data['product'] as $value) {
            array_push($idproduk, $value->id_product);
        }

        $data['promo'] = $this->product_model->promo($idproduk);
        foreach ($data['product'] as $key => $value) {
            foreach ($data['promo'] as $promo_value) {
                if ($promo_value->id_product == $value->id_product) {
                    $data['product'][$key]->promo = $promo_value;
                }
            }
        }

        // 🔹 Data tambahan ke view
        $data['namakategori'] = $nama_kategori;
        $data['descen'] = $desc_en;
        $data['specifications'] = $specifications;
        $data['namasubkategori'] = $nama_sub_kategori;
        $data['gambar_product'] = $this->product_model->all_gambar();

        // 🔹 Banner kategori
        $data['bannerkategori'] = [
            (object)[
                'gambar' => $banner_kategori
            ]
        ];

        // 🚀 Tambahan penting: ambil gambar proses kategori (untuk galeri "Our Process")
        $data['gambar_kategori'] = [];
        if (!empty($id_kategori)) {
            $data['gambar_kategori'] = $this->product_model->get_gambar_kategori_by_id($id_kategori);
        }
        
        // 🚀 Tambahan penting: ambil gambar_kategori_proses2 (untuk galeri "Our Brand")
        $data['gambar_kategori_proses2'] = [];
        if (!empty($id_kategori)) {
            $data['gambar_kategori_proses2'] = $this->product_model->get_gambar_kategori_proses2_by_id($id_kategori);
        }

        $this->load->view('tmplt/header', $data);
        $this->load->view('product', $data);
        $this->load->view('tmplt/footer');
    }
  
    public function detail()  
    {  
        $data['sub_kategori'] = $this->product_model->all_sub_kategori();  
        $data['kategori'] = $this->product_model->kategori();  
        $data['gambar_product'] = $this->product_model->all_gambar();  
        $email = isset($this->session->userdata['logged_in']['email']) ? $this->session->userdata['logged_in']['email'] : '';  
        $where = array('invoice.email' => $email);  
        $data['invo'] = $this->invoice_model->cekstatuspay($where);  
        $data['suminvo'] = $this->invoice_model->suminvo($where);  
        $data['uku2'] = $this->product_model->ambiluk2();  
        $data['uk2'] = $this->product_model->ambiluk2besar();  
        $data['bannermenu'] = $this->fitur_model->bannermenu();  
        $data['titel'] = '| Detail Product';  
  
        $nama_kategori = $this->uri->segment(3);  
        $data['opsi'] = $this->product_model->opsi_product($nama_kategori);  
        $idproduk = array();  
        foreach ($data['opsi'] as $value) {  
            array_push($idproduk, $value->id_product);  
        }  
  
        $data['promo'] = $this->product_model->promo($idproduk);  
        foreach ($data['opsi'] as $key => $value) {  
            foreach ($data['promo'] as $promo_value) {  
                if ($promo_value->id_product == $value->id_product) {  
                    $data['opsi'][$key]->promo = $promo_value;  
                }  
            }  
        }  
  
        $id = $this->uri->segment(4);  
        $data['product'] = $this->product_model->detail_product($id);  
        $idproduk = array();  
        foreach ($data['product'] as $value) {  
            array_push($idproduk, $value->id_product);  
        }  
  
        $data['promo'] = $this->product_model->promo($idproduk);  
        foreach ($data['product'] as $key => $value) {  
            foreach ($data['promo'] as $promo_value) {  
                if ($promo_value->id_product == $value->id_product) {  
                    $data['product'][$key]->promo = $promo_value;  
                }  
            }  
        }  
  
        if ($data['product']) {  
            $data['product'] = $data['product'][0];  
        }  
  
        $this->load->view('tmplt/header', $data);  
        $this->load->view('detailproduct', $data);  
        $this->load->view('tmplt/footer');  
    }  
}  
?>