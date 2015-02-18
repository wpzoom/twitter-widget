<?php
/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */

if (!class_exists('WPZOOM_TwitterOAuth_Request')):
class WPZOOM_TwitterOAuth_Request
{
    protected $parameters;
    protected $httpMethod;
    protected $httpUrl;
    // for debug purposes
    public $baseString;
    public static $version = '1.0';
    public static $POST_INPUT = 'php://input';

    /**
     * Constructor
     *
     * @param string     $httpMethod
     * @param string     $httpUrl
     * @param array|null $parameters
     */
    public function __construct($httpMethod, $httpUrl, array $parameters = array())
    {
        $parameters = array_merge(WPZOOM_TwitterOAuth_Util::parseParameters(parse_url($httpUrl, PHP_URL_QUERY)), $parameters);
        $this->parameters = $parameters;
        $this->httpMethod = $httpMethod;
        $this->httpUrl = $httpUrl;
    }

    /**
     * attempt to build up a request from what was passed to the server
     *
     * @param string|null $httpMethod
     * @param string|null $httpUrl
     * @param array|null  $parameters
     *
     * @return WPZOOM_TwitterOAuth_Request
     */
    public static function fromRequest($httpMethod = null, $httpUrl = null, array $parameters = null)
    {
        $scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ? 'http' : 'https';
        $httpUrl = ($httpUrl) ? $httpUrl : $scheme .
            '://' . $_SERVER['SERVER_NAME'] .
            ':' .
            $_SERVER['SERVER_PORT'] .
            $_SERVER['REQUEST_URI'];
        $httpMethod = ($httpMethod) ? $httpMethod : $_SERVER['REQUEST_METHOD'];

        // We weren't handed any parameters, so let's find the ones relevant to
        // this request.
        // If you run XML-RPC or similar you should use this to provide your own
        // parsed parameter-list
        if (null !== $parameters) {
            // Find request headers
            $headers = WPZOOM_TwitterOAuth_Util::getHeaders();

            // Parse the query-string to find GET parameters
            $parameters = WPZOOM_TwitterOAuth_Util::parseParameters($_SERVER['QUERY_STRING']);

            // It's a POST request of the proper content-type, so parse POST
            // parameters and add those overriding any duplicates from GET
            if ($httpMethod == "POST"
                && isset($headers['Content-Type'])
                && strstr($headers['Content-Type'], 'application/x-www-form-urlencoded')
            ) {
                $post_data = WPZOOM_TwitterOAuth_Util::parseParameters(file_get_contents(self::$POST_INPUT));
                $parameters = array_merge($parameters, $post_data);
            }

            // We have a Authorization-header with OAuth data. Parse the header
            // and add those overriding any duplicates from GET or POST
            if (isset($headers['Authorization'])
                && substr($headers['Authorization'], 0, 6) == 'OAuth '
            ) {
                $headerParameters = WPZOOM_TwitterOAuth_Util::splitHeader($headers['Authorization']);
                $parameters = array_merge($parameters, $headerParameters);
            }
        }

        return new WPZOOM_TwitterOAuth_Request($httpMethod, $httpUrl, $parameters);
    }

