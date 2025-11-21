<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\DummyDataSeeder;

class GenerateDummyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dummy:generate 
                            {--users=1000 : Number of users to create}
                            {--subscriptions=2000 : Number of subscriptions to create}
                            {--payments=5000 : Number of payments to create}
                            {--incomes=500 : Number of income records to create}
                            {--expenses=500 : Number of expense records to create}
                            {--plans=5 : Number of subscription plans to create if none exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate dummy data for load testing using Eloquent models';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating dummy data for load testing...');
        $this->newLine();

        // Override seeder config with command options
        $config = [
            'users' => (int) $this->option('users'),
            'subscriptions' => (int) $this->option('subscriptions'),
            'payments' => (int) $this->option('payments'),
            'incomes' => (int) $this->option('incomes'),
            'expenses' => (int) $this->option('expenses'),
            'subscription_plans' => (int) $this->option('plans'),
        ];

        // Display configuration
        $this->table(
            ['Type', 'Count'],
            [
                ['Users', $config['users']],
                ['Subscription Plans', $config['subscription_plans']],
                ['Subscriptions', $config['subscriptions']],
                ['Payments', $config['payments']],
                ['Incomes', $config['incomes']],
                ['Expenses', $config['expenses']],
            ]
        );

        if (!$this->confirm('Do you want to proceed with generating this data?', true)) {
            $this->info('Operation cancelled.');
            return Command::FAILURE;
        }

        $startTime = microtime(true);

        try {
            // Create seeder instance and set config
            $seeder = new DummyDataSeeder();
            $seeder->setConfig($config);
            $seeder->setCommand($this);
            $seeder->run();
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $this->newLine();
            $this->info("✓ Dummy data generation completed in {$duration} seconds!");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error generating dummy data: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

