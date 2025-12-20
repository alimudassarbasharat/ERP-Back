<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\Student\StudentController;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Teacher\TeacherController;
use App\Http\Controllers\Student\FeeChallanController;
use App\Http\Controllers\Teacher\TeacherFormController;
use App\Http\Controllers\Student\StudentFormController;
use App\Http\Controllers\Classes\ClassController;
use App\Http\Controllers\Section\SectionController;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\Subject\SubjectController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Result\ResultSheetController;
use App\Http\Controllers\Subject\SubjectMarkSheetController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Preferences\UserPreferenceController;
use App\Http\Controllers\Student\CharacterCertificateController;
use App\Http\Controllers\Student\IDCardController;
use App\Http\Controllers\Student\FeeChallanTemplateController;
use App\Http\Controllers\Student\AttendanceReportController;
use App\Http\Controllers\Student\ProgressReportController;
use App\Http\Controllers\Student\LeavingCertificateController;
use App\Http\Controllers\FeeManagement\FeeManagementController;
use App\Http\Controllers\FeeManagement\FeePaymentController;
use App\Http\Controllers\Session\SessionController;
use App\Http\Controllers\Attendance\AttendanceReportsController;
use App\Http\Controllers\Country\CountryCodeController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Statistics\StatisticsController;
use App\Http\Controllers\Messaging\ChannelController;
use App\Http\Controllers\Messaging\MessageController;
use App\Http\Controllers\Messaging\MessagingUserController;
use App\Http\Controllers\Slack\SlackController;
use App\Http\Controllers\Department\DepartmentController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\TicketController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// Passport OAuth Routes
Route::post('/oauth/token', [AccessTokenController::class, 'issueToken'])->middleware('throttle');

