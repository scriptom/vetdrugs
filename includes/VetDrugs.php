<?php


namespace VetDrugs;


class VetDrugs {
    const CALENDAR_CREDENTIALS_FILE = VETDRUGS_PLUGIN_DIR . 'credentials.json';
    /**
     * @var VtZoom
     */
    private $zoom;

    /**
     * @var VtCalendar
     */
    private $calendar;

    /**
     * @var self
     */
    private static $instance = null;

    private function __construct( VtZoom $zoom, $calendar ) {
        $this->zoom     = $zoom;
        $this->calendar = $calendar;
    }

    public static function build() {
        if ( null === self::$instance ) {
            $zoom_api_key                  = get_option( 'vt_zoom_apikey' );
            $zoom_client_secret            = get_option( 'vt_zoom_clientsecret' );
            $calendar_api_auth_config_path = self::CALENDAR_CREDENTIALS_FILE;
            $vtZoom                        = new VtZoom( $zoom_api_key, $zoom_client_secret );
            $vtCalendar                    = new VtCalendar( $calendar_api_auth_config_path, admin_url( 'admin.php?page=vetdrugs' ), VETDRUGS_PLUGIN_DIR . '.token');
            self::$instance                = new self( $vtZoom, $vtCalendar );
        }

        return self::$instance;
    }

    /**
     * @return VtCalendar
     */
    public function getCalendar() {
        return $this->calendar;
    }

    /**
     * @return VtZoom
     */
    public function getZoom() {
        return $this->zoom;
    }
}
