<!--page-wrapper-->
<?php error_reporting(0);?>

		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					
					<div class="card">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Voucher : <?= $detvoucher->kodevoucher  ." (".$detvoucher->diskon."%) | ". $detvoucher->pemkode ?> </h4>
								<br>
								 <div class="form-row">
								    <div class="col-6 form-group">
								      <input type="date" class="form-control" id="min" name="min">
								    </div>
								    <div class="col-6 form-group">
								      <input type="date" class="form-control" id="max" name="max">
								    </div>
								  </div>
							</div>
							<hr/>
							<div class="table-responsive">
								<table id="example" class="table table-striped table-bordered" style="width:100%">
									<thead>
										<tr>
											<th>Id Invoice</th>
											<th>Status</th>
											<th>Tgl Pesan</th>
					                        <th>Kode Voucher</th>
					                        <th>Total Pembayaran (+ongkir)</th>
										</tr>
									</thead>
									<tbody>
										<?php 
						                    $no=1;
						                    $total2 = "";
						                    $data = $this->db->query("SELECT * FROM invoice a INNER JOIN ekspedisi b on b.id_invoice=a.id WHERE a.status = 'y' and a.kodevoucher = '$detvoucher->kodevoucher'")->result();
						                    foreach ($data as $vc) { 
						                    $totalpembayaran = $vc->totalbayar2 + $vc->ongkir?>

						                      <tr>
						                        <td><?= $vc->id ?></td>
						                        <td><?= $vc->status ?></td>
						                        <td><?= $vc->tglpesan ?></td>
						                        <td><?= $vc->kodevoucher ?></td>
						                        <td>Rp<?= number_format($totalpembayaran, 0, ",", ".") ?></td>
						                        <?php $total2 += $totalpembayaran; ?>
						                      </tr>
						                    
						                    <?php } 
						                    $potongan = (5/100)*$total2;
						                    ?>

						                    <tr>
							                 		<td></td>
							                 		<td></td>
							                 		<td></td>
							                 		<td>Total</td>
							                 		<td>Rp<?= number_format($total2, 0, ",", ".") ?> (5% = <?= number_format($potongan, 0, ",", ".") ?>) </td>
							                 	</tr>
									</tbody>
									<tfoot>
										<tr>
											<th>Id Invoice</th>
											<th>Status</th>
											<th>Tgl Pesan</th>
					                        <th>Kode Voucher</th>
					                        <th>Total Pembayaran (+ongkir)</th>
										</tr>
									</tfoot>
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
