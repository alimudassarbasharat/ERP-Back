<?php

namespace App\Services;

use App\Models\ExamDatesheetEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DatesheetConflictService
{
    /**
     * Detect all conflicts for a datesheet
     */
    public function detectConflicts(int $datesheetId): array
    {
        $entries = ExamDatesheetEntry::where('datesheet_id', $datesheetId)
            ->with(['class', 'section', 'subject', 'supervisor', 'invigilator'])
            ->get();

        $conflicts = [];

        foreach ($entries as $entryA) {
            foreach ($entries as $entryB) {
                if ($entryA->id >= $entryB->id) {
                    continue; // Avoid duplicate checks
                }

                // Check if time ranges overlap
                if (!$this->timeRangesOverlap($entryA, $entryB)) {
                    continue;
                }

                // Check class/section conflict
                if ($this->hasClassConflict($entryA, $entryB)) {
                    $conflicts[] = $this->buildConflict('class', $entryA, $entryB, 
                        "Same class/section has overlapping exam times");
                }

                // Check room conflict
                if ($this->hasRoomConflict($entryA, $entryB)) {
                    $conflicts[] = $this->buildConflict('room', $entryA, $entryB,
                        "Same room is used for overlapping exams");
                }

                // Check supervisor conflict
                if ($this->hasSupervisorConflict($entryA, $entryB)) {
                    $conflicts[] = $this->buildConflict('supervisor', $entryA, $entryB,
                        "Same supervisor is assigned to overlapping exams");
                }

                // Check invigilator conflict
                if ($this->hasInvigilatorConflict($entryA, $entryB)) {
                    $conflicts[] = $this->buildConflict('invigilator', $entryA, $entryB,
                        "Same invigilator is assigned to overlapping exams");
                }
            }
        }

        return $conflicts;
    }

    /**
     * Check if two entries have overlapping time ranges
     */
    protected function timeRangesOverlap(ExamDatesheetEntry $entryA, ExamDatesheetEntry $entryB): bool
    {
        if ($entryA->exam_date->format('Y-m-d') !== $entryB->exam_date->format('Y-m-d')) {
            return false;
        }

        $startA = Carbon::parse($entryA->exam_date->format('Y-m-d') . ' ' . $entryA->start_time);
        $endA = Carbon::parse($entryA->exam_date->format('Y-m-d') . ' ' . $entryA->end_time);
        $startB = Carbon::parse($entryB->exam_date->format('Y-m-d') . ' ' . $entryB->start_time);
        $endB = Carbon::parse($entryB->exam_date->format('Y-m-d') . ' ' . $entryB->end_time);

        return $startA->lt($endB) && $startB->lt($endA);
    }

    /**
     * Check class/section conflict
     */
    protected function hasClassConflict(ExamDatesheetEntry $entryA, ExamDatesheetEntry $entryB): bool
    {
        if ($entryA->class_id !== $entryB->class_id) {
            return false;
        }

        // If both have sections, they must be the same
        if ($entryA->section_id && $entryB->section_id) {
            return $entryA->section_id === $entryB->section_id;
        }

        // If one has section and other doesn't, it's a conflict (section-specific vs class-wide)
        if ($entryA->section_id || $entryB->section_id) {
            return false; // Different scope, not a conflict
        }

        // Both are class-wide, conflict
        return true;
    }

    /**
     * Check room conflict
     */
    protected function hasRoomConflict(ExamDatesheetEntry $entryA, ExamDatesheetEntry $entryB): bool
    {
        if (!$entryA->room_id || !$entryB->room_id) {
            return false;
        }

        return $entryA->room_id === $entryB->room_id;
    }

    /**
     * Check supervisor conflict
     */
    protected function hasSupervisorConflict(ExamDatesheetEntry $entryA, ExamDatesheetEntry $entryB): bool
    {
        if (!$entryA->supervisor_id || !$entryB->supervisor_id) {
            return false;
        }

        return $entryA->supervisor_id === $entryB->supervisor_id;
    }

    /**
     * Check invigilator conflict
     */
    protected function hasInvigilatorConflict(ExamDatesheetEntry $entryA, ExamDatesheetEntry $entryB): bool
    {
        if (!$entryA->invigilator_id || !$entryB->invigilator_id) {
            return false;
        }

        return $entryA->invigilator_id === $entryB->invigilator_id;
    }

    /**
     * Build conflict object
     */
    protected function buildConflict(string $type, ExamDatesheetEntry $entryA, ExamDatesheetEntry $entryB, string $reason): array
    {
        return [
            'type' => $type,
            'entry_a' => [
                'id' => $entryA->id,
                'class' => $entryA->class->name ?? 'N/A',
                'section' => $entryA->section->name ?? 'N/A',
                'subject' => $entryA->subject->name ?? 'N/A',
                'date' => $entryA->exam_date->format('Y-m-d'),
                'time' => $entryA->start_time . ' - ' . $entryA->end_time,
                'room' => $entryA->room_name ?? $entryA->room_id ?? 'N/A',
            ],
            'entry_b' => [
                'id' => $entryB->id,
                'class' => $entryB->class->name ?? 'N/A',
                'section' => $entryB->section->name ?? 'N/A',
                'subject' => $entryB->subject->name ?? 'N/A',
                'date' => $entryB->exam_date->format('Y-m-d'),
                'time' => $entryB->start_time . ' - ' . $entryB->end_time,
                'room' => $entryB->room_name ?? $entryB->room_id ?? 'N/A',
            ],
            'reason' => $reason,
            'suggested_resolution' => $this->suggestResolution($type, $entryA, $entryB),
        ];
    }

    /**
     * Suggest resolution for conflict
     */
    protected function suggestResolution(string $type, ExamDatesheetEntry $entryA, ExamDatesheetEntry $entryB): string
    {
        switch ($type) {
            case 'class':
                return "Change exam time for one of the entries or split into different sections";
            case 'room':
                return "Assign different room or change exam time";
            case 'supervisor':
                return "Assign different supervisor or change exam time";
            case 'invigilator':
                return "Assign different invigilator or change exam time";
            default:
                return "Review and adjust exam schedule";
        }
    }

    /**
     * Update conflict flags on entries
     */
    public function updateConflictFlags(int $datesheetId): void
    {
        $conflicts = $this->detectConflicts($datesheetId);
        $conflictEntryIds = collect($conflicts)
            ->pluck('entry_a.id')
            ->merge(collect($conflicts)->pluck('entry_b.id'))
            ->unique()
            ->toArray();

        // Reset all flags
        ExamDatesheetEntry::where('datesheet_id', $datesheetId)
            ->update(['has_conflict' => false, 'conflict_details' => null]);

        // Set flags for conflicted entries
        if (!empty($conflictEntryIds)) {
            foreach ($conflictEntryIds as $entryId) {
                $entryConflicts = collect($conflicts)->filter(function ($conflict) use ($entryId) {
                    return $conflict['entry_a']['id'] === $entryId || $conflict['entry_b']['id'] === $entryId;
                });

                ExamDatesheetEntry::where('id', $entryId)->update([
                    'has_conflict' => true,
                    'conflict_details' => json_encode($entryConflicts->values()->toArray())
                ]);
            }
        }

        // Update datesheet conflict count
        \App\Models\ExamDatesheet::where('id', $datesheetId)
            ->update(['conflict_count' => count($conflicts)]);
    }
}
