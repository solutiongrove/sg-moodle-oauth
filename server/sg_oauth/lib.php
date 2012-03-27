<?php

require_once('OAuth.php');
require_once('SGOAuthDataStore.php');

// get or create an OAuth server
function oauth_get_server() {
	global $OAUTH_SERVER; // cache the object
	if (!$OAUTH_SERVER) {
		$OAUTH_SERVER = new OAuthServer(new SGOAuthDataStore());
		$OAUTH_SERVER->add_signature_method(new OAuthSignatureMethod_HMAC_SHA1());
		$OAUTH_SERVER->add_signature_method(new OAuthSignatureMethod_PLAINTEXT());
	}

	return $OAUTH_SERVER;
}

// look up a consumer based on its key
function oauth_lookup_consumer_entity($consumer_key) {
  global $DB;

  if (!$consumer_key) {
    return NULL;
  }

  if ( $consumer = $DB->get_record('oauth_consumers',array('consumerkey'=>$consumer_key)) ) {
    return $consumer;
  } else {
    return NULL;
  }

}

// create a consumer object from an entity object
function oauth_consumer_from_entity($consumEnt) {
  return new OAuthConsumer($consumEnt->consumerkey, $consumEnt->secret, $consumEnt->callbackurl);
}


// try to automatically find the parameters from a request URL
function oauth_find_parameters($url) {
  $bits = parse_url($url);
  parse_str($bits['query'], $params);

  return $params;
}


// look up a nonce object based on its consumer, token, and nonce value
function oauth_lookup_nonce_entity($consumer, $token, $nonce) {
  global $DB;
  if (!$consumer || !$nonce) {
    return NULL;
  }

  $pairs = array('consumerkey' => $consumer->key, 'nonce' => $nonce);
  if ($token) {
    $pairs['token'] = $token->key;
  }

  if ( $nonce = $DB->get_record('oauth_nonces',$pairs) ) {
    return $nonce;
  } else {
    return NULL;
  }
}


function oauth_save_nonce($consumerKey, $nonce, $tokenKey = NULL, $timeout = 600) {
  global $DB;

  if (!$consumerKey || !$nonce) {
    return NULL;
  }


  $expires = time() + $timeout; // when does this nonce expire?

  $noncEnt = new stdClass();
  $noncEnt->consumerkey = $consumerKey;
  if ($tokenKey) {
    $noncEnt->token = $tokenKey;
  }
  $noncEnt->nonce = $nonce;
  $noncEnt->expires = $expires;
  $DB->insert_record('oauth_nonces', $noncEnt, false);
}


// save the request token to a new record
function oauth_save_request_token($token, $consumer, $user = NULL , $callback = NULL) {
  global $DB;

  $tokEnt = new stdClass();
  $tokEnt->tokentype = 'request';
  if ($user) {
    $tokEnt->userid = $user;
  }
  $tokEnt->token = $token->key;
  $tokEnt->secret = $token->secret;
  $tokEnt->consumerkey = $consumer->key;
  $tokEnt->validated = 0;
  $tokEnt->timecreated = time();
  $tokEnt->timemodified = time();
  if ($callback) {
    $tokEnt->callbackurl = $callback;
  }
  $DB->insert_record('oauth_tokens', $tokEnt, false);

  return $tokEnt;
}


// update the request token to a validated token
function oauth_save_validated_token($token) {
  global $USER, $DB;

  if ($USER->id == 0) {
    return NULL;
  }

  $tokEnt = new stdClass();
  $tokEnt->id = $token->id;
  $tokEnt->userid = $USER->id;
  $tokEnt->validated = 1;
  $verifier = oauth_generate_verifier();
  $tokEnt->verifier = $verifier;
  $tokEnt->timemodified = time();
  $tokEnt->lastcheckedon = time();
  $DB->update_record('oauth_tokens', $tokEnt);

  return $tokEnt;
}


// save the access token to the given entity
function oauth_save_access_token($tokEnt, $token) {
  global $DB;
  $accEnt = new stdClass();
  $accEnt->id = $tokEnt->id;
  $accEnt->callbackurl = NULL;
  $accEnt->token = $token->key;
  $accEnt->secret = $token->secret;
  $accEnt->tokentype = 'access';
  $accEnt->timemodified = time();
  $DB->update_record('oauth_tokens', $accEnt);

  return $accEnt;
}


// look up the entity for the given token key and consumer
function oauth_lookup_token_entity($tokenKey, $token_type, $consumer = false) {
  global $DB;

  if (!$tokenKey) {
    return NULL;
  }

  $pairs = array('token' => $tokenKey);
  if (in_array($token_type,array('access','request'))) {
    $pairs['tokentype'] = $token_type;
  } else {
    return NULL;
  }

  if ( $tokEnt = $DB->get_record('oauth_tokens',$pairs) ) {
    if ($consumer) {
      if ($tokEnt->consumerkey == $consumer->key) {
        return $tokEnt;
      } else {
        return NULL;
      }
    } else {
      return $tokEnt;
    }
  } else {
    return NULL;
  }
}


// creates a token object from an entity object
function oauth_token_from_entity($tokEnt) {
  return new OAuthToken($tokEnt->token, $tokEnt->secret);
}


// generate a verifier code
function oauth_generate_verifier($length = 8) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
  $string = '';

  for ($p = 0; $p < $length; $p++) {
    $string .= $characters[mt_rand(0, strlen($characters))];
  }

  return $string;
}


// look up an access token for a user
function oauth_lookup_access_token_entity($consumerkey, $userid) {
  global $DB;
  if (!$consumerkey || !$userid) {
    return NULL;
  }

  $pairs = array('userid' => $userid, 'consumerkey' => $consumerkey, 'tokentype' => 'access');
  if ( $tokEnt = $DB->get_record('oauth_tokens',$pairs) ) {
    return $tokEnt;
  } else {
    return NULL;
  }
}


