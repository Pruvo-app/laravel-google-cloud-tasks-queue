<?php

namespace Pruvo\LaravelGoogleCloudTasksQueue;

use Carbon\Carbon;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use phpseclib\Crypt\RSA;
use phpseclib\Math\BigInteger;
use SodiumException;
use UnexpectedValueException;

class OpenIdVerificator
{
    private const V3_CERTS = 'GOOGLE_V3_CERTS';
    private const URL_OPENID_CONFIG = 'https://accounts.google.com/.well-known/openid-configuration';
    private const URL_TOKEN_INFO = 'https://www.googleapis.com/oauth2/v3/tokeninfo';

    private $guzzle;
    private $rsa;
    private $jwt;
    private $maxAge = [];

    /**
     * 
     * @param Client $guzzle 
     * @param RSA $rsa 
     * @param JWT $jwt 
     * @return void 
     */
    public function __construct(Client $guzzle, RSA $rsa, JWT $jwt)
    {
        $this->guzzle = $guzzle;
        $this->rsa = $rsa;
        $this->jwt = $jwt;
    }

    /**
     * 
     * @param mixed $openIdToken 
     * @param mixed $kid 
     * @param bool $cache 
     * @return mixed 
     * @throws GuzzleException 
     * @throws SodiumException 
     * @throws SignatureInvalidException 
     * @throws InvalidArgumentException 
     * @throws UnexpectedValueException 
     * @throws BeforeValidException 
     * @throws ExpiredException 
     */
    public function decodeOpenIdToken($openIdToken, $kid, $cache = true)
    {
        if (!$cache) {
            $this->forgetFromCache();
        }

        $publicKey = $this->getPublicKey($kid);

        try {
            return $this->jwt->decode($openIdToken, $publicKey, ['RS256']);
        } catch (SignatureInvalidException $e) {
            if (!$cache) {
                throw $e;
            }

            return $this->decodeOpenIdToken($openIdToken, $kid, false);
        }
    }

    /**
     * 
     * @param mixed $kid 
     * @return string|array<string, phpseclib\Crypt\Math_BigInteger>|false 
     * @throws GuzzleException 
     * @throws SodiumException 
     */
    public function getPublicKey($kid = null)
    {
        $v3Certs = Cache::get(self::V3_CERTS);

        if (is_null($v3Certs)) {
            $v3Certs = $this->getFreshCertificates();
            Cache::put(self::V3_CERTS, $v3Certs, Carbon::now()->addSeconds($this->maxAge[self::URL_OPENID_CONFIG]));
        }

        $cert = $kid ? collect($v3Certs)->firstWhere('kid', '=', $kid) : $v3Certs[0];

        return $this->extractPublicKeyFromCertificate($cert);
    }

    /**
     * 
     * @return mixed 
     * @throws GuzzleException 
     */
    private function getFreshCertificates()
    {
        $jwksUri =  $this->callApiAndReturnValue(self::URL_OPENID_CONFIG, 'jwks_uri');

        return $this->callApiAndReturnValue($jwksUri, 'keys');
    }

    /**
     * 
     * @param mixed $certificate 
     * @return string|array<string, phpseclib\Crypt\Math_BigInteger>|false 
     * @throws SodiumException 
     */
    private function extractPublicKeyFromCertificate($certificate)
    {
        $modulus = new BigInteger(JWT::urlsafeB64Decode($certificate['n']), 256);
        $exponent = new BigInteger(JWT::urlsafeB64Decode($certificate['e']), 256);

        $this->rsa->loadKey(compact('modulus', 'exponent'));

        return $this->rsa->getPublicKey();
    }

    /**
     * 
     * @param mixed $openIdToken 
     * @return mixed 
     * @throws GuzzleException 
     */
    public function getKidFromOpenIdToken($openIdToken)
    {
        return $this->callApiAndReturnValue(self::URL_TOKEN_INFO . '?id_token=' . $openIdToken, 'kid');
    }

    /**
     * 
     * @param mixed $url 
     * @param mixed $value 
     * @return mixed 
     * @throws GuzzleException 
     */
    private function callApiAndReturnValue($url, $value)
    {
        $response = $this->guzzle->get($url);

        $data = json_decode($response->getBody(), true);

        $maxAge = 0;
        foreach ($response->getHeader('Cache-Control') as $line) {
            preg_match('/max-age=(\d+)/', $line, $matches);
            $maxAge = isset($matches[1]) ? (int) $matches[1] : 0;
        }

        $this->maxAge[$url] = $maxAge;

        return Arr::get($data, $value);
    }

    /**
     * Check if Google V3 Certificates is cached
     * @return bool 
     */
    public function isCached()
    {
        return Cache::has(self::V3_CERTS);
    }

    /**
     * Clear Google V3 Certificates cache
     * @return void 
     */
    public function forgetFromCache()
    {
        Cache::forget(self::V3_CERTS);
    }
}
