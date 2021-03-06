<?php

add_action( 'admin_init', 'vt_settings_api' );
function vt_settings_api() {
    register_setting( 'vetdrugs', 'vt_zoom_got_refresh_token' );

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
        'vt_zoom_got_refresh_token',
        __( 'Zoom Authorization', 'vetdrugs' ),
        'vt_zoom_authorization_render',
        'vetdrugs',
        'vetdrugs_zoom_settings',
        [
            'label_for' => 'vt_zoom_got_refresh_token'
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

    if ( isset( $_GET['code'] ) && isset( $_GET['service'] ) ) {
        $code    = $_GET['code'];
        $service = $_GET['service'];
        if ( $service === 'google' ) {
            $service = 'gapi';
            $token   = vetdrugs()
                ->getCalendar()
                ->saveAuthCode( $code );
            add_settings_error( 'vt_settings', 'vt_oauth_success', __( 'Google Calendar account authorized successfully!', 'vetdrugs' ), 'updated' );
        } else {
            $token = vetdrugs()
                ->getZoom()
                ->saveAccessToken( $code, admin_url( 'admin.php?page=vetdrugs&service=zoom' ) );

            add_settings_error( 'vt_settings', 'vt_oauth_success', __( 'Zoom account authorized successfully!', 'vetdrugs' ), 'updated' );
        }
        if ( $token && !array_key_exists( 'error', (array) $token ) ) {
            update_option( "vt_{$service}_got_refresh_token", 1 );
        }

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
    <script>
        var u = new URL(location.href);
        u.searchParams.delete('code');
        history.replaceState({}, document.title, u.toString());
    </script>
    <?php
}

function vt_zoom_settings_section_render() {
    $intro = __( 'Please authenticate with Zoom to enable Meetings integration.', 'vetdrugs' );
    echo "<p>$intro</p>";
}

function vt_calendar_settings_section_render() {
    $intro = __( 'Please authenticate with Google Calendar to enable Events integration.', 'vetdrugs' );
    echo "<p>$intro</p>";
}

function vt_zoom_authorization_render() {
    $current       = get_option( 'vt_zoom_got_refresh_token' );
    $zoom_auth_url = vetdrugs()
        ->getZoom()
        ->createAuthUrl( admin_url( 'admin.php?page=vetdrugs&service=zoom' ) );
    $feedback      = $current
        ? __( 'Auth completed!', 'vetdrugs' )
        : __( 'Not authorized yet.', 'vetdrugs' );
    ?>
    <a class="button-primary"
       href="<?php esc_attr_e( esc_url( $zoom_auth_url ), 'vetdrugs' ) ?>"><?php esc_html_e( 'Authorize with Zoom', 'vetdrugs' ); ?>
    </a>
    <p><span class="dashicons <?= $current ? 'dashicons-yes-alt' : ' dashicons-dismiss' ?>"
             style="color: <?= $current ? '#19d02e' : '#ea1906' ?>"></span>&nbsp;<?php echo esc_html( $feedback ); ?>
    </p>
    <?php
}

function vt_calendar_credentials_field_render() {
    $current         = get_option( 'vt_gapi_got_refresh_token' );
    $google_auth_url = vetdrugs()
        ->getCalendar()
        ->getAuthUrl();
    $feedback        = $current
        ? __( 'Auth completed!', 'vetdrugs' )
        : __( 'Not authorized yet.', 'vetdrugs' );
    ?>
    <a class="button-primary"
       href="<?php esc_attr_e( esc_url( $google_auth_url ), 'vetdrugs' ) ?>"><span
                class="dashicons dashicons-google"
                style="vertical-align: middle"></span>&nbsp;<?php esc_html_e( 'Authorize with Google', 'vetdrugs' ); ?>
    </a>
    <p><span class="dashicons <?= $current ? 'dashicons-yes-alt' : ' dashicons-dismiss' ?>"
             style="color: <?= $current ? '#19d02e' : '#ea1906' ?>"></span>&nbsp;<?php echo esc_html( $feedback ); ?>
    </p>
    <?php
}
