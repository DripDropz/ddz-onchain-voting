<?php

namespace App\Jobs;

use App\Models\Snapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\votingPowersImportedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncVotingPowersFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int|float $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Snapshot $snapshot,
        protected $filePath
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tempLocation = '/tmp/' . basename($this->filePath);
        file_put_contents($tempLocation, Storage::get($this->filePath));

        LazyCollection::make(static function () use($tempLocation) {
            $handle = fopen($tempLocation, 'r');

            while (($line = fgetcsv($handle, null)) !== false) {
                yield $line;
            }

            fclose($handle);
        })
            ->skip(1)
            ->chunk(1000)
            ->each(function (LazyCollection $chunk) {
                $chunk->each(function ($row) {
                    CreateVotingPowerSnapshotJob::dispatch(
                        $this->snapshot->hash,
                        $row[0],
                        $row[1]
                    );
                });
            });

        Storage::delete($this->filePath);

        event(new votingPowersImportedEvent($this->snapshot));
    }
}
