<div class="page-content">
    <div class="holder breadcrumbs-wrap mt-0">
      <div class="container">
        <ul class="breadcrumbs">
          <li><a href="index.html">Home</a></li>
          <li><span>Cart</span></li>
        </ul>
      </div>
    </div>
    <div class="holder">
      <div class="container">
        <div class="page-title text-center">
          <h1>Shopping Cart</h1>
        </div>
        <div class="row">
          
          <div class="col-lg-11 col-xl-13">
            <div class="cart-table">
              
              <div class="cart-table-prd cart-table-prd--head py-1 d-none d-md-flex">
                <div class="cart-table-prd-image text-center">
                  Image
                </div>
                <div class="cart-table-prd-content-wrap">
                  <div class="cart-table-prd-info">Name</div>
                  <div class="cart-table-prd-qty">Qty</div>
                  <div class="cart-table-prd-price">Price</div>
                  <div class="cart-table-prd-action">&nbsp;</div>
                </div>
              </div>
              <div id="cartpagedetail">
                
              </div>
            </div>
            <div class="text-center mt-1"><a href="<?= base_url('cart/deleteall') ?>" class="btn" style="background-color: #E74C3C;">Clear All</a></div>
          </div>

          <div class="col-lg-7 col-xl-5 mt-3 mt-md-0">
            <div class="panel">
            <div class="panel-heading active">
              <h4 class="panel-title">
                <a data-toggle="collapse" data-parent="#productAccordion" href="#collapse2">Discount Code</a>
                <span class="toggle-arrow"><span></span><span></span></span>
              </h4>
            </div>
            <div id="collapse2" class="panel-collapse collapse show">
              <div class="panel-body">
                <p>Got a promo code? Then you're a few randomly combined numbers & letters away from fab savings!</p>
                <div class="form-inline mt-2">
                  <input type="text" name="kodevoucher" id="kodevoucher" class="form-control form-control--sm" placeholder="Promotion/Discount Code">
                  <button class="btn" onclick="cekkupon()">Apply</button>
                </div>
              </div>
            </div>
          </div>
          <br>
          <center>
          <div id="statVoc"></div>
          </center>
          <br>
            <div class="card-total">
              <div class="row d-flex">
                <div class="col card-total-txt">Total</div>
                <div class="col-auto card-total-price text-right" id="totalpay2"></div>
              </div>
              <small class="float-right" style="font-size: 15px; color: red; margin-top: -2px;" id="totalpaybef"></small>
              <br>
              <form action="<?= base_url('cart/to_checkout') ?>" method="post">
              <input type="hidden" name="total" id="totalpay3"/>
              <input type="hidden" name="totaldisc" id="totalpaydisc"/>
              <input type="hidden" name="potongan" id="potongan"/>
              <input type="hidden" name="voucher" id="voc"/>
              <button type="submit" class="btn btn--full btn--lg"><span>Checkout</span></button>
              </form>
              <div class="card-text-info text-right">
                <h5>Standart shipping</h5>
                <p><b>5 - 7 business days</b><br>Orders are shipped from Jakarta and will be dispatched within 5 - 7 working days</p>
              </div>
            </div>
            <div class="mt-2"></div>
          </div>
        </div>
      </div>
    </div>
  </div>