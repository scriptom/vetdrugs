<?php


namespace VetDrugs;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Parsing\Decoder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

final class VtZoom {
    const ZOOM_API_BASE_URI = 'https://api.zoom.us/v2/';
    const ZOOM_AUTH_ENDPOINT = 'https://zoom.us/oauth/';
    const TOKEN_FILENAME = 'zoom-token.json';
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var Client
     */
    private $authClient;

    /**
     * @var string
     */
    private $clientSecret;

    /** @var string */
    private $clientId;

    /** @var string */
    private $tokenPath;

    /** @var array */
    private $accessTokenArray;

    /**
     * VtZoom constructor.
     * @param $credentialsPath
     * @param $tokenPath
     */
    public function __construct( $credentialsPath, $tokenPath ) {
        $this->httpClient = new Client( [
            'base_uri' => self::ZOOM_API_BASE_URI
        ] );
        $this->authClient = new Client( [
            'base_uri' => self::ZOOM_AUTH_ENDPOINT
        ] );
        if ( file_exists( $credentialsPath ) ) {
            $contents           = json_decode( file_get_contents( $credentialsPath ), true );
            $this->clientSecret = $contents['client_secret'];
            $this->clientId     = $contents['client_id'];
        }

        $this->tokenPath = $tokenPath;
    }

    /**
     * @return string|null
     */
    public function getAccessToken() {
        if ( !$this->accessTokenArray ) {
            $this->loadTokens();
        }
        return $this->accessTokenArray['access_token'];
    }

    public function getRefreshToken() {
        if ( !$this->accessTokenArray ) {
            $this->loadTokens();
        }
        return $this->accessTokenArray['refresh_token'];
    }

    private function loadTokens() {
        if ( !file_exists( $this->tokenPath . DIRECTORY_SEPARATOR . self::TOKEN_FILENAME ) ) {
            return null;
        }
        $this->accessTokenArray = json_decode( file_get_contents( $this->tokenPath . DIRECTORY_SEPARATOR . self::TOKEN_FILENAME ), true );
    }

    public function createMeeting( \DateTime $date, $name ) {
        try {
            $jwtToken = $this->getAccessToken();
            $response = $this->httpClient->post( "users/me/meetings", [
                'json'    => [
                    'topic'      => $name,
                    'type'       => 2,
                    /*
                     * We can't use DateTime::ISO8601 to format Zoom's datetimes, since it isn't compliant
                     * (see https://www.php.net/manual/es/class.datetimeinterface.php#datetime.constants.iso8601),
                     * so instead, we ask Carbon to lend a hand.
                     */
                    'start_time' => Carbon::parse( $date )
                        ->toIso8601String(),
                    'duration'   => 15,
                    'timezone'   => $date->getTimezone()
                        ->getName()
                ],
                'headers' => [
                    'Authorization' => "Bearer {$jwtToken}"
                ]
            ] );
            if ( 201 === $response->getStatusCode() ) {
                return json_decode( $response->getBody()
                    ->getContents() );
            }
        } catch ( GuzzleException $e ) {
        }

        return null;
    }

    /** @deprecated */
    protected function createJwtToken() {
        $config = Configuration::forSymmetricSigner( new Sha256(), InMemory::plainText( $this->clientSecret ) );
        return $config->builder()
            ->issuedBy( $this->clientId )
            ->expiresAt( ( new \DateTimeImmutable() )->modify( '+15 minutes' ) )
            ->getToken( $config->signer(), $config->signingKey() );
    }

    public function createAuthUrl( $redirectUri, $state = [] ) {
        $base   = self::ZOOM_AUTH_ENDPOINT;
        $params = http_build_query( [
                'response_type' => 'code',
                'redirect_uri'  => $redirectUri,
                'client_id'     => $this->clientId
            ] + $state );
        return "{$base}authorize?{$params}";
    }

    public function saveAccessToken( $code, $redirectUri ) {
        $authorizationToken = $this->encodeAuthHeaderForOAuth( $this->clientId, $this->clientSecret );
        $response           = $this->authClient->post( 'token', [
            RequestOptions::HEADERS => [
                'Authorization' => "Basic $authorizationToken"
            ],
            RequestOptions::QUERY   => [
                'grant_type'   => 'authorization_code',
                'code'         => $code,
                'redirect_uri' => $redirectUri
            ]
        ] );
        if ( $response->getStatusCode() === 200 ) {
            $token = json_decode( $response->getBody()
                ->getContents(), true );
            $this->persistToken( $token );
            return $token;
        }

        return null;
    }

    public function isAccessTokenExpired() {
        $token = $this->getAccessToken();
        if ( $token ) {
            $payload = ( new Parser() )->parse( $token );
            return $payload->isExpired();
        }
        return true;
    }

    public function requestAccessTokenWithRefreshToken( $refreshToken ) {
        $authorizationToken = $this->encodeAuthHeaderForOAuth( $this->clientId, $this->clientSecret );
        $response           = $this->authClient->post( 'token', [
            RequestOptions::QUERY   => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshToken
            ],
            RequestOptions::HEADERS => [
                'Authorization' => "Basic $authorizationToken"
            ]
        ] );
        if ( $response->getStatusCode() === 200 ) {
            $token = json_decode( $response->getBody()
                ->getContents(), true );
            $this->persistToken( $token );
            return $token;
        }

        return null;
    }

    private function persistToken( $token ) {
        if ( !file_exists( $this->tokenPath ) ) {
            mkdir( $this->tokenPath, 0700 );
        }
        file_put_contents( $this->tokenPath . DIRECTORY_SEPARATOR . self::TOKEN_FILENAME, json_encode( $token ) );
    }

    private function encodeAuthHeaderForOAuth( $clientId, $clientSecret ) {
        return base64_encode( "$clientId:$clientSecret" );
    }
}
