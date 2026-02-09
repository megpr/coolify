<?php

namespace App\Livewire\Project;

use App\Models\PrivateKey;
use App\Models\Project;
use App\Models\Server;
use Livewire\Component;

class Index extends Component
{
    public $projects;

    public $servers;

    public $private_keys;

    // User preferences passed to Alpine.js
    public string $initialSort = 'name_asc';

    public string $initialView = 'grid';

    public array $initialCustomOrder = [];

    // Permission flags for the Blade view
    public bool $canCreateResource = false;

    public bool $canUpdateProjects = false;

    public function mount()
    {
        $this->private_keys = PrivateKey::ownedByCurrentTeamCached();
        $this->projects = Project::ownedByCurrentTeamCached();
        $this->servers = Server::ownedByCurrentTeamCached();

        $user = auth()->user();
        $this->canCreateResource = $user->can('createAnyResource');
        // ProjectPolicy::update() always returns true, so all authenticated users can see Settings
        $this->canUpdateProjects = true;

        $this->loadUserPreferences();
    }

    /**
     * Load the user's project sort/view preferences for the current team.
     */
    private function loadUserPreferences(): void
    {
        $user = auth()->user();
        $teamId = currentTeam()->id;
        $prefs = $user->project_sort_preference ?? [];

        if (is_string($prefs)) {
            $prefs = json_decode($prefs, true) ?? [];
        }

        $teamPrefs = $prefs["team_{$teamId}"] ?? [];

        $this->initialSort = $teamPrefs['sort'] ?? 'name_asc';
        $this->initialView = $teamPrefs['view'] ?? 'grid';
        $this->initialCustomOrder = $teamPrefs['custom_order'] ?? [];
    }

    /**
     * Save the user's project sort/view preferences for the current team.
     * Called from Alpine.js via $wire.saveProjectPreference().
     */
    public function saveProjectPreference(string $sort, string $view, array $customOrder = []): void
    {
        $allowedSorts = ['name_asc', 'name_desc', 'custom'];
        $allowedViews = ['grid', 'list'];

        if (! in_array($sort, $allowedSorts)) {
            $sort = 'name_asc';
        }
        if (! in_array($view, $allowedViews)) {
            $view = 'grid';
        }

        $user = auth()->user();
        $teamId = currentTeam()->id;

        $prefs = $user->project_sort_preference ?? [];
        if (is_string($prefs)) {
            $prefs = json_decode($prefs, true) ?? [];
        }

        $prefs["team_{$teamId}"] = [
            'sort' => $sort,
            'view' => $view,
            'custom_order' => array_map('intval', $customOrder),
        ];

        $user->update(['project_sort_preference' => $prefs]);
    }

    public function render()
    {
        return view('livewire.project.index');
    }
}
