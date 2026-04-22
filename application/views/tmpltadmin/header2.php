<!--header-->
		<header class="top-header">
			<nav class="navbar navbar-expand">
				<div class="left-topbar d-flex align-items-center">
					<a href="javascript:void(0)" class="toggle-btn">	<i class="bx bx-menu"></i>
					</a>
				</div>
				<div class="flex-grow-1 search-bar">
					<div class="input-group">
						<div class="input-group-prepend search-arrow-back">
							<button class="btn btn-search-back" type="button"><i class="bx bx-arrow-back"></i>
							</button>
						</div>
						<input type="text" class="form-control" placeholder="search" />
						<div class="input-group-append">
							<button class="btn btn-search" type="button"><i class="lni lni-search-alt"></i>
							</button>
						</div>
					</div>
				</div>
				<div class="right-topbar ml-auto">
					<ul class="navbar-nav">
						<li class="nav-item search-btn-mobile">
							<a class="nav-link position-relative" href="javascript:void(0)">	<i class="bx bx-search vertical-align-middle"></i>
							</a>
						</li>
						
						<!-- <li class="nav-item dropdown dropdown-lg">
							<a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="javascript:void(0)" data-toggle="dropdown">	<i class="bx bx-bell vertical-align-middle"></i>
								<span class="msg-count">8</span>
							</a>
							<div class="dropdown-menu dropdown-menu-right">
								<a href="javascript:void(0)">
									<div class="msg-header">
										<h6 class="msg-header-title">1 New</h6>
										<p class="msg-header-subtitle">Application Notifications</p>
									</div>
								</a>
								<div class="header-notifications-list">
									<a class="dropdown-item" href="javascript:void(0)">
										<div class="media align-items-center">
											<div class="notify bg-light-mehandi text-mehandi"><i class='bx bx-door-open'></i>
											</div>
											<div class="media-body">
												<h6 class="msg-name">Defense Alerts <span class="msg-time float-right">2 weeks
													ago</span></h6>
												<p class="msg-info">45% less alerts last 4 weeks</p>
											</div>
										</div>
									</a>
								</div>
								<a href="javascript:void(0)">
									<div class="text-center msg-footer">View All Notifications</div>
								</a>
							</div>
						</li> -->

						<li class="nav-item dropdown dropdown-user-profile">
							<a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="javascript:void(0)" data-toggle="dropdown">
								<div class="media user-box align-items-center">
									<div class="media-body user-info">
										<p class="user-name mb-0"><?= $this->session->userdata['logged_admin']['nama'] ?></p>
										<p class="designattion mb-0">Online</p>
									</div>
									<img src="https://via.placeholder.com/110x110" class="user-img" alt="user avatar">
								</div>
							</a>
							<div class="dropdown-menu dropdown-menu-right">	
								<a class="dropdown-item" href="javascript:void(0)"><i
										class="bx bx-tachometer"></i><span>Dashboard</span></a>
								<div class="dropdown-divider mb-0"></div>	
								<a class="dropdown-item" href="<?= base_url('admin/authadmin/logout') ?>" onclick="return confirm('Apakah anda yakin ingin keluar ?')"><i
										class="bx bx-power-off"></i><span>Logout</span></a>
							</div>
						</li>
						
					</ul>
				</div>
			</nav>
		</header>
		<!--end header-->