<?php error_reporting(0);?>

<!--page-wrapper-->
		<div class="page-wrapper">
			<!--page-content-wrapper-->
			<div class="page-content-wrapper">
				<div class="page-content">
					
					<div class="card">
						<div class="card-body">
							<div class="card-title">
								<h4 class="mb-0">Data Bukti Transfer</h4>
							</div>
							<hr/>
							<div class="table-responsive">
								<table id="example" class="table table-striped table-bordered" style="width:100%">
									<thead>
                    					<tr>
				                      		<th>Id Invoice</th>
								            <th>Rekening Atas Nama</th>
							     	        <th>Transfer Via</th>
									        <th>Tanggal</th>
				                        	<th>Total Bayar</th>
							      		   	<th>Bukti</th>
				                    	</tr>
				                  	</thead>
									<tbody>
					                   <?php 
					                	foreach ($bukti as $bkt) { ?>
					                	<tr>
					              			<td><?= $bkt->idinvoice ?></td>
					                    	<td><?= $bkt->atasnama ?></td>
					               			<td><?= $bkt->via ?></td>
					               			<td><?= $bkt->tanggal ?></td>
					               			<td>Rp<?= number_format($bkt->bayar, 0, ",", ".") ?></td>
					                      	<td>
					                      		<button type="button" class="btn btn-primary btn-sm radius-15 px-5" data-toggle="modal" data-target="#modalupdate<?= $bkt->id?>">Bukti tf</button>

					                      		<!-- Modal -->
												<div class="modal fade" id="modalupdate<?= $bkt->id?>" tabindex="-1" role="dialog" aria-hidden="true">
													<div class="modal-dialog modal-dialog-centered">
														<div class="modal-content radius-30">
															<div class="modal-header border-bottom-0">
																<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
																</button>
															</div>
															<div class="modal-body p-5">
															<img src="<?= base_url();  ?>/asset/images/buktitf/<?= $bkt->bukti;  ?>" width="100%">
															</div>
														</div>
													</div>
												</div>
					                      	</td>		
					                	</tr>
					                		
					                		<?php } ?>
					                  </tbody>
					                  <tfoot>
										<tr>
				                      		<th>Id Invoice</th>
								            <th>Rekening Atas Nama</th>
							     	        <th>Transfer Via</th>
									        <th>Tanggal</th>
				                        	<th>Total Bayar</th>
							      		   	<th>Bukti</th>
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
