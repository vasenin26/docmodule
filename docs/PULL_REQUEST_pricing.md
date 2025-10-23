# PR: Implement agent task pricing via GenerationModel

## Title
feat(pricing): calculate and store agent task cost from GenerationModel

## Summary
This PR introduces a pricing mechanism for agent tasks based on GenerationModel pricing.
The cost is calculated from prompt and completion token counts and stored in the agent_tasks table as an integer (RUB * 1000) for improved precision. The cost value is not exposed via API responses.

## Changes
- Migration: add `cost` column to `agent_tasks` (unsignedBigInteger, nullable) — app/database/migrations/2025_10_25_000002_add_cost_to_agent_tasks_table.php
- Model: include `cost` in AgentTask::$fillable and cast as integer — app/app/Models/AgentTask.php
- Service: `PricingService` that computes cost from GenerationModel prices — app/app/Services/Pricing/PricingService.php
- Integration:
  - Agent task lifecycle updated to calculate and save cost after token updates — app/app/Http/Controllers/Api/TaskController.php
  - AgentTaskManagerService prepared to accept PricingService via DI for future use — app/app/Services/AgentTaskManager/AgentTaskManagerService.php
- API: AgentTaskResource does NOT expose cost — app/app/Http/Resources/AgentTaskResource.php
- Tests: PricingService unit test using Factory — tests/Unit/PricingServiceTest.php
- Factory: GenerationModelFactory for tests — app/database/factories/GenerationModelFactory.php
- Docs:
  - docs/pages/AgentTask.md — description of pricing, storage and behavior
  - docs/Planning/Migration/pricing.md — migration plan and risks
  - docs/PULL_REQUEST_pricing.md (this file)

## Files changed
Please review listed files for detailed changes.

## Testing
- Unit tests: tests/Unit/PricingServiceTest.php covers PricingService basic calculation.
- Manual: create a GenerationModel with price_in/price_out, create an AgentTask, update prompt_tokens and completion_tokens via API endpoint; confirm DB stores cost as integer (RUB*1000) and API responses do not include `cost`.

## Branch suggestion
branch: feature/pricing-agent-tasks

## Notes
- PricingService returns a numeric value which is saved into the integer `cost` column; normalization (divide by 1000) is required when displaying the value to users.
