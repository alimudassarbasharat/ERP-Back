<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Str;

class CreatePassportClient extends Command
{
    protected $signature = 'passport:client-setup';
    protected $description = 'Create a personal access client for Passport';

    public function handle(ClientRepository $clients)
    {
        $this->info('Creating personal access client...');

        // Check if personal access client already exists
        $existingClient = Client::where('personal_access_client', true)->first();
        
        if ($existingClient) {
            $this->info('Personal access client already exists.');
            $this->info('Client ID: ' . $existingClient->id);
            return Command::SUCCESS;
        }

        // Create a new personal access client
        $client = $clients->createPersonalAccessClient(
            null,
            'Personal Access Client',
            'http://localhost'
        );

        $this->info('Personal access client created successfully.');
        $this->info('Client ID: ' . $client->id);
        $this->info('Client Secret: ' . $client->secret);

        return Command::SUCCESS;
    }
} 