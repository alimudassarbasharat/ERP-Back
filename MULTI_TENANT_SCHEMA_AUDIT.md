# MULTI-TENANCY SCHEMA AUDIT REPORT
**Date:** 2026-01-08  
**Project:** Student ERP System  
**Database:** PostgreSQL  

## Executive Summary
Comprehensive audit of all database tables to ensure merchant_id multi-tenancy compliance.

---

## TABLES WITH merchant_id ✅ (67 tables)
These tables already have merchant_id properly implemented:

### Core User & Authentication
- users
- admins
- user_preferences
- user_statuses
- user_presence

### Academic Entities
- students
- classes
- sections
- subjects
- teachers
- teacher_personal_details
- teacher_contact_details
- teacher_professional_details
- teacher_additional_details
- academic_records
- academic_years
- contact_information

### Attendance
- attendances
- attendance_records
- attendance_settings

### Exams
- exams

### Fees
- late_fees
- fee_defaults
- fee_payments
- fee_summaries

### Messaging System
- channels
- channel_members
- channel_users
- messages
- message_reactions
- message_attachments
- message_notifications
- message_read_receipts
- direct_messages
- direct_message_conversations
- direct_message_participants
- direct_message_attachments
- direct_message_read_receipts
- typing_indicators

### Ticketing System
- workspaces
- tickets
- ticket_activities
- ticket_attachments
- ticket_comments
- ticket_comment_attachments
- ticket_subtasks
- ticket_time_logs
- ticket_voice_recordings
- ticket_watchers
- ticket_labels

### Relationships & Pivots
- class_subject
- class_subjects
- teacher_subjects
- teacher_classes
- exam_subject

### Permissions (Spatie)
- roles
- permissions
- model_has_roles
- model_has_permissions
- role_has_permissions

### School Management
- schools
- sessions
- departments

### Other
- family_infos
- result_sheet
- student_forms
- country_codes

---

## TABLES WITHOUT merchant_id ❌ (8 tables - REQUIRE ACTION)

### 1. **class_section** (Pivot Table)
- **Type:** Tenant-owned pivot table
- **Relationship:** Many-to-many between classes and sections
- **Backfill Strategy:** Infer from classes.merchant_id
- **SQL Backfill:**
  ```sql
  UPDATE class_section cs
  SET merchant_id = c.merchant_id
  FROM classes c
  WHERE cs.class_id = c.id
  ```

### 2. **events**
- **Type:** Tenant-owned functional table
- **Relationship:** Belongs to user
- **Backfill Strategy:** Infer from users.merchant_id
- **SQL Backfill:**
  ```sql
  UPDATE events e
  SET merchant_id = u.merchant_id
  FROM users u
  WHERE e.user_id = u.id
  ```

### 3. **exam_marksheet_configs**
- **Type:** Tenant-owned functional table
- **Relationship:** Has school_id, belongs to exam
- **Backfill Strategy:** Infer from schools.merchant_id
- **SQL Backfill:**
  ```sql
  UPDATE exam_marksheet_configs emc
  SET merchant_id = s.merchant_id
  FROM schools s
  WHERE emc.school_id = s.id
  ```

### 4. **exam_papers**
- **Type:** Tenant-owned functional table
- **Relationship:** Has school_id, belongs to exam
- **Backfill Strategy:** Infer from schools.merchant_id
- **SQL Backfill:**
  ```sql
  UPDATE exam_papers ep
  SET merchant_id = s.merchant_id
  FROM schools s
  WHERE ep.school_id = s.id
  ```

### 5. **exam_questions**
- **Type:** Tenant-owned functional table
- **Relationship:** Belongs to exam_paper
- **Backfill Strategy:** Infer from exam_papers.merchant_id (after backfilling exam_papers)
- **SQL Backfill:**
  ```sql
  UPDATE exam_questions eq
  SET merchant_id = ep.merchant_id
  FROM exam_papers ep
  WHERE eq.exam_paper_id = ep.id
  ```

### 6. **exam_terms**
- **Type:** Tenant-owned functional table
- **Relationship:** Has school_id and session_id
- **Backfill Strategy:** Infer from schools.merchant_id
- **SQL Backfill:**
  ```sql
  UPDATE exam_terms et
  SET merchant_id = s.merchant_id
  FROM schools s
  WHERE et.school_id = s.id
  ```

### 7. **mention_notifications**
- **Type:** Tenant-owned functional table
- **Relationship:** Belongs to user
- **Backfill Strategy:** Infer from users.merchant_id
- **SQL Backfill:**
  ```sql
  UPDATE mention_notifications mn
  SET merchant_id = u.merchant_id
  FROM users u
  WHERE mn.user_id = u.id
  ```

