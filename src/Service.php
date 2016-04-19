<?php

namespace Homer\Wechat;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class Service
{
    const EXCHANGE_FOR_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    const REFRESH_ACCESS_TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * appid
     * @var string
     */
    private $appId;

    /**
     * @var string
     */
    private $secret;

    public function __construct(array $config, ClientInterface $client = null)
    {
        $this->appId = array_get($config, 'app_id');
        $this->secret = array_get($config, 'secret');

        $this->client = $client ?: $this->createDefaultHttpClient();
    }

    /**
     *
     * exchange code for access token
     *
     * we can get not only access_token, but refresh_token, openid as well as unionid.
     *
     * @param $code
     *
     * @return \stdClass     see https://open.weixin.qq.com/cgi-bin/showdocument?action=doc&id=open1419317851
     * @throws \Exception
     */
    public function exchangeForAccessToken($code)
    {
        return $this->sendRequestAndDecode($this->buildRequestUrlForExchangingAccessToken($code));
    }

    /**
     *
     * refresh access token
     *
     * @param $refreshToken
     *
     * @return \stdClass     see https://open.weixin.qq.com/cgi-bin/showdocument?action=doc&id=open1419317851
     * @throws \Exception
     */
    public function refreshAccessToken($refreshToken)
    {
        return $this->sendRequestAndDecode($this->buildRequestUrlForRefreshingAccessToken($refreshToken));
    }


    private function buildRequestUrlForExchangingAccessToken($code)
    {
        return self::EXCHANGE_FOR_ACCESS_TOKEN_URL . '?' . http_build_query([
            'appid'      => $this->appId,
            'secret'     => $this->secret,
            'code'       => $code,
            'grant_type' => 'authorization_code',
        ]);
    }

    private function buildRequestUrlForRefreshingAccessToken($refreshToken)
    {
        return self::REFRESH_ACCESS_TOKEN_URL . '?' . http_build_query([
            'appid'         => $this->appId,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);
    }

    // issue request and decode the response
    private function sendRequestAndDecode($url)
    {
        $options = [
            RequestOptions::TIMEOUT => 500,
            RequestOptions::VERIFY  => false,
        ];

        $response = $this->client->request('GET', $url, $options);
        $responseBody = (string)$response->getBody();
        if ($response->getStatusCode() != 200) {
            throw new \Exception(sprintf('微信服务异常: %s', $responseBody));
        }

        if (false === ($response = safe_json_decode($responseBody))) {
            throw new \Exception(sprintf('响应异常: %s', $responseBody));
        }

        return $response;
    }

    /**
     * create default http client
     *
     * @return Client
     */
    private function createDefaultHttpClient()
    {
        return new Client();
    }
}