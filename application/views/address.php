<?php

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
?>

<div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="index.html">Home</a></li>
          <li><span>My address</span></li>
        </ul>
      </div>
    </div>
    <div class="holder">
              <?php foreach ($invo as $invo) {} ?>
      <div class="container">
        <div class="row">
          <div class="col-md-4 aside aside--left">
            <div class="list-group">
              <a href="<?= base_url('profil') ?>" class="list-group-item">Account Details</a>
              <a href="<?= base_url('profil/address') ?>" class="list-group-item active">My Addresses</a>
              <?php if ($invo->status == 'n' && $this->session->userdata['logged_in']['status'] == "login") {?>
              <a href="<?= base_url('profil/orderhistory') ?>" class="list-group-item" style="color: red" hidden>My Order History !</a>
              <?php }else{ ?>
                <a href="<?= base_url('profil/orderhistory') ?>" class="list-group-item" hidden>My Order History</a>
              <?php } ?>
              <a target="_blank" href="<?= base_url('home/confirm_payment') ?>" class="list-group-item" hidden>Payment Confirmation</a>
            </div>
          </div>
          <div class="col-md-14 aside">
            <h1 class="mb-3">My Addresses</h1>
            <?php if($alamat == NULL ){?>
            <div class="row">
              <div class="col-sm-9">
                <div class="card">
                  <div class="card-body">
                    <h3>You do not have an address</h3>
                    <div class="mt-2 clearfix">
                      <a href="#" class="link-icn js-show-form" data-form="#addAddress"><i class="icon-pencil"></i>Add Address</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php }else{ foreach($alamat as $almt){?>
            <div class="row">
              <div class="col-sm-9">
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
            <?php } }?>

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
                  <?php if (condition): ?>
                    
                  <?php endif ?>
                  <input id="checkinter2" name="checkinter2" type="checkbox" <?php if($almt->intership){echo "checked";}?> value="1">
                  <label for="checkinter2">International Shipping</label>
                </div>
                <div id="selinteru" style="display:none;">
                <label class="text-uppercase">Nation :</label>
                <div class="form-group select-wrapper" >
                  <select id="country_change" name="country" class="form-control">
                    <option value="">Select Country</option>
                    <?php 
                      $alamat = $almt->country_id.'-'.$almt->negara;
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
                </div>
                <div id="sellokalu2">
                <label class="text-uppercase">City :</label>
                <div class="form-group select-wrapper">
                  <select id="kota_change" name="kota" class="form-control" required="">
                    <option value="<?= $almt->city_id?>"><?= $almt->kota?></option>
                  </select>
                </div>
                </div>
                <div id="sellokalu3">
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
                  </div>
                  <div id="sellokal2">
                  <label class="text-uppercase">City :</label>
                  <div class="form-group select-wrapper">
                    <select id="kota" name="kota" class="form-control">
                      <option value="">Select City</option>
                      
                    </select>
                  </div>
                  </div>
                  <div id="sellokal3">
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
  </div>

<script type="text/javascript">
  $(function () {
        $("#checkinter").click(function () {
            if ($(this).is(":checked")) {
                $("#selinter").show();
                $("#sellokal").hide();
                $("#sellokal2").hide();
                $("#sellokal3").hide();
            } else {
                $("#selinter").hide();
                $("#sellokal").show();
                $("#sellokal2").show();
                $("#sellokal3").show();
            }
        });
    });

    if ($("#checkinter2").is(":checked")) {
        $("#selinteru").show();
        $("#sellokalu").hide();
        $("#sellokalu2").hide();
        $("#sellokalu3").hide();
        $("#provinsi_change").val('');
        $("#kota_change").val('');
        $("#subkota_change").val('');
    } else {
        $("#selinteru").hide();
        $("#sellokalu").show();
        $("#sellokalu2").show();
        $("#sellokalu3").show();
        $("#country_change").val('');
    }

    $(function () {
        $("#checkinter2").click(function () {
            if ($("#checkinter2").is(":checked")) {
                $("#selinteru").show();
                $("#sellokalu").hide();
                $("#sellokalu2").hide();
                $("#sellokalu3").hide();
                $("#provinsi_change").val('');
                $("#kota_change").val('');
                $("#subkota_change").val('');
            } else {
                $("#selinteru").hide();
                $("#sellokalu").show();
                $("#sellokalu2").show();
                $("#sellokalu3").show();
                $("#country_change").val('');
            }
        });
    });

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

          }
        });
      });

    });

</script>