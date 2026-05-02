<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					
					<div class="card radius-15">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Promo</h4>
							</div>
							<hr/>
							<ul class="nav nav-tabs" id="myTab" role="tablist">
								<li class="nav-item" role="presentation"> <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">Promo</a>
								</li>
								<li class="nav-item" role="presentation"> <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">List Produk</a>
								</li>
							</ul>

							<div class="tab-content p-3" id="myTabContent">
								<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
									<form method="post" action="<?= base_url().'admin/fitur/tambahpromo'?>" enctype="multipart/form-data">
										<h3 class="text-center">Tambah</h3>
										<div class="form-group">
											<label>Tanggal Mulai</label>
											<input type="date" class="form-control form-control-lg radius-30" name="tglawal"/>
										</div>
										<div class="form-group">
											<label>Tanggal Berakhir</label>
											<input type="date" class="form-control form-control-lg radius-30" name="tglakhir"/>
										</div>
										<div class="form-group">
											<label>Diskon</label>
											<input type="number" class="form-control form-control-lg radius-30" name="diskon"/>
										</div>
										<div class="form-group">
											<button type="submit" class="btn btn-primary radius-30 btn-lg btn-block">Tambah</button>
										</div>
										<hr/>
									</form>	
								</div>
								<div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
									<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
										<form method="post" action="<?= base_url().'admin/fitur/tambahlistpromo'?>" enctype="multipart/form-data">
											<h3 class="text-center">Tambah list produk</h3>
											<?php foreach ($promo as $prom) {?>
											<div class="form-check">
												<input class="form-check-input" type="radio" name="idpromo" id="exampleRadios1" value="<?= $prom->id_promo ?>">
												<label class="form-check-label" for="exampleRadios1"><?= $prom->tgl_mulai ." s/d ". $prom->tgl_selesai ." / ". $prom->diskon ?>%</label>&nbsp;&nbsp;&nbsp;
												<button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modaleditpromo<?= $prom->id_promo ?>"><i class="fadeIn animated bx bx-edit-alt"></i></button>
											</div>
											<?php } ?>
											<hr>
											<?php foreach ($kategori as $kat){ ?>
											<input class="form-check-input" type="checkbox" id="selectall">
											<label class="form-check-label" for="defaultCheck1"><?= $kat->nama_kategori ?></label>
											<hr>
											<div class="form-row">
												<?php 
												$par = $this->db->query("SELECT * from product where id_kategori = ".$kat->id_kategori)->result();
												foreach($par as $par){ 
												?>
												<div class="form-group col-md-3">
													<input class="form-check-input name" type="checkbox" name="idproduk[]" value="<?= $par->id_product ?>" id="defaultCheck1">
													<label class="form-check-label" for="defaultCheck1"><?= $par->nama_product ?></label>
												</div>
												<?php } ?>
											</div>
											<br>
											<?php } ?>
											
											<div class="form-group">
												<button type="submit" class="btn btn-primary radius-30 btn-lg btn-block">Tambah</button>
											</div>
											<hr/>
										</form>	
									</div>
								</div>
							</div>
							<div class="table-responsive">
								<h4>list produk yang aktif promonya (<?= $prom->tgl_mulai ."-". $prom->tgl_selesai ?>)</h4>
								<table id="example" class="table table-striped table-bordered" style="width:100%">
									<thead>
										<tr>
											<th>Nama Produk</th>
				                              <th>Sub Kategori</th>
										</tr>
									</thead>
									<tbody>
										 <?php 
										 $pd = $this->db->query('select * from list_promo a inner join product b on a.id_product=b.id_product inner join sub_kategori c on b.id_sub_kategori=c.id_sub_kategori')->result();
										 foreach ($pd as $pd) { ?>
				                          <tr>
				                              <td><?= $pd->nama_product ?></td>
				                              <td><?= $pd->nama_sub_kategori ?></td>
				                              
				                          </tr>
				                      <?php } ?>
									</tbody>
									<tfoot>
										<tr>
											  <th>Nama Produk</th>
				                              <th>Sub Kategori</th>
										</tr>
									</tfoot>
								</table>
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
<!-- Modal -->
												<div class="modal fade" id="modaleditpromo<?= $prom->id_promo ?>" tabindex="-1" role="dialog" aria-hidden="true">
													<div class="modal-dialog modal-dialog-centered">
														<div class="modal-content radius-30">
															<div class="modal-header border-bottom-0">
																<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
																</button>
															</div>
															<div class="modal-body p-5">
        													<form  method="post" action="<?= base_url('admin/fitur/update_promo')?>" enctype="multipart/form-data">
																<h3 class="text-center">Edit</h3>
            													<input type="hidden" value="<?= $prom->id_promo ?> " name="id_promo">
																<div class="form-group">
																	<label>Tanggal Awal</label>
																	<input type="date" class="form-control radius-30" name="tglawal" value="<?= $prom->tgl_mulai ?> "/>
																</div>
																<div class="form-group">
																	<label>Tanggal Selesai</label>
																	<input type="date" class="form-control radius-30" name="tglakhir" value="<?= $prom->tgl_selesai ?> "/>
																</div>
																<div class="form-group">
																	<label>Diskon</label>
																	<input type="text" class="form-control radius-30" name="diskon" value="<?= $prom->diskon ?> "/>
																</div>
																<div class="form-group">
																	<button type="submit" class="btn btn-primary radius-30 btn-lg btn-block">Update</button>
																</div>
																<hr/>
															</form>
															</div>
														</div>
													</div>
												</div>