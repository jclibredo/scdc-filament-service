<?php

namespace App\Filament\Resources\InactiveDataResource\Pages;

use App\Filament\Resources\InactiveData\InactiveDataResource as InactiveDataInactiveDataResource;
use App\Filament\Resources\InactiveDataResource;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageInactiveData extends ManageRecords
{
    protected static string $resource = InactiveDataInactiveDataResource::class;

    protected function getTableQuery(): Builder
    {
        // 📥 Extract the selected module directly from the Livewire filter state
        $filterModule = $this->tableFilters['module']['value'] ?? null;

        // If no filter selected, return a blank, queryable Eloquent builder
        if (! $filterModule) {
            return parent::getTableQuery()->whereRaw('1 = 0');
        }

        $modelClass = "App\\Models\\" . $filterModule;

        if (class_exists($modelClass)) {
            // Return the query of the dynamic target model looking for inactive status
            return $modelClass::query()->where('status', false);
        }

        return parent::getTableQuery()->whereRaw('1 = 0');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
