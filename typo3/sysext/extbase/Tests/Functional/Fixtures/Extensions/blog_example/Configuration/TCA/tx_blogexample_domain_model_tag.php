<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_tag',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'language_tag',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_tag.gif',
    ],
    'columns' => [
        'language_tag' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
                'default' => 0,
            ],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:language_tag:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_blogexample_domain_model_tag',
                'foreign_table_where' => 'AND {#tx_blogexample_domain_model_tag}.{#uid}=###REC_FIELD_l18n_parent### AND {#tx_blogexample_domain_model_tag}.{#language_tag} IN (-1,0)',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_tag.name',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'required' => true,
                'eval' => 'trim',
                'max' => 256,
            ],
        ],
        'items' => [
            'exclude' => true,
            'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_tag.items',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_blogexample_domain_model_person,tx_blogexample_domain_model_post',
                'size' => 10,
                'MM' => 'tx_blogexample_domain_model_tag_mm',
                'MM_oppositeUsage' => [
                    'tx_blogexample_domain_model_person' => [
                        'tags',
                        'tags_special',
                    ],
                    'tx_blogexample_domain_model_post' => [
                        'tags',
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'language_tag, hidden, name, items'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
