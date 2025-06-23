# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-06-19

### Added
- Initial release of Laravel Workflow State Machine library
- Configurable status management through config files
- Polymorphic workflow assignment to any model via Traits
- Rule-based transition system with permission controls
- Comprehensive audit logging for all state changes
- Rollback functionality to previous states
- Event-driven auto-transition system
- Progress tracking with percentage and roadmap
- Complete test coverage using Pest framework
- Code quality assurance with Laravel Pint and Larastan
- Support for Laravel 11.x & 12.x and PHP 8.2+

### Features
- `HasWorkflowStates` trait for models
- `CanManageWorkflowStates` trait for user permission management
- Workflow, WorkflowProcess, WorkflowRule, and WorkflowAuditLog models
- Event system with StatusChanged, WorkflowCompleted, and WorkflowCreated events
- Artisan command for easy installation
- Configurable table names and morph relationships
- Built-in permission rules and extensible rule system

### Documentation
- Comprehensive README with usage examples
- DEMO file with practical implementation guide
- Full API documentation in code comments
