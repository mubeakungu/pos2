<?php

if($_SESSION["profile"] != "Administrator"){

  echo '<script>window.location = "home";</script>';
  return;

}

$s = ControllerSettings::all();

function settingValue($s, $key){

  return htmlspecialchars((string)$s[$key], ENT_QUOTES, "UTF-8");

}

?>
<div class="content-wrapper">

  <section class="content-header">

    <h1>Business Settings</h1>

    <ol class="breadcrumb">
      <li><a href="home"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Settings</li>
    </ol>

  </section>

  <section class="content">

    <form role="form" method="post" enctype="multipart/form-data">

    <div class="row">

      <!--=====================================
      BUSINESS DETAILS
      ======================================-->

      <div class="col-md-6">

        <div class="box box-primary">

          <div class="box-header with-border"><h3 class="box-title">Business details</h3>
            <p class="help-block">Shown on receipts and at the top of the app.</p></div>

          <div class="box-body">

            <div class="form-group">
              <label>Business name</label>
              <input type="text" class="form-control" name="settingsBusinessName" value="<?php echo settingValue($s, "businessName"); ?>" required>
            </div>

            <div class="form-group">
              <label>Address</label>
              <input type="text" class="form-control" name="settingsAddress" value="<?php echo settingValue($s, "address"); ?>" placeholder="e.g. Moi Avenue, Nairobi">
            </div>

            <div class="row">
              <div class="form-group col-sm-6">
                <label>Phone</label>
                <input type="text" class="form-control" name="settingsPhone" value="<?php echo settingValue($s, "phone"); ?>" placeholder="e.g. 0712 345 678">
              </div>
              <div class="form-group col-sm-6">
                <label>Email</label>
                <input type="email" class="form-control" name="settingsEmail" value="<?php echo settingValue($s, "email"); ?>">
              </div>
            </div>

            <div class="form-group">
              <label>Tax / KRA PIN</label>
              <input type="text" class="form-control" name="settingsTaxId" value="<?php echo settingValue($s, "taxId"); ?>" placeholder="Printed on receipts if filled in">
            </div>

            <div class="form-group">
              <label>Logo (PNG or JPG)</label>
              <?php if($s["logo"] != ""): ?>
                <div style="margin-bottom:10px">
                  <img src="<?php echo settingValue($s, "logo"); ?>" style="max-height:80px; background:#eee; padding:5px">
                  <label style="font-weight:normal; margin-left:10px"><input type="checkbox" name="settingsRemoveLogo"> Remove logo</label>
                </div>
              <?php endif; ?>
              <input type="file" name="settingsLogo" accept="image/png,image/jpeg">
              <p class="help-block">Without a logo, the business name is shown instead.</p>
            </div>

            <div class="form-group">
              <label>Time zone</label>
              <select class="form-control" name="settingsTimezone">
                <?php
                  foreach(timezone_identifiers_list() as $tz){
                    echo '<option value="'.$tz.'"'.($tz == $s["timezone"] ? ' selected' : '').'>'.$tz.'</option>';
                  }
                ?>
              </select>
            </div>

          </div>

        </div>

      </div>

      <div class="col-md-6">

        <!--=====================================
        CURRENCY AND TAX
        ======================================-->

        <div class="box box-success">

          <div class="box-header with-border"><h3 class="box-title">Currency and tax</h3></div>

          <div class="box-body">

            <div class="row">
              <div class="form-group col-sm-6">
                <label>Currency symbol</label>
                <input type="text" class="form-control" name="settingsCurrencySymbol" value="<?php echo settingValue($s, "currencySymbol"); ?>" maxlength="6" required>
              </div>
              <div class="form-group col-sm-6">
                <label>Currency code</label>
                <input type="text" class="form-control" name="settingsCurrencyCode" value="<?php echo settingValue($s, "currencyCode"); ?>" maxlength="3">
              </div>
            </div>

            <div class="row">
              <div class="form-group col-sm-6">
                <label>Tax name</label>
                <input type="text" class="form-control" name="settingsTaxName" value="<?php echo settingValue($s, "taxName"); ?>" placeholder="VAT">
              </div>
              <div class="form-group col-sm-6">
                <label>Tax rate</label>
                <div class="input-group">
                  <input type="number" class="form-control" name="settingsTaxRate" value="<?php echo (float)$s["taxRate"]; ?>" min="0" max="100" step="0.01" required>
                  <span class="input-group-addon">%</span>
                </div>
              </div>
            </div>

            <div class="checkbox">
              <label><input type="checkbox" name="settingsTaxInclusive" <?php echo $s["taxInclusive"] ? "checked" : ""; ?>> My product prices already include tax</label>
              <p class="help-block">Ticked: the customer pays the shelf price and the tax is worked out from it. Unticked: tax is added on top.</p>
            </div>

          </div>

        </div>

        <!--=====================================
        RECEIPTS AND PAYMENTS
        ======================================-->

        <div class="box box-warning">

          <div class="box-header with-border"><h3 class="box-title">Receipts and payments</h3></div>

          <div class="box-body">

            <div class="form-group">
              <label>Receipt size</label>
              <select class="form-control" name="settingsReceiptFormat">
                <option value="ticket" <?php echo $s["receiptFormat"] == "ticket" ? "selected" : ""; ?>>Small ticket (till roll)</option>
                <option value="a4" <?php echo $s["receiptFormat"] == "a4" ? "selected" : ""; ?>>A4 invoice</option>
              </select>
            </div>

            <div class="form-group">
              <label>Receipt footer message</label>
              <textarea class="form-control" name="settingsReceiptFooter" rows="2"><?php echo settingValue($s, "receiptFooter"); ?></textarea>
            </div>

            <div class="form-group">
              <label>M-Pesa Till / Paybill number</label>
              <input type="text" class="form-control" name="settingsMpesaTill" value="<?php echo settingValue($s, "mpesaTill"); ?>" placeholder="e.g. Till 123456">
              <p class="help-block">Printed on receipts if filled in.</p>
            </div>

          </div>

        </div>

        <!--=====================================
        THERMAL PRINTER
        ======================================-->

        <div class="box box-default">

          <div class="box-header with-border"><h3 class="box-title">Thermal receipt printer (optional)</h3></div>

          <div class="box-body">

            <div class="checkbox">
              <label><input type="checkbox" name="settingsPrinterEnabled" <?php echo $s["printerEnabled"] ? "checked" : ""; ?>> Print a receipt automatically after every sale</label>
            </div>

            <div class="row">
              <div class="form-group col-sm-5">
                <label>Printer connection</label>
                <select class="form-control" name="settingsPrinterType">
                  <option value="windows" <?php echo $s["printerType"] == "windows" ? "selected" : ""; ?>>USB / shared (Windows)</option>
                  <option value="network" <?php echo $s["printerType"] == "network" ? "selected" : ""; ?>>Network (IP address)</option>
                </select>
              </div>
              <div class="form-group col-sm-7">
                <label>Printer share name or IP</label>
                <input type="text" class="form-control" name="settingsPrinterAddress" value="<?php echo settingValue($s, "printerAddress"); ?>" placeholder="e.g. POS58 or 192.168.1.50">
              </div>
            </div>

            <div class="checkbox">
              <label><input type="checkbox" name="settingsOpenCashDrawer" <?php echo $s["openCashDrawer"] ? "checked" : ""; ?>> Open the cash drawer after printing</label>
            </div>

            <p class="help-block">The printer must be an ESC/POS printer connected to the computer running the POS. For USB on Windows, share the printer and enter its share name.</p>

          </div>

        </div>

      </div>

    </div>

    <div class="box">
      <div class="box-footer">
        <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-save"></i> Save settings</button>
      </div>
    </div>

    </form>

    <?php ControllerSettings::ctrSaveSettings(); ?>

  </section>

</div>
