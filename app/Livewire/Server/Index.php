<?php

namespace App\Livewire\Server;

use App\Models\Server;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class Index extends Component
{
    public ?Collection $servers = null;

    // User preferences passed to Alpine.js
    public string $initialSort = 'name_asc';

    public string $initialView = 'grid';

    public array $initialCustomOrder = [];

    public function mount()
    {
        $this->servers = Server::ownedByCurrentTeamCached();
        $this->loadUserPreferences();
    }

    /**
     * Load the user's server sort/view preferences for the current team.
     */
    private function loadUserPreferences(): void
    {
        $user = auth()->user();
        $teamId = currentTeam()->id;
        $prefs = $user->server_sort_preference ?? [];

        if (is_string($prefs)) {
            $prefs = json_decode($prefs, true) ?? [];
        }

        $teamPrefs = $prefs["team_{$teamId}"] ?? [];

        $this->initialSort = $teamPrefs['sort'] ?? 'name_asc';
        $this->initialView = $teamPrefs['view'] ?? 'grid';
        $this->initialCustomOrder = $teamPrefs['custom_order'] ?? [];
    }

    /**
     * Save the user's server sort/view preferences for the current team.
     * Called from Alpine.js via $wire.saveServerPreference().
     */
    public function saveServerPreference(string $sort, string $view, array $customOrder = []): void
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

        $prefs = $user->server_sort_preference ?? [];
        if (is_string($prefs)) {
            $prefs = json_decode($prefs, true) ?? [];
        }

        $prefs["team_{$teamId}"] = [
            'sort' => $sort,
            'view' => $view,
            'custom_order' => array_map('intval', $customOrder),
        ];

        $user->update(['server_sort_preference' => $prefs]);
    }

    public function render()
    {
        return view('livewire.server.index');
    }
}
