<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Objects;
use \LaswitchTech\Core\Abstracts\Controller;

class OrganizationsController extends Controller {

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

        // Set Properties
        switch($namespace){
            case "/organizations/logo":
                $this->Public = true;
                $this->Level = 0;
                break;
        }
    }

    /**
     * Fetch an Organization's Logo
     *
     * @return mixed
     */
    public function logoAction(): array
    {
        // Retrieve the parameters
        $id = $this->Request->getParams('GET', 'id') ?? null;
        $size = $this->Request->getParams('GET', 'size') ?? 128;

        // Retrieve the organization
        $organization = $this->Model->Organizations->fetch($id);

        // Check if user was retrieved
        if(!empty($organization)){

            // Retrieve the vcard
            $organization['vcard'] = $this->Model->Vcards->fetch($organization['vcard']['id']);

            // Check if the organization has an avatar
            if($organization['vcard']['avatar']['uuid']){

                // Retrieve the file content
                $organization['vcard']['avatar']['content'] = $this->Helper->Files->get($organization['vcard']['avatar']['path'] . DIRECTORY_SEPARATOR . $organization['vcard']['avatar']['uuid']);

                // Return the file
                return $organization['vcard']['avatar'];
            }

            // Check if the organization has a website
            if($organization['vcard']['website']){
                $content = $this->Helper->Favicon->content($organization['vcard']['website']);
                $logo = [
                    'type' => $this->Helper->Favicon->mimeType($content),
                    'content' => $content
                ];
                // Convert the logo to png format
                $logo = $this->Helper->Favicon->convert($logo, 'png', $size, $size);
                return $logo;
            }
        }

        // Create the default logo from the img folder
        $logo = [
            'type' => mime_content_type($CONFIG->root() . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo.png'),
            'content' => file_get_contents($CONFIG->root() . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo.png')
        ];

        // Return the default logo
        return $logo;
    }
}
