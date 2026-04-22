<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					
					<div class="card">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Voucher</h4>
								<button style="float: right; margin-top: -30px" type="button" class="btn btn-primary btn-sm radius-30 px-5" data-toggle="modal" data-target="#modaltambahvoucher">Tambah data</button>
							</div>
							<hr/>

							<!-- Modal -->
							<div class="modal fade" id="modaltambahvoucher" tabindex="-1" role="dialog" aria-hidden="true">
								<div class="modal-dialog modal-dialog-centered">
									<div class="modal-content radius-30">
										<div class="modal-header border-bottom-0">
											<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
											</button>
										</div>
										<div class="modal-body p-5">
										<form method="post" action="<?= base_url().'admin/fitur/tambahvoucher'?>" enctype="multipart/form-data">
											<h3 class="text-center">Tambah</h3>
											<div class="form-group">
												<label>Nama Kategori</label>
												<select name="iduser" class="form-control form-control-lg radius-30">
													<?php
														$data = $this->db->query("SELECT * FROM user")->result();
														foreach ($data as $dt){
														echo "<option value=".$dt->iduser.">".$dt->nama."</option>";
														}
													?>
												</select>
											</div>
											<div class="form-group">
												<label>Voucher</label>
												<div class="input-group mb-3">
												  <input type="text" class="form-control form-control form-control-lg radius-30" readonly="" placeholder="Klik Generate" aria-label="Recipient's username" aria-describedby="button-addon2" name="kodevoucher" id="kdvchr">
												  <div class="input-group-append">
												    <button class="btn btn-primary btn-sm radius-30 px-5" type="button" id="button-addon2" onclick="generatevoucher()">Generate</button>
												  </div>
												</div>
											</div>
											<div class="form-group">
												<label>Diskon</label>
												<input type="text" class="form-control form-control-lg radius-30" name="diskon"/>
											</div>
											<div class="form-group">
												<button type="submit" class="btn btn-primary radius-30 btn-lg btn-block">Tambah</button>
											</div>
											<hr/>
										</form>
										</div>
									</div>
								</div>
							</div>

							<div class="table-responsive">
								<table id="example" class="table table-striped table-bordered" style="width:100%">
									<thead>
										<tr>
											<th>Nama</th>
					                        <th>Kode Voucher</th>
					                        <th>Diskon</th>
					                        <th>Detail</th>
					                        <th>Status</th>
										</tr>
									</thead>
									<tbody>
										<?php 
						                    $no=1;
						                    foreach ($voucher as $vc) { ?>

						                      <tr>
						                        <td><?= $vc->nama ?></td>
						                        <td><?= $vc->kodevoucher ?></td>
						                        <td><?= $vc->diskon ?>%</td>
						                        <td><?= anchor('admin/fitur/detailvoucher/'.$vc->kodevoucher,'<div class="btn btn-primary btn-sm radius-30">Detail</div>');?></td>
						                        <?php if ($vc->status == 'y') {?>
						                        <td>Aktif &nbsp;&nbsp;<?= anchor('admin/fitur/nonaktifvoucher/'.$vc->idvoucher,'<div class="btn btn-primary btn-sm radius-30">Non Aktifkan</div>');?></td>
						                    	<?php }else{ ?>
						                    	<td>Tidak Aktif &nbsp;&nbsp;<?= anchor('admin/fitur/aktifvoucher/'.$vc->idvoucher,'<div class="btn btn-primary btn-sm radius-30">Aktifkan</div>');?></td>
						                    	<?php } ?>
						                      </tr>
						                    
						                    <?php } ?>
									</tbody>
									<tfoot>
										<tr>
											<th>Nama</th>
					                        <th>Kode Voucher</th>
					                        <th>Diskon</th>
					                        <th>Detail</th>
					                        <th>Status</th>
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
