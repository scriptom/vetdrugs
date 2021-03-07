<?php

/*
Plugin Name: Vetdrugs
Description: Custom functionality for VetDrugs Checkout form
Version: 1.0
Author: Tomas El Fakih for ChillMarin
License: GPL2
*/

require_once __DIR__ . '/admin/admin.php';

//use VetDrugs\VtZoom;

//function vt_add_checkout_fields( $checkout ) {
//    echo '<div id="vt_appointment_schedule"><h2>' . __( 'Appointment schedule', 'vetdrugs' ) . '</h2>';
//
//    woocommerce_form_field( 'vt_appointment_schedule', array(
//        'type'        => 'date',
//        'class'       => array( 'vt-appointment-schedule form-row-wide' ),
//        'label'       => __( 'Fill in this field', 'vetdrugs' ),
//        'placeholder' => __( 'Select date', 'vetdrugs' ),
//        'required'    => true
//    ), $checkout->get_value( 'vt_appointment_schedule' ) );
//
//    echo '</div>';
//}

//function vt_process_checkout() {
//    // Check if set, if its not set add an error.
//    if ( !$_POST['vt_appointment_schedule'] ) {
//        wc_add_notice( __( 'Please choose a date for the doctors appointment.', 'vetdrugs' ), 'error' );
//    } else {
//        vt_schedule_meeting( $_POST['vt_appointment_schedule'] );
//    }
//}

//function vt_schedule_meeting( $date, $name ) {
//    $userId = get_option('vt_zoom_userid');
//    $meeting = vetdrugs()->createMeeting($userId, $date, $name);
//}

//function vetdrugs() {
//    global $vtZoom;
//    $apiKey       = get_option( '' );
//    $clientId     = get_option( '' );
//    $clientSecret = get_option( '' );
//    $redirectUri  = get_option( '' );
//    $jwtSecret    = get_option( '' );
//    if ( empty( $vtZoom ) ) {
//        $vtZoom = new VtZoom( $apiKey, $clientId, $clientSecret, $redirectUri, $jwtSecret );
//    }
//
//    return $vtZoom;
//}
