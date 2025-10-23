Changes made for Pricing implementation:

- Migration: app/database/migrations/2025_10_25_000002_add_cost_to_agent_tasks_table.php (unsignedBigInteger cost nullable)
- Model: app/app/Models/AgentTask.php (added cost to $fillable and cast to integer)
- Service: app/app/Services/Pricing/PricingService.php (calculates costRaw and returns int in RUB*1000)
- Controller: app/app/Http/Controllers/Api/TaskController.php (after token updates, calculate cost and save)
- Resource: app/app/Http/Resources/AgentTaskResource.php (does not expose cost)

Note: cost stored as integer RUB*1000. Normalize on display by dividing by 1000 where needed.