// Auth Routes
Route::post('/sign-up', [AuthController::class, 'signUp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Password Reset Routes
Route::get('/password/reset/{token}', function ($token) {
    return response()->json(['token' => $token]);
})->name('password.reset');

// Protected Routes
Route::middleware(['auth:api', 'merchant_verification'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin Management
    Route::prefix('admins')->group(function () {
        Route::get('/', [AdminController::class, 'index']);                    // List all admins
        Route::get('/{id}', [AdminController::class, 'show']);                 // Show single admin
        Route::post('/store', [AdminController::class, 'store']);              // Create Admin
        Route::post('/update/{id}', [AdminController::class, 'update']);       // Update admin
        Route::get('/delete/{id}', [AdminController::class, 'destroy']);      // Delete admin
        Route::get('/{id}/roles', [AdminController::class, 'getAdminRoles']); // Get admin roles
    });

    // Role Management
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::get('/{id}', [RoleController::class, 'show']);
        Route::post('/store', [RoleController::class, 'store']);
        Route::post('/update/{id}', [RoleController::class, 'update']);
        Route::get('/delete/{id}', [RoleController::class, 'destroy']);
        Route::get('/permissions/all', [RoleController::class, 'allPermissions']);
        Route::get('/{id}/roles', [RoleController::class, 'getRolePermissions']);
        Route::post('/{id}/permissions', [RoleController::class, 'assignPermissions']);
        Route::post('/assign', [RoleController::class, 'assignRoleToAdmin']);
    });

    //Permission Management
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/store', [PermissionController::class, 'store']);
        Route::get('/{id}', [PermissionController::class, 'show']);
        Route::post('/update/{id}', [PermissionController::class, 'update']);
        Route::post('/delete/{id}', [PermissionController::class, 'delete']);
    });

    // Teacher Management
    Route::prefix('teachers')->group(function () {
        Route::get('/', [TeacherController::class, 'index']);
        Route::post('/store', [TeacherController::class, 'store']);
        Route::post('/store-multiple', [TeacherController::class, 'storeMultiple']);
        Route::get('/form-styles', [TeacherController::class, 'getFormStyles']);
        Route::get('/form-style-preview/{styleId}', [TeacherController::class, 'previewFormStyle']);
        Route::get('/{id}', [TeacherController::class, 'show']);
        Route::post('/update/{id}', [TeacherController::class, 'update']);
        Route::get('/delete/{id}', [TeacherController::class, 'destroy']);

        // Teacher Form Routes
        Route::post('/{teacher}/form-style', [TeacherFormController::class, 'selectStyle']);
        Route::get('/{teacher}/form/{styleId}', [TeacherFormController::class, 'generateForm']);
    });

    // Student Management
    Route::prefix('students')->group(function () {
        // Report card routes
        Route::get('/report-card-templates', [StudentFormController::class, 'templates']);
        Route::post('/report-card-template-preview', [StudentFormController::class, 'templatePreview']);
        Route::post('/generate-report-card', [StudentFormController::class, 'generate']);

        // Fee challan routes
        Route::get('/fee-challan-templates', [FeeChallanController::class, 'getTemplates']);
        Route::get('/fee-challan-template-preview', [FeeChallanController::class, 'previewTemplate']);
        Route::get('/{student}/generate-fee-challan', [FeeChallanController::class, 'generate']);
        Route::post('/classes/{class}/sections/{section}/generate-fee-challans', [FeeChallanController::class, 'generateForClassSection']);

        // Student CRUD routes
        Route::get('/{student}', [StudentController::class, 'show']);
        Route::post('/update/{student}', [StudentController::class, 'update']);
        Route::get('/delete/{student}', [StudentController::class, 'destroy']);
        Route::get('/', [StudentController::class, 'index']);
        Route::post('/store', [StudentController::class, 'store']);
    });

    // Class Management
    Route::prefix('classes')->group(function () {
        Route::get('/', [ClassController::class, 'index']);
        Route::post('/store', [ClassController::class, 'store']);
        Route::get('/{id}', [ClassController::class, 'show']);
        Route::post('/{id}/update', [ClassController::class, 'update']);
        Route::post('/{id}/delete', [ClassController::class, 'destroy']);
        Route::post('/{id}/assign-subjects', [ClassController::class, 'assignSubjects']);
        Route::get('/{id}/subjects', [ClassController::class, 'getSubjects']);
        Route::get('/with-students-and-section', [ClassController::class, 'withStudentsAndSection']);
    });

    // Section Management
    Route::prefix('sections')->group(function () {
        Route::get('/', [SectionController::class, 'index']);
        Route::post('/store', [SectionController::class, 'store']);
        Route::get('/{id}', [SectionController::class, 'show']);
        Route::post('/{id}/update', [SectionController::class, 'update']);
        Route::post('/{id}/delete', [SectionController::class, 'destroy']);
        Route::get('/select-options', [SectionController::class, 'getSectionsForSelect']);
    });

    // Session Management
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index']);
        Route::post('/store', [SessionController::class, 'store']);
        Route::get('/{id}', [SessionController::class, 'show']);
        Route::post('/{id}/update', [SessionController::class, 'update']);
        Route::post('/{id}/delete', [SessionController::class, 'destroy']);
        Route::get('/select-options', [SessionController::class, 'getSessionsForSelect']);
    });

    // Event Routes
    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']);
        Route::post('/add', [EventController::class, 'store']);
        Route::get('/{event}', [EventController::class, 'show']);
        Route::post('/update/{event}', [EventController::class, 'update']);
        Route::post('/delete/{event}', [EventController::class, 'destroy']);
        Route::post('/range', [EventController::class, 'getEventsByDateRange']);
    });

    // Subject Management
    Route::prefix('subjects')->group(function () {
        Route::get('/', [SubjectController::class, 'index']);
        Route::post('/store', [SubjectController::class, 'store']);
        Route::post('/bulk-store', [SubjectController::class, 'bulkStore']);
        Route::get('/{id}/classes', [SubjectController::class, 'classes']);
        Route::post('/{id}/delete', [SubjectController::class, 'delete']);
        Route::get('/{id}/update', [SubjectController::class, 'update']);
    });

    // Exam Management
    Route::prefix('exams')->group(function () {
        Route::get('/', [ExamController::class, 'index']);
        Route::post('/store', [ExamController::class, 'store']);
        Route::get('/{id}', [ExamController::class, 'show']);
        Route::post('/update/{id}', [ExamController::class, 'update']);
        Route::get('/delete/{id}', [ExamController::class, 'delete']);
        Route::get('/{id}/subjects', [ExamController::class, 'subjects']);
    });

    Route::post('/result-sheet/store', [ResultSheetController::class, 'store']);
    Route::get('/result-sheet', [ResultSheetController::class, 'index']);

    Route::post('/subject-mark-sheets/bulk-store', [SubjectMarkSheetController::class, 'bulkStore']);

    // Legacy attendance route
    Route::post('/attendances/store', [AttendanceController::class, 'store']);

    // Comprehensive Attendance System Routes
    Route::prefix('attendance')->group(function () {
        // Dashboard and stats
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('/stats', [AttendanceController::class, 'getAttendanceStats']);

        // Attendance marking
        Route::post('/students', [AttendanceController::class, 'getStudentsForAttendance']);
        Route::get('/students-paginated', [AttendanceController::class, 'getStudentsWithPagination']);
        Route::post('/mark-daily', [AttendanceController::class, 'markDailyAttendance']);
        Route::post('/mark-period', [AttendanceController::class, 'markPeriodAttendance']);

        // Attendance records
        Route::get('/records', [AttendanceController::class, 'getAttendanceRecords']);
        Route::put('/records/{id}', [AttendanceController::class, 'updateAttendance']);
        Route::delete('/records/{id}', [AttendanceController::class, 'deleteAttendance']);

        // Student summaries and reports
        Route::get('/student/{studentId}/summary', [AttendanceController::class, 'getStudentAttendanceSummary']);
        Route::get('/defaulters', [AttendanceController::class, 'getDefaultersList']);

        // Helper endpoints
        Route::get('/status-options', [AttendanceController::class, 'getStatusOptions']);
        Route::get('/attendance-modes', [AttendanceController::class, 'getAttendanceModes']);
        Route::get('/class/{classId}/subjects', [AttendanceController::class, 'getSubjectsForClass']);
        Route::get('/subject/{subjectId}/teachers', [AttendanceController::class, 'getTeachersForSubject']);
    });

    // Attendance Reports & Analytics Routes
    Route::prefix('attendance-reports')->group(function () {
        Route::post('/daily-sheet', [AttendanceReportsController::class, 'getDailyAttendanceSheet']);
        Route::post('/monthly-summary', [AttendanceReportsController::class, 'getMonthlyAttendanceSummary']);
        Route::post('/subject-wise', [AttendanceReportsController::class, 'getSubjectWiseReport']);
        Route::post('/trends', [AttendanceReportsController::class, 'getAttendanceTrends']);
        Route::post('/class-comparison', [AttendanceReportsController::class, 'getClassComparison']);
        Route::post('/export', [AttendanceReportsController::class, 'exportReport']);
    });

    // User Preferences
    Route::prefix('user/preferences')->group(function () {
        Route::get('/report-templates', [UserPreferenceController::class, 'getReportTemplatePreferences']);
        Route::post('/report-templates', [UserPreferenceController::class, 'saveReportTemplatePreference']);
        Route::get('/available-templates', [UserPreferenceController::class, 'getAvailableTemplates']);
    });

    // Character Certificate Routes
    Route::prefix('character-certificate')->name('api.character-certificate.')->group(function () {
        Route::get('/templates', [CharacterCertificateController::class, 'getTemplates'])->name('templates');
        Route::get('/preview', [CharacterCertificateController::class, 'previewTemplate'])->name('preview');
        Route::get('/{student}/generate', [CharacterCertificateController::class, 'generate'])->name('generate');
    });

    // ID Card Routes
    Route::prefix('id-card')->name('api.id-card.')->group(function () {
        Route::get('/templates', [IDCardController::class, 'getTemplates'])->name('templates');
        Route::get('/preview', [IDCardController::class, 'previewTemplate'])->name('preview');
        Route::get('/{student}/generate', [IDCardController::class, 'generate'])->name('generate');
    });

    // Fee Challan Templates Routes
    Route::prefix('students/fee-challan-templates')->name('api.fee-challan.')->group(function () {
        Route::get('/templates', [FeeChallanTemplateController::class, 'getTemplates'])->name('templates');
        Route::get('/preview', [FeeChallanTemplateController::class, 'previewTemplate'])->name('preview');
        Route::get('/{student}/generate', [FeeChallanTemplateController::class, 'generate'])->name('generate');
    });

    // Attendance Report Routes
    Route::prefix('attendance-reports')->name('api.attendance-report.')->group(function () {
        Route::get('/templates', [AttendanceReportController::class, 'getTemplates'])->name('templates');
        Route::get('/preview', [AttendanceReportController::class, 'previewTemplate'])->name('preview');
        Route::get('/{student}/generate', [AttendanceReportController::class, 'generate'])->name('generate');
    });

    // Progress Report Routes
    Route::prefix('students/report-card-templates')->name('api.progress-report.')->group(function () {
        Route::get('/templates', [ProgressReportController::class, 'getTemplates'])->name('templates');
        Route::get('/preview', [ProgressReportController::class, 'previewTemplate'])->name('preview');
        Route::get('/{student}/generate', [ProgressReportController::class, 'generate'])->name('generate');
    });

    // Leaving Certificate Routes
    Route::prefix('leaving-certificates')->name('api.leaving-certificate.')->group(function () {
        Route::get('/templates', [LeavingCertificateController::class, 'getTemplates'])->name('templates');
        Route::get('/preview', [LeavingCertificateController::class, 'previewTemplate'])->name('preview');
        Route::get('/{student}/generate', [LeavingCertificateController::class, 'generate'])->name('generate');
    });

    // Fee Default Management
    Route::prefix('fee-defaults')->group(function () {
        Route::get('/', [FeeManagementController::class, 'index']); // List all fee defaults
        Route::post('/store', [FeeManagementController::class, 'store']); // Create fee default
        Route::post('/update/{id}', [FeeManagementController::class, 'update']); // Update fee default
        Route::get('/delete/{id}', [FeeManagementController::class, 'destroy']); // Delete fee default
    });

    // Fee Payment Management
    Route::post('/fee-partial-payment', [FeePaymentController::class, 'store']);
    Route::post('/fee-full-payment', [FeePaymentController::class, 'fullPayment']);
    Route::get('/fee-payment-history/{studentId}', [FeePaymentController::class, 'paymentHistory']);

    // Late Fee Management
    Route::prefix('late-fees')->group(function () {
        Route::get('/', [FeeManagementController::class, 'lateFeeIndex']);
        Route::post('/store', [FeeManagementController::class, 'lateFeeStore']);
        Route::get('/{id}', [FeeManagementController::class, 'lateFeeShow']);
        Route::post('/update/{id}', [FeeManagementController::class, 'lateFeeUpdate']);
        Route::post('/delete/{id}', [FeeManagementController::class, 'lateFeeDestroy']);
    });

    Route::get('/class-fee-summaries', [FeeManagementController::class, 'classWiseFeeSummary']);
    Route::get('/family-fee-summaries', [FeeManagementController::class, 'familyWiseFeeSummary']);
    Route::post('/class/{classId}/pay-fees', [FeeManagementController::class, 'payClassFees']);
    Route::get('/family/{familyId}/students', [FeeManagementController::class, 'getFamilyStudents']);

    // Settings endpoints
    Route::prefix('settings')->group(function () {
        Route::get('/general', [SettingsController::class, 'getGeneralSettings']);
        Route::post('/general', [SettingsController::class, 'saveGeneralSettings']);
        Route::get('/check-school', [SettingsController::class, 'checkSchoolExists']);
    });

    // Statistics endpoint
    Route::get('/statistics/counts', [StatisticsController::class, 'getCounts']);
    // Departments
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::get('/department/{id}', [DepartmentController::class, 'edit']);

    // Role and Permission Management Routes (Protected)
    Route::prefix('admin/roles-permissions')->group(function () {
        Route::get('/roles', [App\Http\Controllers\Admin\RolePermissionController::class, 'getRoles']);
        Route::get('/roles/{roleId}', [App\Http\Controllers\Admin\RolePermissionController::class, 'getRole']);
        Route::get('/permissions', [App\Http\Controllers\Admin\RolePermissionController::class, 'getPermissions']);
        Route::post('/roles', [App\Http\Controllers\Admin\RolePermissionController::class, 'createRole'])
            ->middleware('role:super-admin');
        Route::put('/roles/{roleId}', [App\Http\Controllers\Admin\RolePermissionController::class, 'updateRole'])
            ->middleware('role:super-admin');
        Route::delete('/roles/{roleId}', [App\Http\Controllers\Admin\RolePermissionController::class, 'deleteRole'])
            ->middleware('role:super-admin');
        Route::post('/assign-role', [App\Http\Controllers\Admin\RolePermissionController::class, 'assignRoleToAdmin'])
            ->middleware('permission:assign-roles');
        Route::post('/assign-user-role', [App\Http\Controllers\Admin\RolePermissionController::class, 'assignRoleToUser'])
            ->middleware('role:super-admin|admin');
        Route::get('/admins-with-roles', [App\Http\Controllers\Admin\RolePermissionController::class, 'getAdminsWithRoles'])
            ->middleware('role:super-admin|admin');
        Route::get('/all-users-with-roles', [App\Http\Controllers\Admin\RolePermissionController::class, 'getAllUsersWithRoles']);
        Route::get('/summary', [App\Http\Controllers\Admin\RolePermissionController::class, 'getPermissionsSummary']);
    });

    // User permissions endpoint (accessible to all authenticated users)
    Route::get('/admin/user-permissions', [App\Http\Controllers\Admin\RolePermissionController::class, 'getUserPermissions']);

    // Messaging System Routes
    Route::prefix('messaging')->group(function () {
        // Channel routes
        Route::prefix('channels')->group(function () {
            Route::get('/', [ChannelController::class, 'index']);
            Route::post('/', [ChannelController::class, 'store']);
            Route::get('/{channelId}', [ChannelController::class, 'show']);
            Route::post('/{channelId}', [ChannelController::class, 'update']);
            Route::delete('/{channelId}', [ChannelController::class, 'destroy']);
            Route::post('/{channelId}/members', [ChannelController::class, 'addMember']);
            Route::delete('/{channelId}/members/{memberId}', [ChannelController::class, 'removeMember']);
            Route::post('/{channelId}/leave', [ChannelController::class, 'leave']);
            Route::get('/{channelId}/available-users', [ChannelController::class, 'getAvailableUsers']);
        });

        // Message routes
        Route::prefix('channels/{channelId}/messages')->group(function () {
            Route::get('/', [MessageController::class, 'index']);
            Route::post('/', [MessageController::class, 'store']);
            Route::post('/{messageId}', [MessageController::class, 'update']);
            Route::delete('/{messageId}', [MessageController::class, 'destroy']);
            Route::post('/{messageId}/reactions', [MessageController::class, 'addReaction']);
            Route::delete('/{messageId}/reactions', [MessageController::class, 'removeReaction']);
            Route::post('/{messageId}/pin', [MessageController::class, 'togglePin']);
            Route::get('/{messageId}/replies', [MessageController::class, 'getThreadReplies']);
        });
    });

    // Direct messaging routes (without messaging prefix)
    Route::prefix('channels')->group(function () {
        Route::get('/', [ChannelController::class, 'index']);
        Route::post('/', [ChannelController::class, 'store']);
        Route::get('/{channelId}', [ChannelController::class, 'show']);
        Route::post('/{channelId}', [ChannelController::class, 'update']);
        Route::delete('/{channelId}', [ChannelController::class, 'destroy']);
        Route::post('/{channelId}/members', [ChannelController::class, 'addMember']);
        Route::delete('/{channelId}/members/{memberId}', [ChannelController::class, 'removeMember']);
        Route::post('/{channelId}/leave', [ChannelController::class, 'leave']);
        Route::get('/{channelId}/available-users', [ChannelController::class, 'getAvailableUsers']);
    });

    Route::prefix('channels/{channelId}/messages')->group(function () {
        Route::get('/', [MessageController::class, 'index']);
        Route::post('/', [MessageController::class, 'store']);
        Route::post('/{messageId}', [MessageController::class, 'update']);

        Route::delete('/{messageId}', [MessageController::class, 'destroy']);
        Route::post('/{messageId}/reactions', [MessageController::class, 'addReaction']);
        Route::delete('/{messageId}/reactions', [MessageController::class, 'removeReaction']);
        Route::post('/{messageId}/pin', [MessageController::class, 'togglePin']);
        Route::get('/{messageId}/replies', [MessageController::class, 'getThreadReplies']);
    });

    // User Management Routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/store', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/update/{id}', [UserController::class, 'update']);
        Route::get('/delete/{id}', [UserController::class, 'destroy']);
        Route::get('/search', [UserController::class, 'search']);
    });

    // Messaging User Routes
    Route::prefix('messaging/users')->group(function () {
        Route::get('/', [MessagingUserController::class, 'index']);
        Route::get('/me', [MessagingUserController::class, 'me']);
        Route::get('/{id}', [MessagingUserController::class, 'show']);
        Route::post('/status', [MessagingUserController::class, 'updateStatus']);
    });

    // Internal Communication Routes
