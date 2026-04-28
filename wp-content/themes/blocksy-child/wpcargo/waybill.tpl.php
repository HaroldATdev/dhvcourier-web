<?php
/**
 * DHV Courier — Waybill WPCargo A4 Landscape (4 per page)
 * Ruta: wp-content/themes/TU-CHILD-THEME/wpcargo/waybill.tpl.php
 *
 * Agregar en functions.php del child theme:
 * add_filter('wpcfe_pdf_paper_size', function(){ return [0,0,842,595]; });
 */

$copies = ['original' => 'Original'];
$copies = apply_filters('wpcargo_print_label_template_copies', $copies);
if (empty($copies)) return false;

$shipment_id  = $shipmentDetails['shipmentID'];
$guia         = get_the_title($shipment_id);

$tracking_url = 'https://dhvcourier.com/track-form/?tracking_number=' . urlencode($guia);
$qr_url       = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($tracking_url);

if ( ! function_exists( 'dhv_meta' ) ) {
  function dhv_meta($id, $key, $fb = '') {
    $v = get_post_meta($id, $key, true);
    return (!empty($v)) ? esc_html($v) : $fb;
  }
}

// Variables for Grid Layout
$is_bulk     = isset($counter) && isset($shipment_num);
$current_idx = $is_bulk ? $counter : 1;
$total_items = $is_bulk ? $shipment_num : 1;
?>

