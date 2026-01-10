<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Models\ExamPaper;
use App\Models\ExamTerm;
use App\Models\ExamMarksheetConfig;
use App\Models\ExamQuestion;
use App\Models\Event;
use App\Models\MentionNotification;
use App\Models\SubjectMarkSheet;
use App\Models\School;
use App\Models\Exam;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $merchantA;
    protected $merchantB;
    protected $userA;
    protected $userB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two merchants and their users
        $this->merchantA = 'MERCHANT_A';
        $this->merchantB = 'MERCHANT_B';

        $this->userA = User::factory()->create([
            'merchant_id' => $this->merchantA,
            'email' => 'user_a@test.com',
        ]);

        $this->userB = User::factory()->create([
            'merchant_id' => $this->merchantB,
            'email' => 'user_b@test.com',
        ]);
    }

    /** @test */
    public function exam_paper_is_scoped_to_merchant()
    {
        // Create exam papers for both merchants
        $paperA = ExamPaper::create([
            'merchant_id' => $this->merchantA,
            'title' => 'Paper A',
            'school_id' => 1,
            'exam_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'total_marks' => 100,
        ]);

        $paperB = ExamPaper::create([
            'merchant_id' => $this->merchantB,
            'title' => 'Paper B',
            'school_id' => 1,
            'exam_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'total_marks' => 100,
        ]);

        // User A should only see Paper A
        $this->actingAs($this->userA);
        $papers = ExamPaper::all();
        $this->assertCount(1, $papers);
        $this->assertEquals('Paper A', $papers->first()->title);

        // User B should only see Paper B
        $this->actingAs($this->userB);
        $papers = ExamPaper::all();
        $this->assertCount(1, $papers);
        $this->assertEquals('Paper B', $papers->first()->title);
    }

    /** @test */
    public function exam_term_is_scoped_to_merchant()
    {
        $termA = ExamTerm::create([
            'merchant_id' => $this->merchantA,
            'school_id' => 1,
            'session_id' => 1,
            'name' => 'Term A',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
        ]);

        $termB = ExamTerm::create([
            'merchant_id' => $this->merchantB,
            'school_id' => 1,
            'session_id' => 1,
            'name' => 'Term B',
            'start_date' => now(),
            'end_date' => now()->addMonths(3),
        ]);

        $this->actingAs($this->userA);
        $terms = ExamTerm::all();
        $this->assertCount(1, $terms);
        $this->assertEquals('Term A', $terms->first()->name);

        $this->actingAs($this->userB);
        $terms = ExamTerm::all();
        $this->assertCount(1, $terms);
        $this->assertEquals('Term B', $terms->first()->name);
    }

    /** @test */
    public function event_is_scoped_to_merchant()
    {
        $eventA = Event::create([
            'merchant_id' => $this->merchantA,
            'user_id' => $this->userA->id,
            'title' => 'Event A',
            'start_date' => now(),
            'end_date' => now()->addHours(2),
        ]);

        $eventB = Event::create([
            'merchant_id' => $this->merchantB,
            'user_id' => $this->userB->id,
            'title' => 'Event B',
            'start_date' => now(),
            'end_date' => now()->addHours(2),
        ]);

        $this->actingAs($this->userA);
        $events = Event::all();
        $this->assertCount(1, $events);
        $this->assertEquals('Event A', $events->first()->title);

        $this->actingAs($this->userB);
        $events = Event::all();
        $this->assertCount(1, $events);
        $this->assertEquals('Event B', $events->first()->title);
    }

    /** @test */
    public function auto_sets_merchant_id_on_create()
    {
        $this->actingAs($this->userA);

        $paper = ExamPaper::create([
            'title' => 'Auto Merchant Paper',
            'school_id' => 1,
            'exam_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'total_marks' => 100,
        ]);

        $this->assertEquals($this->merchantA, $paper->merchant_id);
    }

    /** @test */
    public function cannot_access_other_merchant_records()
    {
        $paperA = ExamPaper::create([
            'merchant_id' => $this->merchantA,
            'title' => 'Paper A',
            'school_id' => 1,
            'exam_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'total_marks' => 100,
        ]);

        // User B tries to access Paper A
        $this->actingAs($this->userB);
        
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        ExamPaper::findOrFail($paperA->id);
    }

    /** @test */
    public function exam_question_inherits_merchant_id_from_paper()
    {
        $this->actingAs($this->userA);

        $paper = ExamPaper::create([
            'merchant_id' => $this->merchantA,
            'title' => 'Paper with Questions',
            'school_id' => 1,
            'exam_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'total_marks' => 100,
        ]);

        $question = ExamQuestion::create([
            'exam_paper_id' => $paper->id,
            'section_name' => 'Section A',
            'question_text' => 'What is 2+2?',
            'question_type' => 'short',
            'marks' => 5,
        ]);

        // Question should inherit merchant_id
        $this->assertEquals($this->merchantA, $question->merchant_id);

        // User B cannot see this question
        $this->actingAs($this->userB);
        $questions = ExamQuestion::all();
        $this->assertCount(0, $questions);
    }

    /** @test */
    public function mention_notification_is_scoped_to_merchant()
    {
        $notificationA = MentionNotification::create([
            'merchant_id' => $this->merchantA,
            'user_id' => $this->userA->id,
            'message_id' => 1,
            'message_type' => 'channel_message',
            'conversation_id' => 1,
            'conversation_type' => 'channel',
            'mentioner_id' => 1,
            'is_read' => false,
        ]);

        $notificationB = MentionNotification::create([
            'merchant_id' => $this->merchantB,
            'user_id' => $this->userB->id,
            'message_id' => 2,
            'message_type' => 'channel_message',
            'conversation_id' => 2,
            'conversation_type' => 'channel',
            'mentioner_id' => 2,
            'is_read' => false,
        ]);

        $this->actingAs($this->userA);
        $notifications = MentionNotification::all();
        $this->assertCount(1, $notifications);

        $this->actingAs($this->userB);
        $notifications = MentionNotification::all();
        $this->assertCount(1, $notifications);
    }

    /** @test */
    public function can_query_without_tenant_scope_when_needed()
    {
        ExamPaper::create([
            'merchant_id' => $this->merchantA,
            'title' => 'Paper A',
            'school_id' => 1,
            'exam_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'total_marks' => 100,
        ]);

        ExamPaper::create([
            'merchant_id' => $this->merchantB,
            'title' => 'Paper B',
            'school_id' => 1,
            'exam_id' => 1,
            'class_id' => 1,
            'subject_id' => 1,
            'total_marks' => 100,
        ]);

        $this->actingAs($this->userA);

        // With tenant scope (default)
        $papers = ExamPaper::all();
        $this->assertCount(1, $papers);

        // Without tenant scope (admin/super-admin use case)
        $allPapers = ExamPaper::withoutTenantScope()->get();
        $this->assertCount(2, $allPapers);
    }
}
