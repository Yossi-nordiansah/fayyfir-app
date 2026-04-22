<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					
					<div class="card radius-15">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Admin</h4>
							</div>
							<hr/>

							<div class="table-responsive">
								<table class="table mb-0">
									<thead>
										<tr>
											<th>Nama</th>
					                        <th>Telp</th>
					                        <th>Email</th>
					                        <th>Username</th>
					                        <th>Level</th>
										</tr>
									</thead>
									<tbody>
										<?php 
						                    $no=1;
						                    foreach ($admin as $adm) { ?>

						                      <tr>
						                        <td><?= $adm->nama ?></td>
						                        <td><?= $adm->notelp ?></td>
						                        <td><?= $adm->email ?></td>
						                        <td><?= $adm->username ?></td>
						                        <td><?= $adm->level ?></td>
						                      </tr>
						                    
						                    <?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>


					<div class="card">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">User</h4>
							</div>
							<hr/>
							<div class="table-responsive">
								<table id="example" class="table table-striped table-bordered" style="width:100%">
									<thead>
										<tr>
											<th>Nama</th>
					                        <th>Telp</th>
					                        <th>Email</th>
					                        <th>Username</th>
					                        <th>Level</th>
										</tr>
									</thead>
									<tbody>
										<?php 
						                    $no=1;
						                    foreach ($user as $adm) { ?>

						                      <tr>
						                        <td><?= $adm->nama ?></td>
						                        <td><?= $adm->notelp ?></td>
						                        <td><?= $adm->email ?></td>
						                        <td><?= $adm->username ?></td>
						                        <td><?= $adm->level ?></td>
						                      </tr>
						                    
						                    <?php } ?>
									</tbody>
									<tfoot>
										<tr>
											<th>Nama</th>
					                        <th>Telp</th>
					                        <th>Email</th>
					                        <th>Username</th>
					                        <th>Level</th>
					                        
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
