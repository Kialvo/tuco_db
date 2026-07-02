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

    // Publication statuses grouped (Group 1 vs Group 2)
    'publication_statuses' => [
        'Group 1 – Site Evaluation' => [
            'Waiting Client Approval',
            'Accepted',
            'Refused by Client – Metrics Too Low',
            'Refused by Client – Too Expensive',
            'Refused by Client – Out of Topic',
        ],
        'Group 2 – Production' => [
            'Waiting Copywriter',
            'Waiting Client Article Approval',
            'Waiting Blog Publication',
            'Published',
            'Publisher disappeared',
        ],
    ],

    // Flat list of Group 2 statuses — used to derive publications.status_group (2 = production, else 1)
    'production_statuses' => [
        'Waiting Copywriter',
        'Waiting Client Article Approval',
        'Waiting Blog Publication',
        'Published',
        'Publisher disappeared',
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

    'publication_status_tones' => [
        'Waiting Client Approval'              => 'amber',
        'Accepted'                             => 'green',
        'Refused by Client – Metrics Too Low'  => 'red',
        'Refused by Client – Too Expensive'    => 'red',
        'Refused by Client – Out of Topic'     => 'red',
        'Waiting Copywriter'                   => 'purple',
        'Waiting Client Article Approval'      => 'amber',
        'Waiting Blog Publication'             => 'blue',
        'Published'                            => 'green',
        'Publisher disappeared'                => 'red',
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
