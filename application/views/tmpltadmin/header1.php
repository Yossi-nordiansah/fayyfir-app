<!DOCTYPE html>
<html lang="en">

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
	<title>Admin Alsharif Shop</title>
	<!--favicon-->
	<link rel="icon" href="<?=base_url() ?>assets/images/favicon-32x32.png" type="image/png" />
	<!-- Vector CSS -->
	<link href="<?=base_url() ?>assets/plugins/vectormap/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
	<!--plugins-->
	<link href="<?=base_url() ?>assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
	<!--Data Tables -->
	<link href="<?=base_url() ?>assets/plugins/datatable/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css">
	<link href="<?=base_url() ?>assets/plugins/datatable/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css">
	<!--plugins-->
	<link href="<?=base_url() ?>assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
	<link href="<?=base_url() ?>assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet" />
	<link href="<?=base_url() ?>assets/plugins/metismenu/css/metisMenu.min.css" rel="stylesheet" />
	<!-- loader-->
	<link href="<?=base_url() ?>assets/css/pace.min.css" rel="stylesheet" />
	<script src="<?=base_url() ?>assets/js/pace.min.js"></script>
	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="<?=base_url() ?>assets/css/bootstrap.min.css" />
	<!-- Icons CSS -->
	<link rel="stylesheet" href="<?=base_url() ?>assets/css/icons.css" />
	<!-- App CSS -->
	<link rel="stylesheet" href="<?=base_url() ?>assets/css/app.css" />
	<link rel="stylesheet" href="<?=base_url() ?>assets/css/dark-sidebar.css" />
	<link rel="stylesheet" href="<?=base_url() ?>assets/css/dark-theme.css" />
</head>

<body>
	<!-- wrapper -->
	<div class="wrapper">
		<!--sidebar-wrapper-->
		<div class="sidebar-wrapper" data-simplebar="true">
			<div class="sidebar-header">
				<div class="">
					<img src="<?=base_url() ?>assets/images/logo-icon.png" class="logo-icon-2" alt="" />
				</div>
				<div>
					<h4 class="logo-text">AlsharifShop</h4>
				</div>
				<a href="javascript:;" class="toggle-btn ml-auto"> <i class="bx bx-menu"></i>
				</a>
			</div>
			<!--navigation-->
			<ul class="metismenu" id="menu">
				<li>
					<a href="<?= base_url('admin/dashboard') ?>">
						<div class="parent-icon icon-color-1"><i class="bx bx-home-alt"></i>
						</div>
						<div class="menu-title">Dashboard</div>
					</a>
				</li>

				<li class="menu-label">Master</li>
				<li>
					<a href="<?= base_url('admin/kategori') ?>">
						<div class="parent-icon icon-color-4"><i class="bx bx-archive"></i>
						</div>
						<div class="menu-title">Kategori & Sub</div>
					</a>
				</li>
				<li>
					<a href="<?= base_url('admin/user') ?>">
						<div class="parent-icon icon-color-5"><i class="bx bx-group"></i>
						</div>
						<div class="menu-title">User</div>
					</a>
				</li>
				<li>
					<a href="<?= base_url('admin/product') ?>">
						<div class="parent-icon icon-color-6"><i class="bx bx-folder"></i>
						</div>
						<div class="menu-title">Produk</div>
					</a>
				</li>
				
				<li class="menu-label">Fitur</li>
				<li>
					<a href="<?= base_url('admin/fitur/banner') ?>">
						<div class="parent-icon icon-color-6"><i class="bx bx-menu"></i>
						</div>
						<div class="menu-title">Banner</div>
					</a>
				</li>
				<li>
					<a href="<?= base_url('admin/fitur/gallery') ?>">
						<div class="parent-icon icon-color-6"><i class="bx bx-image"></i>
						</div>
						<div class="menu-title">Gallery</div>
					</a>
				</li>
				<li>
					<a href="<?= base_url('admin/fitur/voucher') ?>">
						<div class="parent-icon icon-color-6"><i class="bx bx-archive"></i>
						</div>
						<div class="menu-title">Voucher</div>
					</a>
				</li>
				<li>
					<a href="<?= base_url('admin/fitur/promo') ?>">
						<div class="parent-icon icon-color-6"><i class="bx bx-archive"></i>
						</div>
						<div class="menu-title">Promo</div>
					</a>
				</li>

				<li class="menu-label">Transaksi</li>
				<li>
					<a href="<?= base_url('admin/pesanan') ?>">
						<div class="parent-icon icon-color-6"><i class="bx bx-task"></i>
						</div>
						<div class="menu-title">Pesanan</div>
					</a>
				</li>
				<li>
					<a href="<?= base_url('admin/laporan') ?>">
						<div class="parent-icon icon-color-7"><i class="bx bx-file"></i>
						</div>
						<div class="menu-title">Laporan</div>
					</a>
				</li>
				<li>
					<a href="<?= base_url('admin/bukti') ?>">
						<div class="parent-icon icon-color-7"><i class="bx bx-task"></i>
						</div>
						<div class="menu-title">Bukti Transfer</div>
					</a>
				</li>
				
			</ul>
			<!--end navigation-->
		</div>
		<!--end sidebar-wrapper-->