### 8. **subject_mark_sheets**
- **Type:** Tenant-owned functional table
- **Relationship:** Belongs to student
- **Backfill Strategy:** Infer from students.merchant_id
- **SQL Backfill:**
  ```sql
  UPDATE subject_mark_sheets sms
  SET merchant_id = s.merchant_id
  FROM students s
  WHERE sms.student_id = s.id
  ```

---

## SYSTEM TABLES (DO NOT NEED merchant_id) ✅

These tables are system-level and should NOT have merchant_id:
- migrations
- password_resets
- failed_jobs
- oauth_auth_codes
- oauth_access_tokens
- oauth_refresh_tokens
- oauth_clients
- oauth_personal_access_clients
- personal_access_tokens

---

## BACKFILL EXECUTION ORDER

**CRITICAL:** Must be executed in this order due to dependencies:

1. **exam_papers** (depends on schools.merchant_id)
2. **exam_terms** (depends on schools.merchant_id)
3. **exam_marksheet_configs** (depends on schools.merchant_id)
4. **exam_questions** (depends on exam_papers.merchant_id - backfill AFTER exam_papers)
5. **class_section** (depends on classes.merchant_id)
6. **events** (depends on users.merchant_id)
7. **mention_notifications** (depends on users.merchant_id)
8. **subject_mark_sheets** (depends on students.merchant_id)

---

## INDEX STRATEGY

For each table, the following indexes will be added:
- **Single index:** `merchant_id`
- **Composite indexes** (where applicable):
  - `(merchant_id, school_id)` - for exam_papers, exam_terms, exam_marksheet_configs
  - `(merchant_id, created_at)` - for time-based queries
  - `(merchant_id, status)` - for status-based filtering

---

## MODEL UPDATES REQUIRED

All 8 models must be updated to:
1. Use `TenantScope` trait
2. Add `merchant_id` to `$fillable` array
3. Update relationships to scope by merchant_id where needed

**Files to update:**
- `app/Models/ExamPaper.php`
- `app/Models/ExamTerm.php`
- `app/Models/ExamMarksheetConfig.php`
- `app/Models/ExamQuestion.php`
- `app/Models/SubjectMarkSheet.php`
- `app/Models/Event.php`
- `app/Models/MentionNotification.php`
- (class_section is a pivot - no dedicated model)

---

## CONTROLLERS & SERVICES AUDIT

### Controllers to Audit:
- ExamController
- ExamPaperController
- ExamTermController
- ExamMarksheetConfigController
- EventController
- SubjectMarkSheetController
- MentionNotificationController

### Services to Audit:
- ExamService
- ExamPaperService
- EventService
- MarkSheetService

**Action:** Ensure all queries include merchant_id scoping.

---

## VALIDATION GUARDS

Add validation in:
1. **Request classes** - Ensure merchant_id is set on create/update
2. **Service layer** - Throw exception if merchant_id missing
3. **Middleware** - TenantMiddleware already exists, ensure it's applied to all routes

---

## TESTING REQUIREMENTS

1. **Unit Tests:**
   - Test that all models auto-assign merchant_id on create
   - Test that global scopes filter by merchant_id

2. **Integration Tests:**
   - Test that cross-merchant access is impossible
   - Test that queries without merchant_id fail gracefully

3. **Migration Tests:**
   - Test backfill logic doesn't create orphaned records
   - Test indexes are created properly

---

## COMPLETION CHECKLIST

- [ ] Migration created: Add merchant_id columns
- [ ] Migration created: Backfill existing data
- [ ] Migration created: Make merchant_id NOT NULL
- [ ] Migration created: Add indexes and constraints
- [ ] All 8 models updated with TenantScope
- [ ] All controllers audited for merchant_id scoping
- [ ] All services audited for merchant_id scoping
- [ ] Factories updated to set merchant_id
- [ ] Seeders updated to set merchant_id
- [ ] Validation guards implemented
- [ ] Tests written and passing
- [ ] Documentation updated

---

## ESTIMATED IMPACT

- **Tables affected:** 8
- **Models to update:** 7 (+ pivot handling)
- **Controllers to audit:** ~6-8
- **Services to audit:** ~4-6
- **Factories to update:** ~8
- **Seeders to update:** ~8
- **Risk:** LOW (additive migrations, safe backfill strategy)
- **Breaking changes:** NONE (backwards compatible)
