<?php
/**
 * The MIT License
 * Copyright (c) 2007 Andy Smith
 */

if (!class_exists('WPZOOM_TwitterOAuth_SignatureMethod')):
/**
 * A class for implementing a Signature Method
 * See section 9 ("Signing Requests") in the spec
 */
abstract class WPZOOM_TwitterOAuth_SignatureMethod
{
    /**
     * Needs to return the name of the Signature Method (ie HMAC-SHA1)
     *
     * @return string
     */
    abstract public function getName();

    /**
     * Build up the signature
     * NOTE: The output of this function MUST NOT be urlencoded.
     * the encoding is handled in OAuthRequest when the final
     * request is serialized
     *
     * @param WPZOOM_TwitterOAuth_Request $request
     * @param WPZOOM_TwitterOAuth_Consumer $consumer
     * @param WPZOOM_TwitterOAuth_Token $token
     *
     * @return string
     */
    abstract public function buildSignature(WPZOOM_TwitterOAuth_Request $request, WPZOOM_TwitterOAuth_Consumer $consumer, WPZOOM_TwitterOAuth_Token $token = null);

    /**
     * Verifies that a given signature is correct
     *
     * @param WPZOOM_TwitterOAuth_Request $request
     * @param WPZOOM_TwitterOAuth_Consumer $consumer
     * @param WPZOOM_TwitterOAuth_Token $token
     * @param string $signature
     *
     * @return bool
     */
    public function checkSignature(WPZOOM_TwitterOAuth_Request $request, WPZOOM_TwitterOAuth_Consumer $consumer, WPZOOM_TwitterOAuth_Token $token, $signature)
    {
        $built = $this->buildSignature($request, $consumer, $token);

        // Check for zero length, although unlikely here
        if (strlen($built) == 0 || strlen($signature) == 0) {
            return false;
        }

        if (strlen($built) != strlen($signature)) {
            return false;
        }

        // Avoid a timing leak with a (hopefully) time insensitive compare
        $result = 0;
        for ($i = 0; $i < strlen($signature); $i++) {
            $result |= ord($built{$i}) ^ ord($signature{$i});
        }

        return $result == 0;
    }
}
endif;
