<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					
					<div class="card radius-15">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Detail Pemesanan : <?= $invoice->id ?></h4>
							</div>
							<hr/>
							<ul class="nav nav-tabs" id="myTab" role="tablist">
								<li class="nav-item" role="presentation"> <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">Detail Barang</a>
								</li>
								<li class="nav-item" role="presentation"> <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">Detail Pemesan</a>
								</li>
							</ul>
							<div class="tab-content p-3" id="myTabContent">
								<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
									<div class="table-responsive">
										<table class="table mb-0">
											<thead>
												<tr>
													<th>Nama Barang</th>
													<th>Jumlah Pesanan</th>
													<th>ukuran</th>
										      		<th>kategori</th>
										      		<th>Harga</th>
										      		<th>Subtotal</th>
												</tr>
											</thead>
											<tbody>
												<?php 
								                 if (!isset($pesanan)) {
								                 	echo "Data Tidak Ada";
								                 }else{
													foreach ($pesanan as $pro) {
                  									$diskon = $this->db->query("SELECT * FROM voucher where kodevoucher = '$pro->kodevoucher'")->row();
                  									$subtotaldis = $pro->jumlah * $pro->hargasetdiskon;
													$subtotal = $pro->jumlah * $pro->harga;  
								                 ?>

								                      <tr>
								                        <td><?= $pro->namaproduk ?></td>
											        	<td><?= $pro->jumlah ?></td>
											        	<td><?= $pro->ukuran ?></td>
								        				<td><?= $pro->kategori ?></td>
														<?php if ($pro->diskon) {?>
									                    <td>Rp <?= number_format($pro->hargasetdiskon, 0, ",", ".") ?> <small><s style="color:grey;">Rp <?= number_format($pro->harga, 0, ",", ".") ?></s></small></td>
									                    <td>Rp <?= number_format($subtotaldis, 0, ",", ".") ?></td>  
									                    <?php }else{ ?>
									                    <td>Rp <?= number_format($pro->harga, 0, ",", ".") ?></td>
									                    <td>Rp <?= number_format($subtotal, 0, ",", ".") ?></td>
									                    <?php } ?>
								                      </tr>
								                    
								                    <?php } }?>
								                    <tr>
														<td colspan="5" align="right">TOTAL</td>
														<?php if ($pro->totalbayar2) {?>
														<td>Rp<?= number_format($pro->totalbayar2, 0, ",", ".") ?> <br><s>Rp<?= number_format($pro->totalbayar, 0, ",", ".") ?></s> (<?= $diskon->diskon ?>%)</td>
														<?php }else{ ?>
														<td>Rp<?= number_format($pro->totalbayar, 0, ",", ".") ?></td>
														<?php } ?>
													</tr>
											</tbody>
										</table>
									</div>
									<center>
									<a href="<?= base_url('admin/pesanan') ?>"><div class="btn btn-sm btn-primary radius-15">kembali</div></a>
									</center>
								</div>
								<div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
								<div class="table-responsive">
										<table class="table mb-0">
											<thead>
												<tr>
													<td>Nama Penerima</td>
													<td colspan="5"><?= $pro->penerima ?> (<?= $pro->notelp ?>)</td>
												</tr>
												<tr>
													<td>Alamat</td>
													<td colspan="5">
														<?php if ($pro->province != null) {?>
														<?= $pro->alamat .' - '. $pro->subdistrict .' - '. $pro->city .' - '. $pro->province .' - '. $pro->kodepos ?>
														<?php }else{ ?>
															<?= $pro->alamat .' - '. $pro->country ?>
														<?php } ?>
													</td>
												</tr>
												<tr>
													<td>Kurir</td>
													<td colspan="5"><?= $pro->courier ?></td>
												</tr>
												<tr>
													<td>Jenis Pengiriman</td>
													<td colspan="5"><?= $pro->service ?></td>
												</tr>
												<tr>
													<td>Ongkir</td>
													<td colspan="5">Rp<?= number_format($pro->ongkir, 0, ",", ".") ?></td>
												</tr>
											</thead>
										</table>
									</div>

									<center>
									<a href="<?= base_url('admin/pesanan') ?>"><div class="btn btn-sm btn-primary radius-15">kembali</div></a>
									<?= anchor('admin/pesanan/cetak/'.$invoice->id,'<div class="btn btn-primary radius-15 btn-sm">Print</div>');?>
									</center>

								</div>
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
