<?php

function vt_parse_datetime( $datetime ) {
    return \Carbon\Carbon::parse( $datetime )
        ->toDateTime();
}

function vt_periods_overlap( $start_time1, $end_time1, $start_time2, $end_time2 ) {
    $period1 = new Carbon\CarbonPeriod($start_time1, $end_time1);
    $period2 = new Carbon\CarbonPeriod($start_time2, $end_time2);

    return $period1->overlaps($period2);
}
