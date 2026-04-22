<?php

error_reporting(0);

// provinsi
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://pro.rajaongkir.com/api/province",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "key: f286c6e4cbcb3435907d35f08c1aac8c"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  $provinsi = json_decode($response,true);
}

// negara
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://pro.rajaongkir.com/api/v2/internationalDestination",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "key: f286c6e4cbcb3435907d35f08c1aac8c"
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  $negara = json_decode($response,true);
}

foreach ($alamat as $almt) {}
?>

<?php 
$tberat=0;
$berat = 0;
foreach ($pesanan as $ps) {
$tberat = $ps->jumlah * $ps->berat;
$berat = $tberat+$berat;

$tberat =$tberat+$tberat; }



?>

<style type="text/css">
label {
  width: 100%;
}

.card-input-element+.card {
  height: calc(36px + 2*1rem);
  color: var(--primary);
  -webkit-box-shadow: none;
  box-shadow: none;
  border: 1px solid;
  border-radius: 4px;
  border-color: #BFBFBF;
}

.card-input-element+.card:hover {
  cursor: pointer;
}

.card-input-element:checked+.card {
  border: 1px solid #046106;
  -webkit-transition: border .3s;
  -o-transition: border .3s;
  transition: border .3s;
}

.card-input-element:checked+.card::after {
  content: '✓';
  color: #046106;
  /*font-family: 'Material Icons';
  font-size: 24px;*/
  -webkit-animation-name: fadeInCheckbox;
  animation-name: fadeInCheckbox;
  -webkit-animation-duration: .5s;
  animation-duration: .5s;
  -webkit-animation-timing-function: cubic-bezier(241, 196, 15, 0.5);
  animation-timing-function: cubic-bezier(241, 196, 15, 0.5);
}

@-webkit-keyframes fadeInCheckbox {
  from {
    opacity: 0;
    -webkit-transform: rotateZ(-20deg);
  }
  to {
    opacity: 1;
    -webkit-transform: rotateZ(0deg);
  }
}

@keyframes fadeInCheckbox {
  from {
    opacity: 0;
    transform: rotateZ(-20deg);
  }
  to {
    opacity: 1;
    transform: rotateZ(0deg);
  }
}

.hide {
  display: none;
}

