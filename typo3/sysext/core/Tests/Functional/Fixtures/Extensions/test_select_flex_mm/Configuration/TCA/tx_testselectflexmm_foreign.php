<?php

return [
    'ctrl' => [
        'title' => 'DataHandler Testing test_select_flex_mm foreign',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:test_select_flex_mm/Resources/Public/Icons/Extension.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'language_tag',
        'transOrigPointerField' => 'l10n_parent',
        'translationSource' => 'l10n_source',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'language_tag' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:language_tag:>:0',
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_testselectflexmm_foreign',
                'foreign_table_where' => 'AND {#tx_testselectflexmm_foreign}.{#pid}=###CURRENT_PID### AND {#tx_testselectflexmm_foreign}.{#language_tag} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'exclude' => true,
            'displayCond' => 'FIELD:language_tag:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_testselectflexmm_foreign',
                'foreign_table_where' => 'AND {#tx_testselectflexmm_foreign}.{#pid}=###CURRENT_PID### AND {#tx_testselectflexmm_foreign}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],

        'title' => [
            'label' => 'title',
            'config' => [
                'type' => 'input',
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;title,
                    title,
                --div--;meta,
                    language_tag, l10n_parent, l10n_source,
            ',
        ],
    ],

];
