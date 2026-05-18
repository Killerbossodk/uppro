<?php

namespace App\Exports;

use App\Models\Grade;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GradesExport implements FromCollection, WithHeadings, WithMapping
{
    protected $role;
    protected $specialiteId;

    public function __construct($role = null, $specialiteId = null)
    {
        $this->role = $role;
        $this->specialiteId = $specialiteId;
    }

    public function collection()
    {
        $query = Grade::with('meeting.project', 'evaluateur')
            ->where('publiee', true);

        if ($this->role === 'rup_specialite') {
            $query->whereHas('meeting.project', function ($q) {
                $q->where('specialite_id', $this->specialiteId)
                  ->orWhereNull('specialite_id');
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Projet',
            'Soutenance',
            'Note',
            'Évaluateur',
            'Commentaire',
            'Validée par RUP',
            'Publiée',
            'Date'
        ];
    }

    public function map($grade): array
    {
        return [
            $grade->meeting->project->titre ?? 'N/A',
            $grade->meeting->titre ?? 'N/A',
            $grade->note . '/20',
            $grade->evaluateur->name . ' ' . $grade->evaluateur->prenom ?? 'N/A',
            $grade->commentaire ?? '-',
            $grade->valide_par_rup ? 'Oui' : 'Non',
            $grade->publiee ? 'Oui' : 'Non',
            $grade->created_at->format('d/m/Y H:i')
        ];
    }
}