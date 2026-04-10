<?php
/**
 * DHV Courier — Waybill WPCargo A4 Landscape
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
?>
<?php foreach ($copies as $key => $label) : ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>

@page {
  size: 297mm 210mm landscape;
  margin: 0;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 10px;
  width: 297mm;
  height: 210mm;
  background: #fff;
  color: #111;
  overflow: hidden;
}

/* ══ FOOTER FIJO AL FONDO ══ */
.footer-fixed {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 297mm;
  height: 14mm;
  padding: 0 6mm 3mm 6mm;
  border-top: 1px solid #ddd;
}
.footer-fixed table {
  width: 100%;
  border-collapse: collapse;
}
.footer-fixed td {
  vertical-align: middle;
  font-size: 9px;
  color: #333;
  padding: 0 5mm 0 0;
  white-space: nowrap;
}
.ficon {
  width: 11px; height: 11px;
  vertical-align: middle;
  margin-right: 2px;
}
.phone {
  background: #f26522;
  color: #fff;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 9.5px;
  font-weight: 900;
  display: inline-block;
  margin-right: 2mm;
}

/* ══ QR FIJO ESQUINA INFERIOR DERECHA ══ */
.qr-fixed {
  position: fixed;
  bottom: 3mm;
  right: 5mm;
  width: 34mm;
  text-align: center;
}
.qr-fixed img {
  width: 32mm;
  height: 32mm;
  display: block;
  border: 1px solid #ddd;
  border-radius: 3px;
  padding: 1mm;
}
.qr-label {
  font-size: 6px;
  color: #999;
  margin-top: 1px;
  display: block;
  font-style: italic;
}

/* ══ CONTENIDO PRINCIPAL ══ */
.main {
  padding: 5mm 6mm 18mm 6mm; /* bottom deja espacio al footer fijo */
  width: 297mm;
}

/* ══ CABECERA ══ */
table.tbl-header {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 4mm;
}
table.tbl-header > tbody > tr > td {
  vertical-align: top;
  padding: 0;
}
td.cell-logo {
  width: 44mm;
  text-align: center;
  padding-right: 5mm;
}
td.cell-logo img.logo {
  width: 42mm;
  display: block;
  margin: 0 auto;
}
.ruc {
  font-size: 8px;
  font-weight: bold;
  color: #333;
  margin-top: 3px;
}

td.cell-slogan {
  width: 70mm;
  padding-right: 5mm;
}
.slogan {
  font-size: 25px;
  font-weight: 900;
  color: #1e73be;
  line-height: 1.05;
  text-transform: uppercase;
  margin-bottom: 4mm;
}
.barcode-wrap img.bc {
  width: 64mm;
  height: 16mm;
  display: block;
}
.guia-num {
  font-size: 10px;
  font-weight: bold;
  letter-spacing: 2px;
  color: #111;
  display: block;
  margin-top: 2mm;
  text-align: center;
}

td.cell-cities {
  padding-left: 2mm;
}
.envios-title {
  font-size: 11px;
  font-weight: 900;
  color: #1e73be;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 3mm;
}
table.tbl-cities {
  width: 100%;
  border-collapse: collapse;
}
table.tbl-cities td {
  vertical-align: top;
  padding: 1.2mm 2mm 1.2mm 0;
}
.city-name {
  font-size: 9px;
  font-weight: 700;
  color: #111;
  white-space: nowrap;
}
.city-name-wrap {
  font-size: 9px;
  font-weight: 700;
  color: #111;
  white-space: normal;
}
.city-addr {
  font-size: 7px;
  color: #555;
  padding-left: 12px;
  line-height: 1.3;
  display: block;
}
.pin-o {
  display: inline-block;
  width: 8px; height: 8px;
  background: #f26522;
  border-radius: 50% 50% 50% 0;
  -webkit-transform: rotate(-45deg);
  transform: rotate(-45deg);
  margin-right: 3px;
  vertical-align: middle;
}
.pin-b {
  display: inline-block;
  width: 8px; height: 8px;
  background: #1e73be;
  border-radius: 50% 50% 50% 0;
  -webkit-transform: rotate(-45deg);
  transform: rotate(-45deg);
  margin-right: 3px;
  vertical-align: middle;
}

/* ══ SEPARADOR ══ */
.sep {
  border: none;
  border-top: 1px solid #bbb;
  margin: 0 0 4mm 0;
  width: 100%;
}

/* ══ FORMULARIO ══ */
table.tbl-form {
  width: 257mm; /* deja espacio al QR */
  border-collapse: collapse;
}
table.tbl-form td {
  vertical-align: bottom;
  padding: 0 0 0 0;
}
td.flabel {
  width: 36mm;
  font-size: 18px;
  font-weight: 900;
  color: #111;
  text-transform: uppercase;
  white-space: nowrap;
  padding: 5mm 3mm 3mm 0;
  line-height: 1;
}
td.fline {
  border-bottom: 1.5px dotted #555;
  font-size: 13px;
  color: #111;
  padding: 5mm 2mm 3mm 2mm;
}

</style>
</head>
<body>

<div id="<?php echo esc_attr($key); ?>">

<!-- ══ FOOTER FIJO ══ -->
<div class="footer-fixed">
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

<!-- ══ QR FIJO ══ -->
<div class="qr-fixed">
  <img src="<?php echo esc_url($qr_url); ?>" alt="QR Tracking">
  <span class="qr-label">Escanea para rastrear</span>
</div>

<!-- ══ CONTENIDO ══ -->
<div class="main">

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

</div><!-- /main -->

</div>
</body>
</html>
<?php endforeach; ?>