// execute a rest call
function oauth_execute_rest($consumer, $token, $service = '') {
  global $DB;
  if ($tokEnt = oauth_lookup_token_entity($token->key, 'access', $consumer)) {
    if ($tokEnt->userid > 0) {
      switch ($service) {
      case 'user.info':
        $return_value = $DB->get_record('user',array('id' => $tokEnt->userid),'id,username,firstname,lastname,email');
        break;
      case 'user.myteachers':
        $userid = $tokEnt->userid;
        $teacher_role_ids = array();

        if ($teacher_roles = $DB->get_records_sql("SELECT r.* FROM {role} r where shortname = 'editingteacher' or shortname = 'teacher'")) {
          foreach ($teacher_roles as $teacher_role) {
            $teacher_role_ids[] = $teacher_role->id;
          }
        }

        $teachers = array();
        $seen_teacher_ids = array();
        if ($courses = enrol_get_users_courses($userid, true)) {
          foreach ($courses as $course) {
            $context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
            $contextid = $context->id;
            $has_role = false;
            foreach ($teacher_role_ids as $roleid) {
              if (user_has_role_assignment($userid, $roleid, $contextid)) {
                $has_role = TRUE;
                break;
              }
            }
            if (!$has_role) {
              if ($users = get_role_users($teacher_role_ids, $context,false,'u.id,u.username,u.firstname,u.lastname,u.email,r.name as rolename,r.shortname as roleshortname')) {
                foreach ($users as $user) {
                  if (!in_array($user->id, $seen_teacher_ids)) {
                    $teachers[] = $user;
                    $seen_teacher_ids[] = $user->id;
                  }
                }
              }
            }
          }
        }
        $return_value = $teachers;
        break;
      case 'user.mystudents':
        $userid = $tokEnt->userid;
        $teacher_role_ids = array();

        if ($teacher_roles = $DB->get_records_sql("SELECT r.* FROM {role} r where shortname = 'editingteacher' or shortname = 'teacher'")) {
          foreach ($teacher_roles as $teacher_role) {
            $teacher_role_ids[] = $teacher_role->id;
          }
        }

        if ($student_roles = $DB->get_records_sql("SELECT r.* FROM {role} r where shortname = 'student'")) {
          foreach ($student_roles as $student_role) {
            $student_role_ids[] = $student_role->id;
          }
        }

        $students = array();
        $seen_student_ids = array();
        if ($courses = enrol_get_users_courses($userid, true)) {
          foreach ($courses as $course) {
            $context = get_context_instance(CONTEXT_COURSE, $course->id, MUST_EXIST);
            $contextid = $context->id;
            $has_role = false;
            foreach ($teacher_role_ids as $roleid) {
              if (user_has_role_assignment($userid, $roleid, $contextid)) {
                $has_role = TRUE;
                break;
              }
            }
            if ($has_role) {
              if ($users = get_role_users($student_role_ids, $context,false,'u.id,u.username,u.firstname,u.lastname,u.email,r.name as rolename,r.shortname as roleshortname')) {
                foreach ($users as $user) {
                  if (!in_array($user->id, $seen_student_ids)) {
                    $students[] = $user;
                    $seen_student_ids[] = $user->id;
                  }
                }
              }
            }
          }
        }
        $return_value = $students;
        break;
      default:
        return NULL;
      }
      return $return_value;
    } else {
      return NULL;
    }
  } else {
    return NULL;
  }
}

function oauth_delete_token_entity($token) {
  global $DB;
  $DB->delete_records('oauth_tokens',array('id' => $token->id));
}

function oauth_refresh_token_lastcheckedon($token) {
  global $DB;

  $tokEnt = new stdClass();
  $tokEnt->id = $token->id;
  $tokEnt->lastcheckedon = time();
  $DB->update_record('oauth_tokens', $tokEnt);

  return $tokEnt;
}

function oauth_user_clear_lastchecked($user) {
  global $DB;
  if ( $tokens = $DB->get_records('oauth_tokens', array('tokentype'=>'access', 'userid'=>$user->id))) {
    foreach ($tokens as $token) {
      $updated_token = new StdClass();
      $updated_token->id = $token->id;
      $updated_token->lastcheckedon = 0;
      $DB->update_record('oauth_tokens', $updated_token);
      unset($updated_token);
    }
  }
}

// cleanup data
function oauth_cleanup_tokens_and_nonces() {
  global $DB;

  $now = time();
  $token_timeout = 60 * 60; // 1 hour

  if ( $nonces = $DB->get_records('oauth_nonces') ) {
    foreach ($nonces as $nonce) {
      if ($now > $nonce->expires) {
        $DB->delete_records('oauth_nonces',array('id' => $nonce->id));
      }
    }
  }

  if ( $tokens = $DB->get_records('oauth_tokens') ) {
    foreach ($tokens as $token) {
      $consumEnt = oauth_lookup_consumer_entity($token->consumerkey);
      if (!$consumEnt) {
        // no consumer entity, delete it
        $DB->delete_records('oauth_tokens',array('id' => $token->id));
      } elseif ($token->tokentype == 'request' && $now > ($token->timecreated + $token_timeout)) {
        $DB->delete_records('oauth_tokens',array('id' => $token->id));
      } else if ($token->tokentype == 'access' && $token->callbackurl) {
        $updated_token = new StdClass();
        $updated_token->id = $token->id;
        $updated_token->callbackurl = NULL;
        $DB->update_record('oauth_tokens', $updated_token);
        unset($updated_token);
      }
    }
  }


}

?>