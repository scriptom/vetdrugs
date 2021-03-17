<?php


namespace VetDrugs;


use Carbon\Carbon;
use DateTime;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

final class VtCalendar {
    const _TOKEN_FILENAME = 'token.json';
    /**
     * @var string|array
     */
    private $authConfig;

    /**
     * @var string
     */
    private $redirectUri;

    /**
     * @var string
     */
    private $tokenPath;

    /**
     * VtCalendar constructor.
     * @param array|string $authConfig
     * @param $redirectUri
     * @param $tokenPath
     */
    public function __construct( $authConfig, $redirectUri, $tokenPath ) {
        $this->authConfig  = $authConfig;
        $this->redirectUri = $redirectUri;
        $this->tokenPath   = $tokenPath;
    }

    public function getEventsForDate( $date, $calendarId = 'primary' ) {
        $calendar = $this->getCalendarService( $this->getClient() );
        $events   = $calendar->events->listEvents( $calendarId );
        $theDate  = Carbon::parse( $date );
        return array_filter( $events->getItems(), function ( Google_Service_Calendar_Event $event ) use ( $theDate ) {
            $start = Carbon::parse( $event->getStart()
                ->getDate(), $event->getStart()
                ->getTimeZone() );
            return $start->isSameDay( $theDate );
        } );
    }

    public function scheduleEventForDate( DateTime $datetime, $eventData, $calendarId = 'primary' ) {
        $datetime  = \DateTimeImmutable::createFromMutable( $datetime );
        $calendar  = $this->getCalendarService( $this->getClient() );
        $event     = new Google_Service_Calendar_Event();
        $startDate = new \Google_Service_Calendar_EventDateTime();
        // Google requires dateTimes to follow RFC3339
        $startDate->setDateTime( $datetime->format( DateTime::RFC3339 ) );
        $endDate = new \Google_Service_Calendar_EventDateTime();
        $endDate->setDateTime( $datetime->modify( "+{$eventData['duration']} minutes" )
            ->format( DateTime::RFC3339 ) );
        $event->setStart( $startDate );
        $event->setEnd( $endDate );
        $event->setSummary( $eventData['summary'] );
        $event->setDescription( $eventData['description'] );
        return $calendar->events->insert( $calendarId, $event );
    }

    private function getClient() {
        $client = new Google_Client();
        $client->setScopes( Google_Service_Calendar::CALENDAR_EVENTS );
        $client->setAuthConfig( $this->authConfig );
        $client->setRedirectUri( $this->redirectUri );
        $filename = $this->tokenPath . DIRECTORY_SEPARATOR . self::_TOKEN_FILENAME;
        if ( file_exists( $filename ) ) {
            $accessToken = json_decode( file_get_contents( $filename ), true );
            $client->setAccessToken( $accessToken );
        }
        if ( $client->isAccessTokenExpired() ) {
            if ( $client->getRefreshToken() ) {
                $client->fetchAccessTokenWithRefreshToken( $client->getRefreshToken() );
            }
        }
        return $client;
    }

    public function getCalendarService( $client ) {
        return new Google_Service_Calendar( $client );
    }

    public function getAuthUrl() {
        return $this->getClient()
            ->createAuthUrl();
    }

    public function saveAuthCode( $code ) {
        $client = $this->getClient();
        $client->fetchAccessTokenWithAuthCode( $code );
        // Save the token to a file.
        if ( !file_exists( $this->tokenPath ) ) {
            mkdir( $this->tokenPath, 0700, true );
        }
        file_put_contents( $this->tokenPath . DIRECTORY_SEPARATOR . self::_TOKEN_FILENAME, json_encode( $client->getAccessToken() ) );
        return $client->getAccessToken();
    }
}
