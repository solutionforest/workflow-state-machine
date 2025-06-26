<?php

namespace WorkflowStateMachine\Tests\Feature;

use Illuminate\Support\Facades\File;
use WorkflowStateMachine\Models\WorkflowRule;
use WorkflowStateMachine\Tests\TestCase;

class MakeWorkflowRuleCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up any test files
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        // Clean up test files after each test
        $this->cleanupTestFiles();

        parent::tearDown();
    }

    public function test_creates_rule_class_file()
    {
        $this->artisan('workflow:make-rule', ['name' => 'TestRule', '--no-database' => true])
            ->expectsOutput('Rule class TestRule created successfully!')
            ->assertExitCode(0);

        $expectedPath = app_path('Rules/TestRule.php');
        $this->assertTrue(File::exists($expectedPath));

        $content = File::get($expectedPath);
        $this->assertStringContainsString('class TestRule implements WorkflowRuleContract', $content);
        $this->assertStringContainsString('namespace App\\Rules;', $content);
    }

    public function test_adds_rule_suffix_if_missing()
    {
        $this->artisan('workflow:make-rule', ['name' => 'CustomValidation', '--no-database' => true])
            ->expectsOutput('Rule class CustomValidationRule created successfully!')
            ->assertExitCode(0);

        $expectedPath = app_path('Rules/CustomValidationRule.php');
        $this->assertTrue(File::exists($expectedPath));
    }

    public function test_creates_rule_with_database_entry()
    {
        $this->artisan('workflow:make-rule', [
            'name' => 'DatabaseRule',
            '--description' => 'Test rule with database entry',
        ])
            ->expectsOutput('Rule class DatabaseRule created successfully!')
            ->expectsOutput('Rule DatabaseRule inserted into database successfully!')
            ->assertExitCode(0);

        // Check file was created
        $expectedPath = app_path('Rules/DatabaseRule.php');
        $this->assertTrue(File::exists($expectedPath));

        // Check database entry was created
        $rule = WorkflowRule::where('rule_class', 'App\\Rules\\DatabaseRule')->first();
        $this->assertNotNull($rule);
        $this->assertEquals('DatabaseRule', $rule->name);
        $this->assertEquals('Test rule with database entry', $rule->description);
        $this->assertTrue($rule->is_active);
    }

    public function test_handles_existing_file_with_confirmation()
    {
        // Create the rule first
        $this->artisan('workflow:make-rule', ['name' => 'ExistingRule', '--no-database' => true])
            ->assertExitCode(0);

        // Try to create it again, expect confirmation prompt
        $this->artisan('workflow:make-rule', ['name' => 'ExistingRule', '--no-database' => true])
            ->expectsQuestion('Rule class ExistingRule already exists. Overwrite?', false)
            ->assertExitCode(0);
    }

    public function test_rule_class_implements_contract()
    {
        $this->artisan('workflow:make-rule', ['name' => 'ContractRule', '--no-database' => true])
            ->assertExitCode(0);

        $expectedPath = app_path('Rules/ContractRule.php');
        $content = File::get($expectedPath);

        // Check that it implements the contract
        $this->assertStringContainsString('implements WorkflowRuleContract', $content);
        $this->assertStringContainsString('public function handle($model, WorkflowProcess $process, $user): bool', $content);
        $this->assertStringContainsString('public function getMessage(): string', $content);
    }

    public function test_inactive_rule_creation()
    {
        $this->artisan('workflow:make-rule', [
            'name' => 'InactiveRule',
            '--active' => 'false',
            '--description' => 'This rule is inactive',
        ])
            ->assertExitCode(0);

        $rule = WorkflowRule::where('rule_class', 'App\\Rules\\InactiveRule')->first();
        $this->assertNotNull($rule);
        $this->assertFalse($rule->is_active);
    }

    protected function cleanupTestFiles(): void
    {
        $testFiles = [
            app_path('Rules/TestRule.php'),
            app_path('Rules/CustomValidationRule.php'),
            app_path('Rules/DatabaseRule.php'),
            app_path('Rules/ExistingRule.php'),
            app_path('Rules/ContractRule.php'),
            app_path('Rules/InactiveRule.php'),
        ];

        foreach ($testFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up database entries
        WorkflowRule::whereIn('rule_class', [
            'App\\Rules\\TestRule',
            'App\\Rules\\CustomValidationRule',
            'App\\Rules\\DatabaseRule',
            'App\\Rules\\ExistingRule',
            'App\\Rules\\ContractRule',
            'App\\Rules\\InactiveRule',
        ])->delete();
    }
}
