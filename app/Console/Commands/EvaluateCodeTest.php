<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class EvaluateCodeTest extends Command
{
    protected $signature = 'evaluate:code-test';
    protected $description = 'Evaluate project against DigitalTolk Laravel code test requirements.';

    public function handle()
    {
        $score = 0;
        $total = 10;

        $this->info("Evaluating Laravel project...");

        // 1. Check CRUD routes for translations
        $routes = shell_exec('php artisan route:list');
        if (str_contains($routes, 'translations')) {
            $this->info("✔ CRUD routes found");
            $score++;
        } else {
            $this->warn("✘ CRUD routes missing");
        }

        // 2. Check export endpoint
        if (str_contains($routes, 'export')) {
            $this->info("✔ Export endpoint found");
            $score++;
        } else {
            $this->warn("✘ Export endpoint missing");
        }

        // 3. Check controllers
        $controllerPath = app_path('Http/Controllers');
        if (File::glob($controllerPath . '/*Translation*Controller.php')) {
            $this->info("✔ Translation Controller found");
            $score++;
        } else {
            $this->warn("✘ Translation Controller missing");
        }

        // 4. Check database factories & seeders
        $factoryCheck = File::glob(database_path('factories') . '/*.php');
        $seederCheck = File::glob(database_path('seeders') . '/*.php');
        if (!empty($factoryCheck) || !empty($seederCheck)) {
            $this->info("✔ Factories/Seeders found");
            $score++;
        } else {
            $this->warn("✘ Factories/Seeders missing");
        }

        // 5. Check token-based authentication (Sanctum or Passport)
        if (File::exists(config_path('sanctum.php')) || File::exists(config_path('passport.php'))) {
            $this->info("✔ Token-based authentication detected");
            $score++;
        } else {
            $this->warn("✘ Token-based authentication missing");
        }

        // 6. Check Dockerfile
        if (File::exists(base_path('Dockerfile')) || File::exists(base_path('docker-compose.yml'))) {
            $this->info("✔ Docker setup found");
            $score++;
        } else {
            $this->warn("✘ Docker setup missing");
        }

        // 7. Check Swagger
        if (File::exists(config_path('l5-swagger.php'))) {
            $this->info("✔ Swagger configuration found");
            $score++;
        } else {
            $this->warn("✘ Swagger missing");
        }

        // 8. Check tests folder
        if (File::exists(base_path('tests'))) {
            $this->info("✔ Tests folder found");
            $score++;
        } else {
            $this->warn("✘ Tests missing");
        }

        // 9. Check PSR-12 compliance (basic check for php-cs-fixer or similar)
        if (File::exists(base_path('.php-cs-fixer.dist.php')) || File::exists(base_path('.phpcs.xml'))) {
            $this->info("✔ PSR-12 configuration found");
            $score++;
        } else {
            $this->warn("✘ PSR-12 config missing");
        }

        // 10. Check README
        if (File::exists(base_path('README.md'))) {
            $this->info("✔ README.md found");
            $score++;
        } else {
            $this->warn("✘ README.md missing");
        }

        // Final score
        $percentage = ($score / $total) * 100;
        $this->info("Completion Score: {$percentage}% ({$score}/{$total})");

        return Command::SUCCESS;
    }
}
