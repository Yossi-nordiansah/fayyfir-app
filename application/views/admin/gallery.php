<!--page-wrapper-->
<div class="page-wrapper">
  <!--page-content-wrapper-->
  <div class="page-content-wrapper">
    <div class="page-content">

      <div class="card radius-15">
        <div class="card-body">
          <div class="card-title">
            <h4 class="mb-0">Gallery</h4>
            <button style="float: right; margin-top: -30px" type="button" class="btn btn-primary btn-sm radius-30 px-5" data-toggle="modal" data-target="#modaltambahgallery">Tambah data</button>
          </div>
          <hr/>

          <!-- Modal Tambah Gallery -->
          <div class="modal fade" id="modaltambahgallery" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content radius-30">
                <div class="modal-header border-bottom-0">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body p-5">
                  <form method="post" action="<?= base_url('admin/fitur/tambahgallery') ?>" enctype="multipart/form-data">
                    <h3 class="text-center mb-4">Tambah Foto Gallery</h3>

                    <div class="form-group">
                      <label>Image</label>
                      <input type="file" class="form-control form-control-lg radius-30" name="gambar_gallery" required />
                    </div>

                    <div class="form-group">
                      <label>Nama Gambar</label>
                      <input type="text" class="form-control form-control-lg radius-30" name="nama_gambar" required />
                    </div>

                    <div class="form-group">
                      <label>Keterangan (Indonesia)</label>
                      <textarea class="form-control form-control-lg radius-30" rows="3" name="desc_indo"></textarea>
                    </div>

                    <div class="form-group">
                      <label>Keterangan (English)</label>
                      <textarea class="form-control form-control-lg radius-30" rows="3" name="desc_english"></textarea>
                    </div>

                    <div class="form-group">
                      <button type="submit" class="btn btn-primary radius-30 btn-lg btn-block">Tambah</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <!-- End Modal -->

          <!-- Tabel Gallery -->
          <div class="table-responsive">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th width="30%">Gambar</th>
                  <th>Nama</th>
                  <th>Keterangan ID</th>
                  <th>Keterangan EN</th>
                  <th width="10%">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($gallery as $gl) { ?>
                <tr>
                  <td>
                    <img src="<?= base_url('asset/images/gallery/' . $gl->gambar_gallery) ?>" alt="Gallery Image" width="150" class="rounded">
                  </td>
                  <td><?= htmlspecialchars($gl->nama_gambar) ?></td>
                  <td><?= nl2br(htmlspecialchars($gl->desc_indo)) ?></td>
                  <td><?= nl2br(htmlspecialchars($gl->desc_english)) ?></td>
                  <td>
                    <a class="btn btn-danger btn-sm radius-30" href="<?= base_url('admin/fitur/deletefoto/' . $gl->id_gambar) ?>" onclick="return confirm('Yakin ingin menghapus gambar ini?')">
                      <i class="fadeIn animated bx bx-trash-alt"></i>
                    </a>
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
<div class="overlay toggle-btn-mobile"></div>
<a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>