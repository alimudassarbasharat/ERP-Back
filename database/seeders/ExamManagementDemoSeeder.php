<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\Session;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamDatesheet;
use App\Models\ExamDatesheetEntry;
use App\Models\ExamPaper;
use App\Models\ExamQuestion;
use App\Models\ExamMark;
use App\Models\ExamResult;
use App\Models\User;
use App\Enums\ExamPaperStatus;
use App\Enums\ExamMarkStatus;
use App\Enums\ExamStatus;
use Illuminate\Support\Facades\DB;

class ExamManagementDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Get or create school
            $school = School::first();
            if (!$school) {
                $this->command->warn('No school found. Please run SchoolSeeder first.');
                return;
            }

            // Get active session
            $session = Session::where('is_current', true)->first();
            if (!$session) {
                $session = Session::first();
            }
            if (!$session) {
                $this->command->warn('No session found. Please run SessionSeeder first.');
                return;
            }

            // Get classes
            $classes = ClassModel::where('school_id', $school->id)->limit(3)->get();
            if ($classes->isEmpty()) {
                $this->command->warn('No classes found. Please run ClassSeeder first.');
                return;
            }

            // Get subjects
            $subjects = Subject::where('school_id', $school->id)->limit(5)->get();
            if ($subjects->isEmpty()) {
                $this->command->warn('No subjects found. Please run SubjectSeeder first.');
                return;
            }

            // Get students
            $students = Student::where('school_id', $school->id)
                ->where('current_session_id', $session->id)
                ->where('status', 'active')
                ->limit(50)
                ->get();

            if ($students->isEmpty()) {
                $this->command->warn('No students found. Please run StudentSeeder first.');
                return;
            }

            // Get users (teachers, supervisors)
            $teacher = User::where('school_id', $school->id)
                ->where('user_type', 'teacher')
                ->first();
            
            $supervisor = User::where('school_id', $school->id)
                ->whereIn('user_type', ['supervisor', 'admin', 'super-admin'])
                ->first();

            // Create 2 exams
            $exam1 = Exam::create([
                'school_id' => $school->id,
                'session_id' => $session->id,
                'name' => 'Mid Term Exam 2024',
                'term' => 'Mid Term',
                'status' => ExamStatus::LOCKED,
                'start_date' => now()->subDays(30),
                'end_date' => now()->subDays(20),
            ]);

            $exam2 = Exam::create([
                'school_id' => $school->id,
                'session_id' => $session->id,
                'name' => 'Final Exam 2024',
                'term' => 'Final',
                'status' => ExamStatus::RUNNING,
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(10),
            ]);

            // Create datesheet for exam1 (published, clean)
            $datesheet1 = ExamDatesheet::create([
                'exam_id' => $exam1->id,
                'school_id' => $school->id,
                'session_id' => $session->id,
                'title' => 'Mid Term Datesheet 2024',
                'status' => 'published',
                'published_by' => $supervisor?->id,
                'published_at' => now()->subDays(25),
            ]);

            // Create datesheet for exam2 (draft, with conflict)
            $datesheet2 = ExamDatesheet::create([
                'exam_id' => $exam2->id,
                'school_id' => $school->id,
                'session_id' => $session->id,
                'title' => 'Final Exam Datesheet 2024',
                'status' => 'draft',
            ]);

            // Add entries to datesheet1 (clean, no conflicts)
            $class1 = $classes->first();
            $subject1 = $subjects->first();
            $subject2 = $subjects->skip(1)->first();

            ExamDatesheetEntry::create([
                'exam_datesheet_id' => $datesheet1->id,
                'exam_id' => $exam1->id,
                'class_id' => $class1->id,
                'subject_id' => $subject1->id,
                'exam_date' => now()->subDays(28)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'room_name' => 'Room 101',
                'school_id' => $school->id,
            ]);

            ExamDatesheetEntry::create([
                'exam_datesheet_id' => $datesheet1->id,
                'exam_id' => $exam1->id,
                'class_id' => $class1->id,
                'subject_id' => $subject2->id,
                'exam_date' => now()->subDays(27)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'room_name' => 'Room 102',
                'school_id' => $school->id,
            ]);

            // Add entries to datesheet2 (with conflict)
            $class2 = $classes->skip(1)->first();
            $section1 = Section::where('class_id', $class2->id)->first();

            ExamDatesheetEntry::create([
                'exam_datesheet_id' => $datesheet2->id,
                'exam_id' => $exam2->id,
                'class_id' => $class2->id,
                'section_id' => $section1?->id,
                'subject_id' => $subject1->id,
                'exam_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
                'room_name' => 'Room 101',
                'supervisor_id' => $supervisor?->id,
                'school_id' => $school->id,
            ]);

            // Create conflict: same room, overlapping time
            ExamDatesheetEntry::create([
                'exam_datesheet_id' => $datesheet2->id,
                'exam_id' => $exam2->id,
                'class_id' => $class2->id,
                'subject_id' => $subject2->id,
                'exam_date' => now()->addDays(5)->format('Y-m-d'),
                'start_time' => '10:00', // Overlaps with previous entry
                'end_time' => '12:00',
                'room_name' => 'Room 101', // Same room
                'supervisor_id' => $supervisor?->id,
                'school_id' => $school->id,
            ]);

            // Create papers for exam1 (mix of approved/pending)
            $paper1 = ExamPaper::create([
                'exam_id' => $exam1->id,
                'class_id' => $class1->id,
                'subject_id' => $subject1->id,
                'school_id' => $school->id,
                'title' => 'Mid Term - ' . $subject1->name,
                'status' => ExamPaperStatus::APPROVED,
                'paper_version' => 1,
                'total_marks' => 100,
                'duration_minutes' => 120,
                'created_by' => $teacher?->id,
                'reviewed_by' => $supervisor?->id,
                'reviewed_at' => now()->subDays(22),
            ]);

            $paper2 = ExamPaper::create([
                'exam_id' => $exam1->id,
                'class_id' => $class1->id,
                'subject_id' => $subject2->id,
                'school_id' => $school->id,
                'title' => 'Mid Term - ' . $subject2->name,
                'status' => ExamPaperStatus::SUBMITTED,
                'paper_version' => 1,
                'total_marks' => 100,
                'duration_minutes' => 120,
                'created_by' => $teacher?->id,
            ]);

            // Add questions to paper1
            ExamQuestion::create([
                'exam_paper_id' => $paper1->id,
                'section_name' => 'Section A',
                'question_text' => 'What is the capital of Pakistan?',
                'question_type' => 'mcq',
                'marks' => 5,
                'options_json' => json_encode(['Islamabad', 'Karachi', 'Lahore', 'Peshawar']),
                'answer_key' => 'Islamabad',
                'order_no' => 1,
            ]);

            ExamQuestion::create([
                'exam_paper_id' => $paper1->id,
                'section_name' => 'Section B',
                'question_text' => 'Explain the concept of gravity.',
                'question_type' => 'short',
                'marks' => 10,
                'order_no' => 2,
            ]);

            // Create marks for exam1 (mix of submitted/verified)
            $exam1Students = $students->take(30);
            foreach ($exam1Students as $student) {
                // Subject 1 marks (verified)
                ExamMark::create([
                    'exam_id' => $exam1->id,
                    'class_id' => $class1->id,
                    'subject_id' => $subject1->id,
                    'student_id' => $student->id,
                    'school_id' => $school->id,
                    'total_marks' => 100,
                    'obtained_marks' => rand(60, 95),
                    'status' => ExamMarkStatus::VERIFIED,
                    'submitted_by' => $teacher?->id,
                    'submitted_at' => now()->subDays(18),
                    'verified_by' => $supervisor?->id,
                    'verified_at' => now()->subDays(17),
                ]);

                // Subject 2 marks (submitted, pending verification)
                ExamMark::create([
                    'exam_id' => $exam1->id,
                    'class_id' => $class1->id,
                    'subject_id' => $subject2->id,
                    'student_id' => $student->id,
                    'school_id' => $school->id,
                    'total_marks' => 100,
                    'obtained_marks' => rand(55, 90),
                    'status' => ExamMarkStatus::SUBMITTED,
                    'submitted_by' => $teacher?->id,
                    'submitted_at' => now()->subDays(15),
                ]);
            }

            // Create marks for exam2 (draft)
            $exam2Students = $students->skip(30)->take(20);
            foreach ($exam2Students as $student) {
                ExamMark::create([
                    'exam_id' => $exam2->id,
                    'class_id' => $class2->id,
                    'subject_id' => $subject1->id,
                    'student_id' => $student->id,
                    'school_id' => $school->id,
                    'total_marks' => 100,
                    'obtained_marks' => null,
                    'status' => ExamMarkStatus::DRAFT,
                ]);
            }

            // Create results for exam1 (published)
            foreach ($exam1Students as $index => $student) {
                $totalObtained = rand(150, 190); // Combined from 2 subjects
                $totalMarks = 200;
                $percentage = ($totalObtained / $totalMarks) * 100;
                
                ExamResult::create([
                    'exam_id' => $exam1->id,
                    'student_id' => $student->id,
                    'class_id' => $class1->id,
                    'school_id' => $school->id,
                    'session_id' => $session->id,
                    'total_obtained' => $totalObtained,
                    'total_marks' => $totalMarks,
                    'percentage' => round($percentage, 2),
                    'grade' => $this->calculateGrade($percentage),
                    'rank_in_class' => $index + 1,
                    'status' => 'published',
                    'published_at' => now()->subDays(10),
                    'result_snapshot_json' => json_encode([
                        'subjects' => [
                            ['name' => $subject1->name, 'obtained' => rand(60, 95), 'total' => 100],
                            ['name' => $subject2->name, 'obtained' => rand(55, 90), 'total' => 100],
                        ]
                    ]),
                ]);
            }

            $this->command->info('Exam Management demo data seeded successfully!');
            $this->command->info("Created:");
            $this->command->info("- 2 Exams (1 published, 1 running)");
            $this->command->info("- 2 Datesheets (1 published, 1 draft with conflict)");
            $this->command->info("- 2 Papers (1 approved, 1 submitted)");
            $this->command->info("- 50 Marks entries (30 verified, 20 submitted, 20 draft)");
            $this->command->info("- 30 Results (published)");
        });
    }

    private function calculateGrade($percentage)
    {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 80) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 60) return 'C';
        if ($percentage >= 50) return 'D';
        return 'F';
    }
}
