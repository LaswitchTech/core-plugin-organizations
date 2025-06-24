<?php

/**
 * Core Framework - OrganizationsEndpoint
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Objects;
use \LaswitchTech\Core\Abstracts\Endpoint;

class OrganizationsEndpoint extends Endpoint {

    /**
     * Constructor
     */
    public function __construct()
    {

        // Call Parent Constructor
        parent::__construct();

        // Retrieve the namespace
        $namespace = $this->Request->getNamespace();

        // Set Global access
        $this->Public = false;

        // Set Level
        switch($namespace){
            case "/organizations/index":
            case "/organizations/fetch":
            case "/organizations/users":
                $this->Level = 1;
                break;
            case "/organizations/update":
                $this->Level = 3;
                break;
            case "/organizations/create":
                $this->Level = 2;
                break;
        }
    }

    /**
     * Fetch all organizations
     */
    public function indexAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => $this->Model->Organizations->list()];

        // Return the message
        return $message;
    }

    /**
     * Fetch a Organization's Information
     */
    public function fetchAction(): array
    {
        // Import Global Variables
        global $CONFIG;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => [
            "record" => $this->Model->Organizations->get(intval($this->Request->getParams('GET', 'id')))
        ]];

        // Return the message
        return $message;
    }

    /**
     * Update a Organization
     */
    public function updateAction(): array
    {
        // Import Global Variables
        global $CSRF;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check the request method
        if($this->Request->getMethod() == "POST"){
            $message["data"]["CSRF"] = [
                "token" => $CSRF->token(),
                "key" => $CSRF->key()
            ];
        }

        // Retrieve the organization id
        $id = intval($this->Request->getParams('REQUEST','id'));

        // Retrieve the organization
        $organization = $this->Model->Organizations->get($id, false);

        // Check if the organization exists
        if(empty($organization)){
            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the requested organization."];
        }

        // Check if the organization is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "POST"){

                // Retrieve the parameters
                $parameters = $this->Request->getParams('REQUEST');

                // Initialize the Events
                $message['data']['events'] = [];

                // Update the organization
                foreach($parameters as $key => $value){
                    if(isset($organization[$key])){
                        switch($key){
                            case 'users':
                                if(is_array($value)){
                                    $organization[$key] = [];
                                    foreach($value as $objId){
                                        $organization[$key][] = intval($objId);
                                    }
                                    $organization[$key] = array_unique($organization[$key]);
                                } else {
                                    $organization[$key] = $value;
                                }
                                break;
                            case 'isDefault':
                                $organization[$key] = intval(filter_var($value, FILTER_VALIDATE_BOOLEAN));
                                break;
                            default:
                                $organization[$key] = $value;
                                break;
                        }
                    }
                }

                // Remove some fields
                foreach([
                    'id',
                    'created',
                    'modified',
                    'owner',
                    'vcard',
                    'isDeleted',
                    'isActive',
                ] as $key){
                    unset($organization[$key]);
                }

                // Update the organization
                $affectedRows = $this->Model->Organizations->update($id, $organization);

                // Retrieve the final organization
                $message['data']['record'] = $this->Model->Organizations->get($id);
            } else {
                $message = ["status" => 405, "message" => "Method Not Allowed", "data" => "The method is not allowed for the requested URL."];
            }
        }

        return $message;
    }

    /**
     * Create a Organization
     */
    public function createAction(): array
    {
        // Import Global Variables
        global $CSRF, $SMTP, $CONFIG;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check the request method
        if($this->Request->getMethod() == "POST"){
            $message["data"]["CSRF"] = [
                "token" => $CSRF->token(),
                "key" => $CSRF->key()
            ];
        }



        // Check if the organization is accessible
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "POST"){

                // Retrieve the parameters
                $parameters = $this->Request->getParams('REQUEST');

                // Initialize the Events
                $message['data']['events'] = [];

                // Add required fields
                $parameters['owner'] = $this->Auth->organization()->organizationname;
                $parameters['organization'] = $this->Auth->organization()->organization()->id;
                $parameters['password'] = $this->Helper->Organizations->generate(12);
                $parameters['isVerified'] = 1;

                // Required Fields
                $required = ["name", "email", "owner", "organization", "password", "isVerified"];

                // Check if all required fields are set
                if(count(array_intersect_key(array_flip($required), $parameters)) == count($required)){

                    // Connect to the smtp server
                    $SMTP->connect();

                    // Check if the smtp server is connected
                    if($SMTP->isConnected()){

                        // Authenticate to the SMTP Server
                        $SMTP->authenticate();

                        // Check if the SMTP Server is authenticated
                        if($SMTP->isAuthenticated()){

                            // Create the Organization
                            if($organizationId = $this->Model->Organizations->create($parameters)){

                                // Retrieve the Organization's vCard
                                $vCard = $this->Model->Vcards->get($this->Auth->organization()->organization()->vcard['id']);

                                // Write the email
                                $body = '';
                                $body .= '<p>Welcome to '.$vCard['name'].'!</p>';
                                $body .= '<p>Your account has been created and is ready to use.</p>';
                                $body .= '<p>Here is your account password:</p>';
                                $body .= '<pre style="background-color: #F5F5F5; font-weight: 700; font-size: 28px; text-align: center; letter-spacing: 16px; margin: 20px 20px; padding: 20px 0; font-family: Courier, monospace">'.$parameters['password'].'</pre>';
                                $body .= '<p>Please follow the link below to access %BRAND%.</p>';
                                $body .= '<p style="text-align:center;margin-top: 40px;margin-bottom:40px;">';
                                $body .= '<a href="'.$this->Request->getHostAddress().'" target="_blank" style="margin-left: 6px; margin-right: 6px; text-decoration:none; background-color: #528fb3;color: #fff;font-size: 24px;padding: 20px 40px;text-align: center;margin: 20px 20px;border-radius: 8px;">%BRAND%</a>';
                                $body .= '</p>';
                                $body .= '<p>Thank you for choosing '.$vCard['name'].'!</p>';

                                // Create a new message
                                $eml = $SMTP->message()
                                    ->to($parameters['email'])
                                    ->from($vCard['email'] ?? $this->Config->get('smtp','organizationname'))
                                    ->subject('Welcome to '.$vCard['name'])
                                    ->body($body)
                                    ->var('logo', 'data:'.mime_content_type($this->Config->root() . '/dist/img/logo.png').';base64,' . base64_encode(file_get_contents($this->Config->root() . '/dist/img/logo.png')))
                                    ->var('brand', $CONFIG->get('application','name'))
                                    ->var('greetings', "Sincerely,<br>".$vCard['name']."'s Team");

                                // Send the message
                                $eml->send();

                                // Check if the message was sent
                                if($eml->status()){

                                    // Save the message
                                    $eml->save();

                                    // Retrieve the final organization
                                    $message['data']['record'] = $this->Model->Organizations->get($organizationId);
                                } else {
                                    $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while sending the email."];
                                }
                            } else {
                                $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while creating the organization."];
                            }
                        } else {
                            $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while authenticating to the SMTP Server."];
                        }
                    } else {
                        $message = ["status" => 500, "message" => "Internal Server Error", "data" => "An error occurred while connecting to the SMTP Server."];
                    }
                } else {
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Some required fields are missing."];
                }
            } else {
                $message = ["status" => 405, "message" => "Method Not Allowed", "data" => "The method is not allowed for the requested URL."];
            }
        }

        return $message;
    }

    /**
     * Fetch all users
     */
    public function usersAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => $this->Model->Organizations->users()];

        // Return the message
        return $message;
    }
}
