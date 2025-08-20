<?php

namespace App\Console\Commands;

use App\Models\Actualization;
use App\Models\PageVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateActualizationData extends Command
{
    protected $signature = 'actualization:migrate-data';
    protected $description = 'Migrate existing actualization data to new structure';

    public function handle()
    {
        $this->info('Migrating actualization data...');

        $actualizations = Actualization::whereNull('page_version_id')->get();

        if ($actualizations->isEmpty()) {
            $this->info('No actualizations need migration.');
            return;
        }

        $this->info("Found {$actualizations->count()} actualizations to migrate.");

        foreach ($actualizations as $actualization) {
            // Находим черновик, который должен быть связан с этой актуализацией
            $draft = PageVersion::where('page_id', $actualization->page_id)
                ->where('is_draft', true)
                ->where('created_at', '>=', $actualization->created_at)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($draft) {
                $actualization->update(['page_version_id' => $draft->id]);
                $this->info("Updated actualization {$actualization->id} with draft {$draft->id}");
            } else {
                $this->warn("No draft found for actualization {$actualization->id}");
            }
        }

        $this->info('Migration completed!');
    }
}