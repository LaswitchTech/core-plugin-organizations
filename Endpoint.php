<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Base\BaseEndpoint;

class OrganizationsEndpoint extends BaseEndpoint {

    /**
     * Constructor
     */
    public function __construct()
    {
        // Call the parent constructor
        parent::__construct();

        // Initialize the Endpoint
        $this->init('organizations');

        // Set Properties
        $this->required = ['name','locale'];
        $this->optional = ['email','phone','tollfree','mobile','fax','tags','dba','industries','businessNumber','taxExtension','importerExtension','website','address','city','country','state','zipcode'];
    }

    /**
     * Retrieve a record
     */
    public function fetchAction(): array
    {
        // Call the parent constructor
        $message = parent::fetchAction();

        // Check if the records is accessible
        if($message['status'] == 200){

            // Check if the Users is accessible
            if($this->Helper->Core->isInstalled('users')){

                // Initialize the dependencies
                $message['data']['dependencies']['users'] = [];

                // Loop through the users to fetch them.
                foreach($message['data']['record']['users'] ?? [] as $id){
                    $message['data']['dependencies']['users'][$id] = $this->Model->Users->fetch($id);
                }

                // Set the users in the record
                $message['data']['record']['users'] = $message['data']['dependencies']['users'];
            }

            // Check if the vCards Plugin is accessible
            if($this->Helper->Core->isInstalled('vcards')){
                $message['data']['record']['vcard'] = $this->Model->Vcards->fetch(intval($message['data']['record']['vcard']['id']));
            }

            // Check if the Relationship Plugin is accessible
            if($this->Helper->Core->isInstalled('relationship')){
                $message['data']['dependencies']['relationship'] = $this->Model->Relationship->get($this->basename, $message['data']['record']['id']);
                if($this->Helper->Core->isInstalled('vcards') && array_key_exists('vcard', $message['data']['record'])){
                    $message['data']['dependencies']['relationship'] = array_merge(
                        $message['data']['dependencies']['relationship'],
                        $this->Model->Relationship->get('vcards', $message['data']['record']['vcard']['id'])
                    );
                }
            }

            // Check if the Contacts is accessible
            if($this->Helper->Core->isInstalled('contacts')){
                $message['data']['dependencies']['contacts'] = $this->Model->Contacts->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }

            // Check if the Events is accessible
            if($this->Helper->Core->isInstalled('event')){
                $message['data']['dependencies']['event'] = $this->Model->Event->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }

            // Check if the Files is accessible
            if($this->Helper->Core->isInstalled('files')){
                $message['data']['dependencies']['files'] = $this->Model->Files->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }

            // Check if the Notes is accessible
            if($this->Helper->Core->isInstalled('notes')){
                $message['data']['dependencies']['notes'] = $this->Model->Notes->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }

            // Check if the Services is accessible
            if($this->Helper->Core->isInstalled('inventory')){
                $message['data']['dependencies']['inventory'] = $this->Model->Inventory->fetchAll([
                    ["key" => "targetTable", "operator" => "=", "value" => $this->basename],
                    ["key" => "targetId", "operator" => "=", "value" => $message['data']['record']['id']],
                    ["key" => "isArchived", "operator" => "<>", "value" => 1],
                ]);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Create a record
     */
    public function createAction(): array
    {
        // Call the parent constructor
        $message = parent::createAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Retrieve the parameters
            $parameters = $message['data']['parameters'];

            // Initialize the fields array
            $fields = [];

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'Organization',
                    'message' => 'New Organization Created for <vcard>'.$parameters['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/organizations/details?id='.$message['data']['record']['id'].'&name='.urlencode($parameters['name']),
                    'targetTable' => 'organizations',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }

            // Check if the vCards Plugin is accessible
            if($this->Helper->Core->isInstalled('vcards')){

                // Initialize the record
                $record = $parameters;

                // Set the vCard category
                $record['category'] = 'Organization';

                // Create the vCard
                $fields['vcard'] = $this->Model->Vcards->create($record);

                // Check if the Event Plugin is accessible
                if($this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'vCard',
                        'message' => 'New vCard Created for <vcard>'.$fields['vcard'].':'.$parameters['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/organizations/details?id='.$message['data']['record']['id'].'&name='.urlencode($parameters['name']),
                        'targetTable' => 'organizations',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }

            // Check if tags is set
            if($this->Helper->Core->isInstalled('tags') && array_key_exists('tags', $parameters) && !empty($parameters['tags'])){

                // Loop through the tags
                foreach($parameters['tags'] ?? [] as $key => $tag){

                    // Check if the tag is not empty
                    if(!empty($tag)){

                        // Create the tag
                        $this->Model->Tags->create(['name' => $tag]);
                    }
                }
            }

            // Check if industries is set
            if($this->Helper->Core->isInstalled('industries') && array_key_exists('industries', $parameters) && !empty($parameters['industries'])){

                // Loop through the industries
                foreach($parameters['industries'] ?? [] as $key => $industry){

                    // Check if the industry is not empty
                    if(!empty($industry)){

                        // Create the industry
                        $this->Model->Industries->create(['name' => $industry]);
                    }
                }
            }

            // Check if $fields is empty
            if(!empty($fields)){
                $affectedRows = $this->Model->Organizations->update($message['data']['record']['id'], $fields);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Update a record
     */
    public function updateAction(): array
    {
        // Call the parent constructor
        $message = parent::updateAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'Organization',
                    'message' => 'Organization Updated for <vcard>'.$message['data']['record']['vcard']['id'].':'.$message['data']['record']['vcard']['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/organizations/details?id='.$message['data']['record']['id'].'&name='.urlencode($message['data']['record']['vcard']['name']),
                    'targetTable' => 'organizations',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Delete a record
     */
    public function deleteAction(): array
    {
        // Call the parent constructor
        $message = parent::deleteAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'Organization',
                    'message' => 'Organization Deleted for <vcard>'.$message['data']['record']['vcard']['id'].':'.$message['data']['record']['vcard']['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/organizations/details?id='.$message['data']['record']['id'].'&name='.urlencode($message['data']['record']['vcard']['name']),
                    'targetTable' => 'organizations',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }

            // Check if the vCards Plugin is accessible
            if($this->Helper->Core->isInstalled('vcards')){

                // Delete the vCard
                $affectedRows = $this->Model->Vcards->delete($message['data']['record']['vcard']['id']);

                // Check if the Event Plugin is accessible
                if($affectedRows && $this->Helper->Core->isInstalled('event')){

                    // Setup a new event
                    $event = [
                        'category' => 'vCard',
                        'message' => 'vCard Deleted for <vcard>'.$fields['vcard'].':'.$parameters['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                        'icon' => 'circle',
                        'color' => 'secondary',
                        'link' => '/plugin/organizations/details?id='.$message['data']['record']['id'].'&name='.urlencode($parameters['name']),
                        'targetTable' => 'organizations',
                        'targetId' => $message['data']['record']['id'],
                    ];

                    // Create the event
                    $message['data']['event'][] = $this->Model->Event->create($event);
                }
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Archive a record
     */
    public function archiveAction(): array
    {
        // Call the parent constructor
        $message = parent::archiveAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'Organization',
                    'message' => 'Organization Archived for <vcard>'.$message['data']['record']['vcard']['id'].':'.$message['data']['record']['vcard']['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/organizations/details?id='.$message['data']['record']['id'].'&name='.urlencode($parameters['name']),
                    'targetTable' => 'organizations',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Recover a record
     */
    public function recoverAction(): array
    {
        // Call the parent constructor
        $message = parent::recoverAction();

        // Check if the record is accessible
        if($message['status'] == 200){

            // Check if the Event Plugin is accessible
            if($this->Helper->Core->isInstalled('event')){

                // Initialize the Events
                $message['data']['event'] = [];

                // Setup a new event
                $event = [
                    'category' => 'Organization',
                    'message' => 'Organization Recovered for <vcard>'.$message['data']['record']['vcard']['id'].':'.$message['data']['record']['vcard']['name'].'</vcard> by <vcard>'.$this->Auth->user()->vcard['id'].':'.$this->Auth->user()->username.'</vcard>',
                    'icon' => 'circle',
                    'color' => 'secondary',
                    'link' => '/plugin/organizations/details?id='.$message['data']['record']['id'].'&name='.urlencode($parameters['name']),
                    'targetTable' => 'organizations',
                    'targetId' => $message['data']['record']['id'],
                ];

                // Create the event
                $message['data']['event'][] = $this->Model->Event->create($event);
            }
        }

        // Return the message
        return $message;
    }
}
