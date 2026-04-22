<?php error_reporting(0);?>

<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					
					<div class="card">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Laporan</h4>
							</div>
							<hr/>
							<form method="get" action="">
					        <label>Filter Berdasarkan</label><br>
					        <select class="single-select form-control radius-30" name="filter" id="filter">
					            <option value="">Pilih</option>
					            <option value="1">Per Tanggal</option>
					            <option value="2">Per Bulan</option>
					            <option value="3">Per Tahun</option>
					        </select>
					        <br /><br />
					        <div id="form-tanggal">
					            <label>Tanggal</label><br>
					            <input type="date" name="tanggal" class="form-control radius-30" />
					            <br /><br />
					        </div>
					        <div id="form-bulan">
					            <label>Bulan</label><br>
					            <select name="bulan" class="single-select form-control radius-30">
					                <option value="">Pilih</option>
					                <option value="1">Januari</option>
					                <option value="2">Februari</option>
					                <option value="3">Maret</option>
					                <option value="4">April</option>
					                <option value="5">Mei</option>
					                <option value="6">Juni</option>
					                <option value="7">Juli</option>
					                <option value="8">Agustus</option>
					                <option value="9">September</option>
					                <option value="10">Oktober</option>
					                <option value="11">November</option>
					                <option value="12">Desember</option>
					            </select>
					            <br /><br />
					        </div>
					        <div id="form-tahun">
					            <label>Tahun</label><br>
					            <select name="tahun" class="single-select form-control radius-30">
					                <option value="">Pilih</option>
					                <?php
					                foreach($option_tahun as $data){ // Ambil data tahun dari model yang dikirim dari controller
					                    echo '<option value="'.$data->tahun.'">'.$data->tahun.'</option>';
					                }
					                ?>
					            </select>
					            <br /><br />
					        </div>
					        <center>
					        <button type="submit" class="btn btn-primary">Tampilkan</button>
					        <a href="<?= base_url('admin/laporan'); ?>" class="btn btn-danger">Reset</a>
					        </center>
					    </form>
					    <hr />
					    <center><b><?php echo $ket; ?></b>
					    <a href="<?php echo $url_cetak; ?>" target="_blank" style="float: right;" class="btn btn-primary btn-sm">Cetak <i class="bx bx-download"></i></a>
					    </center>
					    <hr />
							<div class="table-responsive">
								<table id="" class="table table-striped table-bordered" style="width:100%">
									<thead>
										<tr>
									        <th>Tanggal</th>
									        <th>Kode Transaksi</th>
									        <th>Barang</th>
									        <th>Jumlah</th>
									        <th>Harga</th>
									        <th>Sub Harga</th>
									        <th>Total</th>
									        <th>Ongkir</th>
									    </tr>
									</thead>
									<tbody>
										<?php if( ! empty($transaksi)){
									      $no = 1;
									      $kode = "";
									      $totalongkir = "";
									      foreach($transaksi as $data){
									      $voucher = $this->db->query("SELECT * FROM voucher where kodevoucher = '$data->kodevoucher'")->row();
									      $tot = $data->jumlah * $data->harga;
									      $totdis = $data->jumlah * $data->hargasetdiskon;
									      // $tota[] = $tot;
									      // $total = array_sum($tota);
									      $tgl = date('d-m-Y', strtotime($data->tglpesan)); 
									     ?>
						                      <tr>
						                      	 <?php if ($kode == $data->id) {echo "<td></td>";}else{ ?>
						                        <td><?= $tgl ?></td>
						                    	<?php } if ($kode == $data->id) {echo "<td></td>";}else{ ?>
						                        <td><?= $data->id ?></td>
						                    	<?php } ?>
						                        <td><?= $data->nama ?> - <?= $data->ukuran ?></td>
						                        <td><?= $data->jumlah ?></td>
												<?php if ($data->diskon) {?>
												<td>Rp<?= number_format($data->hargasetdiskon, 0, ",", ".") ?> <small><s style="color:grey;">Rp <?= number_format($data->harga, 0, ",", ".") ?></s></small></td>
						                        <td>Rp<?= number_format($totdis, 0, ",", ".") ?></td>
												<?php }else{ ?>
						                        <td>Rp<?= number_format($data->harga, 0, ",", ".") ?></td>
						                        <td>Rp<?= number_format($tot, 0, ",", ".") ?></td>
												<?php } ?>
						                        <?php if ($kode == $data->id) {echo "<td></td>";}else{ ?>
						                        <?php if ($data->totalbayar2) {?>
												<td>Rp<?= number_format($data->totalbayar2, 0, ",", ".") ?> <br><small><s>Rp<?= number_format($data->totalbayar, 0, ",", ".") ?></s> (<?= $voucher->diskon ?>%)</small></td>
												<?php }else{ ?>
												<td>Rp<?= number_format($data->totalbayar, 0, ",", ".") ?></td>
												<?php }
												$total2 += $data->totalbayar2;
												if ($data->totalbayar2 == null) {
												 	$total1 += $data->totalbayar;
												 } }?>
						                        <?php if ($kode == $data->id) {echo "<td></td>";}else{ ?>
						                        <td>Rp<?= number_format($data->ongkir, 0, ",", ".") ?></td>
						                    	<?php $kode=$data->id; $totalongkir += $data->ongkir;} ?>
						                      </tr>
						                    
						                <?php $total = $total1+$total2; } } ?>
								                <tr>
							                 		<td></td>
							                 		<td></td>
							                 		<td></td>
							                 		<td></td>
							                 		<td></td>
							                 		<td>Total</td>
							                 		<td>Rp<?= number_format($total, 0, ",", ".") ?></td>
							                 		<td>Rp<?= number_format($totalongkir, 0, ",", ".") ?></td>
							                 	</tr>
							                 	<tr>
							                 		<td></td>
							                 		<td></td>
							                 		<td></td>
							                 		<td></td>
							                 		<td></td>
							                 		<td>Total</td>
							                 		<td colspan="2" text-align="center">
							                 			<?php $totalall = $total + $totalongkir ?>
							                 			Rp<?= number_format($totalall, 0, ",", ".") ?>	
							                 		</td>
							                 	</tr>
									</tbody>
					                 
								</table>
							</div>
						</div>
					</div>
					
				</div>
			</div>
			<!--end page-content-wrapper-->
		</div>
		<!--end page-wrapper-->
		<!--start overlay-->
		<div class="overlay toggle-btn-mobile"></div>
		<!--end overlay-->
		<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
		<!--End Back To Top Button-->
