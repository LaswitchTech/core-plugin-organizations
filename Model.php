<?php

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Base\BaseModel;

class OrganizationsModel extends BaseModel {

    /**
     * Constructor
     */
    public function __construct()
    {
        // Call the parent constructor
        parent::__construct();

        // Initialize the Model
        $this->init('organizations');
    }

    /**
     * Process a record
     *
     * @param array $record
     * @return array
     */
    protected function process(array $record): array
    {
        // Call the parent constructor
        $record = parent::process($record);

        // Process the JSON fields
        if(!is_array($record['users'])){
            $record['users'] = json_decode($record['users'] ?? "[]", true);
        }

        // Return the processed record
        return $record;
    }

    /**
     * Apply Joins to the Query
     *
     * @param Query $Query
     * @return Query
     */
    protected function joins(object $Query): object
    {
        // Apply Joins
        $Query->join('vcard', 'vcards', 'id');

        return $Query;
    }

    /**
     * Update a record
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int
    {
        // Sanitize the Data
        foreach($data as $key => $value){

            // Add exceptions for specific fields
            if($key === 'users' && is_array($value)){

                // Loop through each userId in the array
                foreach($value as $userKey => $userId){

                    // Convert the userId to an integer
                    $value[$userKey] = (int)$userId;
                }

                // Filter unique user IDs
                $value = array_unique($value);
            }

            // Set the value back to the data array
            $data[$key] = $value;
        }

        // Call the parent update method
        return parent::update($id, $data);
    }
}
