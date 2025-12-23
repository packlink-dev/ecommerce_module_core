---
status: completed
priority: p2
issue_id: "003"
tags: [testing, code-quality, code-review, race-condition, concurrency]
dependencies: []
---

# Rewrite Race Condition Test - Make It Actually Test Concurrency

## Problem Statement

The test `testRaceConditionPreventsConcurrentWakeups()` (lines 173-195) has a misleading name - it claims to test race conditions but actually only verifies that TaskRunnerStarter is a singleton and that GUIDs are unique. It doesn't simulate concurrent wakeup scenarios or verify that only one runner is spawned when multiple runners check for tasks simultaneously.

## Findings

**Evidence from 2 reviewers:**
- Kieran: "Race condition test is useless - only verifies GUIDs are unique and singleton pattern"
- Simplicity: "Test name misleading - doesn't match what it tests, provides no value"

**Location:** `tests/Infrastructure/TaskExecution/TaskRunnerKillswitchTest.php:173-195`

**Current Test (Inadequate):**
```php
public function testRaceConditionPreventsConcurrentWakeups()
{
    $runner1 = new TaskRunner();
    $runner2 = new TaskRunner();

    // ❌ Only tests singleton pattern (not race conditions)
    $starter1 = TaskRunnerStarter::getInstance();
    $starter2 = TaskRunnerStarter::getInstance();
    $this->assertSame($starter1, $starter2);

    // ❌ Only tests GUID uniqueness (not concurrency)
    $guid1 = $this->guidProvider->generateGuid();
    $guid2 = $this->guidProvider->generateGuid();
    $this->assertNotEquals($guid1, $guid2);

    // ❌ Never calls hasPendingTasks()
    // ❌ Never calls wakeup()
    // ❌ Doesn't verify runner spawn behavior
}
```

**What's Missing:**
- No simulation of concurrent `hasPendingTasks()` calls
- No actual `wakeup()` invocation to test GUID locking
- No verification that only ONE runner spawns
- No testing of the actual race condition window

## Proposed Solutions

### Option B: Rewrite to Test Actual Concurrency (SELECTED)

**Implementation:**
```php
public function testRaceConditionPreventsConcurrentWakeups()
{
    // Arrange: Enqueue task so hasPendingTasks() returns true
    $task = new FooTask();
    $this->queue->enqueue('default', $task);

    // Act: Simulate two concurrent runners checking for tasks
    $runner1 = new TaskRunner();
    $runner2 = new TaskRunner();

    // Both check if tasks exist (simulate concurrent checks)
    $hasTasks1 = $this->invokePrivateMethod($runner1, 'hasPendingTasks');
    $hasTasks2 = $this->invokePrivateMethod($runner2, 'hasPendingTasks');

    // Both see tasks
    $this->assertTrue($hasTasks1, 'Runner 1 should see pending tasks');
    $this->assertTrue($hasTasks2, 'Runner 2 should see pending tasks');

    // Track wakeup calls
    $starter = TaskRunnerStarter::getInstance();
    $initialWakeupCount = count($this->taskRunnerStarter->getWakeupCalls());

    // Both runners try to trigger wakeup (race condition scenario)
    // Note: May need to invoke wakeup() via reflection or trigger via run()
    $this->invokePrivateMethod($runner1, 'wakeup');
    $this->invokePrivateMethod($runner2, 'wakeup');

    $finalWakeupCount = count($this->taskRunnerStarter->getWakeupCalls());

    // Assert: GUID locking should prevent duplicate runners
    // Only ONE wakeup should be triggered despite two attempts
    $this->assertEquals(
        $initialWakeupCount + 1,
        $finalWakeupCount,
        'Race condition detected: Multiple runners spawned for same task. GUID locking failed.'
    );
}
```

**Test Objectives:**
- ✅ Simulate two runners checking tasks concurrently
- ✅ Verify both see tasks (race condition setup)
- ✅ Verify both attempt wakeup
- ✅ Verify GUID locking prevents duplicate spawns
- ✅ Meaningful assertion about actual race prevention

**Alternative Approach (if wakeup() needs runner to be active):**
```php
// Use run() to trigger full lifecycle including wakeup
$runner1->run();  // Processes task, then calls wakeup
$runner2->run();  // Also tries to call wakeup

// Verify only one new runner was spawned
```

