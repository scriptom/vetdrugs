<?php

add_action( 'admin_init', 'vt_settings_api' );
function vt_settings_api() {
    register_setting( 'vetdrugs', 'vt_apikey', [
        'type'        => 'string',
        'description' => __( 'Zoom API Key', 'vetdrugs' )
    ] );

    register_setting( 'vetdrugs', 'vt_clientsecret', [
        'type'        => 'string',
        'description' => __( 'Zoom API Secret', 'vetdrugs' )
    ] );

    register_setting( 'vetdrugs', 'vt_jwtsecret', [
        'type'        => 'string',
        'description' => __( 'JWT secret used when communicating with the Zoom API', 'vetdrugs' )
    ] );

    add_settings_section(
        'vetdrugs_settings',
        __( 'Zoom API Settings', 'vetdrugs' ),
        'vt_settings_section_render',
        'vetdrugs'
    );

    add_settings_field(
        'vt_apikey_field',
        __( 'Zoom API Key', 'vetdrugs' ),
        'vt_apikey_field_render',
        'vetdrugs',
        'vetdrugs_settings',
        [
                'label_for' => 'vt_apikey_field'
        ]
    );
    add_settings_field(
        'vt_clientsecret_field',
        __( 'Zoom API Client Secret', 'vetdrugs' ),
        'vt_clientsecret_field_render',
        'vetdrugs',
        'vetdrugs_settings',
        [
                'label_for' => 'vt_clientsecret_field'
        ]
    );
    add_settings_field(
        'vt_jwtsecret_field',
        __( 'JWT Secret Key', 'vetdrugs' ),
        'vt_jwtsecret_field_render',
        'vetdrugs',
        'vetdrugs_settings',
        [
                'label_for' => 'vt_jwtsecret_field'
        ]
    );
}

add_action( 'admin_menu', 'vt_register_menu' );
function vt_register_menu() {
    add_menu_page(
        __( 'VetDrugs Settings', 'vetdrugs' ),
        __( 'VetDrugs', 'vetdrugs' ),
        'manage_options',
        'vetdrugs',
        'vt_settings_page_render',
        'none',
        10
    );
}

function vt_settings_page_render() {
    if ( isset( $_GET['settings-updated'] ) ) {
        add_settings_error( 'vt_settings', 'vt_message', __( 'Settings Saved', 'vetdrugs' ), 'updated' );
    }
    settings_errors( 'vt_settings' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'vetdrugs' ); ?>
            <?php do_settings_sections('vetdrugs'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function vt_settings_section_render() {
    $intro = __( 'Here you can edit the Zoom API settings for checkout integration', 'vetdrugs' );
    echo "<p>$intro</p>";
}

function vt_apikey_field_render($args) {
    $current = get_option( 'vt_apikey' );
    ?>
    <input type="password" name="vt_apikey" id="<?= $args['label_for'] ?>" value="<?= $current ? esc_attr( $current ) : '' ?>"/>
    <?php
}

function vt_clientsecret_field_render($args) {
    $current = get_option( 'vt_clientsecret' );
    ?>
    <input type="password" name="vt_clientsecret" id="<?= $args['label_for'] ?>"
           value="<?= $current ? esc_attr( $current ) : '' ?>"/>
    <?php
}

function vt_jwtsecret_field_render($args) {
    $current = get_option( 'vt_jwtsecret' );
    ?>
    <input type="password" name="vt_jwtsecret" id="<?= $args['label_for'] ?>" value="<?= $current ? esc_attr( $current ) : '' ?>"/>
    <?php
}

