<?php

/*
|--------------------------------------------------------------------------
| Link Building CRM — enums, groupings, and badge tones
|--------------------------------------------------------------------------
| Single source of truth for the Campaigns + Publications module. This is
| OWNED by Linkinablink and is intentionally separate from the shared
| `label_options` table (which belongs to the Menford CRM). Editing here
| never touches the shared database.
*/

return [

    // Campaign services (mockup order)
    'services' => [
        'LB Only Publications',
        'LB Sitewide Links',
        'LB Reddit',
        'LB PBN',
        'LB Trustpilot',
        'Custom Outreach',
        'Digital PR',
        'LIAB Marketplace',
    ],

    // Campaign statuses grouped for <optgroup> + inline dropdown
    'campaign_statuses' => [
        'Active' => [
            'Offer Creation',
            'Publishing',
            'Waiting Client',
            'Waiting Payment',
            'Suspended',
        ],
        'Completed' => [
            'Completed in time',
            'Completed with delay (Our fault)',
            'Completed with Delay – Late Budget Approval',
            'Completed with Delay – Sites Added Mid-Campaign',
            'Completed with Delay – Client Unresponsive',
            "Completed with Delay – Publisher's Fault",
        ],
        'Closed' => [
            'Changed their mind',
            'Disappeared',
            'Refused by Client',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Unified publication statuses (Phase 3) — shared by Campaigns AND Storage.
    | The DB (storage.status) stores the SLUG key; the UI shows the label.
    | Slugs match the values historically used by the Storage module so the
    | 10k+ existing rows keep working without a rewrite.
    |----------------------------------------------------------------------
    */
    'publication_statuses' => [
        // Group 1 – Site Evaluation
        'waiting_client_approval'         => ['label' => 'Waiting Client Approval',             'group' => 1, 'tone' => 'amber'],
        'accepted'                        => ['label' => 'Accepted',                            'group' => 1, 'tone' => 'green'],
        'waiting_blog_price_confirmation' => ['label' => 'Waiting Blog Price Confirmation',     'group' => 1, 'tone' => 'amber'],
        'requirements_not_met'            => ['label' => 'Refused by Client – Metrics too low', 'group' => 1, 'tone' => 'red'],
        'high_price'                      => ['label' => 'Refused by Client – High Price',      'group' => 1, 'tone' => 'red'],
        'out_of_topic'                    => ['label' => 'Refused by Client – Out of Topic',    'group' => 1, 'tone' => 'red'],
        'already_used_by_client'          => ['label' => 'Refused by Client – Already Used',    'group' => 1, 'tone' => 'red'],
        // Group 2 – Production
        'waiting_copywriter'              => ['label' => 'Waiting Copywriter',                  'group' => 2, 'tone' => 'purple'],
        'waiting_client_article_approval' => ['label' => 'Waiting Client Article Approval',     'group' => 2, 'tone' => 'amber'],
        'waiting_blog_publication'        => ['label' => 'Waiting Blog Publication',            'group' => 2, 'tone' => 'blue'],
        'article_published'               => ['label' => 'Article Published',                   'group' => 2, 'tone' => 'green'],
        'publisher_disappeared'           => ['label' => 'Publisher Disappeared',               'group' => 2, 'tone' => 'red'],
        'publisher_refused'               => ['label' => 'Publisher Refused',                   'group' => 2, 'tone' => 'red'],
    ],

    'publication_status_groups' => [
        1 => 'Group 1 – Site Evaluation',
        2 => 'Group 2 – Production',
    ],

    'target_types' => [
        'budget'       => 'Budget (€)',
        'publications' => 'Nr. Publications',
    ],

    /*
    |----------------------------------------------------------------------
    | Badge tones — map each status/service to a tone keyword, then a tone
    | to Tailwind classes. Keeps the mockup's colour coding in one place.
    |----------------------------------------------------------------------
    */
    'campaign_status_tones' => [
        'Offer Creation'                                  => 'amber',
        'Publishing'                                      => 'green',
        'Waiting Client'                                  => 'amber',
        'Waiting Payment'                                 => 'red',
        'Suspended'                                       => 'gray',
        'Completed in time'                               => 'green',
        'Completed with delay (Our fault)'                => 'amber',
        'Completed with Delay – Late Budget Approval'     => 'amber',
        'Completed with Delay – Sites Added Mid-Campaign' => 'amber',
        'Completed with Delay – Client Unresponsive'      => 'amber',
        "Completed with Delay – Publisher's Fault"        => 'amber',
        'Changed their mind'                              => 'gray',
        'Disappeared'                                     => 'gray',
        'Refused by Client'                               => 'gray',
    ],

    'service_tones' => [
        'LB Only Publications' => 'green',
        'LB Sitewide Links'    => 'green',
        'LB Reddit'            => 'green',
        'LB PBN'               => 'green',
        'LB Trustpilot'        => 'green',
        'Custom Outreach'      => 'purple',
        'Digital PR'           => 'blue',
        'LIAB Marketplace'     => 'amber',
    ],

    // tone keyword => Tailwind badge classes
    'tone_classes' => [
        'gray'   => 'bg-gray-100 text-gray-700',
        'green'  => 'bg-green-100 text-green-800',
        'amber'  => 'bg-amber-100 text-amber-800',
        'red'    => 'bg-red-100 text-red-800',
        'blue'   => 'bg-blue-100 text-blue-800',
        'indigo' => 'bg-indigo-100 text-indigo-800',
        'purple' => 'bg-purple-100 text-purple-800',
        'sky'    => 'bg-sky-100 text-sky-800',
    ],

    // Team dropdown for "responsible" falls back to app users; no config needed.
];
