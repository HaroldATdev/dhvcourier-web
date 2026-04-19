<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCFIN_Database {

    const VERSION = '2.0.0';

    const CUENTAS = [
        'caja_empresa'         => 'Caja Empresa',
        'balance_motorizado'   => 'Balance Motorizado',
        'deuda_a_remitente'    => 'DHV debe → Remitente',
        'deuda_de_remitente'   => 'Remitente debe → DHV',
        'deuda_a_destinatario' => 'DHV debe → Destinatario',
        'registro_externo'     => 'Registro Externo',
    ];

    const ACTORES = [
        'empresa'      => 'Empresa',
        'motorizado'   => 'Motorizado',
        'remitente'    => 'Remitente',
        'destinatario' => 'Destinatario',
        'ninguno'      => 'Ninguno (prepago)',
    ];

    const ESTADOS_PAGO = [
        'pendiente'  => 'Pendiente',
        'aprobado'   => 'Aprobado',
        'rechazado'  => 'Rechazado',
    ];

    public static function crear_tablas(): void {
        global $wpdb;
        $c = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_condiciones (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre      VARCHAR(100) NOT NULL,
            slug        VARCHAR(50)  NOT NULL,
            descripcion TEXT,
            cobrar_a    VARCHAR(30)  NOT NULL DEFAULT 'destinatario',
            activo      TINYINT(1)   NOT NULL DEFAULT 1,
            orden       INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id), UNIQUE KEY slug (slug)
        ) $c;");

        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_condicion_componentes (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            condicion_id INT UNSIGNED NOT NULL,
            variable     VARCHAR(60)  NOT NULL,
            label        VARCHAR(120) NOT NULL,
            obligatorio  TINYINT(1)   NOT NULL DEFAULT 1,
            orden        INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id), KEY condicion_id (condicion_id)
        ) $c;");

        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_metodos_pago (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre        VARCHAR(100) NOT NULL,
            slug          VARCHAR(50)  NOT NULL,
            actor_destino VARCHAR(30)  NOT NULL DEFAULT 'empresa',
            tipo          VARCHAR(30)  NOT NULL DEFAULT 'efectivo',
            requiere_conf TINYINT(1)   NOT NULL DEFAULT 0,
            activo        TINYINT(1)   NOT NULL DEFAULT 1,
            orden         INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id), UNIQUE KEY slug (slug)
        ) $c;");

        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_reglas (
            id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            metodo_id       INT UNSIGNED NOT NULL,
            condicion_id    INT UNSIGNED,
            cuenta_afectada VARCHAR(60)  NOT NULL,
            base_calculo    VARCHAR(60)  NOT NULL,
            signo           TINYINT(1)   NOT NULL DEFAULT 1,
            descripcion_tpl VARCHAR(220) NOT NULL DEFAULT '',
            PRIMARY KEY (id), KEY metodo_id (metodo_id)
        ) $c;");

        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_penalidad_tipos (
            id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nombre          VARCHAR(150) NOT NULL,
            descripcion     TEXT,
            tipo_monto      VARCHAR(20)  NOT NULL DEFAULT 'fijo',
            monto_default   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            aplica_a        VARCHAR(30)  NOT NULL DEFAULT 'motorizado',
            cuenta_afectada VARCHAR(60)  NOT NULL DEFAULT 'balance_motorizado',
            signo           TINYINT(1)   NOT NULL DEFAULT -1,
            activo          TINYINT(1)   NOT NULL DEFAULT 1,
            PRIMARY KEY (id)
        ) $c;");

        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_transacciones (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            shipment_id    INT UNSIGNED NOT NULL,
            metodo_id      INT UNSIGNED NOT NULL,
            condicion_id   INT UNSIGNED NOT NULL,
            monto_servicio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            monto_total    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            variables_json TEXT,
            estado         VARCHAR(30)  NOT NULL DEFAULT 'pendiente',
            notas          TEXT,
            creado_por     INT UNSIGNED,
            fecha_creacion DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_confirm  DATETIME,
            PRIMARY KEY (id), KEY shipment_id (shipment_id)
        ) $c;");

        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_movimientos (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            transaccion_id INT UNSIGNED,
            shipment_id    INT UNSIGNED NOT NULL,
            cuenta         VARCHAR(60)  NOT NULL,
            monto          DECIMAL(10,2) NOT NULL,
            signo          TINYINT(1)   NOT NULL DEFAULT 1,
            descripcion    VARCHAR(250) NOT NULL DEFAULT '',
            tipo           VARCHAR(30)  NOT NULL DEFAULT 'transaccion',
            fecha          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY shipment_id (shipment_id), KEY cuenta (cuenta)
        ) $c;");

        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_penalidades_aplicadas (
            id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
            shipment_id       INT UNSIGNED NOT NULL,
            penalidad_tipo_id INT UNSIGNED NOT NULL,
            monto_aplicado    DECIMAL(10,2) NOT NULL,
            notas             TEXT,
            aplicado_por      INT UNSIGNED,
            fecha             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id), KEY shipment_id (shipment_id)
        ) $c;");

        // ─── NUEVAS TABLAS v2.0 ──────────────────────────────────────────────

        // Liquidaciones de drivers → DHV (el admin registra cuando el driver trae el efectivo)
        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_liquidaciones (
            id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            driver_id       INT UNSIGNED NOT NULL,
            monto           DECIMAL(10,2) NOT NULL,
            metodo          VARCHAR(80)  NOT NULL DEFAULT 'efectivo',
            notas           TEXT,
            comprobante_url VARCHAR(500) NOT NULL DEFAULT '',
            registrado_por  INT UNSIGNED NOT NULL,
            fecha           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY driver_id (driver_id),
            KEY fecha (fecha)
        ) $c;");

        // Pagos bilaterales cliente ↔ DHV
        // direccion = 'cliente_a_dhv' : cliente paga lo que le debe a DHV
        // direccion = 'dhv_a_cliente' : DHV paga al cliente (p.ej. valor del producto en contraentrega)
        dbDelta("CREATE TABLE {$wpdb->prefix}wcfin_pagos_remitente (
            id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id          INT UNSIGNED NOT NULL,
            direccion        VARCHAR(20)  NOT NULL DEFAULT 'cliente_a_dhv',
            monto            DECIMAL(10,2) NOT NULL,
            metodo           VARCHAR(80)  NOT NULL DEFAULT 'transferencia',
            referencia       VARCHAR(150) NOT NULL DEFAULT '',
            comprobante_url  VARCHAR(500) NOT NULL DEFAULT '',
            notas_emisor     TEXT,
            notas_admin      TEXT,
            estado           VARCHAR(20)  NOT NULL DEFAULT 'pendiente',
            enviado_por      INT UNSIGNED NOT NULL,
            revisado_por     INT UNSIGNED,
            fecha_envio      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            fecha_revision   DATETIME,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY estado (estado)
        ) $c;");

        self::datos_iniciales();
    }

    private static function datos_iniciales(): void {
        global $wpdb;
        if ( $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wcfin_condiciones") > 0 ) return;

        $conds = [
            ['Contraentrega',       'contraentrega', 'destinatario', 1],
            ['Cancelado (prepago)', 'cancelado',     'ninguno',      2],
            ['Crédito',             'credito',       'remitente',    3],
            ['Normal',              'normal',        'remitente',    4],
        ];
        foreach ( $conds as [$n,$s,$c,$o] )
            $wpdb->insert("{$wpdb->prefix}wcfin_condiciones", ['nombre'=>$n,'slug'=>$s,'cobrar_a'=>$c,'activo'=>1,'orden'=>$o]);

        $id_contra = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wcfin_condiciones WHERE slug='contraentrega'");
        foreach ([['monto_servicio','Costo del servicio',1,1],['monto_producto','Valor del producto',1,2],['monto_extras','Cargos adicionales',0,3]] as [$v,$l,$ob,$or])
            $wpdb->insert("{$wpdb->prefix}wcfin_condicion_componentes", ['condicion_id'=>$id_contra,'variable'=>$v,'label'=>$l,'obligatorio'=>$ob,'orden'=>$or]);

        foreach (['normal','credito','cancelado'] as $s) {
            $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wcfin_condiciones WHERE slug=%s",$s));
            $wpdb->insert("{$wpdb->prefix}wcfin_condicion_componentes", ['condicion_id'=>$id,'variable'=>'monto_servicio','label'=>'Costo del servicio','obligatorio'=>1,'orden'=>1]);
        }

        $metodos = [
            ['Motorizado (Efectivo)','motorizado_efectivo','motorizado','efectivo',1,1],
            ['YAPE / PLIN','yape_plin','empresa','digital',0,2],
            ['Depósito cuenta Darwin','deposito_darwin','empresa','banco',1,3],
            ['POS','pos','empresa','pos',0,4],
            ['Depósito cuenta empresa','deposito_empresa','empresa','banco',1,5],
            ['No cobrar (prepago)','no_cobrar','ninguno','prepago',0,6],
        ];
        foreach ($metodos as [$n,$s,$a,$t,$r,$o])
            $wpdb->insert("{$wpdb->prefix}wcfin_metodos_pago", ['nombre'=>$n,'slug'=>$s,'actor_destino'=>$a,'tipo'=>$t,'requiere_conf'=>$r,'activo'=>1,'orden'=>$o]);

        $id_mot     = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wcfin_metodos_pago WHERE slug='motorizado_efectivo'");
        $id_contra  = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wcfin_condiciones  WHERE slug='contraentrega'");
        $id_normal  = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wcfin_condiciones  WHERE slug='normal'");
        $id_credito = $wpdb->get_var("SELECT id FROM {$wpdb->prefix}wcfin_condiciones  WHERE slug='credito'");

        $reglas = [
            // Motorizado + Contraentrega
            [$id_mot,$id_contra,'balance_motorizado','monto_total',      1,'Motorizado recibe efectivo S/ %s'],
            [$id_mot,$id_contra,'caja_empresa',      'monto_servicio',   1,'Ingreso por servicio S/ %s'],
            [$id_mot,$id_contra,'deuda_a_remitente', 'monto_producto',   1,'DHV debe al remitente S/ %s'],
            // Motorizado + Normal
            [$id_mot,$id_normal,'balance_motorizado','monto_servicio',   1,'Motorizado recibe servicio S/ %s'],
            [$id_mot,$id_normal,'caja_empresa',      'monto_servicio',   1,'Ingreso servicio S/ %s'],
            // Motorizado + Crédito (remitente debe a DHV)
            [$id_mot,$id_credito,'deuda_de_remitente','monto_servicio',  1,'Remitente debe a DHV S/ %s'],
            [$id_mot,$id_credito,'caja_empresa',      'monto_servicio',  1,'Ingreso servicio (crédito) S/ %s'],
        ];
        foreach (['yape_plin','pos','deposito_darwin','deposito_empresa'] as $slug) {
            $id_m = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}wcfin_metodos_pago WHERE slug=%s",$slug));
            $reglas[] = [$id_m,$id_contra,'caja_empresa',      'monto_servicio',1,'Ingreso servicio S/ %s'];
            $reglas[] = [$id_m,$id_contra,'deuda_a_remitente', 'monto_producto',1,'DHV debe al remitente S/ %s'];
            $reglas[] = [$id_m,$id_normal,'caja_empresa',      'monto_servicio',1,'Ingreso servicio S/ %s'];
            $reglas[] = [$id_m,$id_credito,'deuda_de_remitente','monto_servicio',1,'Remitente debe a DHV S/ %s'];
            $reglas[] = [$id_m,$id_credito,'caja_empresa',      'monto_servicio',1,'Ingreso servicio S/ %s'];
        }
        foreach ($reglas as [$mid,$cid,$cuenta,$base,$signo,$desc])
            $wpdb->insert("{$wpdb->prefix}wcfin_reglas", ['metodo_id'=>$mid,'condicion_id'=>$cid,'cuenta_afectada'=>$cuenta,'base_calculo'=>$base,'signo'=>$signo,'descripcion_tpl'=>$desc]);

        foreach ([['Entrega tardía','fijo',10,'motorizado','balance_motorizado',-1],['Paquete dañado','fijo',50,'motorizado','balance_motorizado',-1],['No se presentó','fijo',15,'motorizado','balance_motorizado',-1],['Reenvío forzado','fijo',20,'empresa','caja_empresa',-1],['Reclamo de cliente','porcentaje',5,'empresa','caja_empresa',-1]] as [$n,$t,$m,$a,$cta,$sg])
            $wpdb->insert("{$wpdb->prefix}wcfin_penalidad_tipos", ['nombre'=>$n,'tipo_monto'=>$t,'monto_default'=>$m,'aplica_a'=>$a,'cuenta_afectada'=>$cta,'signo'=>$sg,'activo'=>1]);
    }
}
