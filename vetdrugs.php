<?php

/*
Plugin Name: Vetdrugs
Description: Custom functionality for VetDrugs Checkout form
Version: 1.0
Author: Tomas El Fakih for ChillMarin
License: GPL2
*/

use VetDrugs\VetDrugs;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin/admin.php';

define( 'VETDRUGS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

add_action( 'woocommerce_before_order_notes', 'vt_add_checkout_fields' );
function vt_add_checkout_fields( $checkout ) {
    echo '<div id="vt_appointment_schedule"><h3>' . __( 'Appointment schedule', 'vetdrugs' ) . '</h3>';

    woocommerce_form_field( 'vt_appointment_schedule_date', [
        'type'        => 'date',
        'class'       => [ 'vt-appointment-schedule form-row-first' ],
        'label'       => __( 'Appointment date', 'vetdrugs' ),
        'placeholder' => __( 'Select date', 'vetdrugs' ),
        'required'    => true
    ], $checkout->get_value( 'vt_appointment_schedule_date' ) );

    woocommerce_form_field( 'vt_appointment_schedule_time', [
        'type'        => 'time',
        'class'       => [ 'vt-appointment-schedule form-row-last' ],
        'label'       => __( 'Appointment time', 'vetdrugs' ),
        'placeholder' => __( 'Select time', 'vetdrugs' ),
        'required'    => true
    ], $checkout->get_value( 'vt_appointment_schedule_time' ) );

    echo '</div>';
}

add_action( 'woocommerce_checkout_process', 'vt_process_checkout' );
function vt_process_checkout() {
    // Check if set, if its not set add an error.
    if ( !$_POST['vt_appointment_schedule_date'] || !$_POST['vt_appointment_schedule_time'] ) {
        wc_add_notice( __( 'Please choose a date for the doctors appointment.', 'vetdrugs' ), 'error' );
    } else {
        $datetime = vt_parse_datetime( "{$_POST['vt_appointment_schedule_date']} {$_POST['vt_appointment_schedule_time']}" );
        if ( vt_schedule_collides( $datetime ) ) {
            wc_add_notice( __( 'The date chosen for the appointment is already taken. Please choose another time and date', 'vetdrugs' ), 'error' );
        }
    }
}

add_action( 'woocommerce_checkout_update_order_meta', 'vt_update_order_meta' );
function vt_update_order_meta( $order_id ) {
    if ( !empty( $_POST['vt_appointment_schedule_date'] ) && !empty( $_POST['vt_appointment_schedule_time'] ) ) {
        $name    = "{$_POST['billing_first_name']} {$_POST['billing_last_name']}";
        $meeting = vt_schedule_meeting( $_POST['vt_appointment_schedule_date'], $_POST['vt_appointment_schedule_time'], $name );
        update_post_meta( $order_id, '_vt_zoom_meeting', $meeting );
        $event = vt_add_zoom_meeting_to_calendar( $meeting );
        update_post_meta( $order_id, '_gapi_calendar_event', $event );
    }
}

function vt_schedule_meeting( $date, $time, $customer_name ) {
    $schedule_name = "$customer_name's appointment";
    $datetime      = vt_parse_datetime( "$date $time" )
        ->setTimezone( wp_timezone() );
    $meeting       = vetdrugs()
        ->getZoom()
        ->createMeeting( $datetime, $schedule_name );
    do_action( 'vetdrugs_zoom_meeting_scheduled', $meeting );
    return $meeting;
}

function vt_add_zoom_meeting_to_calendar( $meeting ) {
    $meeting       = (array) $meeting;
    $schedule_name = $meeting['topic'];
    $datetime      = vt_parse_datetime( $meeting['start_time'] );
    $event         = vetdrugs()
        ->getCalendar()
        ->scheduleEventForDate( $datetime, [
            'summary'     => $schedule_name,
            'description' => sprintf( __( 'Start URL: %1$s. Join URL: %2$s', 'vetdrugs' ), $meeting['start_url'], $meeting['join_url'] ),
            'duration'    => $meeting['duration']
        ] );
    do_action( 'vetdrugs_calendar_event_saved', $event );
    return $event;
}

function vt_schedule_collides( DateTime $start_time ) {
    $events           = vetdrugs()
        ->getCalendar()
        ->getEventsForDate( $start_time );
    $start_time       = DateTimeImmutable::createFromMutable( $start_time );
    $assumed_end_time = $start_time->modify( '+15 minutes' );
    foreach ( $events as $event ) {
        $event_start = $event->getStart()
            ->getDateTime();
        $event_end   = $event->getEnd()
            ->getDateTime();
        if ( vt_periods_overlap( $event_start, $event_end, $start_time, $assumed_end_time )
            || vt_periods_overlap( $start_time, $assumed_end_time, $event_start, $event_end ) ) {
            return true;
        }
    }
    return false;
}

function vetdrugs() {
    global $vetdrugs;
    if ( empty( $vetdrugs ) ) {
        $vetdrugs = VetDrugs::build();
    }

    return $vetdrugs;
}
