<!--footer -->
		<div class="footer">
			<p class="mb-0">Syndash @2020 | Developed By : <a href="https://themeforest.net/user/codervent" target="_blank">codervent</a>
			</p>
		</div>
		<!-- end footer -->
	</div>
	
	<!-- JavaScript -->
	<!-- jQuery first, then Popper.js, then Bootstrap JS -->
	<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script src="<?= base_url() ?>assets/js/jquery.min.js"></script>
	<script src="<?= base_url() ?>assets/js/popper.min.js"></script>
	<script src="<?= base_url() ?>assets/js/bootstrap.min.js"></script>
	<!--plugins-->
	<script src="<?= base_url() ?>assets/plugins/simplebar/js/simplebar.min.js"></script>
	<script src="<?= base_url() ?>assets/plugins/metismenu/js/metisMenu.min.js"></script>
	<script src="<?= base_url() ?>assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js"></script>
	<!-- Vector map JavaScript -->
	<script src="<?= base_url() ?>assets/plugins/vectormap/jquery-jvectormap-2.0.2.min.js"></script>
	<script src="<?= base_url() ?>assets/plugins/vectormap/jquery-jvectormap-world-mill-en.js"></script>
	<script src="<?= base_url() ?>assets/plugins/vectormap/jquery-jvectormap-in-mill.js"></script>
	<script src="<?= base_url() ?>assets/plugins/vectormap/jquery-jvectormap-us-aea-en.js"></script>
	<script src="<?= base_url() ?>assets/plugins/vectormap/jquery-jvectormap-uk-mill-en.js"></script>
	<script src="<?= base_url() ?>assets/plugins/vectormap/jquery-jvectormap-au-mill.js"></script>
	<script src="<?= base_url() ?>assets/plugins/apexcharts-bundle/js/apexcharts.min.js"></script>
	<script src="<?= base_url() ?>assets/js/index2.js"></script>
	<!--Data Tables js-->
	<script src="<?= base_url() ?>assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
	<script>
		var minDate, maxDate;
 
		// Custom filtering function which will search data in column four between two values
		$.fn.dataTable.ext.search.push(
		    function( settings, data, dataIndex ) {
		        var min = $('#min').val()?new Date($('#min').val()):null;
		        var max = $('#max').val()?new Date($('#max').val()):null;
		        
		        var date = new Date( data[2] );
		 
		        if (
		            ( min === null && max === null ) ||
		            ( min === null && date <= max ) ||
		            ( min <= date   && max === null ) ||
		            ( min <= date   && date <= max )
		        ) {
		            return true;
		        }
		        return false;
		    }
		);
		$(document).ready(function () {
			//Default data table
			 // Create date inputs
		    minDate = new Date($('#min'), {
		        format: 'MMMM Do YYYY'
		    });
		    maxDate = new Date($('#max'), {
		        format: 'MMMM Do YYYY'
		    });
		 
		    // DataTables initialisation
		    var table = $('#example').DataTable();

			// var table = $('#example2').DataTable({
			// 	lengthChange: false,
			// 	buttons: ['copy', 'excel', 'pdf', 'print', 'colvis']
			// });
			 // Refilter the table
		    $('#min, #max').on('change', function () {
		        table.draw();
		    });
			table.buttons().container().appendTo('#example2_wrapper .col-md-6:eq(0)');
		});
	</script>
	<!-- App JS -->
	<script src="<?= base_url() ?>assets/js/app.js"></script>

	<!-- laporan -->
	<script src="<?php echo base_url('assets/jquery-ui/jquery-ui.min.js'); ?>"></script> <!-- Load file plugin js jquery-ui -->
    <script>
    $(document).ready(function(){ // Ketika halaman selesai di load
        $('.input-tanggal').datepicker({
            dateFormat: 'yy-mm-dd' // Set format tanggalnya jadi yyyy-mm-dd
        });
        $('#form-tanggal, #form-bulan, #form-tahun').hide(); // Sebagai default kita sembunyikan form filter tanggal, bulan & tahunnya
        $('#filter').change(function(){ // Ketika user memilih filter
            if($(this).val() == '1'){ // Jika filter nya 1 (per tanggal)
                $('#form-bulan, #form-tahun').hide(); // Sembunyikan form bulan dan tahun
                $('#form-tanggal').show(); // Tampilkan form tanggal
            }else if($(this).val() == '2'){ // Jika filter nya 2 (per bulan)
                $('#form-tanggal').hide(); // Sembunyikan form tanggal
                $('#form-bulan, #form-tahun').show(); // Tampilkan form bulan dan tahun
            }else{ // Jika filternya 3 (per tahun)
                $('#form-tanggal, #form-bulan').hide(); // Sembunyikan form tanggal dan bulan
                $('#form-tahun').show(); // Tampilkan form tahun
            }
            $('#form-tanggal input, #form-bulan select, #form-tahun select').val(''); // Clear data pada textbox tanggal, combobox bulan & tahun
        })
    })
    </script>

	<!-- sub kategori di halaman kategori -->
	<script type="text/javascript">
		  $(document).ready(function(){
		        $('#kategori').change(function(){
		            var id_kategori = $('#kategori').val();
		            $.ajax({
		                url : "<?= base_url()?>admin/kategori/sub_kategori",
		                method : "POST",
		                data : {id_kategori: id_kategori},
		                async : false,
		                dataType : 'json',
		                success: function(data){
		                    var html = '';
		                    var i;
		                    for(i=0; i<data.length; i++){
		                        html 	+= '<tr>'
		                        		+  '<td scope="row"></td>'
		                        		+  '<td value="'+data[i].id_sub_kategori+'">'+data[i].nama_sub_kategori+'</td>'
		                        		+  '</tr>';
		                    }
		                    $('#sub_kategori').html(html);
		                     
		                }
		            });
		        });
		    });
	</script>

	<!-- add product -->
	<script type="text/javascript">

	  $(document).ready(function(){

	    tampilukuran();

	    function tampilukuran(){
	       $.ajax({
	            type: 'POST',
	            url: '<?= base_url('admin/product/dataukuran') ?>',
	            dataType: 'json',
	      
	            success: function(response){
	                console.log(response);
	                var i;
	                var no = 0;
	                var html = "";
	                for(i=0;i < response.length ; i++){
	                    no++;
	                    html = html + '<tr>'
	                                + '<td>' + response[i].ukuran  + '</td>'
	                                + '<td><input type="hidden" value="' + response[i].id_ukuran2  + '" name="idukuran[' +i+ ']"></td>'
	                                + '<td>' + response[i].stok  + '</td>'
	                                + '<td>' + response[i].harga  + '</td>'
	                                + '</tr>';
	                }
	                $(".tampil").html(html);
	            }

	        });
	   		}

	        $('#kategori').change(function(){
	            var id_kategori=$('#kategori').val();
	            $.ajax({
	                url : "<?php echo base_url();?>admin/product/sub_kategori",
	                method : "POST",
	                data : {id_kategori: id_kategori},
	                async : false,
	                dataType : 'json',
	                success: function(data){
	                    var html = '';
	                    var i;
	                    for(i=0; i<data.length; i++){
	                        html += '<option value="'+data[i].id_sub_kategori+'">'+data[i].nama_sub_kategori+'</option>';
	                    }
	                    $('#sub_kategori').html(html);
	                     
	                }
	            });
	        });

	        $(".smpn").click(function(){
	            var data = $('.tmbhuk').serialize();
	            $.ajax({
	              type: 'POST',
	              url: "<?= base_url('admin/product/tambahuk2') ?>",
	              data: data,
	              success: function(response) {
	                
	                tampilukuran();
	              }
	            });
	          });
	    });
	</script>
	<script type="text/javascript">
		function generatevoucher()
        {
           $.ajax({
            url:"<?php echo base_url(); ?>admin/fitur/generatevoucher",
            method:"POST",
          
            cache: false,
            success:function(data)
            {
                $('#kdvchr').val(data);
            }
          })

        }
	</script>
	<SCRIPT language="javascript">
    $(function () {

        $("#selectall").click(function () {
            $('.name').attr('checked', this.checked);
        });

        $(".name").click(function () {
            if ($(".name").length == $(".name:checked").length) {
                $("#selectall").attr("checked", "checked");
            } else {
                $("#selectall").removeAttr("checked");
            }
        });

    });

	</SCRIPT>

</body>

</html>