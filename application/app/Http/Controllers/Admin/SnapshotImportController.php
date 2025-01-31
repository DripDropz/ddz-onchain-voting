<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModelStatusEnum;
use App\Http\Controllers\Controller;
use App\Jobs\SyncVotingPowersFileJob;
use App\Models\Snapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Fluent;
use Illuminate\Support\LazyCollection;

class SnapshotImportController extends Controller
{
    /**
     * Display the new snapshots's form.
     */
    public function parseCSV(Request $request)
    {
        // Init
        $directory = 'voting_powers';
        $filePath = "{$directory}/{$request->filename}";

        //$pathName already exist then delete already existing record
        if ($request->input('count') == '0' && Storage::exists($filePath)) {
            File::delete(Storage::path($filePath));
        };

        //create directory if it doesn't exist
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Persist the temp file into permanent location
        Storage::disk('s3')->copy(
            $request->key,
            $filePath
        );

        // $path = Storage::path($pathName);
        return $this->getParsedCSV(10, $filePath);
    }

    public function getParsedCSV($sampleCount, $filePath)
    {
        // Download the file
        $tempLocation = $this->downloadSnapshotCSVToTempLocation($filePath);

        // Read sample rows
        $parsedSample = LazyCollection::make(static function () use ($tempLocation, $sampleCount) {
            $handle = fopen($tempLocation, 'r');

            $count = 0;
            while ((($line = fgetcsv($handle, null)) !== false) && $count <= $sampleCount) {
                yield $line;
                $count++;
            }

            fclose($handle);
        })
            ->skip(1)
            ->map(static function ($row) {
                return [
                    'voter_id' => $row[0],
                    'voting_power' => $row[1],
                ];
            });

        // Return results
        return response()->json([
            'total_uploaded' => count(file($tempLocation)) - 1,
            'sample_data' => new Fluent($parsedSample)
        ]);
    }

    public function cancelParsedCSV(Request $request)
    {
        $filePath = Storage::path('voting_powers/' . $request->input('filename'));
        Storage::disk('s3')->delete($request->input('filename'));
        File::delete($filePath);
    }

    public function uploadCSV(Request $request, Snapshot $snapshot)
    {
        $response = Gate::inspect('update', Snapshot::class);

        $fileName = $request->input('filename');
        $filePath = "voting_powers/{$fileName}";

        // save snapshot's metadata about file
        $this->updateSnapshotModel($snapshot, $filePath, $fileName);

        // Dispatch job to process the snapshot csv file
        SyncVotingPowersFileJob::dispatch(
            $snapshot,
            $filePath
        );

        return response()->json([
            'true' => false,
        ]);
    }

    protected function updateSnapshotModel(Snapshot $snapshot, $filePath, $fileName)
    {
        $tempLocation = $this->downloadSnapshotCSVToTempLocation($filePath);
        $snap = Snapshot::byHash($snapshot->hash);
        $snap->status = ModelStatusEnum::PENDING->value;
        $snap->metadata =  [
            'snapshot_file' => $fileName,
            'row_count' => count(file($tempLocation)) - 1,
        ];
        $snap->save();
    }

    private function downloadSnapshotCSVToTempLocation(string $filePath): string
    {
        $tempLocation = '/tmp/' . basename($filePath);
        file_put_contents($tempLocation, Storage::get($filePath));
        return $tempLocation;
    }
}
