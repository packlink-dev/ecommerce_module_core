<?php

namespace Packlink\BusinessLogic\ShipmentDraft\Utility;

final class DraftStatus
{
    /**
     * Draft task has not been queued yet.
     */
    const  NOT_QUEUED = 'not_queued';

    /**
     * Draft task is queued or currently running.
     */
    const PROCESSING = 'processing';

    /**
     * Draft task is scheduled for delayed execution.
     */
    const DELAYED = 'delayed';

    /**
     * Draft creation finished successfully.
     */
    const COMPLETED  = 'completed';

    /**
     * Draft creation failed.
     */
    const FAILED = 'failed';

    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            self::NOT_QUEUED,
            self::PROCESSING,
            self::DELAYED,
            self::COMPLETED,
            self::FAILED,
        ];
    }

    /**
     * @param $status
     *
     * @return bool
     */
    public static function isValid($status): bool
    {
        return $status !== null && in_array($status, self::all(), true);
    }
}
