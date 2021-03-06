<?php
namespace PleioRest\Controllers;

class User {

    /**
     * @SWG\Post(
     *     path="/api/users/me",
     *     security={{"oauth2": {"all"}}},
     *     tags={"user"},
     *     summary="Get information of the resource owner.",
     *     description="Get information of the resource owner.",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation."
     *     )
     * )
     */
    public function me($request, $response, $args) {
        $site = elgg_get_site_entity();
        $user = elgg_get_logged_in_user_entity();

        if (!$user) {
            throw new Exception("Could not find the logged in user.");
        }

        $json = array(
            "guid" => $user->guid,
            "username" => $user->username,
            "name" => $user->name,
            "email" => $user->email,
            "icon" => "{$site->url}/mod/profile/icondirect.php?guid={$user->guid}&joindate={$user->time_created}",
            "url" => $user->getURL(),
            "language" => $user->language,
            "isAdmin" => subsite_manager_is_superadmin_logged_in()
        );

        $response = $response->withHeader("Content-type", "application/json");
        return $response->write(json_encode($json, JSON_PRETTY_PRINT));
    }

    /**
     * @SWG\Post(
     *     path="/api/users/me/generate_token",
     *     security={{"oauth2": {"all"}}},
     *     tags={"user"},
     *     summary="Generate a single-use login token for the user.",
     *     description="Generate a single-use login token for the user.",
     *     produces={"application/json"},
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation."
     *     )
     * )
     */
     public function generateToken($request, $response, $args) {
        $duration = 60*5; // 5 minutes
        $user = elgg_get_logged_in_user_entity();

        if (!$user) {
         throw new Exception("Could not find the logged in user.");
        }

        $token = bin2hex(openssl_random_pseudo_bytes(32, $crypto_strong));
        if (!$crypto_strong) {
            throw new Exception("Strength of the generated token is not sufficient. Check your platform.");
        }

        $result = $user->setPrivateSetting('login_token', serialize(array(
            'token' => $token,
            'expiry' => time() + $duration
        )));

        $response = $response->withHeader('Content-type', 'application/json');
        if ($result) {
            $json = array(
                'user_guid' => $user->guid,
                'token' => $token,
                'expiry' => $duration
            );
        } else {
            return $response->withStatus(500);
            $json = array(
                'error' => 'could_not_save_token'
            );
        }

        return $response->write(json_encode($json, JSON_PRETTY_PRINT));
     }

     /**
      * @SWG\Get(
      *     path="/api/users/me/login_token",
      *     security={{"oauth2": {"all"}}},
      *     tags={"user"},
      *     summary="Login the user by single-use token and redirect to the requested page.",
      *     description="Login the user by token and redirect to the requested page.",
      *     produces={"application/json"},
      *     @SWG\Parameter(
      *         name="user_guid",
      *         in="query",
      *         description="The guid of the user",
      *         required=true,
      *         type="integer",
      *         @SWG\Items(type="integer")
      *     ),
      *     @SWG\Parameter(
      *         name="redirect_url",
      *         in="query",
      *         description="The URL to redirect after login (only on the same host)",
      *         required=false,
      *         type="integer",
      *         @SWG\Items(type="integer")
      *     ),
      *     @SWG\Parameter(
      *         name="token",
      *         in="query",
      *         description="The secret single-use token",
      *         required=true,
      *         type="integer",
      *         @SWG\Items(type="integer")
      *     ),

      *     @SWG\Response(
      *         response=301,
      *         description="Succesful operation."
      *     ),
      *     @SWG\Response(
      *         response=403,
      *         description="Invalid or expired token."
      *     )
      * )
      */
    public function loginToken($request, $response, $args) {
        $params = $request->getQueryParams();
        $user_guid = (int) $params['user_guid'];
        $redirect_url = $params['redirect_url'];
        $input_token = $params['token'];

        $ia = elgg_set_ignore_access(true);

        $user = get_entity($user_guid);
        if ($user) {
            $token = $user->getPrivateSetting('login_token');
        }

        elgg_set_ignore_access($ia);

        if (!$user | !$token | !$input_token) {
            return $response->withStatus(403)->write(json_encode(array(
                'error' => 'invalid_token'
            )));
        }

        $token = unserialize($token);
        if (!isset($token['expiry']) | $token['expiry'] < time()) {
            return $response->withStatus(403)->withHeader('Content-type', 'application/json')->write(json_encode(array(
                'error' => 'token_expired'
            ), JSON_PRETTY_PRINT));
        }

        if (!isset($token['token'])) {
            return $response->withStatus(403)->withHeader('Content-type', 'application/json')->write(json_encode(array(
                'error' => 'invalid_token'
            ), JSON_PRETTY_PRINT));
        }

        if  (!elgg_is_logged_in() && $token['token'] == $input_token) {
            login($user);
            $user->removePrivateSetting('login_token');
        }

        $site = elgg_get_site_entity();
        if ($redirect_url) {
            $url = parse_url($redirect_url);
            $site_url = parse_url($site->url);

            if (isset($url['scheme']) | isset($url['host'])) {
                if ($url['scheme'] == $site_url['scheme'] && $url['host'] == $site_url['host']) {
                    $redirect_url = $url['path'];
                } else {
                    $redirect_url = '/';
                }
            } else {
                $redirect_url = $url['path'];
            }
        } else {
            $redirect_url = '/';
        }

        return $response->withStatus(301)->withHeader('Location', $redirect_url);
    }

