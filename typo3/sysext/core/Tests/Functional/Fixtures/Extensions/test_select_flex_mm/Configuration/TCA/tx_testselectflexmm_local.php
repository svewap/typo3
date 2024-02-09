<?php

return [
    'ctrl' => [
        'title' => 'DataHandler Testing test_select_flex_mm local',
        'label' => 'uid',
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
                'foreign_table' => 'tx_testselectflexmm_local',
                'foreign_table_where' => 'AND {#tx_testselectflexmm_local}.{#pid}=###CURRENT_PID### AND {#tx_testselectflexmm_local}.{#language_tag} IN (-1,0)',
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
                'foreign_table' => 'tx_testselectflexmm_local',
                'foreign_table_where' => 'AND {#tx_testselectflexmm_local}.{#pid}=###CURRENT_PID### AND {#tx_testselectflexmm_local}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],

        'flex_1' => [
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sMultiplesidebyside>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>selectMultipleSideBySide</sheetTitle>
                                        <el>
                                            <select_multiplesidebyside_1>
                                                <label>select_multiplesidebyside_1</label>
                                                <config>
                                                    <type>select</type>
                                                    <renderType>selectMultipleSideBySide</renderType>
                                                    <foreign_table>tx_testselectflexmm_foreign</foreign_table>
                                                    <MM>tx_testselectflexmm_flex_1_multiplesidebyside_1_mm</MM>
                                                    <size>5</size>
                                                    <autoSizeMax>5</autoSizeMax>
                                                </config>
                                            </select_multiplesidebyside_1>
                                        </el>
                                    </ROOT>
                                </sMultiplesidebyside>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;flex,
                    flex_1,
                --div--;meta,
                    language_tag, l10n_parent, l10n_source,
            ',
        ],
    ],

];
