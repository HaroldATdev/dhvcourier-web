<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPC_Facturacion_Metabox {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
	}

	public function add_metabox() {
		add_meta_box(
			'wpcfact_shipment_metabox',
			'📄 Comprobante SUNAT',
			array( $this, 'render_metabox' ),
			'wpcargo_shipment',
			'side',
			'high'
		);
	}

	public function render_metabox( $post ) {
		$comprobante = WPC_Facturacion_Comprobante::comprobante_de_envio( $post->ID );

		if ( ! $comprobante ) {
			echo '<p style="color:#666;">Sin comprobante emitido.</p>';
			echo '<a href="admin.php?page=wpcfact-emitir" class="button button-small">Ir a Emitir</a>';
			return;
		}

		$color = 'gray';
		if ( $comprobante->estado === 'ACEPTADO' ) $color = 'green';
		if ( $comprobante->estado === 'PENDIENTE' ) $color = 'orange';
		if ( $comprobante->estado === 'RECHAZADO' ) $color = 'red';

		echo '<div style="background:#f9f9f9; padding:10px; border:1px solid #ddd; border-radius:4px;">';
		echo '<p><strong>Incluido en:</strong> ' . esc_html( $comprobante->serie . '-' . $comprobante->correlativo ) . '</p>';
		echo '<p><strong>Cliente:</strong> ' . esc_html( $comprobante->cliente_nombre ) . '</p>';
		echo '<p><strong>Estado:</strong> <span style="color:' . $color . '; font-weight:bold;">' . esc_html( $comprobante->estado ) . '</span></p>';
		
		echo '<hr style="margin:10px 0;">';
		if ( ! empty( $comprobante->document_id ) && strpos( $comprobante->document_id, 'LOCAL-' ) !== 0 ) {
			echo '<a href="' . esc_url( WPC_Facturacion_APISunat::get_pdf_url( $comprobante->document_id, $comprobante->file_name, 'A4' ) ) . '" target="_blank" class="button button-small">Ver PDF</a> ';
		}
		echo '<a href="admin.php?page=wpcfact-comprobantes" class="button button-small">Ver Detalles</a>';
		echo '</div>';
	}
}

new WPC_Facturacion_Metabox();
