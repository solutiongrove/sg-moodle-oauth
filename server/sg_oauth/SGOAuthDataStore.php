<?php

// data store backed by moodle, for use in the server
// based on elgg's oauth plugin (http://docs.elgg.org/wiki/OAuth)

class SGOAuthDataStore extends OAuthDataStore {

    function lookup_consumer($consumer_key) {
        $consumEnt = sg_oauth_lookup_consumer_entity($consumer_key);
        if ($consumEnt) {
            return sg_oauth_consumer_from_entity($consumEnt);
        } else {
            return NULL;
        }
    }

    // get the entity first, then turn it into an oauth token object
    function lookup_token($consumer, $token_type, $token) {
        $tokEnt = sg_oauth_lookup_token_entity($token, $token_type, $consumer);
        if ($tokEnt) {
            return sg_oauth_token_from_entity($tokEnt);
        } else {
            return NULL;
        }
    }

    function lookup_nonce($consumer, $token, $nonce, $timestamp) {
        // get any nonces that match the consumer and token
        $noncEnt = sg_oauth_lookup_nonce_entity($consumer, $token, $nonce);
        if ($noncEnt) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    function new_request_token($consumer, $callback = null) {
        $key = md5(time() . sg_oauth_generate_verifier(5));
        $secret = md5(md5(time() + time()));
        $token = new OAuthToken($key, $secret);

        // save the token to the database
        sg_oauth_save_request_token($token, $consumer, NULL, $callback);

        return $token;
    }

    function new_access_token($token, $consumer, $verifier = null) {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token

        $reqToken = sg_oauth_lookup_token_entity($token->key, 'request', $consumer);
        if ($reqToken) {

            if ($reqToken->userid != 0 &&
                    $verifier == $reqToken->verifier) {
                // it's been signed by a user
                // if there's a verifier on the token, it matches the one we were handed (or both are null)
                $key = md5(time() . sg_oauth_generate_verifier(5));
                $secret = time() + time();
                $acc = new OAuthToken($key, md5(md5($secret)));

                $tokEnt = sg_oauth_save_access_token($reqToken, $acc);
                return $acc;
            } else {
                // otherwise, delete the request token entity
                sg_oauth_delete_token_entity($reqToken);
                throw new OAuthException('Invalid request token (not signed by a user): ' . $token);
            }
        } else {
            throw new OAuthException('Invalid request token (not found): ' . $token);
        }
    }

}

?>