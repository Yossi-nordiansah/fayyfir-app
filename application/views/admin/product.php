<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
				

					<div class="row">
                     <?php foreach ($kategori as $ktgr) { ?>
						<div class="col-12 col-lg-3">
							<div class="card radius-15">
								<div class="card-body">
									<div class="media align-items-center">
										<div class="media-body">
											<a href="<?= base_url('admin/product/kategori').'/'.$ktgr->nama_kategori ?>">
											<h6 class="mb-0 font-weight-bold"><?= $ktgr->nama_kategori ?></h6>
											<p class="mb-0">All Data</p>
											</a>
										</div>
										<div class="widgets-icons bg-light-primary text-primary rounded-circle"><i class='bx bx-file'></i>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php } ?>
					</div>


					<div class="card">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">All Product</h4>
								<a style="float: right; margin-top: -30px" href="<?= base_url('admin/product/addproduct') ?>" class="btn btn-primary radius-15">Tambah Product</a>
								<h4 style="float: right; color: white">A</h4>
								<button style="float: right; margin-top: -30px" type="button" class="btn btn-primary radius-15" data-toggle="modal" data-target="#modalproductoff">Product Off</button>
							</div>
							<hr/>
							<div class="table-responsive">
								<table id="example" class="table table-striped table-bordered" style="width:100%">
									<thead>
										<tr>
											<th>Nama Produk</th>
				                              <th>Sub Kategori</th>
				                              <th>Ukuran</th>
				                              <th>Stok</th>
				                              <th>Harga</th>
				                              <th>Gambar</th>
				                              <th>Aksi</th>
										</tr>
									</thead>
									<tbody>
										 <?php foreach ($product as $pd) { ?>
				                          <tr>
				                              <td><?= $pd->nama_product ?></td>
				                              <td><?= $pd->nama_sub_kategori ?></td>
				                              <td><?= $pd->ukuran ?></td>
				                              <td><?= $pd->stok ?></td>
				                              <td>Rp<?= number_format($pd->harga, 0, ",", ".") ?></td>
				                              <td>
				                                <center><img src="<?= base_url();  ?>/asset/images/product/<?= $pd->gambar;  ?>" alt="First slide" width="45" height="50" class="rounded-circle"></center>
				                              </td>
				                              <td>
				                              	<a class="btn btn-primary btn-sm" href="<?= base_url('admin/product/edit').'/'.$pd->id_product.'/'.$pd->id_kategori.'/'.$pd->id_sub_kategori ?>"><i class="fadeIn animated bx bx-edit-alt"></i></a>
				                                &nbsp;
				                                <a class="btn btn-danger btn-sm" href="<?= base_url('admin/product/delete_aksi').'/'.$pd->id_product ?>" onclick="return confirm('Anda yakin mau menghapus item ini ?')"><i class="fadeIn animated bx bx-trash-alt"></i></a>
				                              </td>
				                              
				                          </tr>
				                      <?php } ?>
									</tbody>
									<tfoot>
										<tr>
											  <th>Nama Produk</th>
				                              <th>Sub Kategori</th>
				                              <th>Ukuran</th>
				                              <th>Stok</th>
				                              <th>Harga</th>
				                              <th>Gambar</th>
				                              <th>Aksi</th>
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

		<!-- modal product off -->
		<div class="modal fade" id="modalproductoff" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Product Off</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">	<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<div class="card-body">
							<hr/>
							<div class="table-responsive">
								<table id="example" class="table table-striped table-bordered" style="width:100%">
									<thead>
										<tr>
											<th>Nama Produk</th>
				                              <th>Sub Kategori</th>
				                              <th>Gambar</th>
				                              <th>Aktifkan</th>
										</tr>
									</thead>
									<tbody>
										 <?php foreach ($productoff as $pd) { ?>
				                          <tr>
				                              <td><?= $pd->nama_product ?></td>
				                              <td><?= $pd->nama_sub_kategori ?></td>
				                              <td>
				                                <center><img src="<?= base_url();  ?>/asset/images/product/<?= $pd->gambar;  ?>" alt="First slide" width="45" height="45" class="rounded-circle"></center>
				                              </td>
				                              <td>
				                              	<a class="btn btn-primary btn-sm" href="<?= base_url('admin/product/producton').'/'.$pd->id_product?>"><i class="fadeIn animated bx bx-edit-alt"></i></a>
				                              </td>
				                              
				                          </tr>
				                      <?php } ?>
									</tbody>
									<tfoot>
										<tr>
											  <th>Nama Produk</th>
				                              <th>Sub Kategori</th>
				                              <th>Gambar</th>
				                              <th>Aksi</th>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
					</div>
					<div class="modal-footer">
						
					</div>
				</div>
			</div>
		</div>