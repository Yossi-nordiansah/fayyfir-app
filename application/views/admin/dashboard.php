page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					<!--row-->
					<div class="row">
						<div class="col-12 col-lg-3">
							<div class="card radius-15 bg-primary">
								<div class="card-body">
									<div class="media align-items-center">
										<div class="media-body">
											<h4 class="mb-0 font-weight-bold text-white"><?=$totalinvoicesudahbayar?></h4>
											<p class="mb-0 text-white">Total Pesanan Selesai</p>
										</div>
										<div class="font-35 text-white"><i class='bx bx-cart-alt'></i>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-12 col-lg-3">
							<div class="card radius-15 bg-warning">
								<div class="card-body">
									<div class="media align-items-center">
										<div class="media-body">
											<h4 class="mb-0 font-weight-bold text-dark"><?= $totalinvoicebelumbayar ?></h4>
											<p class="mb-0 text-dark">Total Pesanan Proses</p>
										</div>
										<div class="font-35 text-dark"><i class='bx bx-cart-alt'></i>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-12 col-lg-3">
							<div class="card radius-15 bg-success">
								<div class="card-body">
									<div class="media align-items-center">
										<div class="media-body">
											<h4 class="mb-0 font-weight-bold text-white"><?= $totaluser ?></h4>
											<p class="mb-0 text-white">New Users</p>
										</div>
										<div class="font-35 text-white"><i class='bx bx-group'></i>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-12 col-lg-3">
							<div class="card radius-15 bg-danger">
								<div class="card-body">
									<div class="media align-items-center">
										<div class="media-body">
											<h4 class="mb-0 font-weight-bold text-white"><?= $totalproduct ?></h4>
											<p class="mb-0 text-white">Total Product</p>
										</div>
										<div class="font-35 text-white"><i class='bx bx-file'></i>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<!--end row-->
					<div class="card radius-15">
						<div class="card-header border-bottom-0">
							<div class="d-lg-flex align-items-center">
								<div>
									<h5 class="mb-2 mb-lg-0">Sales Update</h5>
								</div>
								<div class="ml-lg-auto mb-2 mb-lg-0">
									<div class="btn-group-round">
										<div class="btn-group">
											<button type="button" class="btn btn-white">Daiiy</button>
											<button type="button" class="btn btn-white">Weekly</button>
											<button type="button" class="btn btn-white">Monthly</button>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="card-body">
							<div id="chart1"></div>
						</div>
					</div>
					
					<div class="row">
						<div class="col-12 col-lg-6 d-flex align-items-stretch">
							<div class="card radius-15 w-100">
								<div class="card-body">
									<div class="d-lg-flex align-items-center">
										<div>
											<h5 class="mb-4">Visitor</h5>
										</div>
									</div>
									<div class="progress-wrapper">
										<p class="mb-1">Pengunjung Hari Ini <span class="float-right"><?= $pengunjunghariini ?></span>
										</p>
										<div class="progress radius-15" style="height:4px;">
											<div class="progress-bar" role="progressbar" style="width: <?= $pengunjunghariini ?>%"></div>
										</div>
									</div>
									<hr>
									<div class="progress-wrapper">
										<p class="mb-1">Pengunjung Online <span class="float-right"><?= $pengunjungonline ?></span>
										</p>
										<div class="progress radius-15" style="height:4px;">
											<div class="progress-bar bg-voilet" role="progressbar" style="width: <?= $pengunjungonline ?>%"></div>
										</div>
									</div>
									<hr>
									<div class="progress-wrapper">
										<p class="mb-1">Total Pengunjung <span class="float-right"><?= $totalpengunjung ?></span>
										</p>
										<div class="progress radius-15" style="height:4px;">
											<div class="progress-bar bg-red-light" role="progressbar" style="width: <?= $totalpengunjung ?>%"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- <div class="col-12 col-lg-6 d-flex align-items-stretch">
							<div class="card radius-15 w-100">
								<div class="card-body">
									<div class="d-flex align-items-center">
										<div>
											<h5 class="mb-0">Sales Forecast</h5>
										</div>
										<div class="dropdown ml-auto">
											<div class="cursor-pointer text-dark font-24 dropdown-toggle dropdown-toggle-nocaret" data-toggle="dropdown"><i class="bx bx-dots-horizontal-rounded"></i>
											</div>
											<div class="dropdown-menu dropdown-menu-right">	<a class="dropdown-item" href="javascript:;">Action</a>
												<a class="dropdown-item" href="javascript:;">Another action</a>
												<div class="dropdown-divider"></div>	<a class="dropdown-item" href="javascript:;">Something else here</a>
											</div>
										</div>
									</div>
									<div class="row mt-3">
										<div class="col-12 col-lg-6">
											<div class="card radius-15 mx-0">
												<div class="card-body">
													<div class="media align-items-center">
														<div class="media-body">
															<p class="text-secondary mb-0">Revenue</p>
															<h4 class="mb-0 ">+24.5%</h4>
														</div>
														<div id="chart4"></div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-12 col-lg-6">
											<div class="card radius-15 mx-0">
												<div class="card-body">
													<div class="media align-items-center">
														<div class="media-body">
															<p class="text-secondary mb-0">Net Profit</p>
															<h4 class="mb-0">-2.7%</h4>
														</div>
														<div id="chart5"></div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-12 col-lg-6">
											<div class="card radius-15 mx-0 mb-3 mb-md-0">
												<div class="card-body">
													<div class="media align-items-center">
														<div class="media-body">
															<p class="text-secondary mb-0">Orders</p>
															<h4 class="mb-0">+32.6%</h4>
														</div>
														<div id="chart6"></div>
													</div>
												</div>
											</div>
										</div>
										<div class="col-12 col-lg-6">
											<div class="card radius-15 mx-0 mb-0">
												<div class="card-body">
													<div class="media align-items-center">
														<div class="media-body">
															<p class="text-secondary mb-0">Visitors</p>
															<h4 class="mb-0">+60.2%</h4>
														</div>
														<div id="chart7"></div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div> -->
					</div>
					<!--end row-->
					
					
				</div>
			</div>
			<!--end page-content-wrapper-->
		</div>
		<!--end page-wrapper-->
		<!--start overlay-->
		<div class="overlay toggle-btn-mobile"></div>
		<!--end overlay-->
		<!--Start Back To Top Button--> <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
		<!--End Back To Top Button