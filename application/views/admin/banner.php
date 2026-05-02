<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					
					<div class="card radius-15">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Banner Utama</h4>
								<button style="float: right; margin-top: -30px" type="button" class="btn btn-primary btn-sm radius-30 px-5" data-toggle="modal" data-target="#modaltambahutama">Tambah data</button>
							</div>
							<hr/>

							<!-- Modal -->
								<div class="modal fade" id="modaltambahutama" tabindex="-1" role="dialog" aria-hidden="true">
									<div class="modal-dialog modal-dialog-centered">
										<div class="modal-content radius-30">
											<div class="modal-header border-bottom-0">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
												</button>
											</div>
											<div class="modal-body p-5">
            								<form method="post" action="<?= base_url('admin/fitur/tambahbanner')?>" enctype="multipart/form-data">
												<h3 class="text-center">Tambah Banner Utama</h3>
												<div class="form-group">
													<label>Image</label>
													<input type="file" class="form-control form-control-lg radius-30" name="gambar" />
												</div>
												<div class="form-group">
												<label>Versi</label>
												<select class="single-select form-control radius-30" name="mobile">
													<option value="n">Versi Desktop & Mobile</option>
												</select>
												</div>
												<div class="form-group">
                									<input type="hidden" name="kategori" value="bannerutama">
													<button type="submit" class="btn btn-primary radius-30 btn-lg btn-block">Tambah</button>
												</div>
												<hr/>
											</form>
											</div>
										</div>
									</div>
								</div>

							<div class="table-responsive">
								<table class="table mb-0">
									<thead>
										<tr>
											<th>Gambar</th>
					                        <th>Versi</th>
					                        <th>hapus</th>
										</tr>
									</thead>
									<tbody>
              							<?php foreach ($bannerutama as $gambar) {?>
					                      <tr>
					                        <td>
                      						<img src="<?= base_url() ?>asset/images/banner/<?= $gambar->gambar ?>" alt="..." width="300px">
					                        </td>
					                        <?php if ($gambar->mobile == 'n') {?>
					                        <td>Versi Dekstop</td>
					                    	<?php }else{ ?>
					                    	<td>Versi Mobile</td>
					                    	<?php } ?>
					                        <td>
					                        	<a class="btn btn-danger btn-sm" href="<?= base_url('admin/fitur/deleteimg').'/'.$gambar->id ?>" onclick="return confirm('Anda yakin mau menghapus item ini ?')"><i class="fadeIn animated bx bx-trash-alt"></i></a>
					                        </td>
					                      </tr>
					                    <?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="card radius-15">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Banner Tengah</h4>
								<button style="float: right; margin-top: -30px" type="button" class="btn btn-primary btn-sm radius-30 px-5" data-toggle="modal" data-target="#modaltambahtengah">Tambah data</button>
							</div>
							<hr/>

							<!-- Modal -->
								<div class="modal fade" id="modaltambahtengah" tabindex="-1" role="dialog" aria-hidden="true">
									<div class="modal-dialog modal-dialog-centered">
										<div class="modal-content radius-30">
											<div class="modal-header border-bottom-0">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
												</button>
											</div>
											<div class="modal-body p-5">
            								<form method="post" action="<?= base_url('admin/fitur/tambahbanner')?>" enctype="multipart/form-data">
												<h3 class="text-center">Tambah Banner Utama</h3>
												<div class="form-group">
													<label>Image</label>
													<input type="file" class="form-control form-control-lg radius-30" name="gambar" />
												</div>
												<div class="form-group">
												<label>Versi</label>
												<select class="single-select form-control radius-30" name="mobile">
													<option value="n">Versi Desktop</option>
													<option value="y">Versi Mobile</option>
												</select>
												</div>
												<div class="form-group">
                									<input type="hidden" name="kategori" value="bannertengah">
													<button type="submit" class="btn btn-primary radius-30 btn-lg btn-block">Tambah</button>
												</div>
												<hr/>
											</form>
											</div>
										</div>
									</div>
								</div>

							<div class="table-responsive">
								<table class="table mb-0">
									<thead>
										<tr>
											<th>Gambar</th>
					                        <th>Versi</th>
					                        <th>hapus</th>
										</tr>
									</thead>
									<tbody>
              							<?php foreach ($bannertengah as $gambar) {?>
					                      <tr>
					                        <td>
                      						<img src="<?= base_url() ?>asset/images/banner/<?= $gambar->gambar ?>" alt="..." width="300px">
					                        </td>
					                        <?php if ($gambar->mobile == 'n') {?>
					                        <td>Versi Dekstop</td>
					                    	<?php }else{ ?>
					                    	<td>Versi Mobile</td>
					                    	<?php } ?>
					                        <td>
					                        	<a class="btn btn-danger btn-sm" href="<?= base_url('admin/fitur/deleteimg').'/'.$gambar->id ?>" onclick="return confirm('Anda yakin mau menghapus item ini ?')"><i class="fadeIn animated bx bx-trash-alt"></i></a>
					                        </td>
					                      </tr>
					                    <?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="card radius-15">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Banner Menu</h4>
								<button style="float: right; margin-top: -30px" type="button" class="btn btn-primary btn-sm radius-30 px-5" data-toggle="modal" data-target="#modaltambahmenu">Tambah data</button>
							</div>
							<hr/>

							<!-- Modal -->
								<div class="modal fade" id="modaltambahmenu" tabindex="-1" role="dialog" aria-hidden="true">
									<div class="modal-dialog modal-dialog-centered">
										<div class="modal-content radius-30">
											<div class="modal-header border-bottom-0">
												<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
												</button>
											</div>
											<div class="modal-body p-5">
            								<form method="post" action="<?= base_url('admin/fitur/tambahbanner')?>" enctype="multipart/form-data">
												<h3 class="text-center">Tambah Banner Utama</h3>
												<div class="form-group">
													<label>Image</label>
													<input type="file" class="form-control form-control-lg radius-30" name="gambar" />
												</div>
												<div class="form-group">
												<label>Versi</label>
												<select class="single-select form-control radius-30" name="mobile">
													<option value="n">Versi Desktop</option>
													<option value="y">Versi Mobile</option>
												</select>
												</div>
												<div class="form-group">
                									<input type="hidden" name="kategori" value="bannermenu">
													<button type="submit" class="btn btn-primary radius-30 btn-lg btn-block">Tambah</button>
												</div>
												<hr/>
											</form>
											</div>
										</div>
									</div>
								</div>

							<div class="table-responsive">
								<table class="table mb-0">
									<thead>
										<tr>
											<th>Gambar</th>
					                        <th>Versi</th>
					                        <th>hapus</th>
										</tr>
									</thead>
									<tbody>
              							<?php foreach ($bannermenu as $gambar) {?>
					                      <tr>
					                        <td>
                      						<img src="<?= base_url() ?>asset/images/banner/<?= $gambar->gambar ?>" alt="..." width="300px">
					                        </td>
					                        <?php if ($gambar->mobile == 'n') {?>
					                        <td>Versi Dekstop</td>
					                    	<?php }else{ ?>
					                    	<td>Versi Mobile</td>
					                    	<?php } ?>
					                        <td>
					                        	<a class="btn btn-danger btn-sm" href="<?= base_url('admin/fitur/deleteimg').'/'.$gambar->id ?>" onclick="return confirm('Anda yakin mau menghapus item ini ?')"><i class="fadeIn animated bx bx-trash-alt"></i></a>
					                        </td>
					                      </tr>
					                    <?php } ?>
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
