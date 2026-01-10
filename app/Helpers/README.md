# Helpers Directory

This directory contains reusable helper classes that can be used across multiple projects.

## Structure

### DateHelper
- `formatDatePk()` - Format date to Pakistan standard (DD-MM-YYYY)
- `formatDateIso()` - Format date to ISO format (YYYY-MM-DD)
- `formatDateTime()` - Format date with time
- `formatHumanReadable()` - Format date to human readable (e.g., "2 days ago")
- `getDateRange()` - Get date range for a period
- `isWithinRange()` - Check if date is within range

### StringHelper
- `snakeToTitle()` - Convert snake_case to Title Case
- `camelToTitle()` - Convert camelCase to Title Case
- `capitalizeWords()` - Capitalize all words
- `getInitials()` - Generate initials from full name
- `truncate()` - Truncate string with ellipsis
- `sanitize()` - Sanitize string (remove HTML, trim, etc.)
- `extractMentions()` - Extract mentions from text
- `slugify()` - Generate slug from string

### AuthHelper
- `getAuthUser()` - Get authenticated user
- `isAuthenticated()` - Check if user is authenticated
- `getAuthUserId()` - Get authenticated user ID
- `hasRole()` - Check if user has role
- `hasPermission()` - Check if user has permission
- `getUserType()` - Get user type (admin, user, etc.)

### ResponseHelper
- `success()` - Success response
- `error()` - Error response
- `validationError()` - Validation error response
- `unauthorized()` - Unauthorized response
- `forbidden()` - Forbidden response
- `notFound()` - Not found response
- `serverError()` - Server error response

## Usage

```php
use App\Helpers\DateHelper;
use App\Helpers\StringHelper;
use App\Helpers\AuthHelper;
use App\Helpers\ResponseHelper;

// Date formatting
$formatted = DateHelper::formatDatePk($date, true);

// String manipulation
$title = StringHelper::snakeToTitle('user_name');

// Auth checks
if (AuthHelper::isAuthenticated()) {
    $userId = AuthHelper::getAuthUserId();
}

// Standardized responses
return ResponseHelper::success($data, 'Operation successful');
return ResponseHelper::error('Error message', $errors, 400);
```

## Best Practices

1. **Keep helpers generic** - Don't add project-specific logic
2. **Single responsibility** - Each helper should have one clear purpose
3. **Reusability** - Write functions that can be used across multiple projects
4. **Documentation** - Always document parameters and return types
5. **Type safety** - Use type hints and return types
