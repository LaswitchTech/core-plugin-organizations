<?php

/**
 * Core Framework - OrganizationsModel
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Model;

class OrganizationsModel extends Model {

    /**
     * Retrieve the list of Organizations
     *
     * @return array
     */
    public function list(): array
    {
        // Retrieve the Organizations
        $Query = $this->Database->query()
            ->table('organizations')
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('vcard', 'vcards', 'id')
            ->where('id', 9999, '<>')
            ->where('isActive', 1)
            ->where('isDeleted', 1, '<>');

        // Fetch the Organizations
        $organizations = $Query->fetch();

        // Return the Organizations
        return $organizations;
    }

    /**
     * Retrieve Organizations's Details
     *
     * @param int $id
     * @param bool $all
     * @return array
     */
    public function get(int $id, bool $all = true): array
    {
        // Retrieve the Organization
        $Query = $this->Database->query()
            ->table('organizations')
            ->select('*')
            ->join('owner', 'users', 'username')
            ->where('id', $id)
            ->where('id', 9999, '<>')
            ->limit(1);

        // Fetch the Organizations
        $organizations = $Query->fetch();

        // Loop through the Organizations
        foreach($organizations as $key => $organization){

            // Decode JSON Fields
            $organization['users'] = json_decode($organization['users'] ?? '[]', true);

            // Check if all the details should be retrieved
            if($all){

                // Retrieve the Users
                $users = [];
                foreach($organization['users'] as $key => $user){

                    // Retrieve the User
                    $Query = $this->Database->query()
                        ->table('users')
                        ->select('*')
                        ->join('owner', 'users', 'username')
                        ->join('vcard', 'vcards', 'id')
                        ->where('id', 9999, '<>')
                        ->where('id', $user)
                        ->limit(1);
                    $users[$user] = $Query->fetch()[0] ?? [];
                }
                $organization['users'] = $users;

                // Retrieve the vCard
                $Query = $this->Database->query()
                    ->table('vcards')
                    ->select('*')
                    ->join('state', 'states', 'code')
                    ->join('country', 'countries', 'code')
                    ->join('avatar', 'files', 'id')
                    ->where('id', $organization['vcard'])
                    ->limit(1);
                $organization['vcard'] = $Query->result()[0] ?? $organization['vcard'];

                // Retrieve the Events
                $Query = $this->Database->query()
                    ->table('events')
                    ->select('*')
                    ->filter()
                    ->where('targetTable', 'organizations')
                    ->where('targetId', $organization['id'])
                    ->index('id');
                $organization['events'] = $Query->result();

                // Retrieve the Files
                $Query = $this->Database->query()
                    ->table('files')
                    ->select('*')
                    ->join('owner', 'users', 'username')
                    ->filter()
                    ->where('id', 9999, '<>')
                    ->where('isArchived', 1, '<>')
                    ->where('targetTable', 'organizations')
                    ->where('targetId', $organization['id'])
                    ->index('id');
                $organization['files'] = $Query->result();

                // Retrieve the Notes
                $Query = $this->Database->query()
                    ->table('notes')
                    ->select('*')
                    ->join('owner', 'users', 'username')
                    ->filter()
                    ->where('id', 9999, '<>')
                    ->where('isArchived', 1, '<>')
                    ->where('targetTable', 'organizations')
                    ->where('targetId', $organization['id'])
                    ->index('id');
                foreach($Query->result() as $note){
                    $note['sharedWith'] = json_decode($note['sharedWith'] ?? '[]', true);
                    $organization['notes'][$note['id']] = $note;
                }

                // Retrieve the Contacts
                $Query = $this->Database->query()
                    ->table('contacts')
                    ->select('*')
                    ->join('owner', 'users', 'username')
                    ->join('vcard', 'vcards', 'id')
                    ->filter()
                    ->where('id', 9999, '<>')
                    ->where('isArchived', 1, '<>')
                    ->where('targetTable', 'organizations')
                    ->where('targetId', $organization['id'])
                    ->index('id');
                $organization['contacts'] = $Query->result();
            }

            // Save the Organization
            return $organization;
        }

        // Return the Organization
        return [];
    }

    /**
     * Update a organization
     *
     * @param int $id
     * @param array $data
     * @return int
     */
    public function update(int $id, array $data): int
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table('organizations')
            ->update($data)
            ->where('id', $id);

        // Execute the Query
        return $Query->execute();
    }

    /**
     * Create a new organization and return the id
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        // Create the Query
        $Query = $this->Database->query()
            ->table('organizations')
            ->insert($data);

        // Execute the Query
        $affectedRows = $Query->execute();

        // Execute the Query
        return $Query->lastId();
    }

    /**
     * Retrieve the Organization's Logo
     *
     * @param int $id
     * @return array
     */
    public function logo(int $id): array
    {
        // Create a Query
        $Query = $this->Database->query()
            ->table('organizations')
            ->select('*')
            ->filter()
            ->where('id', $id)
            ->limit(1);

        // Retrieve the User
        $organization = $Query->result();

        // Check if the organization exists
        if($organization){

            // Select the organization
            $organization = $organization[array_key_first($organization)];

            // Create the Query
            $Query = $this->Database->query()
                ->table('vcards')
                ->select('*')
                ->join('avatar', 'files', 'id')
                ->order('id', 'ASC')
                ->filter()
                ->where('id', 9999, '<>')
                ->filter()
                ->where('id', $organization['vcard'])
                ->limit(1);

            // Retrieve the vCard
            $vCard = $Query->result();

            // Return the vCard
            $organization['vcard'] = $vCard[array_key_first($vCard)] ?? [];
        }

        // Return the Organization
        return $organization;
    }

    /**
     * Retrieve the list of Users
     *
     * @return array
     */
    public function users(): array
    {
        // Retrieve the Groups
        $Query = $this->Database->query()
            ->table('users')
            ->select('*')
            ->join('owner', 'users', 'username')
            ->join('vcard', 'vcards', 'id')
            ->where('id', 9999, '<>')
            ->index('id');

        // Fetch the Groups
        $users = $Query->fetch();

        // Sanitize the Groups
        foreach($users as $key => $user){
            $users[$key]['vcard']['tags'] = json_decode($user['vcard']['tags'] ?? '[]', true);
            $users[$key]['vcard']['industries'] = json_decode($user['vcard']['industries'] ?? '[]', true);
        }

        // Return the Users
        return $users;
    }
}
