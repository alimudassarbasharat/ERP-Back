# API Response Standards & Toast Notification Guidelines

**Project:** Student ERP System  
**Date:** January 8, 2026  
**Status:** MANDATORY FOR ALL APIs

---

## 🎯 CORE REQUIREMENT

**ALL API endpoints MUST use the `ApiResponse` helper.**

**NO EXCEPTIONS.**

This ensures:
1. Consistent response format across entire system
2. Toast-friendly error/success messages
3. Easy frontend integration
4. Professional user experience

---

## 📋 STANDARD RESPONSE FORMAT

Based on the **Class List API** (the gold standard for this project):

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "result": { /* data here */ }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message for toast",
  "error": "Detailed error (debug mode only)"
}
```

### Validation Error Response
```json
{
  "success": false,
  "message": "First field error message",
  "errors": {
    "field1": "Error message",
    "field2": "Error message"
  }
}
```

### Paginated Response
```json
{
  "success": true,
  "message": "Data fetched successfully",
  "result": {
    "data": [...],
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100,
    "from": 1,
    "to": 20
  }
}
```

---

## 🔧 USAGE EXAMPLES

### Import the Helper
```php
use App\Helpers\ApiResponse;
```

### 1. Success Response with Data
```php
// ❌ WRONG - Do NOT use raw response()->json()
return response()->json([
    'success' => true,
    'data' => $user
], 200);

// ✅ CORRECT - Use ApiResponse
return ApiResponse::success($user, 'User fetched successfully');
```

### 2. Created Response (HTTP 201)
```php
// ❌ WRONG
return response()->json(['data' => $class], 201);

// ✅ CORRECT
return ApiResponse::created($class, 'Class created successfully');
```

### 3. Updated Response
```php
// ❌ WRONG
return response()->json(['success' => true, 'class' => $class]);

// ✅ CORRECT
return ApiResponse::updated($class, 'Class updated successfully');
```

### 4. Deleted Response
```php
// ❌ WRONG
return response()->json(['message' => 'Deleted'], 200);

// ✅ CORRECT
return ApiResponse::deleted('Class deleted successfully');
```

### 5. Validation Error
```php
// ❌ WRONG - Manual validation
if (empty($request->name)) {
    return response()->json(['error' => 'Name required'], 422);
}

// ✅ CORRECT - Let ApiResponse handle it
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255'
]);

if ($validator->fails()) {
    return ApiResponse::validationError(
        $validator->errors(),
        'Please fix the validation errors'
    );
}
```

### 6. Not Found
```php
// ❌ WRONG
if (!$class) {
    return response()->json(['error' => 'Not found'], 404);
}

// ✅ CORRECT
if (!$class) {
    return ApiResponse::notFound('Class not found');
}
```

### 7. Forbidden/Unauthorized
```php
// ❌ WRONG
if (!$user->can('edit', $class)) {
    return response()->json(['error' => 'Forbidden'], 403);
}

// ✅ CORRECT
if (!$user->can('edit', $class)) {
    return ApiResponse::forbidden('You do not have permission to edit this class');
}
```

### 8. Server Error
```php
// ❌ WRONG
catch (\Exception $e) {
    return response()->json(['error' => $e->getMessage()], 500);
}

// ✅ CORRECT
catch (\Exception $e) {
    return ApiResponse::serverError(
        'Failed to process request',
        $e->getMessage()
    );
}
```

### 9. Paginated Data
```php
// ❌ WRONG
$classes = Classes::paginate(20);
return response()->json($classes);

// ✅ CORRECT
$classes = Classes::with(['subjects', 'sections'])->paginate(20);

// Option 1: Use raw items
return ApiResponse::paginated($classes, 'Classes fetched successfully');

// Option 2: Transform items first
$transformedData = $classes->items(); // Your transformation logic
return ApiResponse::paginated($classes, 'Classes fetched successfully', $transformedData);
```

### 10. Collection/List
```php
// ❌ WRONG
$sections = Section::all();
return response()->json(['sections' => $sections]);

// ✅ CORRECT
$sections = Section::all();
return ApiResponse::collection($sections, 'Sections fetched successfully');
```

### 11. Bulk Operations
```php
// ❌ WRONG
return response()->json([
    'created' => $created,
    'failed' => $failed
]);

// ✅ CORRECT
return ApiResponse::bulkOperation($created, $failed, 'class');
```

---

## 🚫 FORBIDDEN PATTERNS

### DO NOT:
1. ❌ Use `response()->json()` directly
2. ❌ Return raw arrays: `return ['data' => $user];`
3. ❌ Return raw models: `return $user;`
4. ❌ Use inconsistent keys: `data`, `result`, `response`, `payload`
5. ❌ Skip `success` flag
6. ❌ Skip `message` (required for toast)
7. ❌ Expose detailed errors in production

---

## 📝 CONTROLLER REFACTORING GUIDE

### Before (Non-Standard)
```php
public function index()
{
    $students = Student::all();
    return $students; // ❌ WRONG
}

public function store(Request $request)
{
    $student = Student::create($request->all());
    return ['data' => $student]; // ❌ WRONG
}

public function destroy($id)
{
    Student::destroy($id);
    return response()->json(['message' => 'Deleted']); // ❌ WRONG
}
```

### After (Standard)
```php
use App\Helpers\ApiResponse;

