<?php 

namespace Endurance\Strava;

use Buzz\Browser;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Buzz\Util\Url;

class StravaClient
{
    protected $browser;
    protected $token;

    public function __construct(Browser $browser)
    {
        $this->browser = $browser;
        
        // Set client options
        $client = $this->browser->getClient();

        // Remove the timeout to allow time to download large files
        $client->setTimeout(0);
    }

    public function signIn($email, $password)
    {
        $request = new FormRequest(FormRequest::METHOD_POST);

        // Set the request URL
        $url = new Url('https://www.strava.com/api/v2/authentication/login');
        $url->applyToRequest($request);

        // Set the form fields
        $request->setField('email', $email);
        $request->setField('password', $password);

        $response = new Response();
        $this->browser->getClient()->send($request, $response);

        $result = json_decode($response->getContent(), true);

        if (!isset($result['token'])) {
            throw new \RuntimeException('Unable to sign in');
        }

        $this->token = $result['token'];
    }

    public function isSignedIn()
    {
        return $this->token !== null;
    }

    public function uploadActivity($file)
    {
        if (!$this->isSignedIn()) {
            throw new \RuntimeException('Not signed in');
        }

        $request = new FormRequest(FormRequest::METHOD_POST);

        // Set the request URL
        $url = new Url('http://www.strava.com/api/v2/upload');
        $url->applyToRequest($request);

        // Set the form fields
        $request->setField('token', $this->token);
        $request->setField('type', 'fit');

        // Not using FormUpload as the Strava API expects the data as a field value
        $request->setField('data', file_get_contents($file));

        $response = new Response();
        $this->browser->getClient()->send($request, $response);

        return json_decode($response->getContent(), true);
    }

    public function getMap($rideId)
    {
        if (!$this->isSignedIn()) {
            throw new \RuntimeException('Not signed in');
        }
        
        $response = $this->browser->get("http://www.strava.com/api/v2/rides/$rideId/map_details?token={$this->token}");

        return json_decode($response->getContent(), true);
    }

    public function getRideDetails($rideId)
    {
        // Doesn't require authentication
        $response = $this->browser->get("http://www.strava.com/api/v2/rides/$rideId");

        return json_decode($response->getContent(), true);
    }
}
