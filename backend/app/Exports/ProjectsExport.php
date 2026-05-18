<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProjectsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $role;
    protected $userId;
    protected $specialiteId;
    protected $filters;

    // ✅ CORRIGÉ : Ajouter le 4ème paramètre avec une valeur par défaut
    public function __construct($role = null, $userId = null, $specialiteId = null, $filters = [])
    {
        $this->role = $role;
        $this->userId = $userId;
        $this->specialiteId = $specialiteId;
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Project::with('superviseur', 'specialite');

        // Appliquer les filtres selon le rôle
        if ($this->role === 'professeur') {
            $query->where('superviseur_id', $this->userId);
        } elseif ($this->role === 'rup_specialite') {
            $query->where(function ($q) {
                $q->where('specialite_id', $this->specialiteId)
                  ->orWhereNull('specialite_id');
            });
        }
        // rup_projet voit tout

        // Appliquer les filtres additionnels (année, etc.)
        if (!empty($this->filters['annee']) && $this->filters['annee'] !== 'all') {
            $query->where('annee_universitaire', $this->filters['annee']);
        }
        
        if (!empty($this->filters['specialite_id']) && $this->filters['specialite_id'] !== 'all') {
            $query->where('specialite_id', $this->filters['specialite_id']);
        }

        // Exclure les projets archivés par défaut (sauf si demandé)
        if (empty($this->filters['include_archived'])) {
            $query->where('est_archive', false);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Titre',
            'Description',
            'Niveau',
            'Statut',
            'Superviseur',
            'Spécialité',
            'Année universitaire',
            'Groupes',
            'Créé le',
            'Archivé'
        ];
    }

    public function map($project): array
    {
        return [
            $project->id,
            $project->titre,
            $project->description,
            $project->niveau,
            $this->getStatutLabel($project->statut),
            $project->superviseur?->name . ' ' . $project->superviseur?->prenom,
            $project->specialite?->nom ?? 'Mélangé',
            $project->annee_universitaire,
            $project->groupes?->count() ?? 0,
            $project->created_at->format('d/m/Y H:i'),
            $project->est_archive ? 'Oui' : 'Non'
        ];
    }

    private function getStatutLabel($statut)
    {
        $labels = [
            'brouillon' => 'Brouillon',
            'soumis' => 'Soumis',
            'valide' => 'Validé',
            'refuse' => 'Refusé',
            'archive' => 'Archivé'
        ];
        return $labels[$statut] ?? $statut;
    }
}