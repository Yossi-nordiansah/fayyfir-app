<?php error_reporting(0);?>

<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
				
					<div class="card">
						<div class="card-body p-5">
							<div class="card-title">
								<h4 class="mb-0">Tambah Product</h4>
							</div>
							<hr/>
                			<form method="post" action="<?= base_url().'admin/product/tambah_aksi'?>" enctype="multipart/form-data" accept-charset="utf-8">
							<div class="form-body">
								<div class="form-group">
									<label>Nama Produk</label>
									<input type="text" class="form-control radius-30" name="nama_product" />
								</div>
								<div class="form-row">
									<div class="form-group col-md-6">
										<label>Keterangan Id</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="desc_id"></textarea>
									</div>
									<div class="form-group col-md-6">
										<label>Keterangan Eng</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="desc_en"></textarea>
									</div>
								</div>
								<div class="form-row">
									<div class="form-group col-md-4">
										<label>Size</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="size_pro"></textarea>
									</div>
									<div class="form-group col-md-4">
										<label>Packaging</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="pack_pro"></textarea>
									</div>
									<div class="form-group col-md-4">
										<label>Shipping</label>
										<textarea class="form-control radius-30" rows="3" cols="3" name="ship_pro"></textarea>
									</div>
								</div>
								<div class="form-row">
									<div class="form-group col-md-4">
										<label>Stok</label>
										<input type="text" class="form-control radius-30" name="stok" />
									</div>
									<div class="form-group col-md-4">
										<label>Berat</label>
										<input type="text" class="form-control radius-30" name="berat" />
									</div>
									<div class="form-group col-md-4">
										<label>ukuran (ml/gr/kg)</label>
										<input type="text" name="ukuran" class="form-control radius-30"/>
									</div>
								</div>
								<div class="form-row">
									<div class="form-group col-md-4">
										<label>Harga</label>
										<input type="text" name="harga" class="form-control radius-30" />
									</div>
									<div class="form-group col-md-4">
										<label>Kategori</label>
										<select class="single-select form-control radius-30" name="kategori" id="kategori">
											<option value="">Pilih Kategori</option>
											<?php foreach ($kategori as $ktgr) { ?>
					                            <option value="<?= $ktgr->id_kategori ?>"><?= $ktgr->nama_kategori ?></option>
					                         <?php } ?>
										</select>
									</div>
									<div class="form-group col-md-4">
										<label>Sub Kategori</label>
										<select class="single-select form-control radius-30" name="sub_kategori" id="sub_kategori">

										</select>
									</div>
								</div>
								<div class="form-row">
									<div class="form-group col-md-4">
										<label>New Product</label>
										<select class="single-select form-control radius-30" name="new">
											<option value="">Pilih Kategori</option>
											<option value="n">Tidak</option>
											<option value="y">Iya</option>
										</select>
									</div>
									<div class="form-group col-md-4">
										<label>Best Seller</label>
										<select class="single-select form-control radius-30" name="best_seller">
											<option value="">Pilih Kategori</option>
											<option value="n">Tidak</option>
											<option value="y">Iya</option>
										</select>
									</div>
									<div class="form-group col-md-4">
										<label><button type="button" class="btn btn-primary btn-sm radius-30 px-5" data-toggle="modal" data-target="#modaltambahuk">Tambah data</button></label>
										<table>
											<thead>
												<tr>
													<th>Ukuran</th>
													<th style="color: white">hkhkh</th>
													<th>Stok</th>
													<th style="color: white">hkhkh</th>
													<th>Harga</th>
												</tr>
											</thead>
											<tbody class="tampil">
												
											</tbody>
										</table>
									</div>
								</div>
								<div class="form-group">
									<label>Gambar</label>
									<input type="file"  name="gambar[]" multiple="" class="form-control radius-30"/>
								</div>
								<hr/>
								<button type="submit" class="btn btn-primary" style="float: right">Tambah</button>
								</div>
							</form>
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
							<label>stok</label>
							<input type="text" class="form-control form-control-lg radius-30" name="stok"/>
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