<?php
if (! defined('WP_DEBUG')) {
	die( 'Direct access forbidden.' );
}
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
});
//add_filter('wpcfe_pdf_paper_size', function(){ return [0,0,842,595]; });
function dhv_tracking_autofill_script() {
    if (!is_page('track-form')) return;
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tracking = urlParams.get('tracking_number');

        if (!tracking) return;

        const lastTracking = sessionStorage.getItem('last_tracking');

        // Solo ejecutar si es un tracking diferente
        if (tracking !== lastTracking) {

            const input = document.querySelector('input[name="wpcargo_tracking_number"]');

            if (input) {
                input.value = tracking;

                // Guardamos el tracking actual
                sessionStorage.setItem('last_tracking', tracking);

                const form = input.closest("form");
                if (form) {
                    form.submit();
                }
            }
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'dhv_tracking_autofill_script');



/**
 * =========================================
 * PERSONALIZACIÓN WP CARGO - PERÚ
 * - Departamento (select)
 * - Ciudad (dinámica por departamento)
 * =========================================
 */
function dhv_custom_wpcargo_fields( $fields ){

    // 1. Eliminar campos innecesarios
    unset($fields['billing_country']);
    unset($fields['billing_postcode']);
    unset($fields['billing_state']);

    // 2. Departamentos del Perú
    $departamentos = array(
        'Amazonas' => 'Amazonas',
        'Áncash' => 'Áncash',
        'Apurímac' => 'Apurímac',
        'Arequipa' => 'Arequipa',
        'Ayacucho' => 'Ayacucho',
        'Cajamarca' => 'Cajamarca',
        'Callao' => 'Callao',
        'Cusco' => 'Cusco',
        'Huancavelica' => 'Huancavelica',
        'Huánuco' => 'Huánuco',
        'Ica' => 'Ica',
        'Junín' => 'Junín',
        'La Libertad' => 'La Libertad',
        'Lambayeque' => 'Lambayeque',
        'Lima' => 'Lima',
        'Loreto' => 'Loreto',
        'Madre de Dios' => 'Madre de Dios',
        'Moquegua' => 'Moquegua',
        'Pasco' => 'Pasco',
        'Piura' => 'Piura',
        'Puno' => 'Puno',
        'San Martín' => 'San Martín',
        'Tacna' => 'Tacna',
        'Tumbes' => 'Tumbes',
        'Ucayali' => 'Ucayali'
    );

    $new_fields = array();

    foreach ( $fields as $key => $field ) {

        // Insertar Departamento antes de Ciudad
        if ( $key === 'billing_city' ) {

            $new_fields['departamento'] = array(
                'id'            => 'departamento',
                'label'         => 'Departamento',
                'field'         => 'select',
                'field_type'    => 'select',
                'required'      => true,
                'options'       => $departamentos,
                'field_data'    => array(),
                'field_key'     => 'departamento'
            );

            // Convertir ciudad en select dinámico
            $field['field'] = 'select';
            $field['field_type'] = 'select';
            $field['options'] = array('' => 'Primero seleccione un departamento');
        }

        // Campos obligatorios
        if ( $key === 'billing_email' ) $field['required'] = true;
        if ( $key === 'billing_company' ) $field['required'] = true;
        if ( $key === 'billing_address_1' ) $field['required'] = true;
        if ( $key === 'billing_city' ) $field['required'] = true;

        $new_fields[$key] = $field;
    }

    return $new_fields;
}
add_filter( 'wpcfe_billing_address_fields', 'dhv_custom_wpcargo_fields' );


/**
 * =========================================
 * JS - CIUDADES DINÁMICAS POR DEPARTAMENTO
 * =========================================
 */
function dhv_departamento_ciudad_script() {
    $target_user_id = 0;

    if ( isset( $_GET['umpage'], $_GET['uid'] ) && 'edit' === $_GET['umpage'] ) {
        $target_user_id = absint( $_GET['uid'] );
    } elseif ( is_user_logged_in() ) {
        $target_user_id = get_current_user_id();
    }

    $saved_dep  = $target_user_id ? (string) get_user_meta( $target_user_id, 'departamento', true ) : '';
    $saved_city = $target_user_id ? (string) get_user_meta( $target_user_id, 'billing_city', true ) : '';
?>
<script>
document.addEventListener("DOMContentLoaded", function() {

    const data = {
        "Amazonas": ["Chachapoyas", "Bagua", "Bongará", "Condorcanqui", "Luya", "Rodríguez de Mendoza", "Utcubamba"],
        "Áncash": ["Huaraz", "Aija", "Antonio Raymondi", "Asunción", "Bolognesi", "Carhuaz", "Carlos Fermín Fitzcarrald", "Casma", "Corongo", "Huari", "Huarmey", "Huaylas", "Mariscal Luzuriaga", "Ocros", "Pallasca", "Pomabamba", "Recuay", "Santa", "Sihuas", "Yungay"],
        "Apurímac": ["Abancay", "Andahuaylas", "Antabamba", "Aymaraes", "Cotabambas", "Chincheros", "Grau"],
        "Arequipa": ["Arequipa", "Camaná", "Caravelí", "Castilla", "Caylloma", "Condesuyos", "Islay", "La Unión"],
        "Ayacucho": ["Huamanga", "Cangallo", "Huanca Sancos", "Huanta", "La Mar", "Lucanas", "Parinacochas", "Páucar del Sara Sara", "Sucre", "Víctor Fajardo", "Vilcashuamán"],
        "Cajamarca": ["Cajamarca", "Cajabamba", "Celendín", "Chota", "Contumazá", "Cutervo", "Hualgayoc", "Jaén", "San Ignacio", "San Marcos", "San Miguel", "San Pablo", "Santa Cruz"],
        "Callao": ["Callao"],
        "Cusco": ["Cusco", "Acomayo", "Anta", "Calca", "Canas", "Canchis", "Chumbivilcas", "Espinar", "La Convención", "Paruro", "Paucartambo", "Quispicanchi", "Urubamba"],
        "Huancavelica": ["Huancavelica", "Acobamba", "Angaraes", "Castrovirreyna", "Churcampa", "Huaytará", "Tayacaja"],
        "Huánuco": ["Huánuco", "Ambo", "Dos de Mayo", "Huacaybamba", "Huamalíes", "Leoncio Prado", "Marañón", "Pachitea", "Puerto Inca", "Lauricocha", "Yarowilca"],
        "Ica": ["Ica", "Chincha", "Nazca", "Palpa", "Pisco"],
        "Junín": ["Huancayo", "Concepción", "Chanchamayo", "Jauja", "Junín", "Satipo", "Tarma", "Yauli", "Chupaca"],
        "La Libertad": ["Trujillo", "Ascope", "Bolívar", "Chepén", "Julcán", "Otuzco", "Pacasmayo", "Pataz", "Sánchez Carrión", "Santiago de Chuco", "Gran Chimú", "Virú"],
        "Lambayeque": ["Chiclayo", "Ferreñafe", "Lambayeque"],
        "Lima": ["Lima Metropolitana", "Barranca", "Cajatambo", "Canta", "Cañete", "Huaral", "Huarochirí", "Huaura", "Oyón", "Yauyos"],
        "Loreto": ["Maynas", "Alto Amazonas", "Loreto", "Mariscal Ramón Castilla", "Requena", "Ucayali", "Datem del Marañón", "Putumayo"],
        "Madre de Dios": ["Tambopata", "Manu", "Tahuamanu"],
        "Moquegua": ["Mariscal Nieto", "General Sánchez Cerro", "Ilo"],
        "Pasco": ["Pasco", "Daniel Alcides Carrión", "Oxapampa"],
        "Piura": ["Piura", "Ayabaca", "Huancabamba", "Morropón", "Paita", "Sullana", "Talara", "Sechura"],
        "Puno": ["Puno", "Azángaro", "Carabaya", "Chucuito", "El Collao", "Huancané", "Lampa", "Melgar", "Moho", "San Antonio de Putina", "San Román", "Sandia", "Yunguyo"],
        "San Martín": ["Moyobamba", "Bellavista", "El Dorado", "Huallaga", "Lamas", "Mariscal Cáceres", "Picota", "Rioja", "San Martín", "Tocache"],
        "Tacna": ["Tacna", "Candarave", "Jorge Basadre", "Tarata"],
        "Tumbes": ["Tumbes", "Contralmirante Villar", "Zarumilla"],
        "Ucayali": ["Coronel Portillo", "Atalaya", "Padre Abad", "Purús"]
    };

    const dep  = document.querySelector('[name="departamento"]');
    const city = document.querySelector('[name="billing_city"]');

    if (!dep || !city) return;

    function populateCities(department, selectedCity) {
        city.innerHTML = '';
        if (!data[department]) {
            city.innerHTML = '<option value="">Seleccione</option>';
            return;
        }
        data[department].forEach(function(item) {
            const option = document.createElement('option');
            option.value = item;
            option.textContent = item;
            if (selectedCity && item === selectedCity) option.selected = true;
            city.appendChild(option);
        });

        if (selectedCity && data[department].indexOf(selectedCity) === -1) {
            const customOption = document.createElement('option');
            customOption.value = selectedCity;
            customOption.textContent = selectedCity;
            customOption.selected = true;
            city.appendChild(customOption);
        }
    }

    // Pre-poblar ciudades al cargar si hay departamento guardado
    var savedDep  = <?php echo wp_json_encode( $saved_dep ); ?>;
    var savedCity = <?php echo wp_json_encode( $saved_city ); ?>;
    if (savedDep && dep.value !== savedDep) {
        dep.value = savedDep;
    }
    if (savedDep) {
        populateCities(savedDep, savedCity);
    }

    dep.addEventListener("change", function() {
        populateCities(this.value, '');
    });

});
</script>
<?php
}
add_action('wp_footer', 'dhv_departamento_ciudad_script');



/**
 * =========================================
 * PERSONAL INFO - DNI / RUC + VALIDACIÓN
 * =========================================
 */
function dhv_custom_personal_info_fields( $fields ){

    $new_fields = array();

    foreach ( $fields as $key => $field ) {

        // Hacer obligatorios
        if ( $key === 'first_name' ) $field['required'] = true;
        if ( $key === 'last_name' ) $field['required'] = true;
        if ( $key === 'phone' ) $field['required'] = true;

        $new_fields[$key] = $field;

        /**
         * Insertar DNI/RUC después del teléfono
         */
        if ( $key === 'phone' ) {
            $new_fields['dni_remitente'] = array(
                'id'            => 'dni_remitente',
                'label'         => 'DNI / RUC',
                'placeholder'   => 'Ingrese DNI (8) o RUC (11)',
                'field'         => 'text',
                'field_type'    => 'text',
                'required'      => true,
                'options'       => array(),
                'field_data'    => array(),
                'field_key'     => 'dni_remitente'
            );
        }
    }

    return $new_fields;
}
add_filter( 'wpcfe_personal_info_fields', 'dhv_custom_personal_info_fields' );


/**
 * =========================================
 * VALIDACIÓN DNI / RUC
 * =========================================
 */
function dhv_validar_dni_ruc_remitente() {

    if ( isset($_POST['dni_remitente']) ) {

        $doc = trim($_POST['dni_remitente']);

        // Solo números
        if ( !preg_match('/^[0-9]+$/', $doc) ) {
            wc_add_notice( 'El DNI o RUC solo debe contener números.', 'error' );
            return;
        }

        // DNI
        if ( strlen($doc) === 8 ) {
            return;
        }

        // RUC
        if ( strlen($doc) === 11 ) {

            // Validar inicio típico de RUC Perú
            $inicio = substr($doc, 0, 2);

            if ( !in_array($inicio, ['10','15','17','20']) ) {
                wc_add_notice( 'El RUC no es válido.', 'error' );
            }

            return;
        }

        // Si no cumple ninguno
        wc_add_notice( 'Debe ingresar un DNI (8 dígitos) o RUC (11 dígitos).', 'error' );
    }
}
add_action( 'woocommerce_checkout_process', 'dhv_validar_dni_ruc_remitente' );


/**
 * =========================================
 * JS - DETECCIÓN AUTOMÁTICA (UX PRO)
 * =========================================
 */
function dhv_dni_ruc_script() {
?>
<script>
document.addEventListener("DOMContentLoaded", function(){

    const input = document.querySelector('[name="dni_remitente"]');

    if (!input) return;

    input.addEventListener("input", function(){

        const val = this.value;

        if (val.length <= 8) {
            this.previousElementSibling.innerText = "DNI";
        } else if (val.length > 8) {
            this.previousElementSibling.innerText = "RUC";
        }

    });

});
</script>
<?php
}
add_action('wp_footer', 'dhv_dni_ruc_script');

// Prueba de Auto-Deploy exitosa - 2026