    /**
     * @SWG\Post(
     *     path="/api/users/me/register_push",
     *     security={{"oauth2": {"all"}}},
     *     tags={"user"},
     *     summary="Register application for push notifications.",
     *     description="Register an application to receive push notifications of activities.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="service",
     *         in="query",
     *         description="The service (gcm, apns or wns)",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="device_id",
     *         in="query",
     *         description="The unique device ID.",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="token",
     *         in="query",
     *         description="The token received from Google Cloud Messaging (GCM), Apple Push Notification Gateway Service (APNS) or the full notification URL from Windows Notification Service (WNS).",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation."
     *     )
     * )
     */
    public function registerPush($request, $response, $args) {
        $factory = new \PleioRest\AuthenticationServerFactory();
        $server = $factory->getServer();
        $accessData = $server->getAccessTokenData(\OAuth2\Request::createFromGlobals());

        $vars = $request->getParsedBody();

        $device_id = $vars['device_id'];
        $token = $vars['token'];
        $service = $vars['service'];

        if (!in_array($service, array('gcm', 'apns', 'wns'))) {
            return $response->withStatus(404);
        }

        $handler = new \PleioRest\Services\PushNotificationHandler;
        $handler->addSubscription(elgg_get_logged_in_user_entity(), $accessData['client_id'], $service, $device_id, $token);
    }

    /**
     * @SWG\Post(
     *     path="/api/users/me/deregister_push",
     *     security={{"oauth2": {"all"}}},
     *     tags={"user"},
     *     summary="Deregister application for push notifications.",
     *     description="Deregister an application to receive push notifications of activities.",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="service",
     *         in="query",
     *         description="The service (gcm, apns or wns)",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="device_id",
     *         in="query",
     *         description="The unique device ID registered with register_push.",
     *         required=true,
     *         type="string",
     *         @SWG\Items(type="string")
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Succesful operation."
     *     )
     * )
     */
    public function deregisterPush($request, $response, $args) {
        $factory = new \PleioRest\AuthenticationServerFactory();
        $server = $factory->getServer();
        $accessData = $server->getAccessTokenData(\OAuth2\Request::createFromGlobals());

        $vars = $request->getParsedBody();
        $service = $vars['service'];
        $device_id = $vars['device_id'];

        if (!in_array($service, array('gcm', 'apns', 'wns'))) {
            return $response->withStatus(404);
        }

        $handler = new \PleioRest\Services\PushNotificationHandler;
        $handler->removeSubscription(elgg_get_logged_in_user_entity(), $accessData['client_id'], $service, $device_id);
    }
}