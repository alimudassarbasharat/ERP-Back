# Multi-Tenancy Quick Reference Guide

## 🚀 Quick Start

### Running the Migration
```bash
cd c:\Project\Student ERP System\ERP-Back
php artisan migrate
```

### Running Tests
```bash
php artisan test --filter=MultiTenancyTest
```

---

## 📝 Creating Tenant-Aware Models

### Step 1: Add TenantScope Trait
```php
use App\Traits\TenantScope;

class YourModel extends Model
{
    use TenantScope; // This is all you need!
}
```

### Step 2: Add merchant_id to Fillable
```php
protected $fillable = [
    'merchant_id', // Always include this
    // ... your other fields
];
```

### Step 3: Migration (if creating new table)
```php
Schema::create('your_table', function (Blueprint $table) {
    $table->id();
    $table->string('merchant_id')->index(); // Add this
    // ... your other columns
    $table->timestamps();
});
```

---

## 🔍 Common Queries

### Query Current Merchant's Data
```php
// Automatic - no extra code needed!
$records = YourModel::all();
$record = YourModel::find($id);
```

### Create Record (Auto merchant_id)
```php
// merchant_id is automatically set
$record = YourModel::create([
    'name' => 'Something',
    // No need to set merchant_id
]);
```

### Query All Merchants (Admin Only)
```php
$allRecords = YourModel::withoutTenantScope()->get();
```

### Query Specific Merchant
```php
$records = YourModel::forMerchant($merchantId)->get();
```

---

## 🛠️ Helper Functions

```php
use App\Helpers\TenantHelper;

// Get current merchant_id
$merchantId = TenantHelper::currentMerchantId();

// Get merchant_id or throw exception
$merchantId = TenantHelper::requireMerchantId('operation name');

// Check if model belongs to current merchant
if (TenantHelper::belongsToCurrentMerchant($model)) {
    // Safe to proceed
}

// Check if user is super admin
if (TenantHelper::isSuperAdmin()) {
    // Can access all data
}
```

---

## ⚠️ Common Pitfalls

### ❌ DON'T: Manually filter by merchant_id in queries
```php
// BAD - unnecessary
ExamPaper::where('merchant_id', $merchantId)->get();
```

### ✅ DO: Let TenantScope handle it
```php
// GOOD - automatic filtering
ExamPaper::all();
```

### ❌ DON'T: Manually set merchant_id on create
```php
// BAD - unnecessary
ExamPaper::create([
    'merchant_id' => $merchantId, // Auto-set by TenantScope
    'title' => 'Paper',
]);
```

### ✅ DO: Let TenantScope auto-assign
```php
// GOOD - automatic assignment
ExamPaper::create([
    'title' => 'Paper',
]);
```

### ❌ DON'T: Use raw DB queries without merchant_id
```php
// DANGEROUS - bypasses tenant scoping
DB::table('exam_papers')->where('status', 'active')->get();
```

### ✅ DO: Use Eloquent models
```php
// SAFE - tenant scoped
ExamPaper::where('status', 'active')->get();
```

---

## 🧪 Testing Tenant Isolation

```php
public function test_tenant_isolation()
{
    $merchantA = 'MERCHANT_A';
    $merchantB = 'MERCHANT_B';
    
    $userA = User::factory()->create(['merchant_id' => $merchantA]);
    $userB = User::factory()->create(['merchant_id' => $merchantB]);
    
    // Create data for merchant A
    $this->actingAs($userA);
    $recordA = ExamPaper::create(['title' => 'Paper A']);
    
    // Verify merchant B cannot see it
    $this->actingAs($userB);
    $papers = ExamPaper::all();
    $this->assertCount(0, $papers);
    
    // Verify cannot access directly
    $this->expectException(ModelNotFoundException::class);
    ExamPaper::findOrFail($recordA->id);
}
```

---

## 🔒 Security Best Practices

1. **Always use Eloquent models** - Never use raw DB queries
2. **Use TenantScope trait** - Don't manually filter by merchant_id
3. **Test tenant isolation** - Write tests for new features
4. **Log suspicious activity** - Check logs regularly
5. **Review PRs carefully** - Ensure new code uses TenantScope

---

## 📊 Verification SQL

### Check for NULL merchant_id
```sql
SELECT COUNT(*) FROM exam_papers WHERE merchant_id IS NULL;
SELECT COUNT(*) FROM exam_terms WHERE merchant_id IS NULL;
SELECT COUNT(*) FROM events WHERE merchant_id IS NULL;
```

### Verify Indexes
```sql
SELECT tablename, indexname 
FROM pg_indexes 
WHERE indexname LIKE '%merchant_id%'
ORDER BY tablename;
```

### Count Records by Merchant
```sql
SELECT merchant_id, COUNT(*) 
FROM exam_papers 
GROUP BY merchant_id;
```

---

## 🆘 Troubleshooting

### Issue: merchant_id is NULL
**Cause:** User not authenticated or missing merchant_id  
**Fix:** Ensure user is authenticated and has merchant_id set

### Issue: Can see other merchant's data
**Cause:** TenantScope not added to model  
**Fix:** Add `use TenantScope;` to model class

### Issue: Cannot create records
**Cause:** merchant_id validation failing  
**Fix:** Ensure user has merchant_id or set it manually in seeders

### Issue: Tests failing
**Cause:** User context not set in tests  
**Fix:** Use `$this->actingAs($user)` before creating records

---

## 📚 Additional Resources

- Full documentation: `MULTI_TENANT_IMPLEMENTATION_COMPLETE.md`
- Schema audit: `MULTI_TENANT_SCHEMA_AUDIT.md`
- TenantScope trait: `app/Traits/TenantScope.php`
- TenantHelper: `app/Helpers/TenantHelper.php`
- Tests: `tests/Feature/MultiTenancyTest.php`