Route::prefix('slack')->group(function () {
Route::post('/send', [SlackController::class, 'sendToSlack']);
Route::get('/channels', [SlackController::class, 'getSlackChannels']);
Route::post('/send-channel', [SlackController::class, 'sendToSlackChannel']);
Route::get('/sync', [SlackController::class, 'syncWithSlack']);
Route::get('/status', [SlackController::class, 'getIntegrationStatus']);
});

});

// Country codes endpoint
Route::get('/country-codes', [CountryCodeController::class, 'index']);



// Add search endpoint for students and employees
Route::get('/search', [App\Http\Controllers\UserController::class, 'search']);

// Messaging System Routes
Route::middleware(['auth:api'])->group(function () {
    // Channels
    Route::prefix('channels')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\ChannelController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Api\ChannelController::class, 'store']);
        Route::get('/search', [App\Http\Controllers\Api\ChannelController::class, 'search']);
        Route::get('/{id}', [App\Http\Controllers\Api\ChannelController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Api\ChannelController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Api\ChannelController::class, 'destroy']);
        Route::post('/{id}/join', [App\Http\Controllers\Api\ChannelController::class, 'join']);
        Route::post('/{id}/leave', [App\Http\Controllers\Api\ChannelController::class, 'leave']);
        Route::post('/{id}/members', [App\Http\Controllers\Api\ChannelController::class, 'addMembers']);
    });

    // Messages
    Route::prefix('messages')->group(function () {
        Route::post('/channels/{channelId}', [App\Http\Controllers\Api\MessageController::class, 'sendToChannel'])->middleware('throttle:60,1');
        Route::get('/thread/{messageId}', [App\Http\Controllers\Api\MessageController::class, 'getThread']);
        Route::put('/{id}', [App\Http\Controllers\Api\MessageController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/{id}', [App\Http\Controllers\Api\MessageController::class, 'destroy']);
        Route::post('/{id}/reactions', [App\Http\Controllers\Api\MessageController::class, 'addReaction'])->middleware('throttle:100,1');
        Route::delete('/{id}/reactions', [App\Http\Controllers\Api\MessageController::class, 'removeReaction']);
        Route::get('/search', [App\Http\Controllers\Api\MessageController::class, 'search'])->middleware('throttle:20,1');
    });

    // Direct Messages
    Route::prefix('direct-messages')->group(function () {
        Route::get('/conversations', [App\Http\Controllers\Api\DirectMessageController::class, 'conversations']);
        Route::post('/conversations', [App\Http\Controllers\Api\DirectMessageController::class, 'startConversation']);
        Route::get('/conversations/{id}', [App\Http\Controllers\Api\DirectMessageController::class, 'getMessages']);
        Route::post('/conversations/{id}/messages', [App\Http\Controllers\Api\DirectMessageController::class, 'sendMessage']);
        Route::put('/messages/{id}', [App\Http\Controllers\Api\DirectMessageController::class, 'updateMessage']);
        Route::delete('/messages/{id}', [App\Http\Controllers\Api\DirectMessageController::class, 'deleteMessage']);
        Route::post('/messages/{id}/reactions', [App\Http\Controllers\Api\DirectMessageController::class, 'addReaction']);
        Route::delete('/messages/{id}/reactions', [App\Http\Controllers\Api\DirectMessageController::class, 'removeReaction']);
        Route::post('/conversations/{id}/leave', [App\Http\Controllers\Api\DirectMessageController::class, 'leaveConversation']);
    });

    // Presence and Typing
    Route::prefix('presence')->group(function () {
        Route::post('/status', [App\Http\Controllers\Api\PresenceController::class, 'updateStatus']);
        Route::get('/online-users', [App\Http\Controllers\Api\PresenceController::class, 'getOnlineUsers']);
        Route::post('/typing', [App\Http\Controllers\Api\PresenceController::class, 'typing']);
        Route::post('/stop-typing', [App\Http\Controllers\Api\PresenceController::class, 'stopTyping']);
        Route::get('/typing-users', [App\Http\Controllers\Api\PresenceController::class, 'getTypingUsers']);
        Route::post('/heartbeat', [App\Http\Controllers\Api\PresenceController::class, 'heartbeat']);
    });

    // Get assignable users (admins, teachers, students)
    Route::get('/users/assignable', [App\Http\Controllers\Api\UserListController::class, 'getAssignableUsers']);

    // Ticket System Routes
    Route::prefix('tickets')->group(function () {
        // Workspaces
        Route::get('/workspaces', [WorkspaceController::class, 'index']);
        Route::post('/workspaces', [WorkspaceController::class, 'store']);
        Route::get('/workspaces/{id}', [WorkspaceController::class, 'show']);
        Route::put('/workspaces/{id}', [WorkspaceController::class, 'update']);
        Route::delete('/workspaces/{id}', [WorkspaceController::class, 'destroy']);
        Route::get('/workspaces/{id}/statistics', [WorkspaceController::class, 'statistics']);

        // Tickets
        Route::get('/', [TicketController::class, 'index']);
        Route::post('/', [TicketController::class, 'store']);
        Route::get('/statistics', [TicketController::class, 'statistics']);
        Route::get('/{id}', [TicketController::class, 'show']);
        Route::put('/{id}', [TicketController::class, 'update']);
        Route::delete('/{id}', [TicketController::class, 'destroy']);

        // Subtasks
        Route::post('/{id}/subtasks', [TicketController::class, 'addSubtask']);
        Route::put('/{ticketId}/subtasks/{subtaskId}/toggle', [TicketController::class, 'toggleSubtask']);
        Route::delete('/{ticketId}/subtasks/{subtaskId}', [TicketController::class, 'deleteSubtask']);

        // Comments
        Route::post('/{id}/comments', [TicketController::class, 'addComment']);
        Route::put('/{id}/comments/{commentId}', [TicketController::class, 'updateComment']);
        Route::delete('/{id}/comments/{commentId}', [TicketController::class, 'deleteComment']);

        // Attachments
        Route::post('/{id}/attachments', [TicketController::class, 'uploadAttachment']);
        Route::delete('/{id}/attachments/{attachmentId}', [TicketController::class, 'deleteAttachment']);
        
        // Voice Recordings
        Route::post('/{id}/voice-recording', [TicketController::class, 'uploadVoiceRecording']);
        Route::delete('/{id}/voice-recordings/{voiceRecordingId}', [TicketController::class, 'deleteVoiceRecording']);

        // Time Tracking
        Route::post('/{id}/timer/start', [TicketController::class, 'startTimer']);
        Route::post('/{id}/timer/stop', [TicketController::class, 'stopTimer']);
    });
});
