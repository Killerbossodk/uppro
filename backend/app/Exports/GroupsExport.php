<?php

namespace App\Exports;

use App\Models\Group;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GroupsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $role;
    protected $userId;
    protected $specialiteId;

    public function __construct($role = null, $userId = null, $specialiteId = null)
    {
        $this->role = $role;
        $this->userId = $userId;
        $this->specialiteId = $specialiteId;
    }

    public function collection()
    {
        $query = Group::with('project.superviseur', 'membres');

        if ($this->role === 'professeur') {
            $query->whereHas('project', fn($q) => $q->where('superviseur_id', $this->userId));
        } elseif ($this->role === 'rup_specialite') {
            $query->whereHas('project', function ($q) {
                $q->where('specialite_id', $this->specialiteId)
                  ->orWhereNull('specialite_id');
            });
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Groupe',
            'Projet',
            'Capacité max',
            'Nombre de membres',
            'Inscriptions',
            'Professeur',
            'Spécialité',
            'Créé le'
        ];
    }

    public function map($group): array
    {
        return [
            $group->nom,
            $group->project->titre ?? 'N/A',
            $group->capacite_max,
            $group->membres->count(),
            $group->inscription_ouverte ? 'Ouvertes' : 'Fermées',
            $group->project->superviseur->name . ' ' . $group->project->superviseur->prenom ?? 'N/A',
            $group->project->specialite->nom ?? 'Mélangé',
            $group->created_at->format('d/m/Y')
        ];
    }
}