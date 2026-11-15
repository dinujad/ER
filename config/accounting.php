<?php

return [
    /*
    | When false (default), manual journals must be approved before posting.
    | Set true only for small deployments that want draft → post without checker.
    */
    'manual_journal_skip_approval' => env('ACCOUNTING_MANUAL_JOURNAL_SKIP_APPROVAL', false),

    'currency' => 'LKR',
];
