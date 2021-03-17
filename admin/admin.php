<?php

add_action( 'admin_init', 'vt_settings_api' );
function vt_settings_api() {
    register_setting( 'vetdrugs', 'vt_zoom_apikey' );

    register_setting( 'vetdrugs', 'vt_zoom_clientsecret' );


    register_setting( 'vetdrugs', 'vt_gapi_got_refresh_token' );

    add_settings_section(
        'vetdrugs_zoom_settings',
        __( 'Zoom API Settings', 'vetdrugs' ),
        'vt_zoom_settings_section_render',
        'vetdrugs'
    );

    add_settings_section(
        'vetdrugs_calendar_settings',
        __( 'Google Calendar API Settings', 'vetdrugs' ),
        'vt_calendar_settings_section_render',
        'vetdrugs'
    );

    add_settings_field(
        'vt_zoom_apikey_field',
        __( 'Zoom API Key', 'vetdrugs' ),
        'vt_zoom_apikey_field_render',
        'vetdrugs',
        'vetdrugs_zoom_settings',
        [
            'label_for' => 'vt_zoom_apikey_field'
        ]
    );
    add_settings_field(
        'vt_zoom_clientsecret_field',
        __( 'Zoom API Client Secret', 'vetdrugs' ),
        'vt_zoom_clientsecret_field_render',
        'vetdrugs',
        'vetdrugs_zoom_settings',
        [
            'label_for' => 'vt_zoom_clientsecret_field'
        ]
    );

    add_settings_field(
        'vt_gapi_got_refresh_token',
        __( 'Google Calendar Authorization', 'vetdrugs' ),
        'vt_calendar_credentials_field_render',
        'vetdrugs',
        'vetdrugs_calendar_settings',
        [ 'label_for' => 'vt_calendarapi_credentials_path' ]
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
    if ( isset( $_GET['code'] ) ) {
        $code = $_GET['code'];
        vetdrugs()
            ->getCalendar()
            ->saveAuthCode( $code );
        update_option( 'vt_gapi_got_refresh_token', 1 );

        add_settings_error( 'vt_settings', 'vt_oauth_success', __( 'Google Calendar account authorized successfully!', 'vetdrugs' ), 'updated' );
    }
    settings_errors( 'vt_settings' );
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form method="post" enctype="multipart/form-data" action="options.php">
            <?php settings_fields( 'vetdrugs' ); ?>
            <?php do_settings_sections( 'vetdrugs' ); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function vt_zoom_settings_section_render() {
    $intro = __( 'Here you can edit the Zoom API settings for checkout integration', 'vetdrugs' );
    echo "<p>$intro</p>";
}

function vt_calendar_settings_section_render() {
    $intro = __( 'Here you can edit the Google Calendar API settings for checkout integration', 'vetdrugs' );
    echo "<p>$intro</p>";
}

function vt_zoom_apikey_field_render( $args ) {
    $current = get_option( 'vt_zoom_apikey' );
    ?>
    <input type="password" name="vt_zoom_apikey" id="<?= $args['label_for'] ?>"
           value="<?= $current ? esc_attr( $current ) : '' ?>"/>
    <?php
}

function vt_zoom_clientsecret_field_render( $args ) {
    $current = get_option( 'vt_zoom_clientsecret' );
    ?>
    <input type="password" name="vt_zoom_clientsecret" id="<?= $args['label_for'] ?>"
           value="<?= $current ? esc_attr( $current ) : '' ?>"/>
    <?php
}

function vt_calendar_credentials_field_render( $args ) {
    $current         = get_option( 'vt_gapi_got_refresh_token' );
    $google_auth_url = vetdrugs()
        ->getCalendar()
        ->getAuthUrl();
    $feedback        = $current
        ? __( 'Auth completed!', 'vetdrugs' )
        : __( 'Not authorized yet.', 'vetdrugs' );
    ?>
    <a class="button-primary" target="_blank"
       href="<?php esc_attr_e( esc_url( $google_auth_url ), 'vetdrugs' ) ?>"><span
                class="dashicons dashicons-google"
                style="vertical-align: middle"></span>&nbsp;<?php esc_html_e( 'Authorize with Google', 'vetdrugs' ); ?>
    </a>
    <p><span class="dashicons <?= $current ? 'dashicons-yes-alt' : ' dashicons-dismiss' ?>"
             style="color: <?= $current ? '#19d02e' : '#ea1906' ?>"></span>&nbsp;<?php echo esc_html( $feedback ); ?>
    </p>
    <?php
}
