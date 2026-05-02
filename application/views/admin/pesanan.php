<?php error_reporting(0);?>

<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					
					<div class="card">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Data Pesanan</h4>
								<hr>
								
								  <div class="form-row">
								    <div class="col-6 form-group">
								      <input type="date" class="form-control" id="min" name="min">
								    </div>
								    <div class="col-6 form-group">
								      <input type="date" class="form-control" id="max" name="max">
								    </div>
								  </div>
								
							</div>
							<hr/>
							<div class="table-responsive">
								<table id="example" class="table table-striped table-bordered" style="width:100%">
									<thead>
                    					<tr>
				                      		<th>Id Invoice</th>
								            <th>Nama Pemesan</th>
							     	        <th>Tgl Pesan</th>
							     	        <th>Pengiriman</th>
									        <th>Status Bayar</th>
									        <td>No Resi</td>
				                        	<th>Status Kirim</th>
							      		   	<th>Aksi</th>
				                    	</tr>
				                  	</thead>
									<tbody>
					                   <?php 
					                	$no=1;
					                    $tgl = date('Y-m-d H:i:s');
					                	foreach ($invoice as $invc) { ?>

					                	<tr>
					              			<td><?= $invc->id ?></td>
					                    	<td><?= $invc->nama ?></td>
					               			<td><?= $invc->tglpesan ?></td>
					               			<?php if ($invc->intership == '1'){ ?>
					               			<td>Internasional</td>
					               			<?php }else{ ?>
					               			<td>Domestik</td>
					               			<?php } ?>
					             		    <?php if ($invc->status == 'y') {?>
					                    	<td>Sudah Dibayar</td>
					                    	<?php }else if($invc->btsbayar < $tgl){ ?>
					                      	<td>Expired</td>
					                        <?php }else{ ?>
					                      	<td>Belum Dibayar 
					                      		<a href="<?= base_url('admin/pesanan/updatebayar/').$invc->id ?>" onclick="return confirm('are you sure you want to update ?')" class="btn btn-primary btn-sm radius-30">>></a>
					                      	</td>
					                      	<?php } ?>
					                      	<td>
					                      		<button type="button" class="btn btn-primary btn-sm radius-15" data-toggle="modal" data-target="#modalresi<?= $invc->id?>"><?php if($invc->noresi == null){echo "-";}else{ ?><?= $invc->noresi ?><?php } ?></button>

					                      		<!-- Modal -->
												<div class="modal fade" id="modalresi<?= $invc->id?>" tabindex="-1" role="dialog" aria-hidden="true">
													<div class="modal-dialog modal-dialog-centered">
														<div class="modal-content radius-30">
															<div class="modal-header border-bottom-0">
																<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
																</button>
															</div>
															<div class="modal-body p-5">
				            								<form method="post" enctype="multipart/form-data" action="<?= base_url('admin/pesanan/updateresi')?>">
				            									<div class="form-group">
				            									<label>Id Invoice</label>
													          	<input type="text" class="form-control radius-30" name="id" value="<?= $invc->id ?>" readonly>
													      		</div>
													      		<div class="form-group">
				            									<label>Input No Resi</label>
													          	<input type="text" class="form-control radius-30" name="noresi">
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
					                      	</td>
					                    	<?php if($invc->btsbayar < $tgl && $invc->status == 'n'){ ?>
					                      	<td>Cancelled</td>
					                    	<?php }else{ ?>
					                      	<td>
					                      		<button type="button" class="btn btn-primary btn-sm radius-15" data-toggle="modal" data-target="#modalupdate<?= $invc->id?>" width="50px"><?= $invc->statuskirim ?></button>

					                      		<!-- Modal -->
												<div class="modal fade" id="modalupdate<?= $invc->id?>" tabindex="-1" role="dialog" aria-hidden="true">
													<div class="modal-dialog modal-dialog-centered">
														<div class="modal-content radius-30">
															<div class="modal-header border-bottom-0">
																<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
																</button>
															</div>
															<div class="modal-body p-5">
				            								<form method="post" enctype="multipart/form-data" action="<?= base_url('admin/pesanan/updatekirim')?>">
				            									<div class="form-group">
				            									<label>Kode Pesanan</label>
													          	<input type="text" class="form-control radius-30" name="id" value="<?= $invc->id ?>" readonly>
													      		</div>
													          <div class="form-group">
													          	<label>Status</label>
													          <select class="form-control radius-30" name="statuskirim">
													            <option><?= $invc->statuskirim ?></option>
													            <option>--</option>
													            <option>Being Processed</option>
													            <option>Being Packed</option>
													            <option>Being Sent</option>
													            <option>Package Received</option>
													          </select>
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
					                      	</td>
					                      	<?php } ?>
					                    	<td>
					                    		<?= anchor('admin/pesanan/detail/'.$invc->id,'<div class="btn btn-primary btn-sm radius-30">Detail</div>');?>
					                    	</td>				
					                	</tr>
					                		
					                		<?php } ?>
					                  </tbody>
					                  <tfoot>
										<tr>
				                      		<th>Id Invoice</th>
								            <th>Nama Pemesan</th>
							     	        <th>Tgl Pesan</th>
							     	        <th>Pengiriman</th>
									        <th>Status Bayar</th>
									        <td>No Resi</td>
				                        	<th>Status Kirim</th>
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
