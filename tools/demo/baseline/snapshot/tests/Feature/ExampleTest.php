<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_structure_exists(): void
    {
        // Verify key directories exist
        $this->assertDirectoryExists(base_path('app/Models'));
        $this->assertDirectoryExists(base_path('app/Services'));
        $this->assertDirectoryExists(base_path('database/migrations'));
        $this->assertDirectoryExists(base_path('config'));
    }
}