    /**
     * pretty much a helper function to set up the request
     *
     * @param WPZOOM_TwitterOAuth_Consumer $consumer
     * @param WPZOOM_TwitterOAuth_Token    $token
     * @param string   $httpMethod
     * @param string   $httpUrl
     * @param array    $parameters
     *
     * @return WPZOOM_TwitterOAuth_Request
     */
    public static function fromConsumerAndToken(
        WPZOOM_TwitterOAuth_Consumer $consumer,
        WPZOOM_TwitterOAuth_Token $token = null,
        $httpMethod,
        $httpUrl,
        array $parameters = array()
    ) {
        $defaults = array(
            "oauth_version" => WPZOOM_TwitterOAuth_Request::$version,
            "oauth_nonce" => WPZOOM_TwitterOAuth_Request::generateNonce(),
            "oauth_timestamp" => time(),
            "oauth_consumer_key" => $consumer->key
        );
        if (null !== $token) {
            $defaults['oauth_token'] = $token->key;
        }

        $parameters = array_merge($defaults, $parameters);

        return new WPZOOM_TwitterOAuth_Request($httpMethod, $httpUrl, $parameters);
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool   $allowDuplicates
     */
    public function setParameter($name, $value, $allowDuplicates = true)
    {
        if ($allowDuplicates && isset($this->parameters[$name])) {
            // We have already added parameter(s) with this name, so add to the list
            if (is_scalar($this->parameters[$name])) {
                // This is the first duplicate, so transform scalar (string)
                // into an array so we can add the duplicates
                $this->parameters[$name] = array($this->parameters[$name]);
            }

            $this->parameters[$name][] = $value;
        } else {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * @param $name
     *
     * @return string|null
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param $name
     */
    public function removeParameter($name)
    {
        unset($this->parameters[$name]);
    }

    /**
     * The request parameters, sorted and concatenated into a normalized string.
     *
     * @return string
     */
    public function getSignableParameters()
    {
        // Grab all parameters
        $params = $this->parameters;

        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        return WPZOOM_TwitterOAuth_Util::buildHttpQuery($params);
    }

    /**
     * Returns the base string of this request
     *
     * The base string defined as the method, the url
     * and the parameters (normalized), each urlencoded
     * and the concated with &.
     *
     * @return string
     */
    public function getSignatureBaseString()
    {
        $parts = array(
            $this->getNormalizedHttpMethod(),
            $this->getNormalizedHttpUrl(),
            $this->getSignableParameters()
        );

        $parts = WPZOOM_TwitterOAuth_Util::urlencodeRfc3986($parts);

        return implode('&', $parts);
    }

    /**
     * Returns the HTTP Method in uppercase
     *
     * @return string
     */
    public function getNormalizedHttpMethod()
    {
        return strtoupper($this->httpMethod);
    }

    /**
     * parses the url and rebuilds it to be
     * scheme://host/path
     *
     * @return string
     */
    public function getNormalizedHttpUrl()
    {
        $parts = parse_url($this->httpUrl);

        $scheme = (isset($parts['scheme'])) ? $parts['scheme'] : 'http';
        $port = (isset($parts['port'])) ? $parts['port'] : (($scheme == 'https') ? '443' : '80');
        $host = (isset($parts['host'])) ? strtolower($parts['host']) : '';
        $path = (isset($parts['path'])) ? $parts['path'] : '';

        if (($scheme == 'https' && $port != '443')
            || ($scheme == 'http' && $port != '80')
        ) {
            $host = "$host:$port";
        }
        return "$scheme://$host$path";
    }

    /**
     * Builds a url usable for a GET request
     *
     * @return string
     */
    public function toUrl()
    {
        $postData = $this->toPostdata();
        $out = $this->getNormalizedHttpUrl();
        if ($postData) {
            $out .= '?' . $postData;
        }
        return $out;
    }

    /**
     * Builds the data one would send in a POST request
     *
     * @return string
     */
    public function toPostdata()
    {
        return WPZOOM_TwitterOAuth_Util::buildHttpQuery($this->parameters);
    }

    /**
     * Builds the Authorization: header
     *
     * @param string|null $realm
     *
     * @return string
     * @throws WPZOOM_TwitterOAuthException
     */
    public function toHeader($realm = null)
    {
        $first = true;
        if ($realm) {
            $out = 'Authorization: OAuth realm="' . WPZOOM_TwitterOAuth_Util::urlencodeRfc3986($realm) . '"';
            $first = false;
        } else {
            $out = 'Authorization: OAuth';
        }

        foreach ($this->parameters as $k => $v) {
            if (substr($k, 0, 5) != "oauth") {
                continue;
            }
            if (is_array($v)) {
                throw new WPZOOM_TwitterOAuthException('Arrays not supported in headers');
            }
            $out .= ($first) ? ' ' : ',';
            $out .= WPZOOM_TwitterOAuth_Util::urlencodeRfc3986($k) . '="' . WPZOOM_TwitterOAuth_Util::urlencodeRfc3986($v) . '"';
            $first = false;
        }
        return $out;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toUrl();
    }

    /**
     * @param WPZOOM_TwitterOAuth_SignatureMethod $signatureMethod
     * @param WPZOOM_TwitterOAuth_Consumer        $consumer
     * @param WPZOOM_TwitterOAuth_Token           $token
     */
    public function signRequest(WPZOOM_TwitterOAuth_SignatureMethod $signatureMethod, WPZOOM_TwitterOAuth_Consumer $consumer, WPZOOM_TwitterOAuth_Token $token = null)
    {
        $this->setParameter("oauth_signature_method", $signatureMethod->getName(), false);
        $signature = $this->buildSignature($signatureMethod, $consumer, $token);
        $this->setParameter("oauth_signature", $signature, false);
    }

    /**
     * @param WPZOOM_TwitterOAuth_SignatureMethod $signatureMethod
     * @param WPZOOM_TwitterOAuth_Consumer        $consumer
     * @param WPZOOM_TwitterOAuth_Token           $token
     *
     * @return string
     */
    public function buildSignature(WPZOOM_TwitterOAuth_SignatureMethod $signatureMethod, WPZOOM_TwitterOAuth_Consumer $consumer, WPZOOM_TwitterOAuth_Token $token = null)
    {
        return $signatureMethod->buildSignature($this, $consumer, $token);
    }

    /**
     * @return string
     */
    public static function generateNonce()
    {
        return md5(microtime() . mt_rand());
    }
}
endif;