#red{
  color: red;
}
</style>
<div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="index.html">Home</a></li>
          <li><span>Checkout</span></li>
        </ul>
      </div>
    </div>
    <div class="holder">
      <div class="container">
        <h1 class="text-center">Checkout wizard</h1>
        <div class="row">
          <div class="col-md-10">
            <div class="steps-progress">
              <ul class="nav nav-tabs">
                <li class="nav-item active">
                  <a class="nav-link active" data-toggle="tab" href="#step2" data-step="2"><span>01.</span><span>Shipping Address</span></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" data-toggle="tab" href="#step4" data-step="4"><span>02.</span><span>Expedition & Payment Method</span></a>
                </li>
              </ul>
              <div class="progress">
                <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="1" aria-valuemin="1" aria-valuemax="5" style="width: 50%;"></div>
              </div>
            </div>
            <div class="tab-content">
              
              <div class="tab-pane fade show active" id="step2">
                <div class="tab-pane-inside">
                  <div class="card">
                    <div class="card-body">
                      <h1 class="mb-3">Shipping Address</h1>
                        <div class="row">
                          <div class="col-sm-12">
                            <div class="card">
                              <div class="card-body">
                                <div id="tampil_alamat">
                                </div>
                                <div class="mt-2 clearfix">
                                  <?php if ($almt == null) {?>
                                  <a href="#" class="link-icn js-show-form" data-form="#addAddress" id="red"><i class="icon-pencil"></i>Add Address</a> &nbsp;
                                  <?php }else{ ?>
                                  <a href="#" class="link-icn js-show-form" data-form="#updateAddress<?= $almt->id_alamat?>"><i class="icon-pencil"></i>Edit Address</a>
                                  <?php } ?>
                                </div>
                              </div>
                              
                            </div>
                          </div>
                        </div>

                        <div class="card mt-3 d-none" id="updateAddress<?= $almt->id_alamat ?>">
                          <form  method="post" class="formeditalamat" enctype="multipart/form-data">
                          <input type="hidden" name="idinvoice" value="<?= $ps->idinvoice ?>">
                          <input type="hidden" name="id_alamat" id="id_alamat" value="<?= $almt->id_alamat  ?>">
                          <input type="hidden" name="iduser" id="iduser" value="<?= $almt->iduser  ?>">
                          <div class="card-body">
                            <h3>Update Address</h3>
                            <div class="row mt-2">
                              <div class="col-sm-9">
                                <label class="text-uppercase">Recipient's Name :</label>
                                <div class="form-group">
                                  <input type="text" required="" class="form-control form-control--sm" placeholder="Recipient's Name" name="penerima" id="penerima" value="<?= $almt->penerima  ?>">
                                </div>
                              </div>
                              <div class="col-sm-9">
                                <label class="text-uppercase">Phone Number / Whatsapp :</label>
                                <div class="form-group">
                                  <input type="text" required="" class="form-control form-control--sm" placeholder="Phone Number" name="notelp" id="notelp" value="<?= $almt->notelp  ?>">
                                </div>
                              </div>
                            </div>
                            <div class="form-group">
                                <label id="pth2" for="inputPassword4">Address <span id="bntng">*</span></label>
                                <textarea type="text" class="form-control" placeholder="complete Address" name="alamat" id="alamat" required=""><?= $almt->alamat  ?></textarea>
                            </div>
                            <div class="clearfix justify-content-center">
                              
                              <input id="checkinter2" name="checkinter2" type="checkbox" <?php if($almt->intership){echo "checked";}?> value="1">
                              <label for="checkinter2">International Shipping</label>
                            </div>
                            <div id="selinteru" style="display:none;">
                            <label class="text-uppercase">Nation :</label>
                            <div class="form-group select-wrapper" >
                              <select id="country_change" name="country" class="form-control">
                                <option value="">Select Country</option>
                                <?php 
                                  $alamat = $almt->country_id.'-'.$almt->country_name;
                                  if ($negara['rajaongkir']['status']['code'] == '200'){
                                    foreach ($negara['rajaongkir']['results'] as $ngr) {
                                ?>
                               <option value="<?= $ngr['country_id'].'-'.$ngr['country_name'] ?>" <?php if($alamat == $ngr['country_id'].'-'.$ngr['country_name'] ){echo "selected";}?>><?= $ngr['country_name'] ?></option>
                                <?php      
                                    }
                                  } 
                                ?>
                              </select>
                            </div>
                            </div>
                            <div id="sellokalu">
                            <label class="text-uppercase">Province :</label>
                            <div class="form-group select-wrapper">
                              <select id="provinsi_change" name="provinsi" class="form-control" required="">
                                <option value="">Select Province</option>
                                <?php 
                                  $alamat = $almt->province_id.'-'.$almt->provinsi;
                                  if ($provinsi['rajaongkir']['status']['code'] == '200'){
                                    foreach ($provinsi['rajaongkir']['results'] as $prov) {
                                ?>
                               <option value="<?= $prov['province_id'].'-'.$prov['province'] ?>" <?php if($alamat ==$prov['province_id'].'-'.$prov['province'] ){echo "selected";}?>><?= $prov['province'] ?></option>
                                <?php      
                                    }
                                  } 
                                ?>
                              </select>
                            </div>
                            
                            <label class="text-uppercase">City :</label>
                            <div class="form-group select-wrapper">
                              <select id="kota_change" name="kota" class="form-control" required="">
                                <option value="<?= $almt->city_id?>"><?= $almt->kota?></option>
                              </select>
                            </div>
                            
                            <label class="text-uppercase">Subdistrict :</label>
                            <div class="form-group select-wrapper">
                              <select id="subkota_change" name="subkota" class="form-control" required="">
                                <option value="<?= $almt->subdistrict_id?>"><?= $almt->kecamatan?></option>
                              </select>
                            </div>
                            </div>
                            
                            <div class="mt-2">
                              <button type="reset" class="btn btn--alt js-close-form" data-form="#updateAddress<?= $almt->id_alamat ?>">Cancel</button>
                              <button class="btn ml-1 updatealamat js-close-form" data-form="#updateAddress<?= $almt->id_alamat ?>">Update</button>
                            </div>
                          </div>
                          </form>
                        </div>

                        <div class="card mt-3 d-none" id="addAddress">
                        <form action="<?= base_url('Checkout/addaddress') ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="idinvoice" value="<?= $ps->idinvoice ?>">
                        <input type="hidden" name="iduser" value="<?= $this->session->userdata['logged_in']['iduser'] ?>">
                          <div class="card-body">
                            <h3>Add Address</h3>
                            <div class="row mt-2">
                              <div class="col-sm-9">
                                <label class="text-uppercase">Recipient's Name :</label>
                                <div class="form-group">
                                  <input type="text" class="form-control form-control--sm" placeholder="Recipient's Name" name="penerima" required="">
                                </div>
                              </div>
                              <div class="col-sm-9">
                                <label class="text-uppercase">Phone Number / Whatsapp :</label>
                                <div class="form-group">
                                  <input type="text" class="form-control form-control--sm" placeholder="Phone Number" name="notelp" required="">
                                </div>
                              </div>
                            </div>
                            <div class="form-group">
                                <label class="text-uppercase">Address :</label>
                                <textarea type="text" class="form-control" placeholder="complete Address" name="alamat" required=""></textarea>
                            </div>
                            <div class="clearfix justify-content-center">
                              <input id="checkinter" name="checkinter" type="checkbox" value="1">
                              <label for="checkinter">International Shipping</label>
                            </div>
                            <div id="selinter" style="display:none;">
                            <label class="text-uppercase">Nation :</label>
                            <div class="form-group select-wrapper" >
                              <select id="country" name="country" class="form-control">
                                <option value="">Select Country</option>
                                <?php 
                                  if ($negara['rajaongkir']['status']['code'] == '200'){
                                    foreach ($negara['rajaongkir']['results'] as $ngr) {
                                      echo "<option value='".$ngr['country_id'].'-'.$ngr['country_name']."'>".$ngr['country_name']."</option>";
                                    }
                                  } 
                                ?>
                              </select>
                            </div>
                            </div>
                            <div id="sellokal">
                            <label class="text-uppercase">Province :</label>
                            <div class="form-group select-wrapper">
                              <select id="provinsi" name="provinsi" class="form-control">
                                <option value="">Select Province</option>
                                <?php 
                                  if ($provinsi['rajaongkir']['status']['code'] == '200'){
                                    foreach ($provinsi['rajaongkir']['results'] as $prov) {
                                      echo "<option value='".$prov['province_id'].'-'.$prov['province']."'>".$prov['province']."</option>";
                                    }
                                  } 
                                ?>
                              </select>
                            </div>
                            <label class="text-uppercase">City :</label>
                            <div class="form-group select-wrapper">
                              <select id="kota" name="kota" class="form-control">
                                <option value="">Select City</option>
                                
                              </select>
                            </div>
                            <label class="text-uppercase">Subdistrict :</label>
                            <div class="form-group select-wrapper">
                              <select id="subkota" name="subkota" class="form-control">
                                <option value="">Select Subdistrict</option>
                                
                              </select>
                            </div>
                            </div>
                            
                            <div class="mt-2">
                              <button type="reset" class="btn btn--alt js-close-form btn-warning" data-form="#addAddress">Cancel</button>
                              <button type="submit" class="btn ml-1" >Add</button>
                            </div>
                          </div>
                          </form>
                        </div>

                    </div>
                  </div>
                </div>
              </div>

              <div class="tab-pane fade" id="step4">
                <div class="tab-pane-inside">
                  <form method="post" class="formekspedisi">
                  <div class="card">
                    <div class="card-body">
                      <h2>Devivery Methods</h2>
                       <div class="form-inline form-group select-wrapper">
                          <select id="ekspedisi" name="ekspedisi" class="form-control form-control--sm">
                            <?php 
                              $eks = [
                                'jne'       =>  'JNE',
                                'jnt'       =>  'J&T Express',
                                'pos'       =>  'Pos Indonesia',
                                'sicepat'   =>  'Sicepat',
                                'tiki'      =>  'TIKI',
                                'expedito'  =>  'Expedito (International)'
                              ];
                              foreach ($eks as $key => $value) { ?>
                                <option value="<?= $key ?>" <?php if($key == $this->input->post('ekspedisi')){echo "selected";}?>><?= $value ?></option>";
                              <?php } ?>
                          </select>
                          &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                          <input type="hidden" id="destination" name="destination" value="<?= $almt->subdistrict_id ?>">
                          <input type="hidden" id="destinationcountry" name="destinationcountry" value="<?= $almt->country_id ?>">
                          <input type="hidden" name="weight" id="weight" value="<?= $berat ?>">
                          <button type="button" class="btn btn--sm lihatekspedisi" onclick="hide1()" >See Service</button>
                        </div>  
                        <br>
                         <div class="row" id="tampil_ekspedisi">
                            
                        </div>

                        <p>choose the shipping service you want to use before paying <span id="red">*</span></p>
                    </div>
                  </div>
                  </form>

                </div>
              <form method="post" action="<?= base_url('Checkoutv2/updatetransaksi') ?>">
                  <input type="hidden" id="eks" name="ekspedisi">
                  <input type="hidden" name="ongkir" id="ong">
                  <input type="hidden" name="service" id="ser">
                  <input type="hidden" name="idinvoice" value="<?= $ps->idinvoice ?>">
                  <input type="hidden" name="id_alamat" value="<?= $almt->id_alamat ?>">
                  <input type="hidden" name="notelp" value="<?= $almt->notelp ?>">
                  <input type="hidden" name="weight" value="<?= $berat ?>">
                <div class="clearfix mt-1 mt-md-2">
                 
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-8 pl-lg-8 mt-2 mt-md-0">
            <h2 class="custom-color">Order Summary</h2>
            <div class="cart-table cart-table--sm pt-3 pt-md-0">
              <div class="cart-table-prd cart-table-prd--head py-1 d-none d-md-flex">
                <div class="cart-table-prd-image text-center">
                  Image
                </div>
                <div class="cart-table-prd-content-wrap">
                  <div class="cart-table-prd-info">Name</div>
                  <div class="cart-table-prd-qty">Qty</div>
                  <div class="cart-table-prd-price">Price</div>
                </div>
              </div>
              <?php 
              foreach ($pesanan as $items) {
              $diskon = $this->db->query("SELECT * FROM voucher where kodevoucher = '$items->kodevoucher'")->row();
              $sum = $items->jumlah * $items->harga;
              $sum2 = $items->jumlah * $items->hargasetdiskon?> 
              <div class="cart-table-prd">
                <div class="cart-table-prd-image">
                  <a href="#" class="prd-img"><img class="lazyload fade-up" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?= base_url() ?>asset/images/product/<?= $items->gambar?>" alt=""></a>
                </div>
                <div class="cart-table-prd-content-wrap">
                  <div class="cart-table-prd-info">
                    <h2 class="cart-table-prd-name"><a href=""><?= $items->namaproduk ?></a></h2>
                    size - <?= $items->ukuran ?><br>
                    <?php if ($items->diskon){ ?>
                      Rp<?= number_format($items->hargasetdiskon, 0, ",", ".") ?> <small><s style="color:grey;"><?= number_format($items->harga, 0, ",", ".") ?></s></small>
                    <?php }else{
                      echo number_format($items->harga, 0, ",", ".");
                    } ?>
                  </div>
                  <div class="cart-table-prd-qty">
                    <div class="qty qty-changer">
                      <input type="text" class="qty-input disabled" value="<?= $items->jumlah ?>" data-min="0" data-max="1000" disable="" readonly>
                    </div>
                  </div>
                  <div class="cart-table-prd-price-total">
                    <?php if ($items->diskon){ ?>
                    Rp<?= number_format($sum2, 0, ",", ".") ?>
                    <?php }else{?>
                    Rp<?= number_format($sum, 0, ",", ".") ?>
                    <?php } ?>
                  </div>
                </div>
              </div>
              <?php } ?>
            </div>
            <div class="mt-2"></div>
            <?php if ($items->totalbayar2 != null) {?>
            <div class="cart-total-sm">
              <span>Subtotal</span>
              <span class="card-total-price">Rp<?= number_format($items->totalbayar2, 0, ",", ".") ?></span>
            </div>
            <small class="float-right" style="font-size: 15px; color: red; margin-top: -2px;" ><s>Rp<?= number_format($items->totalbayar, 0, ",", ".") ?></s> (<?= $diskon->diskon ?>%)</small>
            <?php }else{ ?>
            <div class="cart-total-sm">
              <span>Subtotal</span>
              <span class="card-total-price">Rp<?= number_format($items->totalbayar, 0, ",", ".") ?></span>
            </div>
            <?php } ?>
            <br>
            <div class="mt-2">
               <button type="submit" class="btn btn--lg w-100 hide" id="pay" >Make Payment</button>
            </div>
          </div>
          </form>
        </div>
      </div>
    </div>
  </div>

 <script type="text/javascript">
    function show2(ong,ser){
      document.getElementById('pay').style.display = 'block';
      document.getElementById("ong").value = ong;
      document.getElementById("ser").value = ser;
    }
    function hide1(){
      document.getElementById('pay').style.display = 'none';
    }

    $(function () {
        $("#checkinter").click(function () {
            if ($(this).is(":checked")) {
                $("#selinter").show();
                $("#sellokal").hide();
            } else {
                $("#selinter").hide();
                $("#sellokal").show();
            }
        });
    });

    if ($("#checkinter2").is(":checked")) {
        $("#selinteru").show();
        $("#sellokalu").hide();
        $("#provinsi_change").val('');
        $("#kota_change").val('');
        $("#subkota_change").val('');
    } else {
        $("#selinteru").hide();
        $("#sellokalu").show();
        $("#country_change").val('');
    }

    $(function () {
        $("#checkinter2").click(function () {
            if ($("#checkinter2").is(":checked")) {
                $("#selinteru").show();
                $("#sellokalu").hide();
                $("#provinsi_change").val('');
                $("#kota_change").val('');
                $("#subkota_change").val('');
            } else {
                $("#selinteru").hide();
                $("#sellokalu").show();
                $("#country_change").val('');
            }
        });
    });
  </script>

  <script type="text/javascript">
    $(document).ready(function(){

      $.ajax({
          url: "<?= base_url("Checkout/tampil_alamat");?>",
          type: "POST",
          cache: false,
          success: function(data){
            //alert(data);
            $('#tampil_alamat').html(data); 
          }
        });


      $(".updatealamat").click(function(){
              var data = $('.formeditalamat').serialize();
              var city = $('#subkota_change').val();
              var country = $('#country_change').val();

              $.ajax({
                type: 'POST',
                url: "<?= base_url('Checkout/updateaddress') ?>",
                data: data,
                success: function(response) {
                  
                  $.ajax({
                    url: "<?= base_url("Checkout/tampil_alamat");?>",
                    type: "POST",
                    cache: false,
                    success: function(data){
                      //alert(data);
                      $('#tampil_alamat').html(data); 
                    }
                  });
                  $('#destination').val(city);
                  $('#destinationcountry').val(country);

                }
              });
            });

      $(".tambahalamat").click(function(){
              var data = $('.formtambahalamat').serialize();
              var city = $('#subkota').val();
              var country = $('#country').val();

              $.ajax({
                type: 'POST',
                url: "<?= base_url('Checkout/addaddress') ?>",
                data: data,
                success: function(response) {
                  
                  $.ajax({
                    url: "<?= base_url("Checkout/tampil_alamat");?>",
                    type: "POST",
                    cache: false,
                    success: function(data){
                      //alert(data);
                      $('#tampil_alamat').html(data); 
                      $('#destination').val(city);
                      $('#destinationcountry').val(country);

                    }
                  });

                }
              });
            });

  
         $(".lihatekspedisi").click(function(){
        
              // var ekspedisi     = $('#ekspedisi').val();
              // var destination  = $('#destination').val();
              // var weight        = $('#weight').val();
              var data = $('.formekspedisi').serialize();
              var es = $('#ekspedisi').val();

              $.ajax({
                type: 'POST',
                url: "<?= base_url('Checkout/tampil_ekspedisi') ?>",
                data:data,
                // data: {
                //   ekspedisi:ekspedisi,
                //   destination:destination,
                //   weight:weight
                // },
                success: function(response) {
                 $('#tampil_ekspedisi').html(response); 
                 $('#eks').val(es);
                 console.log(response);

                }
              });
            });
    });

   
  </script>