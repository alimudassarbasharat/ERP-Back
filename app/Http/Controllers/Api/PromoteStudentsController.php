<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Session;
use App\Models\Classes;
use App\Models\StudentFeePlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PromoteStudentsController extends Controller
{
    /**
     * Promote students to next class
     */
    public function promote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'required|exists:schools,id',
            'from_session_id' => 'required|exists:sessions,id',
            'to_session_id' => 'required|exists:sessions,id',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:classes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schoolId = $request->school_id;
        $fromSessionId = $request->from_session_id;
        $toSessionId = $request->to_session_id;
        $classIds = $request->class_ids;

        DB::beginTransaction();
        try {
            // Get students to promote
            $studentsQuery = Student::where('school_id', $schoolId)
                ->where('current_session_id', $fromSessionId)
                ->where('status', 'active')
                ->whereNotNull('current_class_id');

            if ($classIds) {
                $studentsQuery->whereIn('current_class_id', $classIds);
            }

            $students = $studentsQuery->get();
            $promoted = 0;
            $passedOut = 0;

            foreach ($students as $student) {
                $currentClass = Classes::find($student->current_class_id);
                
                if (!$currentClass) {
                    continue;
                }

                // Find next class by sequence
                $nextClass = Classes::where('school_id', $schoolId)
                    ->where('sequence', '>', $currentClass->sequence ?? 0)
                    ->orderBy('sequence', 'asc')
                    ->first();

                if ($nextClass) {
                    // Promote to next class
                    $student->current_class_id = $nextClass->id;
                    $student->current_session_id = $toSessionId;
                    $student->save();

                    // Create fee plan for new session
                    StudentFeePlan::firstOrCreate(
                        [
                            'student_id' => $student->id,
                            'session_id' => $toSessionId,
                        ],
                        [
                            'school_id' => $schoolId,
                            'class_id' => $nextClass->id,
                        ]
                    );

                    $promoted++;
                } else {
                    // No next class - mark as passed out
                    $student->status = 'passed_out';
                    $student->save();
                    $passedOut++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Students promoted successfully',
                'data' => [
                    'promoted' => $promoted,
                    'passed_out' => $passedOut,
                    'total' => $students->count(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error promoting students: ' . $e->getMessage(),
            ], 500);
        }
    }
}
