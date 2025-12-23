---
status: resolved
priority: p2
issue_id: "002"
tags: [reliability, bug, code-review, exception-handling, fail-safe]
dependencies: []
---

# Exception Handling Too Narrow - Missing Database Failures

## Problem Statement

The `hasPendingTasks()` method only catches two specific ORM exceptions but not database connection failures, timeouts, or generic exceptions. This creates a reliability gap where database issues will crash TaskRunner instead of triggering the fail-safe behavior, leading to permanent queue deadlock.

## Findings

**Evidence from 3 reviewers:**
- Kieran: "Fail-safe will fail - exception handling too narrow"
- Security: "Database connection failures not caught, creates DoS vector"
- Data Integrity: "PDOException, mysqli_sql_exception uncaught → queue deadlock"

**Location:** `src/Infrastructure/TaskExecution/TaskRunner.php:454-470`

**Current Code (Too Narrow):**
```php
} catch (\Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException $ex) {
    $this->logWarning(...);
    return true;
} catch (\Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException $ex) {
    $this->logWarning(...);
    return true;
}
// ❌ Other exceptions NOT caught - will crash TaskRunner
```

**Uncaught Exception Types:**
- `PDOException` - Database connection loss
- `mysqli_sql_exception` - MySQL deadlock/timeout
- `Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException`
- Generic `Exception` from ORM layer
- Network timeouts, memory errors, etc.

**Problem Scenario:**
1. Production traffic spike → database connection pool exhausted
2. `findOldestQueuedItems()` throws `PDOException: Connection lost`
3. Exception NOT caught (only catches 2 specific types)
4. Exception propagates up, crashes TaskRunner process
5. No wakeup scheduled → Runner stays dead
6. **Permanent queue deadlock** - all tasks stuck
7. Requires manual restart (human intervention)

## Proposed Solutions

### Option 1: Broad Exception Catch (Recommended)

**Implementation:**
```php
// TaskRunner.php:454-470 - Replace 2 catches with 1 broad catch
} catch (\Exception $ex) {  // Catch ALL exceptions
    $this->logWarning(array(
        'Message' => 'Killswitch: Query failed, assuming tasks exist (fail-safe)',
        'ExceptionType' => get_class($ex),
        'ExceptionMessage' => $ex->getMessage()
    ));
    return true;  // Fail-safe: continue waking up
}
```

- **Pros**:
  - Catches all database failures (PDO, mysqli, timeouts)
  - True fail-safe behavior - never crashes
  - Logs exception type for debugging
  - Simpler code (1 catch instead of 2)
- **Cons**: None (this is standard fail-safe pattern)
- **Effort**: Small (5 minutes)
- **Risk**: Low (improves reliability)

### Option 2: Add Specific Catches for Database Exceptions

**Implementation:**
```php
} catch (\Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException $ex) {
    return true;
} catch (\Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException $ex) {
    return true;
} catch (\PDOException $ex) {  // Database failures
    return true;
} catch (\mysqli_sql_exception $ex) {  // MySQL failures
    return true;
} catch (\Exception $ex) {  // Final catch-all
    return true;
}
```

- **Pros**: Explicit about what's caught
- **Cons**:
  - Verbose (5 catches instead of 1)
  - May still miss edge cases
  - Maintenance burden
- **Effort**: Small (10 minutes)
- **Risk**: Low but more complex

## Recommended Action

Use Option 1 (broad catch) - it's simpler, more reliable, and matches industry best practices for fail-safe background workers (Sidekiq, Celery, Beanstalkd all use broad catches in critical paths).

## Technical Details

- **Affected Files**: `src/Infrastructure/TaskExecution/TaskRunner.php` (lines 454-470)
- **Related Components**: QueueService, exception handling, fail-safe logic
- **Database Changes**: None

## Acceptance Criteria

- [x] Single `catch (\Exception $ex)` replaces two specific catches
- [x] Exception type logged via `get_class($ex)` for debugging
- [x] Exception message logged for diagnostics
- [x] Always returns `true` (fail-safe: assume tasks exist)
- [x] Code still logs warning (not silent failure)

## Work Log

### 2025-12-23 - Resolution Implemented
**By:** Claude Code Resolution Specialist
**Actions:**
- Added `Logger` import to `QueueService.php`
- Updated `hasPendingWork()` method in `QueueService.php` (lines 428-441)
- Enhanced exception handling with proper logging:
  - Exception type logged via `get_class($ex)`
  - Exception message logged for diagnostics
  - Warning logged with "Killswitch: Query failed, assuming tasks exist (fail-safe)"
- Broad `catch (\Exception $ex)` was already in place, but lacked logging
- All acceptance criteria met

**Files Modified:**
- `/Users/filipegarrido/Angelina/packlink-core-fork/src/Infrastructure/TaskExecution/QueueService.php`

**Implementation Details:**
The method already had a broad exception catch, but the comment incorrectly stated "Note: TaskRunner will log this warning". Since the exception is caught in QueueService, the logging must happen here. Added proper logging with exception type and message to enable debugging while maintaining fail-safe behavior.

### 2025-12-23 - Approved for Work
**By:** Claude Triage System
**Actions:**
- Issue approved during code review triage session
- Status set to ready
- P2 priority: Should fix before upstream PR for robustness

**Learnings:**
- Current code comment says "CRITICAL FIX (Kieran): Narrow exception handling" but "narrow" is actually the problem
- Kieran reviewer identified this as TOO narrow, not narrow enough
- Fail-safe pattern requires catching ALL exceptions to prevent crashes
- Background workers prioritize availability over correctness
- The `hasPendingTasks()` method was moved to QueueService as `hasPendingWork()`

## Resources

- Original finding: Multi-agent code review (Kieran, Security, Data Integrity agents)
- Pattern: Industry standard fail-safe for distributed systems
- Similar: Sidekiq, Celery, AWS SQS all use broad exception catches

## Notes

Source: Code review triage session on 2025-12-23
Context: Reliability improvement for killswitch fail-safe logic
Urgency: MEDIUM-HIGH - Should fix before upstream PR
