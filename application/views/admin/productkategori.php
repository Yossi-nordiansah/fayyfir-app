<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
				
					<div class="card">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">All <?= $namakategori ?></h4>
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
