<?php

namespace App\Jobs;

use App\Events\votingPowersImportedEvent;
use App\Models\Snapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

class SyncVotingPowersFIleJob implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int|float $timeout = 900;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Snapshot $snapshot, protected $storagePath) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void {
        error_log("Handling an uploaded CSV file... {$this->snapshot} {$this->storagePath}");
        LazyCollection::make(function () {
            $handle = fopen($this->storagePath, 'r');

            while (($line = fgetcsv($handle, null)) !== false) {
                yield $line;
            }

            fclose($handle);
        })
                      ->skip(1)
                      ->chunk(1000)
                      ->each(function (LazyCollection $chunk) {
                          $chunk->each(function ($row) {
                              error_log("Dispatching a job for: {$this->snapshot->hash} {$row[0]} {$row[1]}");
                              CreateVotingPowerSnapshotJob::dispatch($this->snapshot->hash, $row[0], $row[1]);
                          });
                      });

        event(new votingPowersImportedEvent($this->snapshot));
    }
}
