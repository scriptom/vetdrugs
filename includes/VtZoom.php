<?php


namespace VetDrugs;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

final class VtZoom {
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $clientSecret;

    /** @var string */
    private $apiKey;


    /**
     * VtZoom constructor.
     * @param $apiKey
     * @param $clientSecret
     */
    public function __construct( $apiKey, $clientSecret ) {
        $this->httpClient   = new Client( [
            'base_uri' => 'https://api.zoom.us/v2/'
        ] );
        $this->clientSecret = $clientSecret;
        $this->apiKey       = $apiKey;
    }

    public function getUserId() {
        $jwtToken     = $this->createJwtToken();
        $response     = $this->httpClient->get( "users", [
            'headers' => [
                'Authorization' => "Bearer {$jwtToken->toString()}"
            ]
        ] );
        $bodyContents = json_decode( $response->getBody()
            ->getContents() );
        return $bodyContents->users[0]->id;
    }

    public function createMeeting(\DateTime $date, $name ) {
        try {
            $jwtToken = $this->createJwtToken();
            $response = $this->httpClient->post( "users/me/meetings", [
                'json'    => [
                    'topic'      => $name,
                    'type'       => 2,
                    /*
                     * We can't use DateTime::ISO8601 to format Zoom's datetimes, since it isn't compliant
                     * (see https://www.php.net/manual/es/class.datetimeinterface.php#datetime.constants.iso8601),
                     * so instead, we ask Carbon to lend a hand.
                     */
                    'start_time' => Carbon::parse($date)->toIso8601String(),
                    'duration'   => 15,
                    'timezone' => $date->getTimezone()->getName()
                ],
                'headers' => [
                    'Authorization' => "Bearer {$jwtToken->toString()}"
                ]
            ] );
            if ( 201 === $response->getStatusCode() ) {
                return json_decode( $response->getBody()
                    ->getContents() );
            }
        } catch ( GuzzleException $e ) {
        }
    }

    protected function createJwtToken() {
        $config = Configuration::forSymmetricSigner( new Sha256(), InMemory::plainText( $this->clientSecret ) );
        return $config->builder()
            ->issuedBy( $this->apiKey )
            ->expiresAt( ( new \DateTimeImmutable() )->modify( '+15 minutes' ) )
            ->getToken( $config->signer(), $config->signingKey() );
    }
}
