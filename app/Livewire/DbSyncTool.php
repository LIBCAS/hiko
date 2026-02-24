<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use App\Models\AppSyncHistory;
use Illuminate\Support\Facades\Auth;

class DbSyncTool extends Component
{
    public $isSyncing = false;

    public function render()
    {
        return view('livewire.db-sync-tool', [
            'history' => AppSyncHistory::latest()->take(5)->get()
        ]);
    }

    public function sync()
    {
        // Manual Lock using DB History
        // Check if there is a 'running' job created in the last 10 minutes
        // This prevents multiple users from triggering it simultaneously
        $alreadyRunning = AppSyncHistory::where('status', 'running')
            ->where('created_at', '>', now()->subMinutes(10))
            ->exists();

        if ($alreadyRunning) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => __('hiko.sync_already_running')
            ]);
            return;
        }

        $this->isSyncing = true;
        $startTime = microtime(true);

        // Create History Record (This acts as our "lock")
        $log = AppSyncHistory::create([
            'user_name' => Auth::user()->name,
            'status' => 'running',
            'message' => 'Started...',
        ]);

        try {
            // Extend Timeout
            // Allow this script to run for up to 10 minutes
            set_time_limit(600);

            // Run Command
            // Use --yes to skip confirmation prompt
            $exitCode = Artisan::call('db:sync-prod', ['--yes' => true]);

            // Optional: Capture command output if needed for debugging
            // $output = Artisan::output();

            $duration = round(microtime(true) - $startTime);

            if ($exitCode === 0) {
                $log->update([
                    'status' => 'completed',
                    'message' => 'Completed successfully.',
                    'duration_seconds' => $duration
                ]);

                $this->dispatch('alert', ['type' => 'success', 'message' => __('hiko.sync_success')]);
            } else {
                $log->update([
                    'status' => 'failed',
                    'message' => 'Command returned exit code: ' . $exitCode,
                    'duration_seconds' => $duration
                ]);

                $this->dispatch('alert', ['type' => 'error', 'message' => __('hiko.sync_failed')]);
            }

        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime);

            $log->update([
                'status' => 'failed',
                'message' => 'Exception: ' . $e->getMessage(),
                'duration_seconds' => $duration
            ]);

            $this->dispatch('alert', ['type' => 'error', 'message' => $e->getMessage()]);
        } finally {
            $this->isSyncing = false;
        }
    }
}
