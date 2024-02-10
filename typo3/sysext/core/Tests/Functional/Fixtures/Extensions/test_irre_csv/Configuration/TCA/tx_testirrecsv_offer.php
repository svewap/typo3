<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tx_testirrecsv_offer',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'language_tag',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'translationSource' => 'l10n_source',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:test_irre_csv/Resources/Public/Icons/icon_offer.gif',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
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
        'l18n_parent' => [
            'displayCond' => 'FIELD:language_tag:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_testirrecsv_offer',
                'foreign_table_where' => 'AND {#tx_testirrecsv_offer}.{#pid}=###CURRENT_PID### AND {#tx_testirrecsv_offer}.{#language_tag} IN (-1,0)',
                'default' => 0,
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
                'default' => 0,
            ],
        ],
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_offer.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'prices' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_offer.prices',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirrecsv_price',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
                'default' => '',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tabs.general, title, prices,' .
            '--div--;LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tabs.visibility, language_tag, l18n_parent, l18n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];
