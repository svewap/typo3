<?php

return [
    'ctrl' => [
        'title' => 'Parent',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'language_tag',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'title',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'title, child, squeeze,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, language_tag, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden',
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
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_parentchildtranslation_domain_model_main',
                'foreign_table_where' => 'AND {#tx_parentchildtranslation_domain_model_main}.{#pid}=###CURRENT_PID### AND {#tx_parentchildtranslation_domain_model_main}.{#language_tag} IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'title' => [
            'exclude' => true,
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'child' => [
            'exclude' => true,
            'label' => 'Child',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_parentchildtranslation_domain_model_child',
                'foreign_table_where' => 'AND {#tx_parentchildtranslation_domain_model_child}.{#language_tag} IN (0,-1)',
                'default' => 0,
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'squeeze' => [
            'exclude' => true,
            'label' => 'Squeeze',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_parentchildtranslation_domain_model_squeeze',
                'foreign_table_where' => 'AND {#tx_parentchildtranslation_domain_model_squeeze}.{#language_tag} IN (0,-1)',
                'foreign_field' => 'parent',
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
    ],
];
