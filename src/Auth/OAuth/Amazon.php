<?php

namespace Auth\OAuth;

use Auth\OAuth;

// Reference Material
// https://developer.amazon.com/docs/login-with-amazon/authorization-code-grant.html
// https://developer.amazon.com/docs/login-with-amazon/register-web.html
// https://developer.amazon.com/docs/login-with-amazon/obtain-customer-profile.html
class Amazon extends OAuth
{
    /**
     * @var array
     */
    protected $user = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'amazon';
    }

    /**
     * @param $state
     *
     * @return json
     */
    public function parseState(string $state)
    {
        return json_decode(html_entity_decode($state), true);
    }


    /**
     * @return string
     */
    public function getLoginURL(): string
    {
        return 'https://www.amazon.com/ap/oa?' .
            'client_id='.urlencode($this->appID).
            '&redirect_uri='.urlencode($this->callback).
            '&response_type=code'.
            '&state='.urlencode(json_encode($this->state)).
            '&scope=profile';
    }

    /**
     * @param string $code
     *
     * @return string
     */
    public function getAccessToken(string $code): string
    {
        $headers[] = 'Content-Type: application/x-www-form-urlencoded;charset=UTF-8';
        $accessToken = $this->request(
            'POST',
            'https://api.amazon.com/auth/o2/token',
            $headers,
            'code=' . urlencode($code) .
            '&client_id=' . urlencode($this->appID) .
            '&client_secret=' . urlencode($this->appSecret).
            '&redirect_uri='.urlencode($this->callback).
            '&grant_type=authorization_code'
        );
        $accessToken = json_decode($accessToken, true);

        if (isset($accessToken['access_token'])) {
            return $accessToken['access_token'];
        }

        return '';
    }

    /**
     * @param string $accessToken
     *
     * @return string
     */
    public function getUserID(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        if (isset($user['user_id'])) {
            return $user['user_id'];
        }

        return '';
    }

    /**
     * @param string $accessToken
     *
     * @return string
     */
    public function getUserEmail(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        if (isset($user['email'])) {
            return $user['email'];
        }

        return '';
    }

    /**
     * @param string $accessToken
     *
     * @return string
     */
    public function getUserName(string $accessToken): string
    {
        $user = $this->getUser($accessToken);

        if (isset($user['name'])) {
            return $user['name'];
        }

        return '';
    }

    /**
     * @param string $accessToken
     *
     * @return array
     */
    protected function getUser(string $accessToken): array
    {
        if (empty($this->user)) {
            $user = $this->request('GET', 'https://api.amazon.com/user/profile?access_token='.urlencode($accessToken));
            $this->user = json_decode($user, true);
        }
        return $this->user;
    }
}
