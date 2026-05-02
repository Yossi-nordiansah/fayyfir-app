<?php 
foreach ($product as $pd) {}
foreach ($kategori as $ktgr) {}
foreach ($sub_kategori as $sk) {}
if(isset($_SERVER['HTTP_REFERER'])) {
    $previous = base_url('admin/product').'/edit'.'/'.$pd->id_product.'/'.$pd->id_kategori.'/'.$pd->id_sub_kategori ;
}
if(isset($_SERVER['HTTP_REFERER'])) {
    $previousback = $_SERVER['HTTP_REFERER'];
}
 ?>
<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
				
					<div class="card">
						<div class="card-body p-5">
							<div class="card-title">
								<h4 class="mb-0">Edit Product</h4>
							</div>
							<hr/>
                			<?php foreach ($product as $pd) {?>
                			<form method="post" action="<?= base_url().'admin/product/update_aksi'?>" enctype="multipart/form-data" accept-charset="utf-8">
                			<input type="hidden" name="back" class="form-control" value="<?= $previousback ?>">
                			<input type="hidden" name="id_product" class="form-control" value="<?= $pd->id_product ?>">
							<div class="form-body">
								<div class="form-group">
									<label>Nama Produk</label>
									<input type="text" class="form-control radius-30" name="nama_product" value="<?= $pd->nama_product ?>"/>
								</div>
								<div class="form-row">
									<div class="form-group col-md-6">
										<label>Keterangan Id</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="desc_id"><?= $pd->desc_id ?></textarea>
									</div>
									<div class="form-group col-md-6">
										<label>Keterangan Eng</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="desc_en"><?= $pd->desc_en ?></textarea>
									</div>
								</div>
								<div class="form-row">
									<div class="form-group col-md-4">
										<label>Size</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="size_pro"><?= $pd->size_pro ?></textarea>
									</div>
									<div class="form-group col-md-4">
										<label>Packaging</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="pack_pro"><?= $pd->pack_pro ?></textarea>
									</div>
									<div class="form-group col-md-4">
										<label>Shipping</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="ship_pro"><?= $pd->ship_pro ?></textarea>
									</div>
								</div>
								<div class="form-row">
									<div class="form-group col-md-4">
										<label>ukuran (ml/gr/kg)</label>
										<input type="text" name="ukuran" class="form-control radius-30" value="<?= $pd->ukuran  ?>"/>
									</div>
									<div class="form-group col-md-4">
										<label>Stok</label>
										<input type="text" class="form-control radius-30" name="stok" value="<?= $pd->stok  ?>" />
									</div>
									<div class="form-group col-md-4">
										<label>Berat (gr)</label>
										<input type="text" class="form-control radius-30" name="berat" value="<?= $pd->berat  ?>"/>
									</div>
								</div>
								<div class="form-row">
									<div class="form-group col-md-4">
										<label>Harga</label>
										<input type="text" name="harga" class="form-control radius-30" value="<?= $pd->harga ?>"/>
									</div>
									<div class="form-group col-md-4">
										<label>Kategori</label>
										<select class="single-select form-control radius-30" name="kategori" id="kategori">
											<option value="">Pilih Kategori</option>
											<?php foreach ($kategori as $ktgr) { ?>
				                            <option value="<?php echo $ktgr->id_kategori ?>" <?php if($ktgr->id_kategori == $pd->id_kategori ){echo "selected";}?>><?= $ktgr->nama_kategori ?></option>
				                         	<?php } ?>
										</select>
									</div>
									<div class="form-group col-md-4">
										<label>Sub Kategori</label>
										<select class="single-select form-control radius-30" name="sub_kategori" id="sub_kategori">
										<?php foreach ($sub_kategori as $sk) { ?>
				                            <option value="<?php echo $sk->id_sub_kategori ?>" <?php if($sk->id_sub_kategori == $pd->id_sub_kategori ){echo "selected";}?>><?= $sk->nama_sub_kategori ?></option>
				                         <?php } ?>	
										</select>
									</div>
								</div>
								<div class="form-row">
									<div class="form-group col-md-4">
										<label>New Product</label>
										<select class="single-select form-control radius-30" name="new">
                          					<option><?= $pd->new ?></option>
											<option value="">Pilih Kategori</option>
											<option value="n">Tidak</option>
											<option value="y">Iya</option>
										</select>
									</div>
									<div class="form-group col-md-4">
										<label>Best Seller</label>
										<select class="single-select form-control radius-30" name="best_seller">
                          					<option><?= $pd->best_seller ?></option>
											<option value="">Pilih Kategori</option>
											<option value="n">Tidak</option>
											<option value="y">Iya</option>
										</select>
									</div>
									<div class="form-group col-md-4">
										<label>Data Ukuran Tambahan</label>
										<table>
											<thead>
												<tr>
													<th></th>
													<th>Ukuran</th>
													<th style="color: white">hkhkh</th>
													<th>Harga</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($ukuran2 as $uk2) {?>
												<tr>
													<td></td>
													<td><?= $uk2->ukuran ?></td>
													<td></td>
													<td><?= $uk2->harga ?></td>
												</tr>
												<?php } ?>
											</tbody>
										</table>
									</div>
								</div>
								<br>
								<div class="form-group">
									<div class="row">
										<?php foreach ($gambar as $gmbr) {?>
										<div class="col-12 col-lg-3 col-xl-3">
											<div class="card">
												<img src="<?= base_url()?>/asset/images/product/<?= $gmbr->gambar;  ?>" class="card-img-top" alt="...">
												<div class="card-body">
													<hr/>
													<div class="profile-social mt-3 justify-content-center">
														<a class="btn btn-danger btn-sm" href="<?= base_url('admin/product/deleteimg').'/'.$gmbr->id_gambar.'/'.$previous ?>" ><i class="bx bx-trash-alt"></i></a>
													</div>
												</div>
											</div>
										</div>
										<?php } ?>
									</div>
									<center>
										<button type="button" class="btn btn-primary btn-sm radius-30 px-5" data-toggle="modal" data-target="#tambahgambar">Tambah Gambar</button>
									</center>
								</div>
								<hr/>
								<button type="submit" class="btn btn-primary" style="float: right">Update</button>
								<a href="<?= $previousback ?>" class="btn btn-outline-secondary" style="float: right;">Cancel</a>&nbsp;
								</div>
							</form>
							<?php } ?>
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

		<!-- modal tambah ukuran -->
		<div class="modal fade" id="modaltambahuk" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content radius-30">
					<div class="modal-header border-bottom-0">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body p-5">
						<form  method="post" class="tmbhuk" enctype="multipart/form-data">
						<h3 class="text-center">Tambah</h3>
						<div class="form-group">
							<label>Ukuran</label>
							<input type="text" class="form-control form-control-lg radius-30" name="ukuran"/>
						</div>
						<div class="form-group">
							<label>Harga</label>
							<input type="text" class="form-control form-control-lg radius-30" name="harga"/>
						</div>
						<div class="form-group">
							<a class="btn btn-primary radius-30 btn-lg btn-block smpn close">Tambah</a>
						</div>
						<hr/>
					</form>
					</div>
				</div>
			</div>
		</div>

		<!-- Modal -->
		<div class="modal fade" id="tambahgambar" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content radius-30">
					<div class="modal-header border-bottom-0">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body p-5">
        			<form  method="post" action="<?= base_url().'admin/product/tambah_gambar/'?>" enctype="multipart/form-data" accept-charset="utf-8">
        				<input type="hidden" name="id_product" class="form-control" value="<?= $pd->id_product ?>">
        				<input type="hidden" name="prev" class="form-control" value="<?= $previous ?>">
						<h3 class="text-center">Tambah</h3>
						<div class="form-group">
							<label>Image</label>
							<input type="file" class="form-control form-control-lg radius-30" name="gambar[]" multiple=""/>
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