<?php if (!defined('WAYBILL_STYLE_ADDED')): define('WAYBILL_STYLE_ADDED', true); ?>
<style>
@page {
  size: 297mm 210mm landscape;
  margin: 0;
}
body {
  margin: 0;
  padding: 0;
  width: 297mm;
  height: 210mm;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

table.grid-table {
  width: 296mm;
  border-collapse: collapse;
  margin: 0;
  padding: 0;
}
table.grid-table > tbody > tr > td.grid-cell {
  width: 148mm;
  height: 104mm;
  vertical-align: top;
  padding: 0;
  border-right: 1px dashed #ccc;
  border-bottom: 1px dashed #ccc;
}

.label-container {
  font-family: Arial, Helvetica, sans-serif;
  width: 148mm;
  height: 104mm;
  position: relative;
  background: #fff;
  color: #111;
  overflow: hidden;
}

/* ══ FOOTER AL FONDO DE LA ETIQUETA ══ */
.footer-label {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 148mm;
  height: 8.5mm;
  padding: 0 4mm 2mm 4mm;
  border-top: 0.5px solid #ddd;
}
.footer-label table {
  width: 100%;
  border-collapse: collapse;
}
.footer-label td {
  vertical-align: middle;
  font-size: 5.5px;
  color: #333;
  padding: 0 2.5mm 0 0;
  white-space: nowrap;
}
.ficon {
  width: 7px; height: 7px;
  vertical-align: middle;
  margin-right: 1.5px;
}
.phone {
  background: #f26522;
  color: #fff;
  padding: 2px 5px;
  border-radius: 2px;
  font-size: 5.5px;
  font-weight: 900;
  display: inline-block;
  margin-right: 1mm;
}

/* ══ QR FIJO ESQUINA INFERIOR DERECHA ══ */
.qr-label-box {
  position: absolute;
  bottom: 2mm;
  right: 3mm;
  width: 20mm;
  text-align: center;
}
.qr-label-box img {
  width: 19mm;
  height: 19mm;
  display: block;
  border: 0.5px solid #ddd;
  border-radius: 1.5px;
  padding: 0.5mm;
}
.qr-text {
  font-size: 4px;
  color: #999;
  margin-top: 0.5px;
  display: block;
  font-style: italic;
}

/* ══ CONTENIDO PRINCIPAL ══ */
.main-content {
  padding: 4mm 4mm 10mm 4mm; /* bottom deja espacio al footer fijo */
  width: 148mm;
}

/* ══ CABECERA ══ */
table.tbl-header {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 3mm;
}
table.tbl-header > tbody > tr > td {
  vertical-align: top;
  padding: 0;
}
td.cell-logo {
  width: 28mm;
  text-align: center;
  padding-right: 2.5mm;
}
td.cell-logo img.logo {
  width: 25mm;
  display: block;
  margin: 0 auto;
}
.ruc {
  font-size: 5.5px;
  font-weight: bold;
  color: #333;
  margin-top: 2px;
}

td.cell-slogan {
  width: 42mm;
  padding-right: 2.5mm;
}
.slogan {
  font-size: 14.5px;
  font-weight: 900;
  color: #1e73be;
  line-height: 1.05;
  text-transform: uppercase;
  margin-bottom: 2.5mm;
}
.barcode-wrap img.bc {
  width: 36mm;
  height: 10mm;
  display: block;
}
.guia-num {
  font-size: 11px;
  font-weight: bold;
  letter-spacing: 1.5px;
  color: #111;
  display: block;
  margin-top: 2.5mm;
  text-align: center;
}

td.cell-cities {
  padding-left: 1mm;
}
.envios-title {
  font-size: 9px;
  font-weight: 900;
  color: #1e73be;
  text-transform: uppercase;
  letter-spacing: 0.25px;
  margin-bottom: 2mm;
}
table.tbl-cities {
  width: 100%;
  border-collapse: collapse;
}
table.tbl-cities td {
  vertical-align: top;
  padding: 0.5mm 1mm 0.5mm 0 !important;
}
.city-name {
  font-size: 9.5px !important;
  font-weight: 700;
  color: #111;
  white-space: nowrap;
}
.city-name-wrap {
  font-size: 9.5px !important;
  font-weight: 700;
  color: #111;
  white-space: normal;
}
.city-addr {
  font-size: 7.5px !important;
  color: #555;
  padding-left: 9px;
  line-height: 1.3;
  display: block;
}
.pin-o {
  display: inline-block;
  width: 5px; height: 5px;
  background: #f26522;
  border-radius: 50% 50% 50% 0;
  -webkit-transform: rotate(-45deg);
  transform: rotate(-45deg);
  margin-right: 2px;
  vertical-align: middle;
}
.pin-b {
  display: inline-block;
  width: 5px; height: 5px;
  background: #1e73be;
  border-radius: 50% 50% 50% 0;
  -webkit-transform: rotate(-45deg);
  transform: rotate(-45deg);
  margin-right: 2px;
  vertical-align: middle;
}

/* ══ SEPARADOR ══ */
.sep {
  border: none;
  border-top: 0.5px solid #bbb;
  margin: 0 0 2.5mm 0;
  width: 100%;
}

/* ══ FORMULARIO ══ */
table.tbl-form {
  width: 125mm; /* deja espacio al QR */
  border-collapse: collapse;
}
table.tbl-form td {
  vertical-align: bottom;
}
td.flabel {
  width: 25mm;
  font-size: 13px !important;
  font-weight: 900;
  color: #111;
  text-transform: uppercase;
  white-space: nowrap;
  padding: 5mm 2mm 5mm 0 !important;
  line-height: 1;
}
td.fline {
  border-bottom: 0.75px dotted #555;
  font-size: 11px !important;
  color: #111;
  padding: 5mm 2mm 5mm 2mm !important;
}
</style>
<?php endif; ?>

<?php if ($current_idx % 4 == 1): ?>
<table class="grid-table">
<?php endif; ?>

<?php if ($current_idx % 2 == 1): ?>
  <tr>
<?php endif; ?>

  <td class="grid-cell">
    <?php foreach ($copies as $key => $label) : ?>
    <div class="label-container" id="<?php echo esc_attr($key); ?>">

      <!-- ══ FOOTER FIJO RELATIVO ══ -->
      <div class="footer-label">
        <table>
        <tr>
          <td>
            <img class="ficon" src="https://cdn-icons-png.flaticon.com/512/561/561127.png" alt="">
            courier@grupodhv.com
          </td>
          <td>
            <img class="ficon" src="https://cdn-icons-png.flaticon.com/512/841/841364.png" alt="">
            www.grupodhv.com
          </td>
          <td>
            <span class="phone">934 072 960</span>
            <span class="phone">919 291 859</span>
            <span class="phone">936 340 139</span>
          </td>
        </tr>
        </table>
      </div>

      <!-- ══ QR FIJO RELATIVO ══ -->
      <div class="qr-label-box">
        <img src="<?php echo esc_url($qr_url); ?>" alt="QR Tracking">
        <span class="qr-text">Escanea para rastrear</span>
      </div>

      <!-- ══ CONTENIDO ══ -->
      <div class="main-content">

        <!-- CABECERA -->
        <table class="tbl-header">
        <tr>
          <td class="cell-logo">
            <img class="logo" src="https://grupodhv.com/wp-content/uploads/2025/03/6-1.png" alt="DHV Courier">
            <div class="ruc">RUC: 20611135786</div>
          </td>
          <td class="cell-slogan">
            <div class="slogan">EFICIENCIA,<br>RAPIDEZ Y<br>CONFIANZA</div>
            <div class="barcode-wrap">
              <img class="bc"
                   src="<?php echo esc_url($shipmentDetails['barcode']); ?>"
                   alt="<?php echo esc_attr($guia); ?>">
              <span class="guia-num"><?php echo esc_html($guia); ?></span>
            </div>
          </td>
          <td class="cell-cities">
            <div class="envios-title">ENVÍOS A LIMA Y PROVINCIA</div>
            <table class="tbl-cities">
              <colgroup>
                <col style="width:31%">
                <col style="width:17%">
                <col style="width:21%">
                <col style="width:31%">
              </colgroup>
              <tr>
                <td><span class="city-name"><span class="pin-o"></span>Villa el Salvador</span><span class="city-addr">Av. Mariano Pastor Sevilla S/N</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Ica</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Tarapoto</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Piura</span></td>
              </tr>
              <tr>
                <td><span class="city-name"><span class="pin-o"></span>Santa Anita</span><span class="city-addr">Av. Rosales con Cascanueces</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Trujillo</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Moyobamba</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Tumbes</span></td>
              </tr>
              <tr>
                <td><span class="city-name"><span class="pin-o"></span>Callao</span><span class="city-addr">Av. Elmer Faucett 4615</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Chiclayo</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Rioja</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Chachapoyas</span></td>
              </tr>
              <tr>
                <td><span class="city-name"><span class="pin-o"></span>SJL</span><span class="city-addr">Jr. Mejoranas 763</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Bagua</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Pedro Ruiz</span></td>
                <td><span class="city-name-wrap"><span class="pin-b"></span>Rodríguez de Mendoza</span></td>
              </tr>
              <tr>
                <td></td>
                <td><span class="city-name"><span class="pin-b"></span>Jaén</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Arequipa</span></td>
                <td><span class="city-name"><span class="pin-b"></span>Huambo</span></td>
              </tr>
            </table>
          </td>
        </tr>
        </table>

        <hr class="sep">

        <!-- FORMULARIO -->
        <table class="tbl-form">
          <tr>
            <td class="flabel">NOMBRE</td>
            <td class="fline"><?php echo dhv_meta($shipment_id, 'destinatario'); ?></td>
          </tr>
          <tr>
            <td class="flabel">TELÉFONO</td>
            <td class="fline"><?php echo dhv_meta($shipment_id, 'telefono_destinatario'); ?></td>
          </tr>
          <tr>
            <td class="flabel">DIRECCIÓN</td>
            <td class="fline"><?php echo dhv_meta($shipment_id, 'direccion_destinatario'); ?></td>
          </tr>
          <tr>
            <td class="flabel">CIUDAD</td>
            <td class="fline"><?php echo dhv_meta($shipment_id, 'lugar_destino'); ?></td>
          </tr>
          <tr>
            <td class="flabel">REFERENCIA</td>
            <td class="fline"><?php echo dhv_meta($shipment_id, 'referencia_destinatario'); ?></td>
          </tr>
        </table>

      </div><!-- /main-content -->

    </div>
    <?php endforeach; ?>
  </td>

<?php if ($current_idx % 2 == 0 || $current_idx == $total_items): ?>
  </tr>
<?php endif; ?>

<?php if ($current_idx % 4 == 0 || $current_idx == $total_items): ?>
</table>
<?php endif; ?>

