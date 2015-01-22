<?php
/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */

if (!class_exists('WPZOOM_TwitterOAuth_Token')):
class WPZOOM_TwitterOAuth_Token
{
    /** @var string */
    public $key;
    /** @var string */
    public $secret;

    /**
     * @param string $key    The OAuth Token
     * @param string $secret The OAuth Token Secret
     */
    public function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * Generates the basic string serialization of a token that a server
     * would respond to request_token and access_token calls with
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf("oauth_token=%s&oauth_token_secret=%s",
            WPZOOM_TwitterOAuth_Util::urlencodeRfc3986($this->key),
            WPZOOM_TwitterOAuth_Util::urlencodeRfc3986($this->secret)
        );
    }
}
endif;
