import { usePoll } from '@inertiajs/react';

export function AuditPoller() {
    usePoll(2000, { only: ['audit'] });

    return null;
}
