<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use App\Models\AppSyncHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
            'user_email' => Auth::user()?->email,
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

            $duration = round(microtime(true) - $startTime);

            if ($exitCode === 0) {
                $log->update([
                    'status' => 'completed',
                    'message' => 'Completed successfully.',
                    'duration_seconds' => $duration
                ]);

                $this->dispatch('alert', ['type' => 'success', 'message' => __('hiko.sync_success')]);
            } else {
                $message = $this->commandFailureMessage($exitCode);

                $log->update([
                    'status' => 'failed',
                    'message' => $message,
                    'duration_seconds' => $duration
                ]);

                $this->dispatch('alert', [
                    'type' => 'error',
                    'message' => __('hiko.sync_failed_with_message', ['message' => $message]),
                ]);
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

    private function commandFailureMessage(int $exitCode): string
    {
        $output = trim(Artisan::output());

        if ($output !== '') {
            return Str::limit($output, 1000);
        }

        return 'Command returned exit code: ' . $exitCode;
    }
}
