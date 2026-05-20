<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'full_label' => $this->full_label,

            // Jumlah karyawan — hanya muncul jika withCount('employees') dipanggil
            'total_employees' => $this->when(
                isset($this->employees_count),
                fn () => $this->employees_count
            ),

            // Relasi company
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', fn () => [
                            'id' => $this->company->id,
                            'name' => $this->company->name,
                        ]),

            // Relasi department (nullable)
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => $this->department
                            ? ['id' => $this->department->id, 'name' => $this->department->name]
                            : null
            ),

            // Relasi job_level (nullable)
            'job_level_id' => $this->job_level_id,
            'job_level' => $this->whenLoaded('jobLevel', fn () => $this->jobLevel
                ? ['id' => $this->jobLevel->id, 'name' => $this->jobLevel->name]
                : null
            ),

            // Relasi job_grade (nullable)
            'job_grade_id' => $this->job_grade_id,
            'job_grade' => $this->whenLoaded('jobGrade', fn () => $this->jobGrade
                ? ['id' => $this->jobGrade->id, 'name' => $this->jobGrade->name, 'code' => $this->jobGrade->code]
                : null
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
