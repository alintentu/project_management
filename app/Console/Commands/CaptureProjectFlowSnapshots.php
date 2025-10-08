<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\ProjectFlowSnapshotService;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Console\Command;

final class CaptureProjectFlowSnapshots extends Command
{
    protected $signature = 'flow:snapshots:capture 
        {--project=* : Limit capture to the given project IDs.}
        {--backfill-hours=0 : Number of prior hours (per project) to backfill snapshots for.}';

    protected $description = 'Capture flow dashboard snapshots for projects to power historical trend analytics.';

    public function __construct(
        private readonly ProjectFlowSnapshotService $snapshotService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $projectIds = collect((array) $this->option('project'))
            ->filter(static fn ($value) => $value !== null && $value !== '')
            ->map(static fn ($value) => (int) $value)
            ->all();

        $projects = Project::query()
            ->when($projectIds !== [], static fn ($query) => $query->whereIn('id', $projectIds))
            ->orderBy('name')
            ->get();

        if ($projects->isEmpty()) {
            $this->warn('No projects found to snapshot.');

            return self::SUCCESS;
        }

        $backfill = max((int) $this->option('backfill-hours'), 0);
        $now = new DateTimeImmutable('now');

        $this->info(sprintf(
            'Capturing flow snapshots for %d projects (backfill %d hour%s).',
            $projects->count(),
            $backfill,
            $backfill === 1 ? '' : 's'
        ));

        foreach ($projects as $project) {
            $this->line(sprintf('→ %s (ID %d)', $project->name ?? 'Unnamed project', $project->id));

            for ($offset = $backfill; $offset >= 0; $offset--) {
                $timestamp = $offset === 0
                    ? $now
                    : $now->sub(new DateInterval(sprintf('PT%dH', $offset)));

                $snapshot = $this->snapshotService->capture($project, $timestamp);

                $this->line(sprintf(
                    '   captured at %s',
                    $snapshot->captured_at->format(DateTimeImmutable::ATOM)
                ), 'v');
            }
        }

        $this->info('Flow snapshots captured successfully.');

        return self::SUCCESS;
    }
}
