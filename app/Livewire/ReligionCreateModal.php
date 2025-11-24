<?php

namespace App\Livewire;

use App\Models\Religion;
use App\Services\ReligionTreeService;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class ReligionCreateModal extends Component
{
    public $showModal = false;
    public $parentId = null;
    public $parentLabel = '';
    public $nameCzech = '';
    public $nameEnglish = '';

    protected $listeners = ['openModal'];

    protected $rules = [
        'nameCzech' => 'required|string|max:255',
        'nameEnglish' => 'required|string|max:255',
        'parentId' => 'nullable|exists:religions,id',
    ];

    protected $messages = [
        'nameCzech.required' => 'Czech name is required.',
        'nameEnglish.required' => 'English name is required.',
    ];

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->parentId = null;
        $this->parentLabel = '';
        $this->nameCzech = '';
        $this->nameEnglish = '';
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function create()
    {
        // Validate input
        $this->validate();

        try {
            Log::info('Creating religion', [
                'nameCzech' => $this->nameCzech,
                'nameEnglish' => $this->nameEnglish,
                'parentId' => $this->parentId,
                'parentLabel' => $this->parentLabel
            ]);

            $religionTreeService = app(ReligionTreeService::class);

            // Create the religion node with the Czech name as base
            $religion = $religionTreeService->create($this->nameCzech, $this->parentId);

            Log::info('Religion created', ['id' => $religion->id, 'name' => $religion->name]);

            // Save translations directly to religion_translations table
            foreach (['cs' => $this->nameCzech, 'en' => $this->nameEnglish] as $locale => $name) {
                DB::table('religion_translations')->upsert([
                    'religion_id' => $religion->id,
                    'locale'      => $locale,
                    'name'        => $name,
                    'slug'        => null,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ], ['religion_id', 'locale'], ['name', 'slug', 'updated_at']);
            }

            // Recompute path for both locales
            $religionTreeService->recomputeLocalePaths([$religion->id], 'cs', 'cs');
            $religionTreeService->recomputeLocalePaths([$religion->id], 'en', 'en');

            Log::info('Dispatching religionCreated event', ['religionId' => $religion->id]);

            // Dispatch browser event to refresh the tree (Alpine.js will listen)
            // Use js() to ensure it's a browser event, not Livewire component event
            $this->js("
                console.log('Livewire js() executing - dispatching religionCreated');
                window.dispatchEvent(new CustomEvent('religionCreated', { detail: { religionId: {$religion->id} } }));
            ");

            // Reset form and close modal
            $this->resetForm();
            $this->dispatch('close-modal');

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create religion: ' . $e->getMessage(), ['exception' => $e]);
            $this->addError('general', __('hiko.cannot_create_religion'));
            return false;
        }
    }

    public function render()
    {
        return view('livewire.religion-create-modal');
    }
}