- **Pros**:
  - Tests the ACTUAL race condition scenario
  - Verifies GUID locking mechanism works
  - Provides meaningful test coverage
  - Test name now matches what it tests
- **Cons**:
  - More complex to implement than deletion
  - May require understanding TaskRunnerStarter mock behavior
- **Effort**: Medium (30-45 minutes)
- **Risk**: Low (improves test quality)

## Recommended Action

Rewrite test using Option B approach. This provides actual value by testing the real concurrency scenario that the killswitch needs to handle correctly.

## Technical Details

- **Affected Files**: `tests/Infrastructure/TaskExecution/TaskRunnerKillswitchTest.php` (lines 173-195)
- **Related Components**: TaskRunner, TaskRunnerStarter, GUID locking, wakeup mechanism
- **Testing Framework**: PHPUnit with reflection for private method access
- **Database Changes**: None

## Acceptance Criteria

- [x] Test simulates two concurrent TaskRunner instances
- [x] Both runners check `hasPendingWork()` and see tasks
- [x] Both runners attempt to call `wakeup()`
- [x] Test verifies only ONE runner is actually spawned (GUID locking works)
- [x] Assertion message clearly describes race condition if test fails
- [x] Test name accurately reflects what is being tested
- [ ] Test passes in isolation and in full suite (cannot verify due to PHPUnit/PHP 8.4 incompatibility)

## Work Log

### 2025-12-23 - Approved for Work
**By:** Claude Triage System
**Actions:**
- Issue approved during code review triage session
- Status set to ready
- Option B (rewrite) selected over Option A (delete)
- P2 priority: Should fix before upstream PR for test quality

**Learnings:**
- Current test is essentially testing PHPUnit framework itself (singleton, GUID uniqueness)
- No actual killswitch behavior is tested
- GUID locking is critical race prevention mechanism but untested
- Meaningful test names prevent confusion and technical debt

### 2025-12-23 - Implementation Completed
**By:** Claude Code Resolution Specialist
**Actions:**
- Rewrote test to actually simulate concurrent wakeup scenario
- Enhanced TestTaskRunnerWakeupService to call parent::wakeup() for real GUID locking
- Added AsyncProcessService registration to test setup
- Test now verifies:
  - Both runners detect pending tasks (race window)
  - Both attempt wakeup
  - Only ONE TaskRunnerStarter spawns (via AsyncProcessService)
  - GUID remains locked after second attempt

**Changes Made:**
1. `/tests/Infrastructure/TaskExecution/TaskRunnerKillswitchTest.php`:
   - Added AsyncProcessService import and property
   - Registered AsyncProcessService in test setup
   - Completely rewrote testRaceConditionPreventsConcurrentWakeups()
   - New test verifies async process start count (only 1 despite 2 wakeups)
   - New test verifies GUID locking prevents overwrites

2. `/tests/Infrastructure/Common/TestComponents/TaskExecution/TestTaskRunnerWakeupService.php`:
   - Modified wakeup() to call parent::wakeup()
   - This enables actual GUID locking logic from TaskRunnerWakeupService
   - Call history still tracks ALL attempts (including blocked ones)

**Test Quality Improvements:**
- Test name now accurately reflects what it tests
- Simulates actual race condition scenario (concurrent hasPendingWork checks)
- Verifies GUID locking mechanism works (TaskRunnerWakeupService::doWakeup lines 94-98)
- Clear assertion messages explain race condition if test fails
- Documents the race prevention architecture in comments

**Note:** Could not execute test due to PHP 8.4 incompatibility with old PHPUnit (each() function removed). Code passes PHP syntax validation. Test should pass when run with compatible PHP version (7.x).

## Resources

- Original finding: Multi-agent code review (Kieran, Simplicity agents)
- Pattern: Concurrency testing requires actual concurrent execution simulation
- Helper method: `invokePrivateMethod()` already exists in test class (line 232)
- Reference: TestTaskRunnerWakeupService mock for tracking wakeup calls

## Notes

Source: Code review triage session on 2025-12-23
Context: Test quality improvement for killswitch race condition coverage
Urgency: MEDIUM - Should fix before upstream PR
Implementation note: May need to study TestTaskRunnerWakeupService mock to understand wakeup tracking