public function index(Request $request)
{
    try {
        $query = Student::with(['class', 'section']);
        
        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        
        $students = $query->paginate($request->get('per_page', 20));
        
        return ApiResponse::paginated($students, 'Students fetched successfully');
    } catch (\Exception $e) {
        return ApiResponse::serverError('Failed to fetch students', $e->getMessage());
    }
}

public function store(StoreStudentRequest $request)
{
    try {
        $student = Student::create($request->validated());
        return ApiResponse::created($student, 'Student created successfully');
    } catch (\Exception $e) {
        return ApiResponse::serverError('Failed to create student', $e->getMessage());
    }
}

public function destroy($id)
{
    try {
        $student = Student::findOrFail($id);
        $student->delete();
        return ApiResponse::deleted('Student deleted successfully');
    } catch (ModelNotFoundException $e) {
        return ApiResponse::notFound('Student not found');
    } catch (\Exception $e) {
        return ApiResponse::serverError('Failed to delete student', $e->getMessage());
    }
}
```

---

## 🎨 FRONTEND INTEGRATION

### Toast Display
All responses automatically trigger toasts:

```javascript
// Success response
{
  "success": true,
  "message": "Class created successfully" // ← Shows as SUCCESS TOAST
}

// Error response
{
  "success": false,
  "message": "Class not found" // ← Shows as ERROR TOAST
}

// Validation error
{
  "success": false,
  "message": "Name is required", // ← Shows as ERROR TOAST
  "errors": { "name": "Name is required" }
}
```

### Axios Interceptor (Auto-Toast)
```javascript
// Response interceptor
axios.interceptors.response.use(
  (response) => {
    // Success toast
    if (response.data.success && response.data.message) {
      toast.success(response.data.message);
    }
    return response;
  },
  (error) => {
    // Error toast
    if (error.response?.data?.message) {
      toast.error(error.response.data.message);
    } else {
      toast.error('An unexpected error occurred');
    }
    return Promise.reject(error);
  }
);
```

---

## ✅ VALIDATION RULES

### Form Request Validation
```php
class StoreClassRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:classes,name',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Class name is required',
            'name.unique' => 'A class with this name already exists',
        ];
    }
}
```

The validation errors are **automatically** converted to ApiResponse format by the Exception Handler.

---

## 📊 HTTP STATUS CODES

Use correct status codes:

| Code | Method | Use Case |
|------|--------|----------|
| 200 | `success()` | General success |
| 201 | `created()` | Resource created |
| 204 | `noContent()` | Success, no content |
| 400 | `error(..., 400)` | Bad request |
| 401 | `unauthorized()` | Not authenticated |
| 403 | `forbidden()` | Not authorized |
| 404 | `notFound()` | Resource not found |
| 422 | `validationError()` | Validation failed |
| 500 | `serverError()` | Server error |

---

## 🔒 SECURITY BEST PRACTICES

### 1. Never Expose Sensitive Data in Production
```php
// ❌ WRONG - Exposes stack trace in production
catch (\Exception $e) {
    return ApiResponse::serverError($e->getMessage(), $e->getTrace());
}

// ✅ CORRECT - Only shows details in debug mode
catch (\Exception $e) {
    return ApiResponse::serverError(
        'An error occurred',
        $e->getMessage() // Only shown if config('app.debug') === true
    );
}
```

### 2. Use Custom Messages
```php
// ❌ WRONG - Technical message
return ApiResponse::error('SQLSTATE[23000]: Integrity constraint violation');

// ✅ CORRECT - User-friendly message
return ApiResponse::error('This record cannot be deleted because it is being used by other data');
```

---

## 📋 MIGRATION CHECKLIST

For each controller:
- [ ] Import `use App\Helpers\ApiResponse;`
- [ ] Replace all `response()->json()` with `ApiResponse::`
- [ ] Ensure all success responses have `message` for toast
- [ ] Ensure all errors use appropriate method (`notFound`, `forbidden`, etc.)
- [ ] Wrap operations in try-catch
- [ ] Use FormRequest for validation
- [ ] Test that toasts appear correctly

---

## 🧪 TESTING

### Test Response Format
```php
public function test_index_returns_standard_format()
{
    $response = $this->getJson('/api/classes');
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'result' => [
            'data',
            'current_page',
            'last_page',
            'per_page',
            'total',
        ]
    ]);
    $this->assertTrue($response->json('success'));
}

public function test_validation_returns_standard_format()
{
    $response = $this->postJson('/api/classes', []);
    
    $response->assertStatus(422);
    $response->assertJsonStructure([
        'success',
        'message',
        'errors'
    ]);
    $this->assertFalse($response->json('success'));
}
```

---

## 🚀 DEPLOYMENT

Before deploying:
1. Audit ALL controllers
2. Search codebase for `response()->json(` and replace
3. Test all endpoints return standard format
4. Verify toasts appear on frontend
5. Check error messages are user-friendly

---

## 📞 SUPPORT

If you need help:
1. Review Class List API (gold standard example)
2. Check ApiResponse helper methods
3. Review this documentation
4. Test your endpoint with Postman

---

**Remember:** Consistency is key. Every API must return the same format for a professional, polished user experience.

**Status:** MANDATORY - No exceptions allowed.
